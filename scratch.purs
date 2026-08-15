module Scratch where

import Prelude
import Data.Maybe (Maybe(..))
import Data.Map as Map
import Data.Set as Set
import Effect (Effect)
import Effect.Console as Console
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..))
import PureScript.Backend.Optimizer.Convert (BackendModule)

countNodes :: NeutralExpr -> Int
countNodes (NeutralExpr expr) = 1 + case expr of
  Var _ -> 0
  Local _ _ -> 0
  Lit _ -> 0
  App f args -> countNodes f + sum (map countNodes args)
  Abs _ body -> countNodes body
  UncurriedAbs _ body -> countNodes body
  Let _ _ val body -> countNodes val + countNodes body
  LetRec binds body -> sum (map (\b -> countNodes b.expr) binds) + countNodes body
  Accessor _ obj -> countNodes obj
  Update obj updates -> countNodes obj + sum (map countNodes updates)
  RecordUpdate obj updates -> countNodes obj + sum (map countNodes updates)
  Ctor _ _ -> 0
  CtorDef _ _ -> 0
  Branch branches def -> sum (map (\b -> countNodes b.body) branches) + (case def of
    Just d -> countNodes d
    Nothing -> 0)
  Fail _ -> 0
  Effect _ -> 0
  EffectAction _ -> 0
  Coerce body -> countNodes body
  Typed _ body -> countNodes body
  PrimOp _ -> 0
  _ -> 0

sum :: Array Int -> Int
sum = foldl (+) 0
