-- | Reuse exact scope-aware results for immutable TcoExpr nodes. Renaming and
-- | capture-by-reference decisions remain local to each translation context.
module Phpurs.FreeVars (freeVars) where

import Data.Set (Set)
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr)
import PureScript.Backend.Optimizer.FreeVars (freeVarsWith)

foreign import memoizeFreeVars
  :: ((TcoExpr -> Set String) -> TcoExpr -> Set String)
  -> TcoExpr
  -> Set String

freeVars :: TcoExpr -> Set String
freeVars = memoizeFreeVars freeVarsWith
