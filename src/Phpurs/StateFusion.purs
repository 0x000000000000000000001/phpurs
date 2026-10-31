-- | Immediately execute a proven State chain in a private scalar loop. The
-- | proof lives in `StateFusion.Prove`; the rewrite in `StateFusion.Rewrite`.
module Phpurs.StateFusion (Result, optimize, workerBudget) where

import Prelude

import Data.Array as A
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Set as Set
import Data.Tuple (Tuple(..))
import Phpurs.Budget as Budget
import Phpurs.Optimize.NeutralExpr (bounded)
import Phpurs.Pass (smallModule)
import Phpurs.StateFusion.Prove (proveBuilder)
import Phpurs.StateFusion.Rewrite (fuseModule)
import Phpurs.StateFusion.Types as Types
import PureScript.Backend.Optimizer.Convert (BackendModule)

type Result = Types.Result

workerBudget :: Int
workerBudget = Budget.workerLimit

optimize :: BackendModule -> Result
optimize mod
  | not (smallModule mod) =
      { module_: mod, privateNames: Set.empty, checkedDepths: Set.empty }
  | otherwise =
      let
        helpers = Map.fromFoldable
          ( A.concatMap
              ( \g ->
                  if g.recursive then []
                  else A.filter (\(Tuple _ e) -> bounded Budget.proofNodes e) g.bindings
              )
              mod.bindings
          )
        builders = Map.fromFoldable
          ( A.mapMaybe
              ( \g -> case g.recursive, g.bindings of
                  true, [ Tuple ident expr ] -> Tuple ident <$> proveBuilder mod.name helpers ident
                    expr
                  _, _ -> Nothing
              )
              mod.bindings
          )
      in
        if Map.isEmpty builders then unchanged else fuseModule mod builders
      where
      unchanged = { module_: mod, privateNames: Set.empty, checkedDepths: Set.empty }
