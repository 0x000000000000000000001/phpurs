-- | Translate one typed optimizer expression while threading lexical scope,
-- | tail/effect position and temporary names through its children. Record
-- | update fusion stays beside expression translation because they recurse
-- | together and must share evaluation order and the temporary counter.
module Phpurs.CodeGen.Expr (translateExpr, wrapInStmts) where

import Prelude

import Data.Array as Array
import Phpurs.CodeGen.Context (TranslationContext, TranslationResult)
import Phpurs.CodeGen.Effects (isEffectNode)
import Phpurs.CodeGen.Expr.Statements as Statements
import Phpurs.CodeGen.Expr.Prims as Prims
import Phpurs.CodeGen.Expr.Terms as Terms
import Phpurs.CodeGen.Expr.Update as Update
import Phpurs.CodeGen.Expr.Support as Support
import Phpurs.CodeGen.Expr.Support (closureCaptures)
import Phpurs.PhpAst (PhpExpr(..))
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..))
import PureScript.Backend.Optimizer.CoreFn (Ident(..), Qualified(..))
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..))
import PureScript.Backend.Optimizer.Syntax as Syn

wrapInStmts :: Array String -> Array PhpExpr -> PhpExpr -> PhpExpr
wrapInStmts = Support.wrapInStmts

-- | Lower sibling operands in order, sharing a single temporary counter.
-- | A closed record whose every field is updated can be built directly. The
-- | old object is never mutated; nested updates must target the expected field
-- | of the same root. Open rows, partial updates and ambiguous paths keep the
-- | existing clone path.
translateExpr :: TranslationContext -> Int -> TcoExpr -> TranslationResult
translateExpr ctx nextId tcoExpr
  | isEffectNode tcoExpr && not ctx.inEffectBlock =
      let
        res = translateExpr (ctx { isTail = false, inEffectBlock = true }) nextId tcoExpr
        useVars = Array.nub (closureCaptures ctx tcoExpr)
      in
        { stmts: []
        , expr: PhpFunction useVars [] "" (res.stmts <> [ PhpReturn res.expr ])
        , nextId: res.nextId
        }
  | otherwise = translateSyntax ctx nextId tcoExpr

translateSyntax :: TranslationContext -> Int -> TcoExpr -> TranslationResult
translateSyntax ctx nextId tcoExpr@(TcoExpr _ syntax) = case syntax of
  Lit lit -> Terms.translateLit translateExpr ctx nextId lit
  Var (Qualified moduleName (Ident name)) -> Terms.translateVar translateExpr nextId moduleName name
  Local ident level -> Terms.translateLocal translateExpr ctx nextId ident level
  App fn args -> Terms.translateApp translateExpr ctx nextId tcoExpr fn args
  UncurriedApp fn args -> Terms.translateUncurriedApp translateExpr ctx nextId fn args
  UncurriedEffectApp fn args -> Terms.translateUncurriedEffectApp translateExpr ctx nextId fn args
  Abs args body -> Terms.translateAbs translateExpr ctx nextId tcoExpr args body
  UncurriedAbs args body -> Terms.translateUncurriedAbs translateExpr ctx nextId tcoExpr args body
  UncurriedEffectAbs args body -> Terms.translateUncurriedEffectAbs translateExpr ctx nextId tcoExpr
    args
    body
  Accessor e acc -> Terms.translateAccessor translateExpr ctx nextId e acc
  Let ident level val body ->
    Statements.translateLet translateExpr ctx nextId ident level val body
  LetRec lvl binds body ->
    Statements.translateLetRec translateExpr ctx nextId tcoExpr lvl binds body
  EffectBind ident level val body ->
    Statements.translateEffectBind translateExpr ctx nextId ident level val body
  EffectPure e -> Statements.translateEffectPure translateExpr ctx nextId e
  EffectDefer e -> Statements.translateEffectDefer translateExpr ctx nextId tcoExpr e
  Branch pairs def ->
    Statements.translateBranch translateExpr ctx nextId pairs def
  Update e props ->
    Update.translateUpdate translateExpr ctx nextId e props
  CtorSaturated (Qualified mbMod _) _ _ (Ident ctorName) args ->
    Prims.translateCtorSaturated translateExpr ctx nextId mbMod ctorName args
  CtorDef _ _ (Ident ctorName) fields -> Prims.translateCtorDef translateExpr ctx nextId ctorName
    fields
  PrimOp op -> Prims.translatePrimOp translateExpr ctx nextId op
  PrimEffect effect -> Prims.translatePrimEffect translateExpr ctx nextId effect
  PrimUndefined -> Prims.translatePrimUndefined translateExpr nextId
  Syn.TypeApp a _ -> Prims.translateTypeApp translateExpr ctx nextId a
  Fail msg -> Prims.translateFail translateExpr nextId msg
  Typed ty _ -> Update.translateTyped translateExpr ctx nextId ty tcoExpr
