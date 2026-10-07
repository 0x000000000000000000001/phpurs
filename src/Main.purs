-- | Load typed modules, invoke the optimizer and coordinate PHP file emission.
module Main (main, mainWithCache, mainWithToolchain) where

import Prelude

import Data.Array as Array
import Data.Either (Either(..), note)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Newtype (unwrap)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Effect (Effect)
import Effect.Aff (Aff, launchAff_, throwError)
import Effect.Class (liftEffect)
import Effect.Console as Console
import Effect.Exception (error)
import Effect.Ref as Ref
import Node.Buffer.Immutable as Bytes
import Node.Encoding (Encoding(..))
import Node.Process as Process
import Phpurs.AstMetrics (countModuleNodes)
import Phpurs.BuildCache as BuildCache
import Phpurs.BuildInputs (captureForeign, loadInputsWithProfile)
import Phpurs.BuildProfile as Profile
import Phpurs.CacheKey (Toolchain, fingerprintBytes)
import Phpurs.ComposerMerge (mergeComposers)
import Phpurs.EntryPoint (printBundleEntryPoint, printModularEntryPoint)
import Phpurs.Metrics as Metrics
import Phpurs.ModuleCache (CacheHooks)
import Phpurs.ModuleState (newBuildRefs, publishModuleStateWithProfile, printModuleState, translateModuleStateWithContracts, supportsEmission)
import Phpurs.ForeignTraversals as ForeignTraversals
import Phpurs.NativeCallbacks as NativeCallbacks
import Phpurs.OutputManifest (OutputKind(..), finalizeOutputs, writeOutput)
import Phpurs.PackagePaths (resolvePackagePaths)
import Phpurs.PurmetaBudget as PurmetaBudget
import Phpurs.PurmetaProfile (withProfile)
import Phpurs.RewriteLimit as RewriteLimit
import PureScript.Backend.Optimizer.App (loadDirectives, parseCLIArgs)
import PureScript.Backend.Optimizer.Builder (buildModules)
import PureScript.Backend.Optimizer.CoreFn (Ident(..), Module(..), ModuleName(..), importName)
import PureScript.Backend.Optimizer.Directives.Defaults (defaultDirectives)
import PureScript.Backend.Optimizer.Reachability (moduleReachability)
import PureScript.Backend.Optimizer.Semantics.Foreign (coreForeignSemantics)

main :: Effect Unit
main = run Nothing Nothing

-- | A development import has no immutable executable identity. Explicit hooks
-- | remain available for state-layer tests; the packaged CLI supplies Toolchain.
mainWithCache :: CacheHooks -> Effect Unit
mainWithCache cache = run Nothing (Just cache)

mainWithToolchain :: Toolchain -> Effect Unit
mainWithToolchain toolchain = run (Just toolchain) Nothing

run :: Maybe Toolchain -> Maybe CacheHooks -> Effect Unit
run toolchain override = launchAff_ do
  argsRaw <- liftEffect Process.argv
  -- Match the shared parser's handling of grouped Spago backend arguments.
  let cliArgs = Array.concatMap (String.split (Pattern " ")) argsRaw
  Metrics.measure "backend total" \_ ->
    Profile.withProfile (Array.elem "--profile-build" cliArgs) \profile ->
      compile toolchain override cliArgs profile

compile :: Maybe Toolchain -> Maybe CacheHooks -> Array String -> Profile.Profile -> Aff Unit
compile toolchain override cliArgs profile = do
  let
    args = parseCLIArgs cliArgs
    verbose = Array.elem "--verbose" cliArgs
    bundleOnly = Array.elem "--bundle-only" cliArgs
    emitBundle = args.bundle || bundleOnly
    emitModules = not bundleOnly
    emission = { emitModules, emitBundle }
    outputDir = fromMaybe "output" args.mbOutputDir

  purmetaBudget <- case PurmetaBudget.parseBudget cliArgs of
    Left message -> throwError $ error message
    Right budget -> pure budget
  rewriteLimit <- case RewriteLimit.parseLimit cliArgs of
    Left message -> throwError $ error message
    Right limit -> pure limit

  loaded <- Metrics.measure "load TAST + sort" \_ -> loadInputsWithProfile profile outputDir
  let finalModules = loaded.modules

  { refs, directives, packagePaths, foreignSources, nativeCallbacks, traversals, cache, targetMainModules } <- Metrics.measure "prepare" \_ -> do
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
      ffi <- Profile.measureAff profile "ffi" (unwrap core.name) \_ -> captureForeign packagePaths.ffiRoots m
      pure { core, ffi }) (Array.fromFoldable finalModules)
    cwd <- liftEffect Process.cwd
    let
      foreignSources = Map.fromFoldable $ map (\c -> Tuple c.core.name c.ffi.source) captures
      -- Use the same captured source/signature as codegen and cache identity.
      -- Reconstruct this evidence even when its module will be a cache hit.
      nativeCallbacks = if loaded.cacheable then Set.unions (map
        (\c -> NativeCallbacks.fromForeign c.core.name c.core.foreign c.ffi.source) captures)
        else Set.empty
      traversals = if loaded.cacheable then Set.unions (map
        (\c -> ForeignTraversals.fromForeign c.core.name c.core.foreign c.ffi.source) captures)
        else Set.empty
      inputs = if not loaded.cacheable then Left "Incomplete or duplicate module input" else
        note "Missing captured CoreFn fingerprint" $ traverse (\c -> do
          let name = unwrap c.core.name
          coreFn <- Map.lookup name loaded.fingerprints
          pure { name, coreFn, foreignInput: c.ffi.input, dependencies: map (unwrap <<< importName) c.core.imports }) captures
    cache <- Profile.measureAff profile "cache.plan" "" \_ -> case override, toolchain of
      Just hooks, _ -> pure { hooks, report: pure unit }
      _, Just identity | not (Array.elem "--no-cache" cliArgs) -> liftEffect $ BuildCache.prepareCache
        { toolchain: identity
        , options: { cwd, outputDir, ffiRoots: packagePaths.ffiRoots, emitModules, emitBundle, mainModule: args.mbMainModule, autoloadPath: args.mbAutoloadPath, rewriteLimit }
        , directives: fingerprintBytes (Bytes.fromString defaultDirectives UTF8)
        } inputs
      _, _ -> pure BuildCache.disabled
    pure { refs, directives, packagePaths, foreignSources, nativeCallbacks, traversals, cache, targetMainModules }

  Metrics.measure "optimize + emit" \_ ->
    PurmetaBudget.withBudget purmetaBudget $
    withProfile (Array.elem "--profile-purmeta" cliArgs) $
    buildModules
      { directives
      , rewriteLimit
      , analyzeCustom: \_ _ -> Nothing
      , foreignSemantics: coreForeignSemantics
      , traceIdents: Set.empty
      , onPrepareModule: \_ m -> pure m
      , onSkipModule: \_ (Module coreFnMod) -> do
          let name = unwrap coreFnMod.name
          restored <- Profile.measureAff profile "cache.load" name \_ -> cache.hooks.load coreFnMod.name
          case restored of
            Just state | state.backend.name == coreFnMod.name && supportsEmission emission state -> do
              liftEffect $ Profile.restoredModule profile name
              publishModuleStateWithProfile profile emission outputDir refs state
              pure (Just state.backend)
            _ -> do
              liftEffect $ Profile.beginOptimization profile name
              pure Nothing
      , onCodegenModule: \_ (Module coreFnMod) backendMod _ -> do
          liftEffect $ Profile.endOptimization profile
          let modNameStr = unwrap backendMod.name
          when verbose $ Profile.measureAff profile "diagnostics" modNameStr \_ -> do
            let totalNodes = countModuleNodes backendMod
            liftEffect $ Console.log $ "Generating PHP code for " <> modNameStr <> " (Total AST Nodes: " <> show totalNodes <> ")"
          let importsArray = map (\i -> String.split (Pattern ".") (unwrap (importName i))) coreFnMod.imports
          let foreignSource = fromMaybe "" (Map.lookup backendMod.name foreignSources)
          currentArities <- liftEffect $ Ref.read refs.globalAritiesRef
          translated <- liftEffect $ Profile.measurePure profile "translate" modNameStr \_ ->
            translateModuleStateWithContracts nativeCallbacks traversals importsArray foreignSource currentArities backendMod
          state <- liftEffect $ Profile.measurePure profile "print" modNameStr \_ -> printModuleState emission translated
          publishModuleStateWithProfile profile emission outputDir refs state
          Profile.measureAff profile "cache.store" modNameStr \_ -> cache.hooks.store state
      }
      finalModules

  Metrics.measure "finalize" \_ -> do
    backendModules <- liftEffect $ Ref.read refs.backendModulesRef
    arities <- liftEffect $ Ref.read refs.globalAritiesRef

    _ <- traverse
      ( \mainMod -> do
          let entryPoint = { mainModule: mainMod, autoloadPath: args.mbAutoloadPath, arities }

          if emitBundle then do
            bundleContent <- liftEffect $ Ref.read refs.bundleContentRef
            code <- liftEffect $ Profile.measurePure profile "print" mainMod \_ ->
              bundleContent <> "\n" <> printBundleEntryPoint entryPoint
            Profile.measureAff profile "php.write" mainMod \_ ->
              writeOutput refs.outputs outputDir BundleOutput (mainMod <> "/main.bundle.php") code
          else pure unit

          when emitModules do
            reachable <- liftEffect $ Profile.measurePure profile "entrypoint" mainMod \_ ->
              let reachableSet = moduleReachability [ModuleName mainMod] backendModules
              in Array.filter (\(Module m) -> Set.member m.name reachableSet) (Array.fromFoldable finalModules)
            modEntryPoint <- liftEffect $ Profile.measurePure profile "print" mainMod \_ ->
              printModularEntryPoint entryPoint (map (\(Module m) -> m.name) reachable)
            when verbose $ liftEffect $ Console.log $ "Generating main.mod.php for " <> mainMod
            Profile.measureAff profile "php.write" mainMod \_ ->
              writeOutput refs.outputs outputDir ModularOutput (mainMod <> "/main.mod.php") modEntryPoint
      )
      targetMainModules

    if emitBundle then do
      case args.mbMainModule of
        Just _ -> pure unit
        Nothing -> do
          bundleContent <- liftEffect $ Ref.read refs.bundleContentRef
          Profile.measureAff profile "php.write" "" \_ ->
            writeOutput refs.outputs outputDir BundleOutput "bundle.php" bundleContent
    else pure unit

    Profile.measureAff profile "composer" "" \_ ->
      liftEffect $ mergeComposers { outputDir, packageRoots: packagePaths.composerRoots }
    Profile.measureAff profile "cleanup" "" \_ ->
      liftEffect $ finalizeOutputs refs.outputs outputDir emission loaded.cacheable
  liftEffect cache.report
