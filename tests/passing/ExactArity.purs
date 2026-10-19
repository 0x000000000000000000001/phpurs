module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Test.Assert (assert')

add :: Int -> Int -> Int
add x y = x + y

use :: Int -> Int
use x = add x 2

makeAdder :: Int -> Int -> Int
makeAdder n x = n + x

choose :: Boolean -> (Int -> Int)
choose b = if b then \x -> x + 1 else \x -> x + 2

main :: Effect Unit
main = do
  assert' "saturated exact call" (use 40 == 42)
  assert' "direct exact call" (add 40 2 == 42)
  let partial = add 40
  assert' "retained partial application" (partial 2 == 42)
  assert' "reused partial application" (partial 3 == 43)
  assert' "closure-valued result" (choose true 41 == 42 && choose false 40 == 42)
  assert' "function value keeps its wrapper" (let f = add in f 40 2 == 42)
  assert' "saturated nested closure" (makeAdder 40 2 == 42)
  log "Done"
