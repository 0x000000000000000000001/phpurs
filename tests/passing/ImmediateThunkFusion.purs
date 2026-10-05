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
foreign import countedValue :: Int -> Int
foreign import readValueCalls :: Effect Int

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

consumeCaptured :: Int -> Int -> Int
consumeCaptured depth value = force (grow depth (Deferred (\_ -> value)))

consumeStaticCaptured :: Int -> Int
consumeStaticCaptured value = force (grow 17 (Deferred (\_ -> value)))

sumCaptures :: Int -> Int -> Int -> Int -> Int
sumCaptures 0 _ _ acc = acc
sumCaptures repeats depth value acc =
  sumCaptures (repeats - 1) depth (value + 1) (acc + force (grow depth (Deferred (\_ -> value))))

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
  let
    checkCaptured = opaque consumeCaptured
    checkStaticCaptured = opaque consumeStaticCaptured
  assert' "captured zero depth" (checkCaptured (opaque 0) (opaque (-31)) == -31)
  assert' "captured one step" (checkCaptured (opaque 1) (opaque 7) == 8)
  assert' "captured long chain" (checkCaptured (opaque 1000) (opaque 11) == 1011)
  assert' "captured static depth" (checkStaticCaptured (opaque 11) == 28)
  assert' "evaluated scalar capture" (checkCaptured (countedDepth 17) (countedValue 11) == 28)
  valueCalls <- readValueCalls
  allDepthCalls <- readDepthCalls
  assert' "capture and depth each evaluated once" (valueCalls == 1 && allDepthCalls == 2)
  assert' "capture follows current loop parameter" (sumCaptures (opaque 7) (opaque 17) (opaque 11) 0 == 217)
  let
    capturedValue = opaque 31
    retainedCapture = opaque (grow (opaque 17) (Deferred (\_ -> capturedValue)))
  assert' "retained scalar closure first force" (force retainedCapture == 48)
  assert' "retained scalar closure second force" (force retainedCapture == 48)
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
  let Deferred capturedRoot = retainedCapture
  assert' "retained scalar closure crosses FFI" (invoke capturedRoot == 48)
  assert' "retained scalar closure representation" (typeOf (unsafeToForeign capturedRoot) == "function" && tagOf (unsafeToForeign capturedRoot) == "Function")
  log "Done"
