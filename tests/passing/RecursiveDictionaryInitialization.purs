module Main where

import Prelude

import Control.Monad.ST as ST
import Effect (Effect)
import Effect.Console (log)
import Effect.Ref as Ref
import Test.Assert (assert')

foreign import opaque :: forall a. a -> a

main :: Effect Unit
main = do
  valueRef <- Ref.new 20
  functionRef <- Ref.new ((*) 2)

  -- Read the operations through mutable references to retain their runtime
  -- dictionaries instead of reducing every application to a known function.
  applyRef <- Ref.new ((<*>) :: Effect (Int -> Int) -> Effect Int -> Effect Int)
  mapRef <- Ref.new (map :: (Int -> Int) -> Effect Int -> Effect Int)
  effectApply <- Ref.read applyRef
  effectMap <- Ref.read mapRef
  applied <- effectApply (Ref.read functionRef) (Ref.read valueRef)
  mapped <- effectMap (_ + 2) (Ref.read valueRef)
  assert' "Effect Apply dictionary initializes before use" (applied == 40)
  assert' "Effect Functor dictionary initializes before use" (mapped == 22)

  let
    -- Keep the ST Apply dictionary live without relying on mutable ST refs.
    stResult = ST.run (opaque (<*>) (pure ((*) 2)) (pure 20))
  assert' "ST Apply dictionary initializes before use" (stResult == 40)
  log "Done"
