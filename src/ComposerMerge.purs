module Phpurs.ComposerMerge
  ( mergeComposers
  ) where

import Prelude

import Effect (Effect)
import Data.Maybe (Maybe(..))
import Data.Nullable (Nullable, toNullable)

foreign import mergeComposersImpl :: Nullable String -> Effect Unit

mergeComposers :: String -> Effect Unit
mergeComposers dir = mergeComposersImpl (toNullable (if dir == "" then Nothing else Just dir))
