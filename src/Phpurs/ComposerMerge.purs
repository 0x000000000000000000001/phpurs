-- | Collect Composer requirements from resolved, ordered package roots.
module Phpurs.ComposerMerge
  ( ComposerOptions
  , mergeComposers
  ) where

import Prelude (Unit)

import Effect (Effect)

-- | Roots arrive deduplicated in manifest precedence order.
type ComposerOptions =
  { outputDir :: String
  , packageRoots :: Array String
  }

foreign import mergeComposersImpl :: ComposerOptions -> Effect Unit

mergeComposers :: ComposerOptions -> Effect Unit
mergeComposers = mergeComposersImpl
