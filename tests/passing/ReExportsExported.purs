module Main where

import Effect.Console (log)
import C (a)

-- The PHP backend only emits modules reachable from `main`. Importing the
-- re-export chain explicitly keeps `A`, `B` and `C` in the output, instead of
-- relying on the JavaScript FFI to require a pruned module at runtime.
main = log a
