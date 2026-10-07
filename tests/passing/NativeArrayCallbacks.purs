module Main where

import Prelude

import Data.Array as Array
import Effect (Effect)
import Effect.Console (log)
import Foreign (tagOf, typeOf, unsafeToForeign)
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a
foreign import newCallback :: Effect (Int -> Int -> Int)
foreign import newPredicate :: Effect (Int -> Boolean)
foreign import setThreshold :: Int -> Effect Unit
foreign import readEvents :: Effect String

sumInput :: Array Int -> Int
sumInput items = Array.foldl (+) 0 items

productInput :: Array Int -> Int
productInput items = Array.foldl (*) 1 items

keep :: Array Int -> Array Int
keep items = Array.filter (\x -> mod x 3 == 0) items

combined :: Array Int -> Int
combined items = Array.foldl (+) 0 (Array.filter (\x -> mod x 3 == 0) items)

captured :: Int -> Array Int -> Array Int
captured bound items = Array.filter (\x -> x > bound) items

main :: Effect Unit
main = do
  let
    sumKnown = opaque sumInput
    productKnown = opaque productInput
    filtered = opaque keep
    together = opaque combined
    items = opaque [ 1, 2, 3, 6, 9 ]
  assert' "empty sum" (sumKnown (opaque []) == 0)
  assert' "empty product" (productKnown (opaque []) == 1)
  assert' "known sum" (sumKnown items == 21)
  assert' "known product" (productKnown (opaque [ 1, 2, 3 ]) == 6)
  assert' "filter preserves order" (filtered items == [ 3, 6, 9 ])
  assert' "input retained" (items == [ 1, 2, 3, 6, 9 ] && together items == 18)
  assert' "negative elements" (filtered (opaque [ -6, -5, 0, 6 ]) == [ -6, 0, 6 ])
  assert' "long array" (together (Array.range 1 (opaque 900)) == 135450)
  assert' "captured predicate" (opaque captured (opaque 5) items == [ 6, 9 ])
  let partial = opaque (Array.foldl (opaque (+)) (opaque 7))
  assert' "partial unknown fold" (partial items == 28 && partial (opaque [ 1 ]) == 8)
  callback <- newCallback
  let retained = opaque (Array.foldl callback 0)
  before <- readEvents
  assert' "partial construction does not call callback" (before == "")
  assert' "two curried stages" (retained (opaque [ 1, 2 ]) == 3)
  assert' "retained callback" (retained (opaque [ 3 ]) == 3)
  events <- readEvents
  assert' "fold callback order" (events == "first:0,second:1,first:1,second:2,first:0,second:3")
  predicate <- newPredicate
  let retainedFilter = opaque (Array.filter predicate)
  assert' "retained filter" (retainedFilter items == [ 3, 6, 9 ])
  setThreshold 5
  assert' "predicate capture is current" (retainedFilter items == [ 6, 9 ])
  visits <- readEvents
  assert' "filter visit order" (visits == events <> ",visit:1,visit:2,visit:3,visit:6,visit:9,visit:1,visit:2,visit:3,visit:6,visit:9")
  assert' "callback Closure boundary" (typeOf (unsafeToForeign callback) == "function" && tagOf (unsafeToForeign callback) == "Function")
  assert' "partial filter Closure boundary" (typeOf (unsafeToForeign retainedFilter) == "function" && tagOf (unsafeToForeign retainedFilter) == "Function")
  log "Done"
