module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Foreign (tagOf, typeOf, unsafeToForeign)
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a
foreign import counted :: Int -> Int
foreign import readCalls :: Effect Int
foreign import invoke :: (Int -> Int) -> Int -> Int

type Chain = (Int -> Int) -> Int -> Int

neutral :: Chain
neutral _ x = x

advance :: Chain -> Chain
advance previous f x = f (previous f x)

assemble :: Int -> Chain
assemble 0 = neutral
assemble n = advance (assemble (n - 1))

compose :: Chain -> Chain -> Chain
compose m n f x = m (n f) x

square :: Int -> Chain
square n = compose (assemble n) (assemble n)

consume :: Int -> Int
consume n = square n (\x -> x + 3) 7

main :: Effect Unit
main = do
  let
    n = opaque 5
    check = opaque consume
  assert' "closed composed chain" (check n == 82)
  assert' "neutral chain" (check (opaque 0) == 7)
  assert' "one element" (check (opaque 1) == 10)
  let
    retained = opaque (assemble n)
    bound = opaque (retained counted)
  before <- readCalls
  assert' "binding does not invoke callback" (before == 0)
  assert' "first retained call" (bound 7 == 22)
  assert' "second retained call" (bound 17 == 32)
  after <- readCalls
  assert' "callback invoked per element" (after == 10)
  let alternate = retained (\x -> x - 2)
  assert' "alternate binding" (alternate 7 == -3)
  assert' "old binding stays usable" (invoke bound 7 == 22)
  assert' "public representation" (typeOf (unsafeToForeign bound) == "function" && tagOf (unsafeToForeign bound) == "Function")
  assert' "unknown callback" (square n (opaque (\x -> x + 3)) 7 == 82)
  log "Done"
