module Phpurs.CodeGen where

import Prelude

import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..), Level(..), Pair(..), BackendAccessor(..), BackendOperator(..), BackendOperator1(..), BackendOperator2(..), BackendEffect(..), BackendOperatorOrd(..), BackendOperatorNum(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.CoreFn (Qualified(..), Ident(..), ModuleName(..), ProperName(..), Literal(..), ConstructorType(..), Prop(..))
import PureScript.Backend.Optimizer.Convert (BackendModule, BackendBindingGroup)
import Phpurs.PhpAst (PhpExpr(..), PhpFile)
import Phpurs.FreeVars (freeVars)
import Data.Maybe (Maybe(..), isJust)
import Data.Array.NonEmpty (toArray)
import Data.Tuple (Tuple(..))
import Data.Array as Array
import Data.Array (mapWithIndex)
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.Foldable (foldl, foldr)
import Data.Newtype (unwrap)

type TranslationRes = { stmts :: Array PhpExpr, expr :: PhpExpr, nextId :: Int }

wrapInStmts :: Array PhpExpr -> PhpExpr -> PhpExpr
wrapInStmts [] expr = expr
wrapInStmts stmts expr = PhpCall (PhpFunction [] [] (stmts <> [PhpReturn expr])) []

safeIdent :: Ident -> String
safeIdent (Ident i) = i

safeQual :: Qualified Ident -> String
safeQual (Qualified mbMod (Ident i)) = case mbMod of
  Just (ModuleName m) -> String.replaceAll (Pattern ".") (Replacement "_") m <> "_" <> i
  Nothing -> i

translateOperator1 :: BackendOperator1 -> PhpExpr -> PhpExpr
translateOperator1 OpBooleanNot e = PhpBinOp "!" (PhpRaw "") e
translateOperator1 OpIntBitNot e = PhpBinOp "~" (PhpRaw "") e
translateOperator1 OpIntNegate e = PhpBinOp "-" (PhpRaw "") e
translateOperator1 OpNumberNegate e = PhpBinOp "-" (PhpRaw "") e
translateOperator1 OpArrayLength e = PhpCall (PhpRaw "count") [e]
translateOperator1 (OpIsTag (Qualified _ (Ident tag))) e = PhpBinOp "&&" (PhpCall (PhpRaw "is_object") [e]) (PhpBinOp "===" (PhpPropertyAccess e "tag") (PhpString tag))

translateOperator2 :: BackendOperator2 -> PhpExpr -> PhpExpr -> PhpExpr
translateOperator2 OpArrayIndex arr idx = PhpArrayIndex arr 0
translateOperator2 OpBooleanAnd l r = PhpBinOp "&&" l r
translateOperator2 OpBooleanOr l r = PhpBinOp "||" l r
translateOperator2 (OpBooleanOrd OpEq) l r = PhpBinOp "===" l r
translateOperator2 (OpBooleanOrd OpNotEq) l r = PhpBinOp "!==" l r
translateOperator2 (OpBooleanOrd OpGt) l r = PhpBinOp ">" l r
translateOperator2 (OpBooleanOrd OpGte) l r = PhpBinOp ">=" l r
translateOperator2 (OpBooleanOrd OpLt) l r = PhpBinOp "<" l r
translateOperator2 (OpBooleanOrd OpLte) l r = PhpBinOp "<=" l r
translateOperator2 (OpCharOrd OpEq) l r = PhpBinOp "===" l r
translateOperator2 (OpCharOrd OpNotEq) l r = PhpBinOp "!==" l r
translateOperator2 (OpCharOrd OpGt) l r = PhpBinOp ">" l r
translateOperator2 (OpCharOrd OpGte) l r = PhpBinOp ">=" l r
translateOperator2 (OpCharOrd OpLt) l r = PhpBinOp "<" l r
translateOperator2 (OpCharOrd OpLte) l r = PhpBinOp "<=" l r
translateOperator2 OpIntBitAnd l r = PhpBinOp "&" l r
translateOperator2 OpIntBitOr l r = PhpBinOp "|" l r
translateOperator2 OpIntBitShiftLeft l r = PhpBinOp "<<" l r
translateOperator2 OpIntBitShiftRight l r = PhpBinOp ">>" l r
translateOperator2 OpIntBitXor l r = PhpBinOp "^" l r
translateOperator2 OpIntBitZeroFillShiftRight l r = PhpBinOp ">>" l r
translateOperator2 (OpIntNum OpAdd) l r = PhpBinOp "+" l r
translateOperator2 (OpIntNum OpSubtract) l r = PhpBinOp "-" l r
translateOperator2 (OpIntNum OpMultiply) l r = PhpBinOp "*" l r
translateOperator2 (OpIntNum OpDivide) l r = PhpBinOp "/" l r
translateOperator2 (OpIntOrd OpEq) l r = PhpBinOp "===" l r
translateOperator2 (OpIntOrd OpNotEq) l r = PhpBinOp "!==" l r
translateOperator2 (OpIntOrd OpGt) l r = PhpBinOp ">" l r
translateOperator2 (OpIntOrd OpGte) l r = PhpBinOp ">=" l r
translateOperator2 (OpIntOrd OpLt) l r = PhpBinOp "<" l r
translateOperator2 (OpIntOrd OpLte) l r = PhpBinOp "<=" l r
translateOperator2 (OpNumberNum OpAdd) l r = PhpBinOp "+" l r
translateOperator2 (OpNumberNum OpSubtract) l r = PhpBinOp "-" l r
translateOperator2 (OpNumberNum OpMultiply) l r = PhpBinOp "*" l r
translateOperator2 (OpNumberNum OpDivide) l r = PhpBinOp "/" l r
translateOperator2 (OpNumberOrd OpEq) l r = PhpBinOp "===" l r
translateOperator2 (OpNumberOrd OpNotEq) l r = PhpBinOp "!==" l r
translateOperator2 (OpNumberOrd OpGt) l r = PhpBinOp ">" l r
translateOperator2 (OpNumberOrd OpGte) l r = PhpBinOp ">=" l r
translateOperator2 (OpNumberOrd OpLt) l r = PhpBinOp "<" l r
translateOperator2 (OpNumberOrd OpLte) l r = PhpBinOp "<=" l r
translateOperator2 OpStringAppend l r = PhpBinOp "." l r
translateOperator2 (OpStringOrd OpEq) l r = PhpBinOp "===" l r
translateOperator2 (OpStringOrd OpNotEq) l r = PhpBinOp "!==" l r
translateOperator2 (OpStringOrd OpGt) l r = PhpBinOp ">" l r
translateOperator2 (OpStringOrd OpGte) l r = PhpBinOp ">=" l r
translateOperator2 (OpStringOrd OpLt) l r = PhpBinOp "<" l r
translateOperator2 (OpStringOrd OpLte) l r = PhpBinOp "<=" l r
translateOperator2 _ l r = PhpBinOp "???" l r

translateExprImpl :: Array String -> Int -> BackendSyntax NeutralExpr -> TranslationRes
translateExprImpl recVars nextId syntax = case syntax of
  Lit lit ->
    case lit of
      LitInt i -> { stmts: [], expr: PhpInt i, nextId }
      LitNumber n -> { stmts: [], expr: PhpNumber n, nextId }
      LitString s -> { stmts: [], expr: PhpString s, nextId }
      LitChar c -> { stmts: [], expr: PhpString (String.singleton (String.codePointFromChar c)), nextId }
      LitBoolean b -> { stmts: [], expr: PhpBoolean b, nextId }
      LitArray arr -> 
        let acc = foldl (\a (NeutralExpr e) -> 
              let res = translateExprImpl recVars a.nextId e
              in { stmts: a.stmts <> res.stmts, exprs: Array.snoc a.exprs res.expr, nextId: res.nextId }
            ) { stmts: [], exprs: [], nextId } arr
        in { stmts: acc.stmts, expr: PhpArray acc.exprs, nextId: acc.nextId }
      LitRecord rec ->
        let acc = foldl (\a (Prop key (NeutralExpr val)) -> 
              let res = translateExprImpl recVars a.nextId val
              in { stmts: a.stmts <> res.stmts, exprs: Array.snoc a.exprs { key, value: res.expr }, nextId: res.nextId }
            ) { stmts: [], exprs: [], nextId } rec
        in { stmts: acc.stmts, expr: PhpAssocArray acc.exprs, nextId: acc.nextId }

  Var qi -> { stmts: [], expr: PhpGlobalVar (case qi of (Qualified mbMod _) -> map (\(ModuleName m) -> String.split (Pattern ".") m) mbMod) (case qi of (Qualified _ (Ident i)) -> i), nextId }

  Local (Just (Ident i)) _ -> { stmts: [], expr: PhpVar i, nextId }
  Local Nothing _ -> { stmts: [], expr: PhpVar "TODO_LOCAL_NOTHING", nextId }

  App fn args ->
    let resFn = translateExprImpl recVars nextId (unwrapExpr fn)
        accFinal = foldl (\acc (NeutralExpr arg) -> 
            let argRes = translateExprImpl recVars acc.nextId arg
            in { stmts: acc.stmts <> argRes.stmts, expr: PhpCall acc.expr [argRes.expr], nextId: argRes.nextId }
          ) { stmts: resFn.stmts, expr: resFn.expr, nextId: resFn.nextId } (toArray args)
    in accFinal

  UncurriedApp fn args ->
    let resFn = translateExprImpl recVars nextId (unwrapExpr fn)
        accArgs = foldl (\acc (NeutralExpr arg) -> 
            let argRes = translateExprImpl recVars acc.nextId arg
            in { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
          ) { stmts: [], exprs: [], nextId: resFn.nextId } args
    in { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }

  UncurriedEffectApp fn args ->
    let resFn = translateExprImpl recVars nextId (unwrapExpr fn)
        accArgs = foldl (\acc (NeutralExpr arg) -> 
            let argRes = translateExprImpl recVars acc.nextId arg
            in { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
          ) { stmts: [], exprs: [], nextId: resFn.nextId } args
    in { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }

  Abs args body ->
    let rawArgsArray = map (\(Tuple mbI _) -> case mbI of
          Just (Ident i) -> i
          Nothing -> "_") (toArray args)
        argsArray = dedupArgs rawArgsArray
        fvs = freeVars (NeutralExpr syntax)
        useVars = Array.fromFoldable fvs
        resBody = translateExprImpl recVars nextId (unwrapExpr body)
    in { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [PhpReturn resBody.expr]), nextId: resBody.nextId }

  UncurriedAbs args body ->
    let rawArgsArray = map (\(Tuple mbI _) -> case mbI of
          Just (Ident i) -> i
          Nothing -> "_") args
        argsArray = dedupArgs rawArgsArray
        fvs = freeVars (NeutralExpr syntax)
        useVars = Array.fromFoldable fvs
        resBody = translateExprImpl recVars nextId (unwrapExpr body)
    in { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [PhpReturn resBody.expr]), nextId: resBody.nextId }

  UncurriedEffectAbs args body ->
    let rawArgsArray = map (\(Tuple mbI _) -> case mbI of
          Just (Ident i) -> i
          Nothing -> "_") args
        argsArray = dedupArgs rawArgsArray
        fvs = freeVars (NeutralExpr syntax)
        useVars = Array.fromFoldable fvs
        resBody = translateExprImpl recVars nextId (unwrapExpr body)
    in { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [PhpReturn resBody.expr]), nextId: resBody.nextId }

  Accessor e acc ->
    let res = translateExprImpl recVars nextId (unwrapExpr e)
    in case acc of
      GetProp prop -> { stmts: res.stmts, expr: PhpPropertyAccess res.expr prop, nextId: res.nextId }
      GetIndex idx -> { stmts: res.stmts, expr: PhpArrayIndex res.expr idx, nextId: res.nextId }
      GetCtorField _ _ _ _ prop idx -> { stmts: res.stmts, expr: PhpPropertyAccess res.expr prop, nextId: res.nextId }

  Let (Just (Ident i)) _ val body ->
    let resVal = translateExprImpl recVars nextId (unwrapExpr val)
        resBody = translateExprImpl recVars resVal.nextId (unwrapExpr body)
    in { stmts: resVal.stmts <> [PhpAssign i resVal.expr] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  Let Nothing (Level l) val body ->
    let resVal = translateExprImpl recVars nextId (unwrapExpr val)
        resBody = translateExprImpl recVars resVal.nextId (unwrapExpr body)
    in { stmts: resVal.stmts <> [PhpAssign ("__local_var_" <> show l) resVal.expr] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  LetRec _ binds body ->
    let newRecVars = map (\(Tuple (Ident i) _) -> i) (toArray binds)
        combinedRecVars = recVars <> newRecVars
        initStmts = map (\v -> PhpAssign v (PhpRaw "null")) newRecVars
        accBinds = foldl (\acc (Tuple (Ident i) (NeutralExpr val)) -> 
            let res = translateExprImpl combinedRecVars acc.nextId val
            in { stmts: acc.stmts <> res.stmts <> [PhpAssign i res.expr], nextId: res.nextId }
          ) { stmts: initStmts, nextId } (toArray binds)
        resBody = translateExprImpl combinedRecVars accBinds.nextId (unwrapExpr body)
    in { stmts: accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  EffectBind (Just (Ident i)) _ val body ->
    let resVal = translateExprImpl recVars nextId (unwrapExpr val)
        resBody = translateExprImpl recVars resVal.nextId (unwrapExpr body)
    in { stmts: resVal.stmts <> [PhpAssign i resVal.expr] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  EffectBind Nothing (Level l) val body ->
    let resVal = translateExprImpl recVars nextId (unwrapExpr val)
        resBody = translateExprImpl recVars resVal.nextId (unwrapExpr body)
    in { stmts: resVal.stmts <> [PhpAssign ("__local_var_" <> show l) resVal.expr] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  EffectPure e -> translateExprImpl recVars nextId (unwrapExpr e)

  EffectDefer e -> 
    let res = translateExprImpl recVars nextId (unwrapExpr e)
    in { stmts: [], expr: PhpFunction [] [] (res.stmts <> [PhpReturn res.expr]), nextId: res.nextId }

  Branch pairs def ->
    let resDef = translateExprImpl recVars nextId (unwrapExpr def)
        tmpVar = "__t" <> show resDef.nextId
        accPairs = foldl (\acc (Pair (NeutralExpr cond) (NeutralExpr body)) -> 
            let resCond = translateExprImpl recVars acc.nextId cond
                resBody = translateExprImpl recVars resCond.nextId body
            in { ifNodes: Array.snoc acc.ifNodes { cond: wrapInStmts resCond.stmts resCond.expr, body: resBody.stmts <> [PhpAssign tmpVar resBody.expr] }, nextId: resBody.nextId }
          ) { ifNodes: [], nextId: resDef.nextId + 1 } (toArray pairs)
        
        finalElse = resDef.stmts <> [PhpAssign tmpVar resDef.expr]
        nestedIf = foldr (\ifNode accElse -> [PhpIf ifNode.cond ifNode.body accElse]) finalElse accPairs.ifNodes
    in { stmts: nestedIf, expr: PhpVar tmpVar, nextId: accPairs.nextId }

  Update e props ->
    let resE = translateExprImpl recVars nextId (unwrapExpr e)
        tmpVar = "__obj" <> show resE.nextId
        accProps = foldl (\acc (Prop key (NeutralExpr val)) -> 
            let resVal = translateExprImpl recVars acc.nextId val
            in { stmts: acc.stmts <> resVal.stmts <> [PhpAssignExpr (PhpPropertyAccess (PhpVar tmpVar) key) resVal.expr], nextId: resVal.nextId }
          ) { stmts: [], nextId: resE.nextId + 1 } props
    in { stmts: resE.stmts <> [PhpAssign tmpVar (PhpClone resE.expr)] <> accProps.stmts, expr: PhpVar tmpVar, nextId: accProps.nextId }

  CtorSaturated _ _ _ (Ident ctorName) args ->
    let numFields = Array.length args
        accArgs = foldl (\acc (Tuple _ (NeutralExpr val)) -> 
            let resVal = translateExprImpl recVars acc.nextId val
            in { stmts: acc.stmts <> resVal.stmts, exprs: Array.snoc acc.exprs resVal.expr, nextId: resVal.nextId }
          ) { stmts: [], exprs: [], nextId } args
        body = PhpNew ("Phpurs_Data" <> show numFields) ([PhpString ctorName] <> accArgs.exprs)
    in { stmts: accArgs.stmts, expr: body, nextId: accArgs.nextId }

  CtorDef _ _ (Ident ctorName) fields ->
    let numFields = Array.length fields
        body = PhpNew ("Phpurs_Data" <> show numFields) ([PhpString ctorName] <> map PhpVar fields)
        safeCtorName = String.replaceAll (Pattern "'") (Replacement "\\'") ctorName
        singletonBody = PhpBinOp "??=" (PhpRaw ("$GLOBALS['__phpurs_data0_" <> safeCtorName <> "']")) body
    in if numFields == 0 then { stmts: [], expr: singletonBody, nextId } else { stmts: [], expr: PhpFunction fields [] [PhpReturn body], nextId }

  PrimOp op -> case op of
    Op1 op1 (NeutralExpr e) ->
      let resE = translateExprImpl recVars nextId e
      in { stmts: resE.stmts, expr: translateOperator1 op1 resE.expr, nextId: resE.nextId }
    Op2 op2 (NeutralExpr e1) (NeutralExpr e2) ->
      let res1 = translateExprImpl recVars nextId e1
          res2 = translateExprImpl recVars res1.nextId e2
      in { stmts: res1.stmts <> res2.stmts, expr: translateOperator2 op2 res1.expr res2.expr, nextId: res2.nextId }

  PrimEffect _ -> { stmts: [], expr: PhpString "TODO_PrimEffect", nextId }
  PrimUndefined -> { stmts: [], expr: PhpRaw "null", nextId }
  Fail msg -> { stmts: [PhpThrow msg], expr: PhpRaw "null", nextId }

unwrapExpr :: NeutralExpr -> BackendSyntax NeutralExpr
unwrapExpr (NeutralExpr e) = e

translate :: BackendModule -> PhpFile
translate mod =
  let
    modNameStr = String.replaceAll (Pattern ".") (Replacement "_") (unwrap mod.name)
    modPrefix = modNameStr <> "_"
    decls = Array.concatMap (\group -> 
      if group.recursive then
        let recVars = map (\(Tuple (Ident name) _) -> name) group.bindings
        in Array.concatMap (\(Tuple (Ident name) (NeutralExpr expr)) ->
             let res = translateExprImpl recVars 0 expr
             in [ { identifier: modPrefix <> name, expression: PhpValueThunk (modPrefix <> name) (wrapInStmts res.stmts res.expr) } ]
           ) group.bindings
      else
        Array.concatMap (\(Tuple (Ident name) (NeutralExpr expr)) ->
          let res = translateExprImpl [] 0 expr
          in [ { identifier: modPrefix <> name, expression: PhpValueThunk (modPrefix <> name) (wrapInStmts res.stmts res.expr) } ]
        ) group.bindings
    ) mod.bindings

  in
    { namespace: String.split (Pattern ".") (unwrap mod.name), decls, imports: [] }

dedupArgs :: Array String -> Array String
dedupArgs args = Array.mapWithIndex (\idx name -> 
  let isShadowed = isJust (Array.findIndex (\x -> x == name) (Array.drop (idx + 1) args))
  in if isShadowed || name == "__unused" || name == "$__unused" || name == "_" 
     then name <> "_" <> show idx 
     else name) args
