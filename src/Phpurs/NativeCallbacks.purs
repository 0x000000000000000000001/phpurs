-- Evidence about native callback wrappers, separate from flattened type arity.
-- Foreign evidence is tied to the exact captured PHP bytes used for emission.
module Phpurs.NativeCallbacks (Callbacks, binaryInt, fromForeign) where

import Prelude

import Data.Array as A
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Set (Set)
import Data.Set as Set
import Node.Buffer.Immutable as Bytes
import Node.Encoding (Encoding(..))
import Phpurs.CacheKey (fingerprintBytes, fingerprintString)
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident(..), ModuleName(..), Qualified(..))

-- Members have a native two-argument wrapper, Int accumulator/return checks,
-- and a closed arithmetic body with no callback, effect or stack observation.
type Callbacks = Set (Qualified Ident)

binaryInt :: ExprType -> Boolean
binaryInt (Func [ Int, Int ] Int) = true
binaryInt (Func [ Int ] (Func [ Int ] Int)) = true
binaryInt _ = false

-- phpurs-prelude/src/Data/Semiring.php: both exports are raw binary arithmetic
-- functions. GenNativeForeign emits two-argument wrappers for these exact
-- signatures. A changed/missing/overridden FFI source gets no contract.
semiringSource :: String
semiringSource = "e3a03820f8461b5485f4515d91b6c0f17d092013e8c7cccc2ee8e5448f39b706"

fromForeign :: ModuleName -> Map Ident (Maybe ExprType) -> String -> Callbacks
fromForeign mn@(ModuleName "Data.Semiring") bindings source
  | fingerprintString (fingerprintBytes (Bytes.fromString source UTF8)) == semiringSource =
      Set.fromFoldable $ A.mapMaybe prove [ Ident "intAdd", Ident "intMul" ]
  where
  prove ident = case Map.lookup ident bindings of
    Just (Just ty) | binaryInt ty -> Just (Qualified (Just mn) ident)
    _ -> Nothing
fromForeign _ _ _ = Set.empty
