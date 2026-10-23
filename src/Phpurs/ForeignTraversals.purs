-- Exact contracts for the PHP traversal bodies consumed by ArrayCallbacks.
-- Evidence is derived from the same source/signature captures as FFI emission.
module Phpurs.ForeignTraversals (Traversal(..), Traversals, fromForeign) where

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
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident(..), ModuleName(..))

data Traversal = FoldlArray | FilterImpl

derive instance eqTraversal :: Eq Traversal
derive instance ordTraversal :: Ord Traversal
type Traversals = Set Traversal

-- count once, ascending numeric indices, f(acc) before xs[i], no mutation,
-- callback retention or additional effects; the wrapper has three native args.
foldSignature :: ExprType -> Boolean
foldSignature
  ( ForAll vars
      ( Func
          [ Func [ TypeVar b, TypeVar a ] (TypeVar next), TypeVar initial, Array (TypeVar item) ]
          (TypeVar result)
      )
  ) =
  A.length vars == 2 && a /= b && A.elem a vars && A.elem b vars && next == b && initial == b
    && result == b
    && item == a
foldSignature _ = false

-- Unary predicate once per value in foreach insertion order, dense append-only
-- result, no mutation/retention. Fn2 is exported as the raw uncurried FFI value.
filterSignature :: ExprType -> Boolean
filterSignature
  ( ForAll [ a ]
      ( ADT "Data.Function.Uncurried.Fn2" [ "Data", "Function", "Uncurried", "Fn2" ]
          [ Func [ TypeVar item ] Boolean, Array (TypeVar input), Array (TypeVar result) ]
      )
  ) =
  item == a && input == a && result == a
filterSignature _ = false

fromForeign :: ModuleName -> Map Ident (Maybe ExprType) -> String -> Traversals
fromForeign mn bindings source = case contract of
  Just { ident, signature, hashes, traversal }
    | A.elem (fingerprintString (fingerprintBytes (Bytes.fromString source UTF8))) hashes ->
        case Map.lookup (Ident ident) bindings of
          Just (Just ty) | signature ty -> Set.singleton traversal
          _ -> Set.empty
  _ -> Set.empty
  where
  contract = case mn of
    ModuleName "Data.Foldable" -> Just
      { ident: "foldlArray"
      , signature: foldSignature
      , traversal: FoldlArray
      -- Original source and the AST-equivalent formatted source.
      , hashes:
          [ "ea67972f7d24df92cc8eb6c316316c43ee133c114b41c39269b2ebe49f53e19f"
          , "319880b50aa8038935fdf14ade3d69ad8a273cfee3708c8d2c159c5e86656130"
          ]
      }
    ModuleName "Data.Array" -> Just
      { ident: "filterImpl"
      , signature: filterSignature
      , traversal: FilterImpl
      , hashes:
          [ "0233eeb307fce0499fb051f835f21a09c78cdd1447522fbe2b8067e53e7efe64"
          , "fae37d9d4cc2fc9585ae4e7b5d8ef893c7978c24dd8d280424353f30cdd049b4"
          ]
      }
    _ -> Nothing
