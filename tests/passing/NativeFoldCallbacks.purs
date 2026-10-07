module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Foreign (tagOf, typeOf, unsafeToForeign)
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a
foreign import newCallback :: Effect (Int -> Int -> Int)
foreign import readEvents :: Effect String

data Chain a = End | Cell a (Chain a)

reduce :: forall a b. (b -> a -> b) -> b -> Chain a -> b
reduce _ acc End = acc
reduce f acc (Cell item rest) = reduce f (f acc item) rest

sumInput :: Chain Int -> Int
sumInput items = reduce (+) 0 items

productInput :: Chain Int -> Int
productInput items = reduce (*) 1 items

dynamicInitial :: Int -> Chain Int -> Int
dynamicInitial initial items = reduce (+) initial items

build :: Int -> Chain Int
build n = go n End
  where
  go 0 acc = acc
  go remaining acc = go (remaining - 1) (Cell remaining acc)

main :: Effect Unit
main = do
  let
    sumKnown = opaque sumInput
    productKnown = opaque productInput
    items = opaque (Cell 1 (Cell 2 (Cell 3 End)))
  assert' "empty sum" (sumKnown (opaque End) == 0)
  assert' "empty product" (productKnown (opaque End) == 1)
  assert' "native addition" (sumKnown items == 6)
  assert' "native multiplication" (productKnown items == 6)
  assert' "negative elements" (sumKnown (opaque (Cell (-7) (Cell 3 End))) == -4)
  assert' "long traversal" (sumKnown (build (opaque 900)) == 405450)
  assert' "input is reusable" (sumKnown items == 6 && productKnown items == 6)
  assert' "dynamic initial remains supported" (opaque dynamicInitial (opaque 7) items == 13)
  let
    unknown = opaque (+)
    partial = opaque (reduce unknown (opaque 0))
  assert' "unknown callback" (partial items == 6)
  assert' "partial traversal is reusable" (partial (opaque (Cell 7 End)) == 7)
  callback <- newCallback
  let retained = opaque (reduce callback 0)
  before <- readEvents
  assert' "partial construction does not invoke callback" (before == "")
  assert' "two curried stages" (retained (opaque (Cell 1 (Cell 2 End))) == 3)
  assert' "retained callback reused" (retained (opaque (Cell 3 End)) == 3)
  events <- readEvents
  assert' "stages keep their order" (events == "first:0,second:1,first:1,second:2,first:0,second:3")
  assert' "callback Closure boundary" (typeOf (unsafeToForeign callback) == "function" && tagOf (unsafeToForeign callback) == "Function")
  assert' "partial traversal Closure boundary" (typeOf (unsafeToForeign retained) == "function" && tagOf (unsafeToForeign retained) == "Function")
  log "Done"
