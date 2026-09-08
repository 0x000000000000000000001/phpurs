module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Test.Assert (assert')

data Color = Red | Blue
data Chain = End | Link Color Int Chain

foreign import opaque :: forall a. a -> a
foreign import inspectColor :: Color -> Boolean
foreign import inspectBox :: { color :: Color, chain :: Chain } -> Boolean
foreign import retainColor :: (Unit -> Color) -> Effect Unit
foreign import recalledColor :: Effect Boolean

flipColor :: Color -> Color
flipColor Red = Blue
flipColor Blue = Red

build :: Int -> Color -> Chain -> Chain
build n color acc =
  if n <= 0 then acc
  else build (n - 1) (flipColor color) (Link color n acc)

weight :: Chain -> Int
weight End = 0
weight (Link Red n rest) = n + weight rest
weight (Link Blue n rest) = 2 * n + weight rest

-- Scalar entry/exit; all Color/Chain values can stay in a private region.
score :: Int -> Int
score n = weight (build n Red End)

exportColor :: Int -> Color
exportColor n = if n > 0 then Red else Blue

exportBox :: Int -> { color :: Color, chain :: Chain }
exportBox n = { color: exportColor n, chain: build n Red End }

exportCallback :: Int -> Unit -> Color
exportCallback n _ = exportColor n

-- Each scalar result below hides a different outward flow of an ADT value.
observedColor :: Int -> Int
observedColor n = if inspectColor (exportColor n) then score n else -1

observedBox :: Int -> Int
observedBox n = if inspectBox (exportBox n) then score n else -1

applyUnknown :: (Color -> Int) -> Int -> Int
applyUnknown f n = f (exportColor n)

retainedCallback :: Int -> Effect Unit
retainedCallback n = retainColor (\_ -> exportColor n)

main :: Effect Unit
main = do
  let
    n = opaque 4
    run = opaque score
    builder = opaque build
    first = builder n Red End
    second = builder 2 Blue first
    box = opaque (exportBox n)
    classify = opaque (\c -> case c of
      Red -> 7
      Blue -> 9)
  assert' "scalar region keeps its result" (run n == 14 && run 5 == 21 && run 0 == 0)
  assert' "public constructors and old roots stay boxed" (weight first == 14 && weight second == 19 && weight first == 14)
  assert' "public partial applications keep their contract" (weight ((builder n) Red End) == 14 && weight (builder 0 Blue first) == 14)
  assert' "direct and nested FFI keep enum objects" (inspectColor Red && inspectBox box && observedColor n == 14 && observedBox n == 14)
  assert' "unknown calls keep boxed values" (applyUnknown classify n == 7 && applyUnknown classify 0 == 9)
  assert' "public callback keeps boxed results" (inspectColor (exportCallback n unit))
  retainedCallback n
  firstRecall <- recalledColor
  secondRecall <- recalledColor
  assert' "retained callback keeps boxed results on repeated calls" (firstRecall && secondRecall)
  log "Done"
