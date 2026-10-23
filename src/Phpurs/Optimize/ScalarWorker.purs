-- | Shared scalar loop used after a thunk or State chain has been proven safe.
-- | Each pass owns its proof and fallback guard; this module only emits the loop.
module Phpurs.Optimize.ScalarWorker (Step, call, makeWorker) where

import Prelude

import Data.Array.NonEmpty as NEA
import Data.Maybe (Maybe(..))
import Data.Tuple (Tuple(..))
import Phpurs.Optimize.NeutralExpr (int, typed)
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident(..), Qualified)
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax
  ( BackendOperator(..)
  , BackendOperator2(..)
  , BackendOperatorNum(..)
  , BackendOperatorOrd(..)
  , BackendSyntax(..)
  , Level(..)
  , Pair(..)
  )

type Step = { op :: BackendOperatorNum, amount :: Int }

call :: Qualified Ident -> NeutralExpr -> NeutralExpr -> NeutralExpr
call name depth value = NeutralExpr (App (NeutralExpr (Var name)) (NEA.cons' depth [ value ]))

-- Keep one arithmetic operation per original thunk, including PHP's overflow
-- and floating-point rounding behavior. No multiplication by the chain length.
makeWorker :: Qualified Ident -> Step -> NeutralExpr
makeWorker name step = typed (Func [ Int, Int ] Int) $ NeutralExpr $ Abs
  ( NEA.cons' (Tuple (Just (Ident "remaining")) (Level 0))
      [ Tuple (Just (Ident "value")) (Level 1) ]
  )
  ( typed Int
      ( NeutralExpr
          ( Branch
              (NEA.singleton (Pair (binary (OpIntOrd OpEq) remaining (int 0)) value))
              ( typed Int
                  ( call name
                      (binary (OpIntNum OpSubtract) remaining (int 1))
                      (binary (OpIntNum step.op) value (int step.amount))
                  )
              )
          )
      )
  )
  where
  remaining = typed Int (NeutralExpr (Local (Just (Ident "remaining")) (Level 0)))
  value = typed Int (NeutralExpr (Local (Just (Ident "value")) (Level 1)))
  binary op left right = NeutralExpr (PrimOp (Op2 op left right))
