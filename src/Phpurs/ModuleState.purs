-- | Per-module output and one publication path for fresh and restored modules.
module Phpurs.ModuleState
  ( Emission
  , ModuleState
  , BuildRefs
  , newBuildRefs
  , renderModuleState
  , TranslatedModule
  , translateModuleState
  , printModuleState
  , supportsEmission
  , publishModuleState
  , publishModuleStateWithProfile
  ) where

import Prelude

import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), isJust)
import Data.Newtype (unwrap)
import Data.Set (Set)
import Effect (Effect)
import Effect.Aff (Aff, attempt, throwError)
import Effect.Class (liftEffect)
import Effect.Exception (error)
import Effect.Ref (Ref)
import Effect.Ref as Ref
import Node.FS.Aff as FS
import Phpurs.BuildProfile as Profile
import Phpurs.CodeGen (translate)
import Phpurs.GenNativeForeign (genForeignModule)
import Phpurs.OutputManifest (OutputKind(..), OutputTracker, newOutputTracker, writeOutput)
import Phpurs.PhpAst (PhpFile)
import Phpurs.Printer (printPhpFile)
import PureScript.Backend.Optimizer.Convert (BackendImplementations, BackendModule)
import PureScript.Backend.Optimizer.CoreFn (ModuleName)

type Emission = { emitModules :: Boolean, emitBundle :: Boolean }

type ModuleState =
  { backend :: BackendModule
  , arities :: Map String Int
  , modularPhp :: Maybe String
  , bundlePhp :: Maybe String
  }

type BuildRefs =
  { bundleContentRef :: Ref String
  , outputs :: OutputTracker
  , globalAritiesRef :: Ref (Map String Int)
  , backendModulesRef :: Ref (Map ModuleName { imports :: Set ModuleName, implementations :: BackendImplementations })
  }

newBuildRefs :: Effect BuildRefs
newBuildRefs = do
  outputs <- newOutputTracker
  bundleContentRef <- Ref.new "<?php\n\n"
  globalAritiesRef <- Ref.new Map.empty
  backendModulesRef <- Ref.new Map.empty
  pure { bundleContentRef, globalAritiesRef, backendModulesRef, outputs }

renderModuleState :: Emission -> Array (Array String) -> String -> Map String Int -> BackendModule -> ModuleState
renderModuleState modes imports foreignSource currentArities backend =
  printModuleState modes (translateModuleState imports foreignSource currentArities backend)

type TranslatedModule =
  { backend :: BackendModule
  , phpFile :: PhpFile
  , foreignCode :: String
  , arities :: Map String Int
  , allArities :: Map String Int
  }

translateModuleState :: Array (Array String) -> String -> Map String Int -> BackendModule -> TranslatedModule
translateModuleState imports foreignSource currentArities backend =
  let
    phpFile = translate imports backend
    foreignModule = genForeignModule { moduleName: backend.name, bindings: backend.foreign, source: foreignSource }
    -- Store this module's contribution, not a copy of the preceding environment.
    arities = Map.union foreignModule.arities phpFile.arities
    allArities = Map.union arities currentArities
  in
    { backend, phpFile, foreignCode: foreignModule.code, arities, allArities }

printModuleState :: Emission -> TranslatedModule -> ModuleState
printModuleState modes { backend, phpFile, foreignCode, arities, allArities } =
  { backend
  , arities
  , modularPhp: if modes.emitModules then Just (printPhpFile false foreignCode allArities phpFile) else Nothing
  , bundlePhp: if modes.emitBundle then Just (printPhpFile true foreignCode allArities phpFile) else Nothing
  }

supportsEmission :: Emission -> ModuleState -> Boolean
supportsEmission modes state =
  (not modes.emitModules || isJust state.modularPhp) && (not modes.emitBundle || isJust state.bundlePhp)

-- | Call exactly once, in builder order, on hits as well as misses. Output write
-- | failures propagate; cache I/O must not catch them or turn them into misses.
publishModuleState :: Emission -> String -> BuildRefs -> ModuleState -> Aff Unit
publishModuleState = publishModuleStateWithProfile Profile.disabled

publishModuleStateWithProfile :: Profile.Profile -> Emission -> String -> BuildRefs -> ModuleState -> Aff Unit
publishModuleStateWithProfile profile modes outputDir refs state = do
  unless (supportsEmission modes state) $ throwError $ error "Incomplete PHP module state"
  let name = state.backend.name
  when modes.emitModules do
    liftEffect $ Ref.modify_ (Map.insert name { imports: state.backend.imports, implementations: state.backend.implementations }) refs.backendModulesRef
  _ <- Profile.measureAff profile "php.mkdir" (unwrap name) \_ -> attempt (FS.mkdir (outputDir <> "/" <> unwrap name))
  liftEffect $ Ref.modify_ (Map.union state.arities) refs.globalAritiesRef
  when modes.emitBundle case state.bundlePhp of
    Just code -> liftEffect $ Ref.modify_ (\previous -> previous <> code <> "\n") refs.bundleContentRef
    Nothing -> pure unit
  when modes.emitModules case state.modularPhp of
    Just code -> Profile.measureAff profile "php.write" (unwrap name) \_ ->
      writeOutput refs.outputs outputDir ModularOutput (unwrap name <> "/index.php") code
    Nothing -> pure unit
