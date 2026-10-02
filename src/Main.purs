-- | Load typed modules, invoke the optimizer and coordinate PHP file emission.
module Main (main, mainWithCache) where

import Prelude

import Data.Array as Array
import Data.Foldable (foldl)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe, isJust)
import Data.Newtype (unwrap)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Effect (Effect)
import Effect.Aff (Aff, launchAff_)
import Effect.Class (liftEffect)
import Effect.Console as Console
import Effect.Ref as Ref
import Node.Encoding (Encoding(..))
import Node.FS.Aff as FS
import Node.Process as Process
import Phpurs.ComposerMerge (mergeComposers)
import Phpurs.EntryPoint (printBundleEntryPoint, printModularEntryPoint)
import Phpurs.FileEmission (writeTextFileIfChanged)
import Phpurs.Metrics as Metrics
import Phpurs.ModuleCache (CacheHooks)
import Phpurs.ModuleState (newBuildRefs, publishModuleState, renderModuleState, supportsEmission)
import Phpurs.PackagePaths (findForeignFile, resolvePackagePaths)
import PureScript.Backend.Optimizer.App (coreFnModulesFromOutput, loadDirectives, parseCLIArgs)
import PureScript.Backend.Optimizer.Builder (buildModules)
import PureScript.Backend.Optimizer.CoreFn (Ident(..), Module(..), ModuleName(..), importName)
import PureScript.Backend.Optimizer.Reachability (moduleReachability)
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Semantics.Foreign (coreForeignSemantics)
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..))

readForeignSource :: { roots :: Array String, moduleName :: String, modulePath :: String } -> Aff String
readForeignSource { roots, moduleName, modulePath } = do
  path <- liftEffect $ findForeignFile roots moduleName modulePath
  case path of
    Nothing -> pure ""
    Just ffiPath -> FS.readTextFile UTF8 ffiPath

countNodes :: NeutralExpr -> Int
countNodes (NeutralExpr expr) = 1 + case expr of
  Var _ -> 0
  Local _ _ -> 0
  Lit _ -> 0
  App f args -> countNodes f + foldl (+) 0 (map countNodes args)
  Abs _ body -> countNodes body
  UncurriedApp f args -> countNodes f + foldl (+) 0 (map countNodes args)
  UncurriedAbs _ body -> countNodes body
  UncurriedEffectApp f args -> countNodes f + foldl (+) 0 (map countNodes args)
  UncurriedEffectAbs _ body -> countNodes body
  Accessor obj _ -> countNodes obj
  Update obj _ -> countNodes obj
  CtorSaturated _ _ _ _ args -> foldl (+) 0 (map (\(Tuple _ a) -> countNodes a) args)
  CtorDef _ _ _ _ -> 0
  LetRec _ binds body -> foldl (+) 0 (map (\(Tuple _ a) -> countNodes a) binds) + countNodes body
  Let _ _ val body -> countNodes val + countNodes body
  EffectBind _ _ val body -> countNodes val + countNodes body
  EffectPure val -> countNodes val
  EffectDefer val -> countNodes val
  Branch _ _ -> 0
  PrimOp _ -> 0
  _ -> 0

main :: Effect Unit
main = mainWithCache { load: \_ -> pure Nothing, store: \_ -> pure unit }

-- | Explicit hooks let the state layer exercise PBO's hit path. CLI activation
-- | will supply keys from the same captured input bytes used by the loader.
mainWithCache :: CacheHooks -> Effect Unit
mainWithCache cache = launchAff_ $ Metrics.measure "backend total" \_ -> do
  argsRaw <- liftEffect Process.argv
  let 
    -- Match the shared parser's handling of grouped Spago backend arguments.
    cliArgs = Array.concatMap (String.split (Pattern " ")) argsRaw
    args = parseCLIArgs cliArgs
    bundleOnly = Array.elem "--bundle-only" cliArgs
    emitBundle = args.bundle || bundleOnly
    emitModules = not bundleOnly
    emission = { emitModules, emitBundle }
    outputDir = fromMaybe "output" args.mbOutputDir

  finalModules <- Metrics.measure "load TAST + sort" \_ -> coreFnModulesFromOutput outputDir

  { refs, directives, packagePaths } <- Metrics.measure "prepare" \_ -> do
    refs <- liftEffect newBuildRefs

    -- Composer precedence follows module-name order, not dependency order.
    let modulePaths = map (\(Module m) -> m.path) $ Array.sortWith (\(Module m) -> m.name) (Array.fromFoldable finalModules)
    packagePaths <- liftEffect $ resolvePackagePaths { ffiDir: args.mbFfiDir, modulePaths }
    directives <- loadDirectives
    pure { refs, directives, packagePaths }

  Metrics.measure "optimize + emit" \_ ->
    buildModules
      { directives
      , rewriteLimit: 10000
      , analyzeCustom: \_ _ -> Nothing
      , foreignSemantics: coreForeignSemantics
      , traceIdents: Set.empty
      , onPrepareModule: \_ m -> pure m
      , onSkipModule: \_ (Module coreFnMod) -> do
          restored <- cache.load coreFnMod.name
          case restored of
            Just state | state.backend.name == coreFnMod.name && supportsEmission emission state -> do
              publishModuleState emission outputDir refs state
              pure (Just state.backend)
            _ -> pure Nothing
      , onCodegenModule: \_ (Module coreFnMod) backendMod _ -> do
          let modNameStr = unwrap backendMod.name
          let totalNodes = foldl (+) 0 (map (\bg -> foldl (+) 0 (map (\(Tuple _ expr) -> countNodes expr) bg.bindings)) backendMod.bindings)
          liftEffect $ Console.log $ "Generating PHP code for " <> modNameStr <> " (Total AST Nodes: " <> show totalNodes <> ")"
          let importsArray = map (\i -> String.split (Pattern ".") (unwrap (importName i))) coreFnMod.imports
          foreignSource <- if Map.isEmpty backendMod.foreign then pure ""
            else readForeignSource { roots: packagePaths.ffiRoots, moduleName: modNameStr, modulePath: coreFnMod.path }
          currentArities <- liftEffect $ Ref.read refs.globalAritiesRef
          let state = renderModuleState emission importsArray foreignSource currentArities backendMod
          publishModuleState emission outputDir refs state
          cache.store state
      }
      finalModules

  Metrics.measure "finalize" \_ -> do
    backendModules <- liftEffect $ Ref.read refs.backendModulesRef

    let
      targetMainModules = case args.mbMainModule of
        Just mainMod -> [ mainMod ]
        Nothing -> Array.mapMaybe (\(Module m) -> if isJust (Array.elemIndex (Ident "main") m.exports) then Just (unwrap m.name) else Nothing) (Array.fromFoldable finalModules)

    _ <- traverse
      ( \mainMod -> do
          let entryPoint = { mainModule: mainMod, autoloadPath: args.mbAutoloadPath }

          if emitBundle then do
            bundleContent <- liftEffect $ Ref.read refs.bundleContentRef
            writeTextFileIfChanged (outputDir <> "/" <> mainMod <> "/main.bundle.php") (bundleContent <> "\n" <> printBundleEntryPoint entryPoint)
          else pure unit

          when emitModules do
            let
              reachableSet = moduleReachability [ModuleName mainMod] backendModules
              reachable = Array.filter (\(Module m) -> Set.member m.name reachableSet) (Array.fromFoldable finalModules)
              modEntryPoint = printModularEntryPoint entryPoint (map (\(Module m) -> m.name) reachable)
            liftEffect $ Console.log $ "Generating main.mod.php for " <> mainMod
            writeTextFileIfChanged (outputDir <> "/" <> mainMod <> "/main.mod.php") modEntryPoint
      )
      targetMainModules

    if emitBundle then do
      case args.mbMainModule of
        Just _ -> pure unit
        Nothing -> do
          bundleContent <- liftEffect $ Ref.read refs.bundleContentRef
          writeTextFileIfChanged (outputDir <> "/bundle.php") bundleContent
    else pure unit

    liftEffect $ mergeComposers { outputDir, packageRoots: packagePaths.composerRoots }
