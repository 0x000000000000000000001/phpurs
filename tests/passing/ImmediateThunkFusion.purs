module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Foreign (tagOf, typeOf, unsafeToForeign)
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a
foreign import countedSeed :: Unit -> Int
foreign import readCalls :: Effect Int
foreign import invoke :: (Unit -> Int) -> Int
foreign import countedDepth :: Int -> Int
foreign import readDepthCalls :: Effect Int

newtype Deferred a = Deferred (Unit -> a)

force :: forall a. Deferred a -> a
force (Deferred f) = f unit

grow :: Int -> Deferred Int -> Deferred Int
grow 0 acc = acc
grow n acc = grow (n - 1) (Deferred (\_ -> force acc + 1))

consume :: Unit -> Int
consume _ = force (grow 17 (Deferred (\_ -> 11)))

consumeDepth :: Int -> Int
consumeDepth depth = force (grow depth (Deferred (\_ -> 11)))

main :: Effect Unit
main = do
  assert' "immediate closed chain" (consume (opaque unit) == 28)
  assert' "zero depth" (force (grow 0 (Deferred (\_ -> 11))) == 11)
  let
    unknown = opaque (\_ -> 11)
    dynamic = opaque 17
  assert' "unknown seed" (force (grow 17 (Deferred unknown)) == 28)
  assert' "unknown depth" (force (grow dynamic (Deferred (\_ -> 11))) == 28)
  let checkDepth = opaque consumeDepth
  assert' "dynamic zero" (checkDepth (opaque 0) == 11)
  assert' "dynamic one" (checkDepth (opaque 1) == 12)
  assert' "dynamic long chain" (checkDepth (opaque 1000) == 1011)
  assert' "evaluated depth" (checkDepth (countedDepth 17) == 28)
  depthCalls <- readDepthCalls
  assert' "depth evaluated once" (depthCalls == 1)
  let retained = opaque (grow 17 (Deferred countedSeed))
  before <- readCalls
  assert' "construction stays lazy" (before == 0)
  assert' "first force" (force retained == 21)
  assert' "second force" (force retained == 21)
  after <- readCalls
  assert' "each force calls the seed" (after == 2)
  let Deferred root = retained
  assert' "public closure crosses FFI" (invoke root == 21)
  assert' "public closure representation" (typeOf (unsafeToForeign root) == "function" && tagOf (unsafeToForeign root) == "Function")
  log "Done"
