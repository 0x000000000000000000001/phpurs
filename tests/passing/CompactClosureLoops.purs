module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Foreign (tagOf, typeOf, unsafeToForeign)
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a
foreign import invoke :: (Int -> Int) -> Int -> Int
foreign import inspect :: (Int -> Int) -> Boolean

-- Captures both the prior function and the current iteration's scalar value.
grow :: Int -> (Int -> Int) -> Int -> Int
grow n acc =
  if n <= 0 then acc
  else grow (n - 1) (\x -> acc x + n)

-- Each intermediate root crosses FFI. This loop must retain ordinary closures.
growObserved :: Int -> (Int -> Int) -> Int -> Int
growObserved n acc =
  if n <= 0 then acc
  else if inspect acc then growObserved (n - 1) (\x -> acc x + n)
  else acc

main :: Effect Unit
main = do
  let
    seed = opaque 7
    base = opaque (\x -> seed + x)
    builder = opaque grow
    f = builder (opaque 17) base
    second = builder (opaque 2) f
    empty = builder (opaque 0) base
    observed = opaque growObserved (opaque 17) base
    record = opaque { callback: f }
  assert' "loop captures each old iteration" (f 3 == 163 && f 5 == 165 && f 3 == 163)
  assert' "partial and overapplication" (builder 2 base 3 == 13 && (builder 2) base 5 == 15)
  assert' "extend a public root without changing it" (second 3 == 166 && f 3 == 163)
  assert' "zero depth preserves the base callable" (empty 3 == 10 && invoke empty 5 == 12)
  assert' "public root keeps its Closure contract" (invoke f 3 == 163 && invoke record.callback 5 == 165)
  assert' "Foreign recognizes direct and nested roots" (typeOf (unsafeToForeign f) == "function" && tagOf (unsafeToForeign record.callback) == "Function")
  assert' "intermediate FFI escape retains Closure" (observed 3 == 163 && invoke observed 5 == 165)
  log "Done"
