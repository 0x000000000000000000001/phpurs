-- | Shared inspection and construction of the optimizer's typed expressions.
-- | These helpers do not establish a pass-specific purity or ownership proof.
module Phpurs.Optimize.NeutralExpr
  ( Binder
  , bounded
  , hasType
  , int
  , integer
  , isLocalBinder
  , local
  , peel
  , require
  , signature
  , typed
  ) where

import Prelude

import Data.Foldable (foldl, foldr)
import Data.List (List(..))
import Data.Maybe (Maybe(..))
import Data.Tuple (Tuple(..))
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident, Literal(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..), Level)
import PureScript.Backend.Optimizer.Syntax as Syn

type Binder = Tuple (Maybe Ident) Level

require :: Boolean -> Maybe Unit
require true = Just unit
require false = Nothing

-- | Check size, depth and immediate width before a pass walks recursively.
bounded :: Int -> NeutralExpr -> Boolean
bounded cap expr = go 0 (Cons (Tuple 0 expr) Nil)
  where
  go count _ | count > cap = false
  go _ Nil = true
  go count (Cons (Tuple depth (NeutralExpr syntax)) rest)
    | depth > 96 || foldl (\n _ -> n + 1) 0 syntax > 64 = false
    | otherwise = go (count + 1)
        (foldr (\child acc -> Cons (Tuple (depth + 1) child) acc) rest syntax)

peel :: NeutralExpr -> BackendSyntax NeutralExpr
peel (NeutralExpr (Typed _ e)) = peel e
peel (NeutralExpr (Syn.TypeApp e _)) = peel e
peel (NeutralExpr syntax) = syntax

hasType :: ExprType -> NeutralExpr -> Boolean
hasType ty (NeutralExpr (Typed actual _)) = ty == actual
hasType ty (NeutralExpr (Syn.TypeApp e _)) = hasType ty e
hasType _ _ = false

local :: Level -> NeutralExpr -> Boolean
local level expr = case peel expr of
  Local _ actual -> level == actual
  _ -> false

integer :: NeutralExpr -> Maybe Int
integer expr = case peel expr of
  Lit (LitInt n) -> Just n
  _ -> Nothing

typed :: ExprType -> NeutralExpr -> NeutralExpr
typed ty = NeutralExpr <<< Typed ty

int :: Int -> NeutralExpr
int = typed Int <<< NeutralExpr <<< Lit <<< LitInt

-- | Match both components when the proof depends on the exact binder.
isLocalBinder :: Binder -> NeutralExpr -> Boolean
isLocalBinder binder expr = case peel expr of
  Local ident level -> binder == Tuple ident level
  _ -> false

-- | Find the first function annotation, retaining its argument and result types.
signature :: NeutralExpr -> Maybe { args :: Array ExprType, ret :: ExprType }
signature (NeutralExpr (Typed (Func args ret) _)) = Just { args, ret }
signature (NeutralExpr (Typed _ e)) = signature e
signature (NeutralExpr (Syn.TypeApp e _)) = signature e
signature _ = Nothing
