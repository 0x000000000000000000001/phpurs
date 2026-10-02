-- | Load typed modules, invoke the optimizer and coordinate PHP file emission.
module Main (main, mainWithCache, mainWithToolchain) where

import Prelude

import Data.Array as Array
import Data.Either (Either(..), note)
import Data.Foldable (foldl)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Newtype (unwrap)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Effect (Effect)
import Effect.Aff (launchAff_, throwError)
import Effect.Class (liftEffect)
import Effect.Console as Console
import Effect.Exception (error)
import Effect.Ref as Ref
import Node.Buffer.Immutable as Bytes
import Node.Encoding (Encoding(..))
import Node.Process as Process
import Phpurs.BuildCache as BuildCache
import Phpurs.BuildInputs (captureForeign, loadInputs)
import Phpurs.CacheKey (Toolchain, fingerprintBytes)
import Phpurs.ComposerMerge (mergeComposers)
import Phpurs.EntryPoint (printBundleEntryPoint, printModularEntryPoint)
import Phpurs.Metrics as Metrics
import Phpurs.ModuleCache (CacheHooks)
import Phpurs.ModuleState (newBuildRefs, publishModuleState, renderModuleState, supportsEmission)
import Phpurs.OutputManifest (OutputKind(..), finalizeOutputs, writeOutput)
import Phpurs.PackagePaths (resolvePackagePaths)
import Phpurs.PurmetaBudget as PurmetaBudget
import Phpurs.PurmetaProfile (withProfile)
import PureScript.Backend.Optimizer.App (loadDirectives, parseCLIArgs)
import PureScript.Backend.Optimizer.Builder (buildModules)
import PureScript.Backend.Optimizer.CoreFn (Ident(..), Module(..), ModuleName(..), importName)
import PureScript.Backend.Optimizer.Directives.Defaults (defaultDirectives)
import PureScript.Backend.Optimizer.Reachability (moduleReachability)
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Semantics.Foreign (coreForeignSemantics)
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..))

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
main = run Nothing Nothing

-- | A development import has no immutable executable identity. Explicit hooks
-- | remain available for state-layer tests; the packaged CLI supplies Toolchain.
mainWithCache :: CacheHooks -> Effect Unit
mainWithCache cache = run Nothing (Just cache)

mainWithToolchain :: Toolchain -> Effect Unit
mainWithToolchain toolchain = run (Just toolchain) Nothing

run :: Maybe Toolchain -> Maybe CacheHooks -> Effect Unit
run toolchain override = launchAff_ $ Metrics.measure "backend total" \_ -> do
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

  purmetaBudget <- case PurmetaBudget.parseBudget cliArgs of
    Left message -> throwError $ error message
    Right budget -> pure budget

  loaded <- Metrics.measure "load TAST + sort" \_ -> loadInputs outputDir
  let finalModules = loaded.modules

  { refs, directives, packagePaths, foreignSources, cache, targetMainModules } <- Metrics.measure "prepare" \_ -> do
    let
      modules = Array.fromFoldable finalModules
      exportsMain (Module m) = Array.elem (Ident "main") m.exports
    targetMainModules <- case args.mbMainModule of
      Nothing -> pure $ map (\(Module m) -> unwrap m.name) $ Array.filter exportsMain modules
      Just mainMod -> case Array.find (\(Module m) -> unwrap m.name == mainMod) modules of
        Nothing -> throwError $ error $ "Main module " <> mainMod <> " was not loaded"
        Just m | not (exportsMain m) -> throwError $ error $ "Module " <> mainMod <> " does not export main"
        Just _ -> pure [ mainMod ]
    refs <- liftEffect newBuildRefs

    -- Composer precedence follows module-name order, not dependency order.
    let modulePaths = map (\(Module m) -> m.path) $ Array.sortWith (\(Module m) -> m.name) (Array.fromFoldable finalModules)
    packagePaths <- liftEffect $ resolvePackagePaths { ffiDir: args.mbFfiDir, modulePaths }
    directives <- loadDirectives
    captures <- traverse (\m@(Module core) -> do
      ffi <- captureForeign packagePaths.ffiRoots m
      pure { core, ffi }) (Array.fromFoldable finalModules)
    cwd <- liftEffect Process.cwd
    let
      foreignSources = Map.fromFoldable $ map (\c -> Tuple c.core.name c.ffi.source) captures
      inputs = if not loaded.cacheable then Left "Incomplete or duplicate module input" else
        note "Missing captured CoreFn fingerprint" $ traverse (\c -> do
          let name = unwrap c.core.name
          coreFn <- Map.lookup name loaded.fingerprints
          pure { name, coreFn, foreignInput: c.ffi.input, dependencies: map (unwrap <<< importName) c.core.imports }) captures
    cache <- case override, toolchain of
      Just hooks, _ -> pure { hooks, report: pure unit }
      _, Just identity | not (Array.elem "--no-cache" cliArgs) -> liftEffect $ BuildCache.prepareCache
        { toolchain: identity
        , options: { cwd, outputDir, ffiRoots: packagePaths.ffiRoots, emitModules, emitBundle, mainModule: args.mbMainModule, autoloadPath: args.mbAutoloadPath, rewriteLimit: 10000 }
        , directives: fingerprintBytes (Bytes.fromString defaultDirectives UTF8)
        } inputs
      _, _ -> pure BuildCache.disabled
    pure { refs, directives, packagePaths, foreignSources, cache, targetMainModules }

  Metrics.measure "optimize + emit" \_ ->
    PurmetaBudget.withBudget purmetaBudget $
    withProfile (Array.elem "--profile-purmeta" cliArgs) $
    buildModules
      { directives
      , rewriteLimit: 10000
      , analyzeCustom: \_ _ -> Nothing
      , foreignSemantics: coreForeignSemantics
      , traceIdents: Set.empty
      , onPrepareModule: \_ m -> pure m
      , onSkipModule: \_ (Module coreFnMod) -> do
          restored <- cache.hooks.load coreFnMod.name
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
          let foreignSource = fromMaybe "" (Map.lookup backendMod.name foreignSources)
          currentArities <- liftEffect $ Ref.read refs.globalAritiesRef
          let state = renderModuleState emission importsArray foreignSource currentArities backendMod
          publishModuleState emission outputDir refs state
          cache.hooks.store state
      }
      finalModules

  Metrics.measure "finalize" \_ -> do
    backendModules <- liftEffect $ Ref.read refs.backendModulesRef

    _ <- traverse
      ( \mainMod -> do
          let entryPoint = { mainModule: mainMod, autoloadPath: args.mbAutoloadPath }

          if emitBundle then do
            bundleContent <- liftEffect $ Ref.read refs.bundleContentRef
            writeOutput refs.outputs outputDir BundleOutput (mainMod <> "/main.bundle.php") (bundleContent <> "\n" <> printBundleEntryPoint entryPoint)
          else pure unit

          when emitModules do
            let
              reachableSet = moduleReachability [ModuleName mainMod] backendModules
              reachable = Array.filter (\(Module m) -> Set.member m.name reachableSet) (Array.fromFoldable finalModules)
              modEntryPoint = printModularEntryPoint entryPoint (map (\(Module m) -> m.name) reachable)
            liftEffect $ Console.log $ "Generating main.mod.php for " <> mainMod
            writeOutput refs.outputs outputDir ModularOutput (mainMod <> "/main.mod.php") modEntryPoint
      )
      targetMainModules

    if emitBundle then do
      case args.mbMainModule of
        Just _ -> pure unit
        Nothing -> do
          bundleContent <- liftEffect $ Ref.read refs.bundleContentRef
          writeOutput refs.outputs outputDir BundleOutput "bundle.php" bundleContent
    else pure unit

    liftEffect $ mergeComposers { outputDir, packageRoots: packagePaths.composerRoots }
    liftEffect $ finalizeOutputs refs.outputs outputDir emission loaded.cacheable
  liftEffect cache.report
