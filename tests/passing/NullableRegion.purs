module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Test.Assert (assert')

-- No enum is needed to select a closed nullable region.
data Chain = Stop | Cell Int Chain
data Wrapped = Missing | Present Chain

foreign import opaque :: forall a. a -> a
foreign import inspectChain :: Chain -> Boolean
foreign import inspectWrapped :: Wrapped -> Boolean
foreign import observe :: (Chain -> Int) -> Chain -> Int

build :: Int -> Chain
build n = if n <= 0 then Stop else Cell n (build (n - 1))

total :: Chain -> Int
total Stop = 0
total (Cell n rest) = n + total rest

score :: Int -> Int
score n = total (build n)

makeWrapped :: Int -> Wrapped
makeWrapped n = if n < 0 then Missing else Present (build n)

nestedWeight :: Wrapped -> Int
nestedWeight Missing = -100
nestedWeight (Present chain) = 1000 + total chain

nestedScore :: Int -> Int
nestedScore n = nestedWeight (makeWrapped n)

observedScore :: Int -> Int
observedScore n = observe total (build n)

main :: Effect Unit
main = do
  let
    n = opaque 4
    run = opaque score
    nested = opaque nestedScore
    publicBuild = opaque build
    publicWrap = opaque makeWrapped
    old = publicBuild n
    extended = Cell 100 old
    callback = opaque (\_ -> publicBuild n)
  assert' "nullable-only scalar region" (run n == 10 && run 0 == 0 && run 17 == 153)
  assert' "nested empty remains distinct from outer empty" (nested (-1) == -100 && nested 0 == 1000 && nested n == 1010)
  assert' "public roots remain persistent" (total old == 10 && total extended == 110 && total old == 10)
  assert' "public empty and nodes stay boxed at FFI" (inspectChain old && inspectChain (publicBuild 0))
  assert' "nested public layouts stay boxed at FFI" (inspectWrapped (publicWrap (-1)) && inspectWrapped (publicWrap 0) && inspectWrapped (publicWrap n))
  assert' "unknown consumer keeps public tree" (observedScore n == 10 && observe total old == 10)
  assert' "retained callback keeps boxed results" (inspectChain (callback unit) && total (callback unit) == 10)
  log "Done"
