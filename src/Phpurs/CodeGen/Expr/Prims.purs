-- | Constructor, primitive-operator and effect rules for expression
-- | translation.
module Phpurs.CodeGen.Expr.Prims
  ( translateCtorDef
  , translateCtorSaturated
  , translateFail
  , translatePrimEffect
  , translatePrimOp
  , translatePrimUndefined
  , translateTypeApp
  ) where

import Prelude

import Data.Array as Array
import Data.Maybe (Maybe(..))
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.Tuple (Tuple(..))
import Phpurs.CodeGen.Context (TranslationContext, TranslationResult)
import Phpurs.CodeGen.Expr.Support
  ( Translate
  , closureCaptures
  , translateValue
  , translateValues
  , wrapInStmts
  )
import Phpurs.CodeGen.Operators (isLogicalShortCircuit, translateOperator1, translateOperator2)
import Phpurs.PhpAst (PhpExpr(..))
import Phpurs.RuntimeNames as RuntimeNames
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..))
import PureScript.Backend.Optimizer.CoreFn (ModuleName(..))
import PureScript.Backend.Optimizer.Syntax (BackendEffect(..), BackendOperator(..))

translateCtorSaturated
  :: Translate
  -> TranslationContext
  -> Int
  -> Maybe ModuleName
  -> String
  -> Array (Tuple String TcoExpr)
  -> TranslationResult
translateCtorSaturated translate ctx nextId mbMod ctorName args =
  let
    safeCtorName = String.replaceAll (Pattern "'") (Replacement "_prime_") ctorName
    absClass = case mbMod of
      Just (ModuleName m) -> "\\" <> String.replaceAll (Pattern ".") (Replacement "\\") m <> "\\"
        <> String.replaceAll (Pattern ".") (Replacement "_") m
        <> "_"
        <> safeCtorName
      Nothing -> "\\" <> String.replaceAll (Pattern "_") (Replacement "\\") ctx.moduleName <> "\\"
        <> ctx.moduleName
        <> "_"
        <> safeCtorName
    accArgs = translateValues translate ctx nextId (map (\(Tuple _ val) -> val) args)
    body = PhpNew absClass accArgs.exprs
  in
    { stmts: accArgs.stmts, expr: body, nextId: accArgs.nextId }

translateCtorDef
  :: Translate -> TranslationContext -> Int -> String -> Array String -> TranslationResult
translateCtorDef _translate ctx nextId ctorName fields =
  let
    safeCtorName = String.replaceAll (Pattern "'") (Replacement "_prime_") ctorName
    absClass = "\\" <> String.replaceAll (Pattern "_") (Replacement "\\") ctx.moduleName <> "\\"
      <> ctx.moduleName
      <> "_"
      <> safeCtorName
    numFields = Array.length fields
    body = PhpNew absClass (map PhpVar fields)
    safeCtorNameStr = String.replaceAll (Pattern "'") (Replacement "\\'") ctorName
    singletonKey = "__phpurs_data0_" <> ctx.moduleName <> "_" <> safeCtorNameStr
    singletonBody = PhpBinOp "??=" (PhpGlobalSlot singletonKey) body
  in
    if numFields == 0 then { stmts: [], expr: singletonBody, nextId }
    else
      { stmts: []
      , expr: PhpFunction [] (map (\n -> { name: n, type_: "" }) fields) "" [ PhpReturn body ]
      , nextId
      }

translatePrimOp
  :: Translate -> TranslationContext -> Int -> BackendOperator TcoExpr -> TranslationResult
translatePrimOp translate ctx nextId op =
  case op of
    Op1 op1 e@(TcoExpr _ _) ->
      let
        resE = translateValue translate ctx nextId e
      in
        { stmts: resE.stmts, expr: translateOperator1 op1 resE.expr, nextId: resE.nextId }
    Op2 op2 e1@(TcoExpr _ _) e2@(TcoExpr _ _) ->
      let
        res1 = translateValue translate ctx nextId e1
        res2 = translateValue translate ctx res1.nextId e2
        -- `&&` and `||` short-circuit: the right operand's statements must only
        -- run when it is evaluated. A nested branch in the right operand would
        -- otherwise read fields of a constructor the left operand ruled out.
        lazyRight = isLogicalShortCircuit op2 && not (Array.null res2.stmts)
        captures = Array.nub (closureCaptures ctx e2)
        rightExpr = if lazyRight then wrapInStmts captures res2.stmts res2.expr else res2.expr
      in
        { stmts: res1.stmts <> (if lazyRight then [] else res2.stmts)
        , expr: translateOperator2 op2 res1.expr rightExpr
        , nextId: res2.nextId
        }

translatePrimEffect
  :: Translate -> TranslationContext -> Int -> BackendEffect TcoExpr -> TranslationResult
translatePrimEffect translate ctx nextId effect =
  case effect of
    EffectRefNew val ->
      let
        res = translateValue translate ctx nextId val
      in
        { stmts: res.stmts
        , expr: PhpNamedCall (RuntimeNames.qualified RuntimeNames.refNew) [ res.expr ]
        , nextId: res.nextId
        }
    EffectRefRead ref ->
      let
        res = translateValue translate ctx nextId ref
      in
        { stmts: res.stmts
        , expr: PhpNamedCall (RuntimeNames.qualified RuntimeNames.refRead) [ res.expr ]
        , nextId: res.nextId
        }
    EffectRefWrite ref val ->
      let
        resRef = translateValue translate ctx nextId ref
        resVal = translateValue translate ctx resRef.nextId val
      in
        { stmts: resRef.stmts <> resVal.stmts
        , expr: PhpNamedCall (RuntimeNames.qualified RuntimeNames.refWrite)
            [ resRef.expr, resVal.expr ]
        , nextId: resVal.nextId
        }

translatePrimUndefined :: Translate -> Int -> TranslationResult
translatePrimUndefined _translate nextId =
  { stmts: [], expr: PhpNull, nextId }

translateTypeApp :: Translate -> TranslationContext -> Int -> TcoExpr -> TranslationResult
translateTypeApp translate ctx nextId a =
  translate ctx nextId a

translateFail :: Translate -> Int -> String -> TranslationResult
translateFail _translate nextId msg =
  { stmts: [ PhpThrow (PhpRaw ("\"" <> msg <> " at \" . __FILE__ . \":\" . __LINE__")) ]
  , expr: PhpNull
  , nextId
  }
