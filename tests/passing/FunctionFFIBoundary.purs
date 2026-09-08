module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Foreign (Foreign, tagOf, typeOf, unsafeFromForeign, unsafeToForeign)
import Test.Assert (assert')

type Handler = { callback :: Int -> Int, label :: String }

foreign import opaque :: forall a. a -> a
foreign import invokeClosure :: (Int -> Int) -> Int -> Int
foreign import invokeRecord :: Handler -> Int -> Int
foreign import roundTripForeign :: Foreign -> Foreign
foreign import retain :: Handler -> Effect Unit
foreign import recall :: Int -> Effect Int

main :: Effect Unit
main = do
  let
    seed = opaque 7
    f = opaque (\x -> seed + x)
    otherSeed = opaque 11
    other = opaque (\x -> otherSeed + x)
    handler = opaque { callback: f, label: "captured" }
    foreignFunction = unsafeToForeign f
    foreignRecord = unsafeToForeign handler
    returned = unsafeFromForeign (roundTripForeign foreignFunction) :: Int -> Int
    returnedRecord = unsafeFromForeign (roundTripForeign foreignRecord) :: Handler
  assert' "direct call preserves captured seed" (f 3 == 10 && f 5 == 12)
  assert' "record preserves callable capture" (handler.callback 3 == 10 && handler.label == "captured")
  assert' "Foreign recognizes the function" (typeOf foreignFunction == "function" && tagOf foreignFunction == "Function")
  assert' "Foreign recognizes the record and its callback" (typeOf foreignRecord == "object" && tagOf foreignRecord == "Object" && typeOf (unsafeToForeign handler.callback) == "function" && tagOf (unsafeToForeign handler.callback) == "Function")
  assert' "typed PHP Closure accepts direct and record routes" (invokeClosure f 3 == 10 && invokeClosure handler.callback 3 == 10 && invokeRecord handler 3 == 10)
  assert' "Foreign return preserves invocation and tags" (returned 3 == 10 && invokeClosure returned 4 == 11 && typeOf (unsafeToForeign returned) == "function" && tagOf (unsafeToForeign returned) == "Function")
  assert' "Foreign record return preserves nested function" (returnedRecord.callback 5 == 12 && invokeRecord returnedRecord 6 == 13 && tagOf (unsafeToForeign returnedRecord.callback) == "Function")
  retain handler
  first <- recall 1
  second <- recall 2
  assert' "FFI retains and repeatedly invokes the original capture" (first == 8 && second == 9)
  retain { callback: other, label: "other" }
  third <- recall 1
  assert' "independent captures survive FFI retention" (third == 12 && invokeClosure f 1 == 8)
  log "Done"
