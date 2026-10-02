-- | Track successfully emitted PHP, including restored modules, for bounded
-- | removal of obsolete compiler-owned outputs after successful finalization.
module Phpurs.OutputManifest
  ( OutputTracker
  , OutputKind(..)
  , newOutputTracker
  , writeOutput
  , finalizeOutputs
  ) where

import Prelude

import Effect (Effect)
import Effect.Aff (Aff)
import Effect.Class (liftEffect)
import Phpurs.FileEmission (writeTextFileIfChanged)

foreign import data OutputTracker :: Type

data OutputKind = ModularOutput | BundleOutput

foreign import newOutputTracker :: Effect OutputTracker
foreign import rememberOutput :: OutputTracker -> Boolean -> String -> String -> Effect Unit

writeOutput :: OutputTracker -> String -> OutputKind -> String -> String -> Aff Unit
writeOutput tracker outputDir kind relative contents = do
  writeTextFileIfChanged (outputDir <> "/" <> relative) contents
  let bundle = case kind of
        ModularOutput -> false
        BundleOutput -> true
  liftEffect $ rememberOutput tracker bundle relative contents

-- | Incomplete/ambiguous input cannot authorize removal. Disabled emission
-- | families retain their previous ownership records as well as their files.
foreign import finalizeOutputs
  :: OutputTracker
  -> String
  -> { emitModules :: Boolean, emitBundle :: Boolean }
  -> Boolean
  -> Effect Unit
