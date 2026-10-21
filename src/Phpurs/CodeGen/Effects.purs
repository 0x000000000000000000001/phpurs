-- | Recognize explicit effect syntax and decide whether an expression must be
-- | executed through the runtime effect helper.
module Phpurs.CodeGen.Effects
  ( isEffectNode
  , executeIfOpaque
  ) where

import Phpurs.PhpAst (PhpExpr(..))
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..)) as Syn
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..))

-- | Explicit effect syntax produces its result inside an effect block. Outside
-- | such a block, the same classification tells us to defer its execution.
isEffectNode :: TcoExpr -> Boolean
isEffectNode (TcoExpr _ syntax) = case syntax of
  EffectBind _ _ _ _ -> true
  EffectPure _ -> true
  EffectDefer _ -> false
  PrimEffect _ -> true
  UncurriedEffectApp _ _ -> true
  Let _ _ _ body -> isEffectNode body
  LetRec _ _ body -> isEffectNode body
  -- Annotations preserve the body's evaluation convention, including when an
  -- EffectBind or EffectFn returns an already computed callable value.
  Typed _ body -> isEffectNode body
  Syn.TypeApp body _ -> isEffectNode body
  _ -> false

executeIfOpaque :: TcoExpr -> PhpExpr -> PhpExpr
executeIfOpaque expr phpExpr =
  if isEffectNode expr then phpExpr
  else PhpCall (PhpRaw "\\phpurs_execute_effect") [ phpExpr ]
