-- | Load typed modules, invoke the optimizer and coordinate PHP file emission.
module Main (main, mainWithCache, mainWithToolchain) where

import Prelude

import Data.Array as Array
import Data.Either (Either(..))
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Newtype (unwrap)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Effect (Effect)
import Effect.Aff (Aff, launchAff_, throwError)
import Effect.Class (liftEffect)
import Effect.Console as Console
import Effect.Exception (error)
import Effect.Ref as Ref
import Node.Process as Process
import Main.Options as Options
import Main.Prepare as Prepare
import Main.Outputs as Outputs
import Main.Selection as Selection
import Phpurs.AstMetrics (countModuleNodes)
import Phpurs.BuildInputs (loadInputsWithProfile)
import Phpurs.BuildProfile as Profile
import Phpurs.CacheKey (Toolchain)
import Phpurs.Metrics as Metrics
import Phpurs.ModuleCache (CacheHooks)
import Phpurs.ModuleState
  ( publishModuleStateWithProfile
  , printModuleState
  , translateModuleStateWithContracts
  , supportsEmission
  )
import Phpurs.PurmetaBudget as PurmetaBudget
import Phpurs.PurmetaProfile (withProfile)
import PureScript.Backend.Optimizer.Builder (buildModules)
import PureScript.Backend.Optimizer.CoreFn (Module(..), importName)
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
  options <- case Options.parseOptions cliArgs of
    Left message -> throwError $ error message
    Right parsed -> pure parsed
  let
    args = options.args
    verbose = options.verbose
    emission = options.emission
    outputDir = options.outputDir
    purmetaBudget = options.purmetaBudget
    rewriteLimit = options.rewriteLimit

  loaded <- Metrics.measure "load TAST + sort" \_ -> loadInputsWithProfile profile outputDir
  let allModulesArray = Array.fromFoldable loaded.modules
  targetMainModules <- case Selection.selectTargetMains args.mbMainModule allModulesArray of
    Left message -> throwError $ error message
    Right mains -> pure mains
  let finalModules = Selection.selectModules targetMainModules loaded.modules

  { refs, directives, packagePaths, foreignSources, nativeCallbacks, traversals, cache } <-
    Metrics.measure "prepare" \_ ->
      Prepare.prepare
        { profile
        , args
        , allModules: allModulesArray
        , finalModules
        , loaded
        , override
        , toolchain
        , emission
        , outputDir
        , rewriteLimit
        , noCache: options.noCache
        }

  Metrics.measure "optimize + emit" \_ ->
    PurmetaBudget.withBudget purmetaBudget
      $ withProfile options.profilePurmeta
      $
        buildModules
          { directives
          , rewriteLimit
          , analyzeCustom: \_ _ -> Nothing
          , foreignSemantics: coreForeignSemantics
          , traceIdents: Set.empty
          , onPrepareModule: \_ m -> pure m
          , onSkipModule: \_ (Module coreFnMod) -> do
              let name = unwrap coreFnMod.name
              restored <- Profile.measureAff profile "cache.load" name \_ -> cache.hooks.load
                coreFnMod.name
              case restored of
                Just state
                  | state.backend.name == coreFnMod.name && supportsEmission emission state -> do
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
                liftEffect $ Console.log $ "Generating PHP code for " <> modNameStr
                  <> " (Total AST Nodes: "
                  <> show totalNodes
                  <> ")"
              let
                importsArray = map (\i -> String.split (Pattern ".") (unwrap (importName i)))
                  coreFnMod.imports
              let foreignSource = fromMaybe "" (Map.lookup backendMod.name foreignSources)
              currentArities <- liftEffect $ Ref.read refs.globalAritiesRef
              translated <- liftEffect $ Profile.measurePure profile "translate" modNameStr \_ ->
                translateModuleStateWithContracts nativeCallbacks traversals importsArray
                  foreignSource
                  currentArities
                  backendMod
              state <- liftEffect $ Profile.measurePure profile "print" modNameStr \_ ->
                printModuleState emission translated
              publishModuleStateWithProfile profile emission outputDir refs state
              Profile.measureAff profile "cache.store" modNameStr \_ -> cache.hooks.store state
          }
          finalModules

  Metrics.measure "finalize" \_ ->
    Outputs.finalize
      { profile
      , refs
      , outputDir
      , emission
      , mainModule: args.mbMainModule
      , autoloadPath: args.mbAutoloadPath
      , verbose
      , targetMainModules
      , finalModules
      , composerRoots: packagePaths.composerRoots
      , cacheable: loaded.cacheable
      }

  liftEffect cache.report
