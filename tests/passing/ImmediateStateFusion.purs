module Main where

import Prelude

import Effect (Effect)
import Effect.Console (log)
import Foreign (tagOf, typeOf, unsafeToForeign)
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a
foreign import countedDepth :: Int -> Int
foreign import countedInitial :: Int -> Int
foreign import readInputs :: Effect String
foreign import observedStep :: Int -> Int
foreign import readCalls :: Effect Int
foreign import invoke :: (Int -> { result :: Unit, store :: Int }) -> Int -> Int

-- Different names and record labels from the performance corpus.
newtype Store s a = Store (s -> { result :: a, store :: s })

runStore :: forall s a. Store s a -> s -> { result :: a, store :: s }
runStore (Store f) s = f s

bindStore :: forall s a b. Store s a -> (a -> Store s b) -> Store s b
bindStore (Store f) g = Store \s ->
  let
    first = f s
    Store next = g first.result
  in next first.store

pureStore :: forall s a. a -> Store s a
pureStore value = Store \s -> { result: value, store: s }

readStore :: forall s. Store s s
readStore = Store \s -> { result: s, store: s }

writeStore :: forall s. s -> Store s Unit
writeStore s = Store \_ -> { result: unit, store: s }

changeStore :: forall s. (s -> s) -> Store s Unit
changeStore f = bindStore readStore \s -> writeStore (f s)

assemble :: Int -> Store Int Unit
assemble 0 = pureStore unit
assemble n = bindStore (changeStore (\s -> s + 3)) \_ -> assemble (n - 1)

consume :: Int -> Int -> Int
consume depth initial = (runStore (assemble depth) initial).store

staticConsume :: Int -> Int
staticConsume initial = (runStore (assemble 17) initial).store

unknownChain :: Int -> (Int -> Int) -> Store Int Unit
unknownChain 0 _ = pureStore unit
unknownChain n step = bindStore (changeStore step) \_ -> unknownChain (n - 1) step

main :: Effect Unit
main = do
  let check = opaque consume
  assert' "zero depth" (check (opaque 0) (opaque (-31)) == -31)
  assert' "one step" (check (opaque 1) (opaque 11) == 14)
  assert' "dynamic chain" (check (opaque 60) (opaque 11) == 191)
  assert' "static chain" (opaque staticConsume (opaque 11) == 62)
  assert' "ordered inputs" (check (countedDepth 17) (countedInitial 11) == 62)
  inputs <- readInputs
  assert' "each input once, in order" (inputs == "depth,initial")
  let
    retained = opaque (assemble (opaque 17))
    first = runStore retained (opaque 11)
    second = runStore retained (opaque (-31))
    Store root = retained
  assert' "retained Store" (first.store == 62 && second.store == 20)
  assert' "public result field" (first.result == unit && second.result == unit)
  assert' "public Closure FFI" (invoke root 7 == 58)
  assert' "public function representation" (typeOf (unsafeToForeign root) == "function" && tagOf (unsafeToForeign root) == "Function")
  let saved = opaque (unknownChain (opaque 3) observedStep)
  before <- readCalls
  assert' "unknown callback remains deferred" (before == 0)
  assert' "unknown first run" ((runStore saved (opaque 11)).store == 17)
  assert' "unknown second run" ((runStore saved (opaque 7)).store == 13)
  after <- readCalls
  assert' "callback executes once per transition and run" (after == 6)
  let
    computed = bindStore (pureStore (opaque (\x -> x + 5))) \f -> pureStore (f 7)
    result = runStore (opaque computed) (opaque 10)
  assert' "callable result is kept separate from state" (result.result == 12 && result.store == 10)
  log "Done"
