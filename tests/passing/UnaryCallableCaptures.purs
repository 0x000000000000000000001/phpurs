module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Effect.Ref as Ref
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a
foreign import invoke :: (Int -> Int) -> Int -> Int
foreign import overapply :: (Int -> Int -> Int) -> Int -> Int -> Int

make :: Int -> Int -> Int
make seed = opaque (\x -> seed + x)

makeNested :: Int -> Int -> Int -> Int
makeNested seed = opaque (\x -> opaque (\y -> seed + x + y))

chain :: Int -> (Int -> Int) -> Int -> Int
chain n acc =
  if n <= 0 then acc
  else chain (n - 1) (opaque (\x -> acc x + n))

main :: Effect Unit
main = do
  let
    a = opaque make (opaque 7)
    b = opaque make (opaque 11)
    nested = opaque makeNested (opaque 20)
    retained = opaque chain 100 (opaque identity)
  assert' "captures stay distinct and reusable" (a 3 == 10 && b 3 == 14 && a 3 == 10)
  assert' "FFI accepts callable captures" (invoke a 2 == 9)
  assert' "nested closure and partial application" (nested 1 2 == 23)
  assert' "FFI overapplication" (overapply nested 1 2 == 23)
  assert' "loop retains old captures" (retained 5 == 5055 && retained 7 == 5057)
  ref <- Ref.new 0
  let update = opaque (\n -> do
        old <- Ref.read ref
        Ref.write (old + n) ref)
  update 2
  update 3
  value <- Ref.read ref
  assert' "captured effect stays deferred and repeats" (value == 5)
  log "Done"
