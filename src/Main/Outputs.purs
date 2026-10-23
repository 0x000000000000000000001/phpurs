-- | Final PHP output emission: entrypoints, bundles, runtime, Composer and
-- | bounded cleanup of obsolete owned files.
module Main.Outputs (Config, finalize) where

import Prelude

import Data.Array as Array
import Data.List (List)
import Data.List as List
import Data.Maybe (Maybe(..))
import Data.Set as Set
import Data.Traversable (traverse)
import Data.String as String
import Effect.Aff (Aff)
import Effect.Class (liftEffect)
import Effect.Console as Console
import Effect.Ref as Ref
import Main.Selection as Selection
import Phpurs.BuildProfile as Profile
import Phpurs.ComposerMerge (mergeComposers)
import Phpurs.EntryPoint (printBundleEntryPoint, printModularEntryPoint)
import Phpurs.ModuleState (BuildRefs, Emission)
import Phpurs.OutputManifest (OutputKind(..), finalizeOutputs, writeOutput)
import Phpurs.Printer.Runtime as Runtime
import PureScript.Backend.Optimizer.CoreFn (Module(..), ModuleName(..))

type Config a =
  { profile :: Profile.Profile
  , refs :: BuildRefs
  , outputDir :: String
  , emission :: Emission
  , mainModule :: Maybe String
  , autoloadPath :: Maybe String
  , verbose :: Boolean
  , targetMainModules :: Array String
  , finalModules :: List (Module a)
  , composerRoots :: Array String
  , cacheable :: Boolean
  }

finalize :: forall a. Config a -> Aff Unit
finalize config = do
  let
    refs = config.refs
    outputDir = config.outputDir
    emission = config.emission
    mainModule = config.mainModule
    verbose = config.verbose
    finalModules = config.finalModules
  arities <- liftEffect $ Ref.read refs.globalAritiesRef
  when (emission.emitModules && not (List.null finalModules)) $
    Profile.measureAff config.profile "php.write" "" \_ ->
      writeOutput refs.outputs outputDir ModularOutput "runtime.php" Runtime.sharedRuntimeFile

  _ <- traverse
    ( \mainMod -> do
        let
          entryPoint = { mainModule: mainMod, autoloadPath: config.autoloadPath, arities }
          reachableSet = Selection.reachableFrom (Selection.moduleClosure finalModules)
            [ ModuleName mainMod ]
          reachableModules = Array.filter (\(Module m) -> Set.member m.name reachableSet)
            (Array.fromFoldable finalModules)

        if emission.emitBundle then do
          bundleContent <- case mainModule of
            Just _ -> do
              parts <- liftEffect $ Ref.read refs.bundleModulesRef
              let bundleSelected = Array.filter (\item -> Set.member item.name reachableSet) parts
              pure $ Runtime.bundleHeader <> String.joinWith "\n"
                (map (\item -> item.code <> "\n") bundleSelected)
            Nothing -> liftEffect $ Ref.read refs.bundleContentRef
          code <- liftEffect $ Profile.measurePure config.profile "print" mainMod \_ ->
            bundleContent <> "\n" <> printBundleEntryPoint entryPoint
          Profile.measureAff config.profile "php.write" mainMod \_ ->
            writeOutput refs.outputs outputDir BundleOutput (mainMod <> "/main.bundle.php") code
        else pure unit

        when emission.emitModules do
          modEntryPoint <- liftEffect $ Profile.measurePure config.profile "print" mainMod \_ ->
            printModularEntryPoint entryPoint (map (\(Module m) -> m.name) reachableModules)
          when verbose $ liftEffect $ Console.log $ "Generating main.mod.php for " <> mainMod
          Profile.measureAff config.profile "php.write" mainMod \_ ->
            writeOutput refs.outputs outputDir ModularOutput (mainMod <> "/main.mod.php")
              modEntryPoint
    )
    config.targetMainModules

  if emission.emitBundle then do
    case mainModule of
      Just _ -> pure unit
      Nothing -> do
        bundleContent <- liftEffect $ Ref.read refs.bundleContentRef
        Profile.measureAff config.profile "php.write" "" \_ ->
          writeOutput refs.outputs outputDir BundleOutput "bundle.php" bundleContent
  else pure unit

  Profile.measureAff config.profile "composer" "" \_ ->
    liftEffect $ mergeComposers { outputDir, packageRoots: config.composerRoots }
  Profile.measureAff config.profile "cleanup" "" \_ ->
    liftEffect $ finalizeOutputs refs.outputs outputDir emission config.cacheable
