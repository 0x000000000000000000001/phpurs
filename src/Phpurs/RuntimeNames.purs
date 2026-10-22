-- | Canonical names of the generated PHP runtime helpers.
module Phpurs.RuntimeNames
  ( curryFallback
  , effectMap
  , executeEffect
  , promise
  , promiseMap
  , qualified
  , record
  , refNew
  , refRead
  , refWrite
  , value
  ) where

import Prelude

curryFallback :: String
curryFallback = "phpurs_curry_fallback"

executeEffect :: String
executeEffect = "phpurs_execute_effect"

-- | Structural JSON-like conversion: associative arrays become stdClass,
-- | lists stay lists. The root follows the value itself.
value :: String
value = "phpurs_value"

-- | Like `value`, but the root is always a record.
record :: String
record = "phpurs_record"

-- | Build the thenable shape expected by the Promise/Aff runtime.
promise :: String
promise = "phpurs_promise"

-- | Map the resolved value of a thenable.
promiseMap :: String
promiseMap = "phpurs_promise_map"

-- | Map the value produced by an Effect thunk.
effectMap :: String
effectMap = "phpurs_effect_map"

refNew :: String
refNew = "phpurs_ref_new"

refRead :: String
refRead = "phpurs_ref_read"

refWrite :: String
refWrite = "phpurs_ref_write"

-- | Fully qualify a helper call from inside a generated PHP namespace.
qualified :: String -> String
qualified name = "\\" <> name
