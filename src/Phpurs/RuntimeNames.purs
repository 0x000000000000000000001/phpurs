-- | Canonical names of the generated PHP runtime helpers.
module Phpurs.RuntimeNames
  ( curryFallback
  , executeEffect
  , qualified
  , refNew
  , refRead
  , refWrite
  ) where

import Prelude

curryFallback :: String
curryFallback = "phpurs_curry_fallback"

executeEffect :: String
executeEffect = "phpurs_execute_effect"

refNew :: String
refNew = "phpurs_ref_new"

refRead :: String
refRead = "phpurs_ref_read"

refWrite :: String
refWrite = "phpurs_ref_write"

-- | Fully qualify a helper call from inside a generated PHP namespace.
qualified :: String -> String
qualified name = "\\" <> name
