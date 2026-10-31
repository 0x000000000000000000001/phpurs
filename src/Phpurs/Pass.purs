-- | Shared protocol for local optimization passes: a pure `BackendModule`
-- | rewrite that also reports the private names it introduced. Each pass keeps
-- | its own proofs and budgets; this module owns the scaffolding they repeat.
module Phpurs.Pass
  ( Result
  , Pass
  , unchanged
  , smallModule
  , maxModuleBindings
  , maxGroupBindings
  , scanNodes
  , scanGroups
  , scanGroupsWithin
  , privateName
  ) where

import Prelude

import Control.Monad.State (State)
import Data.Array as A
import Data.Newtype (unwrap)
import Data.Set (Set)
import Data.Set as Set
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Phpurs.Budget as Budget
import Phpurs.Optimize.NeutralExpr (bounded)
import PureScript.Backend.Optimizer.Convert (BackendBindingGroup, BackendModule)
import PureScript.Backend.Optimizer.CoreFn (Ident(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr)

type Result = { module_ :: BackendModule, privateNames :: Set Ident }

-- | A pass never performs IO and never mutates its input module.
type Pass = BackendModule -> Result

unchanged :: BackendModule -> Result
unchanged mod = { module_: mod, privateNames: Set.empty }

maxModuleBindings :: Int
maxModuleBindings = Budget.moduleBindings

maxGroupBindings :: Int
maxGroupBindings = Budget.groupBindings

-- | Whether the local passes may scan this module. Passes with extra limits
-- | (data declarations, constructor widths) compose their own guard with this.
smallModule :: BackendModule -> Boolean
smallModule mod =
  A.length mod.bindings <= maxModuleBindings
    && A.all (\group -> A.length group.bindings <= maxGroupBindings) mod.bindings

-- | Expressions above the scan budget are left untouched; the pass still sees
-- | the rest of the module.
scanNodes :: Int
scanNodes = Budget.scanNodes

-- | Apply a stateful expression rewrite to every binding in the module.
scanGroups
  :: forall s
   . (NeutralExpr -> State s NeutralExpr)
  -> BackendModule
  -> State s (Array (BackendBindingGroup Ident NeutralExpr))
scanGroups = scanGroupsWithin (bounded scanNodes)

-- | Like `scanGroups`, with a pass-specific eligibility predicate. Passes that
-- | count nodes differently keep their own gate without duplicating the walk.
scanGroupsWithin
  :: forall s
   . (NeutralExpr -> Boolean)
  -> (NeutralExpr -> State s NeutralExpr)
  -> BackendModule
  -> State s (Array (BackendBindingGroup Ident NeutralExpr))
scanGroupsWithin within scan mod = traverse scanGroup mod.bindings
  where
  scanGroup group = do
    bindings <- traverse scanBinding group.bindings
    pure group { bindings = bindings }

  scanBinding (Tuple name expr) = Tuple name <$> scanExpr expr

  scanExpr expr
    | within expr = scan expr
    | otherwise = pure expr

privateName :: String -> Ident -> Ident
privateName prefix name = Ident (prefix <> unwrap name)
