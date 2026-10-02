-- | Optional, versioned storage. Eligibility keys are supplied by the caller.
module Phpurs.ModuleCache
  ( CacheHooks
  , CacheRequest
  , loadModuleState
  , saveModuleState
  ) where

import Prelude

import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Effect (Effect)
import Effect.Aff (Aff)
import Phpurs.CacheKey (Fingerprint, fingerprintString)
import Phpurs.ModuleState (Emission, ModuleState, supportsEmission)
import PureScript.Backend.Optimizer.CoreFn (ModuleName)

type CacheHooks =
  { load :: ModuleName -> Aff (Maybe ModuleState)
  , store :: ModuleState -> Aff Unit
  }

type CacheRequest =
  { directory :: String
  , key :: Fingerprint
  , moduleName :: ModuleName
  , emission :: Emission
  }

foreign import loadModuleStateImpl
  :: (ModuleState -> Maybe ModuleState)
  -> Maybe ModuleState
  -> String
  -> String
  -> String
  -> Effect (Maybe ModuleState)

foreign import saveModuleStateImpl :: String -> String -> ModuleState -> Effect Boolean

-- | Missing, incompatible or corrupt optional cache data is a miss. Validate
-- | the complete entry before allowing the driver to publish any of its state.
loadModuleState :: CacheRequest -> Effect (Maybe ModuleState)
loadModuleState request = do
  result <- loadModuleStateImpl Just Nothing request.directory (fingerprintString request.key) (unwrap request.moduleName)
  pure case result of
    Just state | supportsEmission request.emission state -> Just state
    _ -> Nothing

-- | Atomically replace one entry. False means the optional cache could not be
-- | written; generated-output failures belong to publishModuleState instead.
saveModuleState :: String -> Fingerprint -> ModuleState -> Effect Boolean
saveModuleState directory key = saveModuleStateImpl directory (fingerprintString key)
