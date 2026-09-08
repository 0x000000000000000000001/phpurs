module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a

data Tree = Leaf Int | Fork Tree Tree

branch :: Int -> Tree -> Int
branch outside tree = case tree of
  Fork (Leaf x) (Leaf y) ->
    if x > y then outside + 100 * x + y
    else outside + 100 * y + x
  Fork (Fork (Leaf x) (Leaf y)) (Leaf z) -> outside + 100 * x + 10 * y + z
  Fork (Leaf x) (Fork (Leaf y) (Leaf z)) -> outside + 100 * z + 10 * y + x
  Leaf x -> outside + x
  _ -> outside

capture :: Tree -> Int -> Int
capture tree = case tree of
  Leaf value -> opaque (\delta -> value + delta)
  Fork (Leaf value) _ -> opaque (\delta -> 2 * value + delta)
  _ -> opaque (\delta -> delta)

recursiveCapture :: Tree -> Int -> Int
recursiveCapture tree = case tree of
  Leaf value ->
    let
      go n = if n <= 0 then value else value + opaque go (n - 1)
    in opaque go
  _ -> opaque (\n -> n)

keepCaptures :: Int -> (Int -> Int) -> Int
keepCaptures n previous =
  if n <= 0 then previous 5
  else case opaque (Leaf n) of
    Leaf value -> keepCaptures (n - 1) (opaque (\delta -> previous delta + value))
    _ -> previous 5

main :: Effect Unit
main = do
  let
    run = opaque branch
    left = opaque (Fork (Leaf 3) (Leaf 4))
    right = opaque (Fork (Leaf 8) (Leaf 2))
    outside = opaque 1000
  assert' "exclusive alternatives" (run outside left == 1403 && run outside right == 1802)
  assert' "nested left branch" (run outside (opaque (Fork (Fork (Leaf 1) (Leaf 2)) (Leaf 3))) == 1123)
  assert' "nested right branch" (run outside (opaque (Fork (Leaf 1) (Fork (Leaf 2) (Leaf 3)))) == 1321)
  assert' "value remains live after branch" (run outside left + outside == 2403)
  let
    first = opaque capture (opaque (Leaf 7))
    second = opaque capture (opaque (Fork (Leaf 11) (Leaf 0)))
  assert' "closures preserve their branch values" (first 3 == 10 && second 3 == 25 && first 3 == 10)
  assert' "recursive reference preserves captured value" (opaque recursiveCapture (opaque (Leaf 4)) 3 == 16)
  assert' "loop preserves previous captures" (opaque keepCaptures 10 (opaque identity) == 60)
  log "Done"
