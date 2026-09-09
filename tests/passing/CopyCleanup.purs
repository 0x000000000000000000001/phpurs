module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a

data Tree = End | Fork Int Tree Tree

total :: Tree -> Int
total End = 0
total (Fork n a b) = n + total a + total b

-- The two trees must be swapped simultaneously on each real TCO backedge.
spin :: Int -> Tree -> Tree -> Int
spin n a b = if n <= 0 then 100 * total a + total b else spin (n - 1) b a

score :: Int -> Int
score n = spin n (Fork 10 End End) (Fork 20 (Fork 1 End End) End)

rebuild :: Int -> Tree -> Tree
rebuild n End = Fork n End End
rebuild n (Fork x a b) = if n < 0 then Fork x b a else Fork (x + n) a b

shared :: Int -> Int
shared n =
  let old = Fork 3 (Fork 4 End End) End
      newer = rebuild n old
  in 100 * total old + total newer

main :: Effect Unit
main = do
  let run = opaque score
      retain = opaque shared
  assert' "empty iteration and even swaps" (run 0 == 1021 && run 2 == 1021 && run 20000 == 1021)
  assert' "odd swaps" (run 1 == 2110 && run 3 == 2110 && run 20001 == 2110)
  assert' "terminal branches retain old roots" (retain (-1) == 707 && retain 0 == 707 && retain 8 == 715)
  log "Done"
