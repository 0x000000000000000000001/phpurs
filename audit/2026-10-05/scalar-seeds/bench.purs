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

run :: Int -> Int -> Int -> Int -> Int
run 0 _ _ acc = acc
run repeats depth value acc =
  run (repeats - 1) depth value (acc + force (grow depth (Deferred (\_ -> value))))

main :: Effect Unit
main = log (show (run (opaque 1000) (opaque 1000) (opaque 11) 0))
