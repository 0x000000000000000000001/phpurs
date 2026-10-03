-- | Count occurrences in the optimized expression tree, including Typed and
-- | TypeApp wrappers, but excluding type metadata and binding/group wrappers.
module Phpurs.AstMetrics (countNodes, countModuleNodes) where

import Prelude

import Data.Foldable (foldMap, foldl)
import Data.List (List(..))
import Data.List as List
import Data.Tuple (Tuple(..))
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))

countNodes :: NeutralExpr -> Int
countNodes root = go 0 (Cons root Nil)
  where
  go total Nil = total
  go total (Cons (NeutralExpr syntax) remaining) =
    -- The syntax Foldable visits every immediate child. Collect into a List
    -- rather than foldlDefault's composed functions, which can overflow on a
    -- wide node. This tail-recursive worklist also handles deep expressions.
    let children = foldMap List.singleton syntax
    in go (total + 1) (foldl (flip Cons) remaining children)

countModuleNodes :: BackendModule -> Int
countModuleNodes backend = foldl countGroup 0 backend.bindings
  where
  countGroup total group = foldl (\acc (Tuple _ expr) -> acc + countNodes expr) total group.bindings
