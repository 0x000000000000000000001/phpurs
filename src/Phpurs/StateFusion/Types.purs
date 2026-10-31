-- | Shared types for the State-fusion proof and its rewrite.
module Phpurs.StateFusion.Types (Builder, Layout, Locals, Result) where

import Data.Set (Set)
import Phpurs.Optimize.NeutralExpr (Binder)
import Phpurs.Optimize.ScalarWorker (Step)
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (Ident)

type Layout = { value :: String, state :: String }
type Builder = { layout :: Layout, step :: Step }
type Locals = Set Binder
type Result = { module_ :: BackendModule, privateNames :: Set Ident, checkedDepths :: Set Ident }
