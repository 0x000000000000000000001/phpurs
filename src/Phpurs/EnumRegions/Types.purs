-- | Shared state for the enum-region proof and rewrite.
module Phpurs.EnumRegions.Types (Check, CheckState, Constructor, Context, initial, regionNodeCount) where

import Prelude

import Control.Monad.State.Trans (StateT)
import Data.Foldable (foldl, foldr)
import Data.List (List(..))
import Data.Map (Map)
import Data.Maybe (Maybe)
import Data.Set (Set)
import Data.Set as Set
import Data.Tuple (Tuple(..))
import Phpurs.Budget as Budget
import PureScript.Backend.Optimizer.CoreFn
  ( ConstructorType
  , DataDecl
  , ExprType
  , Ident
  , ModuleName
  , ProperName
  , Qualified
  )
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))

type Context =
  { name :: ModuleName
  , bindings :: Map Ident NeutralExpr
  , layouts :: Map String DataDecl
  , constructors :: Map (Qualified Ident) Constructor
  }

type Constructor =
  { type_ :: ExprType
  , fields :: Array ExprType
  , tag :: Maybe Int
  , constructorType :: ConstructorType
  , typeName :: ProperName
  }

type CheckState =
  { fuel :: Int
  , depth :: Int
  , workers :: Set Ident
  , layouts :: Set String
  , usesCompactLayout :: Boolean
  }

type Check a = StateT CheckState Maybe a

regionNodeCount :: Int -> NeutralExpr -> Int
regionNodeCount cap expr = go 0 (Cons (Tuple 0 expr) Nil)
  where
  go count _ | count >= cap = cap
  go count Nil = count
  go count (Cons (Tuple depth (NeutralExpr syntax)) rest)
    | depth > Budget.sizeDepth || foldl (\n _ -> n + 1) 0 syntax > Budget.sizeWidth = cap
    | otherwise = go (count + 1)
        (foldr (\child acc -> Cons (Tuple (depth + 1) child) acc) rest syntax)

initial :: CheckState
initial =
  { fuel: Budget.regionNodes
  , depth: 0
  , workers: Set.empty
  , layouts: Set.empty
  , usesCompactLayout: false
  }
