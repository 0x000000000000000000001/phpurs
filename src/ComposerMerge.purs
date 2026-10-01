-- | Collect Composer requirements from package roots and already loaded modules.
module Phpurs.ComposerMerge
  ( ComposerOptions
  , mergeComposers
  ) where

import Prelude (Unit)

import Data.Maybe (Maybe)
import Data.Nullable (Nullable, toNullable)
import Effect (Effect)

-- | Module paths arrive in module-name order to preserve dependency precedence.
type ComposerOptions =
  { outputDir :: String
  , ffiDir :: Maybe String
  , modulePaths :: Array String
  }

foreign import mergeComposersImpl
  :: { outputDir :: String, ffiDir :: Nullable String, modulePaths :: Array String }
  -> Effect Unit

mergeComposers :: ComposerOptions -> Effect Unit
mergeComposers { outputDir, ffiDir, modulePaths } =
  mergeComposersImpl { outputDir, ffiDir: toNullable ffiDir, modulePaths }
