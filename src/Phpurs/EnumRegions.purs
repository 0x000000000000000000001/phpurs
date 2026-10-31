-- | Closed scalar computations can use private ADT layouts. The proof lives in
-- | `EnumRegions.Proof`; the rewrite in `EnumRegions.Rewrite`.
module Phpurs.EnumRegions (module Rewrite, nodeBudget, workerBudget) where

import Phpurs.Budget as Budget
import Phpurs.EnumRegions.Rewrite (optimize) as Rewrite

nodeBudget :: Int
nodeBudget = Budget.regionNodes

workerBudget :: Int
workerBudget = Budget.workerLimit
