module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a
foreign import stamp :: Int -> Effect Int
foreign import reset :: Effect Unit
foreign import events :: Effect String

data Box = Box Int
data Choice = LeftBox Box | RightBox Box

choose :: Choice -> Box -> Box
choose value fallback = case value of
  LeftBox (Box n) -> if n > 0 then Box (n + 1) else fallback
  RightBox (Box n) -> Box (n + 2)

caller :: Choice -> Box -> Box
caller value fallback = choose value fallback

ordered :: Int -> Effect Box
ordered n = do
  first <- stamp n
  second <- stamp (n + 1)
  pure (choose (LeftBox (Box first)) (Box second))

unbox :: Box -> Int
unbox (Box n) = n

main :: Effect Unit
main = do
  let
    a = opaque (LeftBox (Box 7))
    b = opaque (RightBox (Box 11))
    f = opaque choose
    partial = f a
    captured = opaque (\fallback -> caller a (Box fallback))
  assert' "terminal branches" (unbox (opaque caller a (Box 0)) == 8 && unbox (opaque caller b (Box 0)) == 13)
  assert' "fallback branch" (unbox (opaque caller (opaque (LeftBox (Box (-2)))) (Box 30)) == 30)
  assert' "function remains available as a value" (unbox (partial (Box 0)) == 8)
  assert' "caller retains its outer capture" (unbox (captured 50) == 8)
  reset
  actual <- opaque ordered (opaque 4)
  assert' "arguments evaluated once" (unbox actual == 5)
  trace <- events
  assert' "arguments evaluated left to right including unused fallback" (trace == "4,5")
  log "Done"
