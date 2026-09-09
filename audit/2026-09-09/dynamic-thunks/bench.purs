module Main where

import Prelude
import Effect (Effect)
import Effect.Console (log)

foreign import opaque :: forall a. a -> a

newtype Deferred a = Deferred (Unit -> a)

force :: forall a. Deferred a -> a
force (Deferred f) = f unit

grow :: Int -> Deferred Int -> Deferred Int
grow 0 acc = acc
grow n acc = grow (n - 1) (Deferred (\_ -> force acc + 1))

run :: Int -> Int -> Int -> Int
run 0 _ acc = acc
run repeats depth acc = run (repeats - 1) depth (acc + force (grow depth (Deferred (\_ -> 0))))

main :: Effect Unit
main = log (show (run (opaque 1000) (opaque 1000) 0))
