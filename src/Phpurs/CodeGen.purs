-- | The Code Generator for the `phpurs` backend.
-- | Translates the `TcoExpr` (the optimized AST from `purescript-backend-optimizer`)
-- | into `PhpAst` (the PHP-specific AST).
-- |
-- | Responsibilities:
-- | - Handling `Let` bindings and converting them into PHP statements.
-- | - Translating pattern matching (`Case`) into PHP nested `if` statements.
-- | - Translating uncurried functions into native PHP functions.
-- | - Performing basic Tail Call Optimization (TCO) loop generation (`resolveContinues`).
module Phpurs.CodeGen where

import Prelude

import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..), Level(..), Pair(..), BackendAccessor(..), BackendOperator(..), BackendOperator1(..), BackendOperator2(..), BackendOperatorOrd(..), BackendOperatorNum(..))
import PureScript.Backend.Optimizer.Codegen.Tco as Tco
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..), tcoAnalysisOf, unTcoExpr)
import PureScript.Backend.Optimizer.CoreFn (Qualified(..), Ident(..), ModuleName(..), Literal(..), Prop(..))
import PureScript.Backend.Optimizer.Convert (BackendModule)
import Phpurs.PhpAst (PhpExpr(..), PhpFile)
import Phpurs.FreeVars (freeVars, localId)
import Data.Maybe (Maybe(..), isJust, fromMaybe)
import Data.Array.NonEmpty (toArray, fromArray)
import Data.Tuple (Tuple(..))
import Data.Array as Array
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.Foldable (foldl, foldr, foldMap)
import Data.Traversable (traverse)
import Debug as Debug
import Data.Newtype (unwrap)
import Data.Map as Map
import Data.Map (Map)
import Data.Set (Set)
import Data.Set as Set

type TranslationRes = { stmts :: Array PhpExpr, expr :: PhpExpr, nextId :: Int }

wrapInStmts :: Array String -> Array PhpExpr -> PhpExpr -> PhpExpr
wrapInStmts _ [] expr = expr
wrapInStmts captures stmts expr = PhpCall (PhpFunction captures [] (stmts <> [ PhpReturn expr ])) []

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
translateOperator1 OpArrayLength e = PhpCall (PhpRaw "count") [ e ]
translateOperator1 (OpIsTag (Qualified _ (Ident tag))) e = PhpBinOp "&&" (PhpCall (PhpRaw "is_object") [ e ]) (PhpBinOp "===" (PhpPropertyAccess e "tag") (PhpString tag))

translateOperator2 :: BackendOperator2 -> PhpExpr -> PhpExpr -> PhpExpr
translateOperator2 OpArrayIndex arr _ = PhpArrayIndex arr 0
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


type LoopCtx = { ident :: String, params :: Array String, doneVar :: String, loopFuncVar :: String, resultVar :: String, varPrefix :: String }

flattenApp :: TcoExpr -> Tuple (TcoExpr) (Array TcoExpr)
flattenApp tcoExpr@(TcoExpr _ syntax) = case syntax of
  App fn args ->
    let
      Tuple innerFn innerArgs = flattenApp fn
    in
      Tuple innerFn (innerArgs <> toArray args)
  _ -> Tuple tcoExpr []

translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Array LoopCtx -> Boolean -> Int -> TcoExpr -> TranslationRes
translateExprImpl recVars namedBound bound _currentBindingName loopCtx isTail nextId tcoExpr@(TcoExpr _tcoAnalysis syntax) = case syntax of
  Lit lit ->
    case lit of
      LitInt i -> { stmts: [], expr: PhpInt i, nextId }
      LitNumber n -> { stmts: [], expr: PhpNumber n, nextId }
      LitString s -> { stmts: [], expr: PhpString s, nextId }
      LitChar c -> { stmts: [], expr: PhpString (String.singleton (String.codePointFromChar c)), nextId }
      LitBoolean b -> { stmts: [], expr: PhpBoolean b, nextId }
      LitArray arr ->
        let
          acc = foldl
            ( \a expr@(TcoExpr _ _) ->
                let
                  res = translateExprImpl recVars namedBound bound Nothing [] false a.nextId expr
                in
                  { stmts: a.stmts <> res.stmts, exprs: Array.snoc a.exprs res.expr, nextId: res.nextId }
            )
            { stmts: [], exprs: [], nextId }
            arr
        in
          { stmts: acc.stmts, expr: PhpArray acc.exprs, nextId: acc.nextId }
      LitRecord rec ->
        let
          acc = foldl
            ( \a (Prop key val@(TcoExpr _ _)) ->
                let
                  res = translateExprImpl recVars namedBound bound Nothing [] false a.nextId val
                in
                  { stmts: a.stmts <> res.stmts, exprs: Array.snoc a.exprs { key, value: res.expr }, nextId: res.nextId }
            )
            { stmts: [], exprs: [], nextId }
            rec
        in
          { stmts: acc.stmts, expr: PhpAssocArray acc.exprs, nextId: acc.nextId }

  Var qi -> { stmts: [], expr: PhpGlobalVar (case qi of (Qualified mbMod _) -> map (\(ModuleName m) -> String.split (Pattern ".") m) mbMod) (case qi of (Qualified _ (Ident i)) -> i), nextId }

  Local (Just (Ident i)) (Level l) ->
    let
      v = localId (Just (Ident i)) (Level l)
    in
      { stmts: [], expr: PhpVar (fromMaybe v (Map.lookup v bound)), nextId }
  Local Nothing (Level l) ->
    let
      v = localId Nothing (Level l)
    in
      { stmts: [], expr: PhpVar (fromMaybe v (Map.lookup v bound)), nextId }

  App fn args ->
    let
      resFn = translateExprImpl recVars namedBound bound Nothing [] false nextId fn
      argsArr = toArray args
      
      Tuple flatFn flatArgs = flattenApp tcoExpr
      
      isTailCallTo = if isTail then case flatFn of
        TcoExpr _ (Local mbIdent (Level l)) ->
          let v = fromMaybe (localId mbIdent (Level l)) (Map.lookup (localId mbIdent (Level l)) bound)
          in Array.findIndex (\ctx -> ctx.ident == v) loopCtx
        TcoExpr _ (Var (Qualified mbMod (Ident name))) ->
          let fullName = fromMaybe "" (map (\(ModuleName m) -> String.joinWith "_" (String.split (Pattern ".") m) <> "_") mbMod) <> name
          in Array.findIndex (\ctx -> ctx.ident == fullName) loopCtx
        _ -> Nothing
      else Nothing

      accFinal = foldl
        ( \acc arg@(TcoExpr _ _) ->
            let
              argRes = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
        )
        { stmts: resFn.stmts, exprs: [], nextId: resFn.nextId }
        argsArr

    in case isTailCallTo of
      Just index ->
        let
          targetCtx = fromMaybe { ident: "", params: [], doneVar: "", resultVar: "", loopFuncVar: "", varPrefix: "" } (Array.index loopCtx index)
          
          flatAccFinal = foldl
            ( \acc arg@(TcoExpr _ _) ->
                let
                  argRes = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId arg
                in
                  { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
            )
            { stmts: [], exprs: [], nextId: nextId }
            flatArgs
            
          tcoStmts = Array.mapWithIndex (\i e -> PhpAssign ("__tco_" <> show (flatAccFinal.nextId + i)) e) flatAccFinal.exprs
          assignStmts = Array.mapWithIndex (\i _ -> PhpAssign (targetCtx.varPrefix <> (fromMaybe "" (Array.index targetCtx.params i))) (PhpVar ("__tco_" <> show (flatAccFinal.nextId + i)))) flatAccFinal.exprs
          
          popStmts = map (\ctx -> PhpAssign ctx.doneVar (PhpBoolean true)) (Array.take index loopCtx)
          
          finalStmts = flatAccFinal.stmts <> tcoStmts <> assignStmts <> popStmts <> [ PhpAssign targetCtx.doneVar (PhpBoolean false), PhpReturn (PhpRaw "null") ]
        in { stmts: finalStmts, expr: PhpRaw "null", nextId: flatAccFinal.nextId + Array.length flatArgs }
      Nothing -> 
        let curriedCall = foldl (\acc e -> PhpCall acc [e]) resFn.expr accFinal.exprs
        in { stmts: accFinal.stmts, expr: curriedCall, nextId: accFinal.nextId }

  UncurriedApp fn args ->
    let
      resFn = translateExprImpl recVars namedBound bound Nothing [] false nextId fn
      
      isTailCallTo = if isTail then case resFn.expr of
        PhpGlobalVar mbMod name ->
          let fullName = fromMaybe "" (map (\m -> String.joinWith "_" m <> "_") mbMod) <> name
          in Array.findIndex (\ctx -> ctx.ident == fullName) loopCtx
        PhpVar v ->
          Array.findIndex (\ctx -> ctx.ident == v) loopCtx
        _ -> Nothing
      else Nothing

      accArgs = foldl
        ( \acc arg@(TcoExpr _ _) ->
            let
              argRes = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: acc.nextId }
        )
        { stmts: [], exprs: [], nextId: resFn.nextId }
        args
        
    in case isTailCallTo of
      Just index ->
        let
          targetCtx = fromMaybe { ident: "", params: [], doneVar: "", resultVar: "", loopFuncVar: "", varPrefix: "" } (Array.index loopCtx index)
          tcoStmts = Array.mapWithIndex (\i e -> PhpAssign ("__tco_" <> show (accArgs.nextId + i)) e) accArgs.exprs
          assignStmts = Array.mapWithIndex (\i _ -> PhpAssign (targetCtx.varPrefix <> (fromMaybe "" (Array.index targetCtx.params i))) (PhpVar ("__tco_" <> show (accArgs.nextId + i)))) accArgs.exprs
          
          popStmts = map (\ctx -> PhpAssign ctx.doneVar (PhpBoolean true)) (Array.take index loopCtx)
          
          finalStmts = accArgs.stmts <> tcoStmts <> assignStmts <> popStmts <> [ PhpAssign targetCtx.doneVar (PhpBoolean false), PhpReturn (PhpRaw "null") ]
        in { stmts: finalStmts, expr: PhpRaw "null", nextId: accArgs.nextId + Array.length args }
      Nothing ->
        { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }

  UncurriedEffectApp fn args ->
    let
      resFn = translateExprImpl recVars namedBound bound Nothing [] false nextId fn
      accArgs = foldl
        ( \acc arg@(TcoExpr _ _) ->
            let
              argRes = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: acc.nextId }
        )
        { stmts: [], exprs: [], nextId: resFn.nextId }
        args
    in
      { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }

  Abs args body ->
    let
      argsArray = map (\(Tuple mbI lvl) -> localId mbI lvl) (toArray args)
      fvs = freeVars tcoExpr
      useVars = map (\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      resBody = translateExprImpl recVars namedBound bound Nothing [] true nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }

  UncurriedAbs args body ->
    let
      argsArray = map (\(Tuple mbI lvl) -> localId mbI lvl) args
      fvs = freeVars tcoExpr
      useVars = map (\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      resBody = translateExprImpl recVars namedBound bound Nothing [] true nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }

  UncurriedEffectAbs args body ->
    let
      argsArray = map (\(Tuple mbI lvl) -> localId mbI lvl) args
      fvs = freeVars tcoExpr
      useVars = map (\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      resBody = translateExprImpl recVars namedBound bound Nothing [] false nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }

  Accessor e acc ->
    let
      res = translateExprImpl recVars namedBound bound Nothing [] false nextId e
    in
      case acc of
        GetProp prop -> { stmts: res.stmts, expr: PhpPropertyAccess res.expr prop, nextId: res.nextId }
        GetIndex idx -> { stmts: res.stmts, expr: PhpArrayIndex res.expr idx, nextId: res.nextId }
        GetCtorField _ _ _ _ prop _ -> { stmts: res.stmts, expr: PhpPropertyAccess res.expr prop, nextId: res.nextId }

  Let (Just (Ident i)) (Level l) val body ->
    let
      oldVarName = localId (Just (Ident i)) (Level l)
      varName = oldVarName <> "_" <> show nextId
      resVal = translateExprImpl recVars namedBound bound (Just varName) [] false nextId val
      newBound = Map.insert oldVarName varName bound
      resBody = translateExprImpl recVars namedBound newBound Nothing loopCtx isTail (resVal.nextId + 1) body
    in
      { stmts: resVal.stmts <> [ PhpAssign varName resVal.expr ] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  Let Nothing (Level l) val body ->
    let
      oldVarName = localId Nothing (Level l)
      varName = oldVarName <> "_" <> show nextId
      resVal = translateExprImpl recVars namedBound bound (Just varName) [] false nextId val
      newBound = Map.insert oldVarName varName bound
      resBody = translateExprImpl recVars namedBound newBound Nothing loopCtx isTail (resVal.nextId + 1) body
    in
      { stmts: resVal.stmts <> [ PhpAssign varName resVal.expr ] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  LetRec lvl binds body ->
    let
      oldNewPairs = map
        ( \(Tuple ident _) ->
            let
              oldName = localId (Just ident) lvl
            in
              { oldName, newName: oldName <> "_" <> show nextId }
        )
        (toArray binds)
      newBound = foldl (\acc pair -> Map.insert pair.oldName pair.newName acc) bound oldNewPairs
      newRecVars = map _.newName oldNewPairs
      combinedRecVars = recVars <> newRecVars
      
      isLoop = (unwrap (tcoAnalysisOf tcoExpr)).role.isLoop
      mutRecBinds = if isLoop && Array.length (toArray binds) == 1 then
        traverse (\(Tuple ident val) -> case extractUncurriedAbs val of
            Just abs -> Just { ident: localId (Just ident) lvl, args: abs.args, body: abs.body, fvs: abs.fvs }
            Nothing -> Nothing
        ) (toArray binds)
      else Nothing
    in case mutRecBinds of
      Just fns ->
        let
          initStmts = map (\pair -> PhpAssign pair.newName (PhpRaw "null")) oldNewPairs
          
          loopCtxs = map (\fn ->
            let newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
            in { ident: newName, params: fn.args, doneVar: "__tco_done_" <> newName <> "_" <> show nextId, resultVar: "__tco_res_" <> newName <> "_" <> show nextId, loopFuncVar: "__tco_loop_" <> newName <> "_" <> show nextId, varPrefix: "__tco_var_" <> newName <> "_" <> show nextId <> "_" }
          ) fns
          
          combinedLoopCtx = loopCtxs <> loopCtx
          
          fnWrapperStmts = map
            ( \fn ->
                let
                  newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
                  ctx = fromMaybe { ident: "", params: [], doneVar: "", resultVar: "", loopFuncVar: "", varPrefix: "" } (Array.find (\c -> c.ident == newName) loopCtxs)
                  
                  loopVars = map (\p -> ctx.varPrefix <> p) fn.args
                  
                  initVarStmts = Array.mapWithIndex (\i p -> PhpAssign (fromMaybe "" (Array.index loopVars i)) (PhpVar p)) fn.args
                  
                  doneAssign = PhpAssign ctx.doneVar (PhpBoolean false)
                  resAssign = PhpAssign ctx.resultVar (PhpRaw "null")
                  
                  resBodyMut = translateExprImpl combinedRecVars namedBound newBound Nothing combinedLoopCtx true nextId fn.body
                  
                  mappedFvs = map (\v -> fromMaybe v (Map.lookup v newBound)) (Array.fromFoldable fn.fvs)
                  useVarsLoop = Array.nub (map (\mapped -> if Array.elem mapped combinedRecVars then "&" <> mapped else mapped) mappedFvs)
                  
                  allLoopCtxs = loopCtx <> loopCtxs
                  mutVarsToCapture = foldMap (\c -> Array.cons ("&" <> c.doneVar) (map (\p -> "&" <> c.varPrefix <> p) c.params)) allLoopCtxs
                  
                  useVarsInner = mutVarsToCapture <> useVarsLoop
                  
                  mutVarsToCaptureOuter = foldMap (\c -> Array.cons ("&" <> c.doneVar) (map (\p -> "&" <> c.varPrefix <> p) c.params)) loopCtx
                  useVarsOuter = mutVarsToCaptureOuter <> useVarsLoop
                  
                  innerLoopInit = Array.mapWithIndex (\i p -> PhpAssign p (PhpVar (fromMaybe "" (Array.index loopVars i)))) fn.args
                  innerFuncBody = Array.cons (PhpAssign ctx.doneVar (PhpRaw "true")) (innerLoopInit <> resBodyMut.stmts <> [ PhpReturn resBodyMut.expr ])
                  
                  innerFunc = PhpAssign ctx.loopFuncVar (PhpFunction useVarsInner fn.args innerFuncBody)
                  
                  whileLoop = PhpWhile (PhpBinOp "===" (PhpVar ctx.doneVar) (PhpBoolean false))
                    [ PhpAssign ctx.resultVar (PhpCall (PhpVar ctx.loopFuncVar) (map PhpVar loopVars)) ]
                    
                in
                  PhpAssign newName (PhpFunction useVarsOuter fn.args (initVarStmts <> [doneAssign, resAssign, innerFunc, whileLoop, PhpReturn (PhpVar ctx.resultVar)]))
            )
            fns
            
          resBodyOuter = translateExprImpl combinedRecVars namedBound newBound Nothing loopCtx isTail (nextId + 1) body
        in
          { stmts: initStmts <> fnWrapperStmts <> resBodyOuter.stmts, expr: resBodyOuter.expr, nextId: resBodyOuter.nextId }
          
      Nothing ->
        let
          initStmts = map (\pair -> PhpAssign pair.newName (PhpRaw "null")) oldNewPairs
          accBinds = foldl
            ( \acc (Tuple (Ident ident) val@(TcoExpr _ _)) ->
                let
                  oldName = localId (Just (Ident ident)) lvl
                  newName = fromMaybe oldName (Map.lookup oldName newBound)
                  res = translateExprImpl combinedRecVars namedBound newBound (Just newName) [] false acc.nextId val
                in
                  { stmts: acc.stmts <> res.stmts <> [ PhpAssign newName res.expr ], nextId: res.nextId }
            )
            { stmts: initStmts, nextId: nextId + 1 }
            (toArray binds)
          resBody = translateExprImpl combinedRecVars namedBound newBound Nothing loopCtx isTail accBinds.nextId body
        in
          { stmts: accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  EffectBind (Just (Ident i)) (Level l) val body ->
    let
      oldVarName = localId (Just (Ident i)) (Level l)
      varName = oldVarName <> "_" <> show nextId
      resVal = translateExprImpl recVars namedBound bound (Just varName) [] false nextId val
      newBound = Map.insert oldVarName varName bound
      resBody = translateExprImpl recVars namedBound newBound Nothing loopCtx isTail (resVal.nextId + 1) body
    in
      { stmts: resVal.stmts <> [ PhpAssign varName resVal.expr ] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  EffectBind Nothing (Level l) val body ->
    let
      oldVarName = localId Nothing (Level l)
      varName = oldVarName <> "_" <> show nextId
      resVal = translateExprImpl recVars namedBound bound (Just varName) [] false nextId val
      newBound = Map.insert oldVarName varName bound
      resBody = translateExprImpl recVars namedBound newBound Nothing loopCtx isTail (resVal.nextId + 1) body
    in
      { stmts: resVal.stmts <> [ PhpAssign varName resVal.expr ] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  EffectPure e -> translateExprImpl recVars namedBound bound Nothing loopCtx isTail nextId e

  EffectDefer e ->
    let
      res = translateExprImpl recVars namedBound bound Nothing [] false nextId e
      fvs = freeVars tcoExpr
      useVars = map (\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
    in
      { stmts: [], expr: PhpFunction useVars [] (res.stmts <> [ PhpReturn res.expr ]), nextId: res.nextId }

  Branch pairs def ->
    let
      resDef = translateExprImpl recVars namedBound bound Nothing loopCtx isTail nextId def
      tmpVar = "__t" <> show resDef.nextId
      accPairs = foldl
        ( \acc (Pair condExpr@(TcoExpr _ _cond) bodyExpr@(TcoExpr _ _body)) ->
            let
              resCond = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId condExpr
              resBody = translateExprImpl recVars namedBound bound Nothing loopCtx isTail resCond.nextId bodyExpr
            in
              { ifNodes: Array.snoc acc.ifNodes { cond: wrapInStmts (map (\v -> fromMaybe v (Map.lookup v bound)) (Array.fromFoldable (freeVars condExpr))) resCond.stmts resCond.expr, body: resBody.stmts <> [ PhpAssign tmpVar resBody.expr ] }, nextId: resBody.nextId }
        )
        { ifNodes: [], nextId: resDef.nextId + 1 }
        (toArray pairs)

      finalElse = resDef.stmts <> [ PhpAssign tmpVar resDef.expr ]
      nestedIf = foldr (\ifNode accElse -> [ PhpIf ifNode.cond ifNode.body accElse ]) finalElse accPairs.ifNodes
    in
      { stmts: nestedIf, expr: PhpVar tmpVar, nextId: accPairs.nextId }

  Update e props ->
    let
      resE = translateExprImpl recVars namedBound bound Nothing [] false nextId e
      tmpVar = "__obj" <> show resE.nextId
      accProps = foldl
        ( \acc (Prop key val@(TcoExpr _ _)) ->
            let
              resVal = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId val
            in
              { stmts: acc.stmts <> resVal.stmts <> [ PhpAssignExpr (PhpPropertyAccess (PhpVar tmpVar) key) resVal.expr ], nextId: resVal.nextId }
        )
        { stmts: [], nextId: resE.nextId + 1 }
        props
    in
      { stmts: resE.stmts <> [ PhpAssign tmpVar (PhpClone resE.expr) ] <> accProps.stmts, expr: PhpVar tmpVar, nextId: accProps.nextId }

  CtorSaturated _ _ _ (Ident ctorName) args ->
    let
      numFields = Array.length args
      accArgs = foldl
        ( \acc (Tuple _ val@(TcoExpr _ _)) ->
            let
              resVal = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId val
            in
              { stmts: acc.stmts <> resVal.stmts, exprs: Array.snoc acc.exprs resVal.expr, nextId: resVal.nextId }
        )
        { stmts: [], exprs: [], nextId }
        args
      body = PhpNew ("Phpurs_Data" <> show numFields) ([ PhpString ctorName ] <> accArgs.exprs)
    in
      { stmts: accArgs.stmts, expr: body, nextId: accArgs.nextId }

  CtorDef _ _ (Ident ctorName) fields ->
    let
      numFields = Array.length fields
      body = PhpNew ("Phpurs_Data" <> show numFields) ([ PhpString ctorName ] <> map PhpVar fields)
      safeCtorName = String.replaceAll (Pattern "'") (Replacement "\\'") ctorName
      singletonBody = PhpBinOp "??=" (PhpRaw ("$GLOBALS['__phpurs_data0_" <> safeCtorName <> "']")) body
    in
      if numFields == 0 then { stmts: [], expr: singletonBody, nextId } else { stmts: [], expr: PhpFunction [] fields [ PhpReturn body ], nextId }

  PrimOp op -> case op of
    Op1 op1 e@(TcoExpr _ _) ->
      let
        resE = translateExprImpl recVars namedBound bound Nothing [] false nextId e
      in
        { stmts: resE.stmts, expr: translateOperator1 op1 resE.expr, nextId: resE.nextId }
    Op2 op2 e1@(TcoExpr _ _) e2@(TcoExpr _ _) ->
      let
        res1 = translateExprImpl recVars namedBound bound Nothing [] false nextId e1
        res2 = translateExprImpl recVars namedBound bound Nothing [] false res1.nextId e2
      in
        { stmts: res1.stmts <> res2.stmts, expr: translateOperator2 op2 res1.expr res2.expr, nextId: res2.nextId }

  PrimEffect _ -> { stmts: [], expr: PhpString "TODO_PrimEffect", nextId }
  PrimUndefined -> { stmts: [], expr: PhpRaw "null", nextId }
  Fail msg -> { stmts: [ PhpThrow (PhpRaw ("\"" <> msg <> " at \" . __FILE__ . \":\" . __LINE__")) ], expr: PhpRaw "null", nextId }

unwrapExpr :: TcoExpr -> BackendSyntax TcoExpr
unwrapExpr (TcoExpr _ e) = e

-- | Main translation function.
-- | Takes the list of module imports and a `BackendModule` (containing `TcoExpr` bindings)
-- | and returns a fully constructed `PhpFile` ready for printing.
translate :: Array (Array String) -> BackendModule -> PhpFile
translate imports mod =
  let
    modNameStr = String.replaceAll (Pattern ".") (Replacement "_") (unwrap mod.name)
    modPrefix = modNameStr <> "_"
    
    Tuple _ tcoBindings = foldl
      (\(Tuple env acc) group ->
          let
            neBindings = fromArray group.bindings
            env' = case neBindings of
              Just ne | group.recursive -> Tco.topLevelTcoEnvGroup mod.name ne <> env
              _ -> env
            tcoBinds = map (\(Tuple k v) -> Tuple k (Tco.analyze env' v)) group.bindings
          in
            Tuple env' (Array.snoc acc { recursive: group.recursive, bindings: tcoBinds })
      )
      (Tuple [] [])
      mod.bindings

    decls = Array.concatMap
      ( \group ->
          let
            recVars = if group.recursive then map (\(Tuple (Ident name) _) -> modPrefix <> name) group.bindings else []
          in
            if group.recursive && Array.length group.bindings == 1 then
              let
                mutRecBinds = traverse (\(Tuple (Ident name) val) -> map (\abs -> { ident: modPrefix <> name, args: abs.args, body: abs.body, fvs: abs.fvs }) (extractUncurriedAbs val)) group.bindings
              in case mutRecBinds of
                Just fns ->
                  let
                    loopCtxs = map (\fn ->
                      { ident: fn.ident, params: fn.args, doneVar: "__tco_done_" <> fn.ident, resultVar: "__tco_res_" <> fn.ident, loopFuncVar: "__tco_loop_" <> fn.ident, varPrefix: "__tco_var_" <> fn.ident <> "_" }
                    ) fns
                    
                    fnWrapperStmts = map
                      ( \fn ->
                          let
                            ctx = fromMaybe { ident: "", params: [], doneVar: "", resultVar: "", loopFuncVar: "", varPrefix: "" } (Array.find (\c -> c.ident == fn.ident) loopCtxs)
                            loopVars = map (\p -> ctx.varPrefix <> p) fn.args
                            initVarStmts = Array.mapWithIndex (\i p -> PhpAssign (fromMaybe "" (Array.index loopVars i)) (PhpVar p)) fn.args
                            doneAssign = PhpAssign ctx.doneVar (PhpBoolean false)
                            resAssign = PhpAssign ctx.resultVar (PhpRaw "null")
                            
                            resBodyMut = translateExprImpl recVars Map.empty Map.empty Nothing loopCtxs true 0 fn.body
                            
                            mappedFvs = map (\v -> v) (Array.fromFoldable fn.fvs)
                            useVarsLoopRaw = Array.nub (map (\mapped -> if Array.elem mapped recVars then "&" <> mapped else mapped) mappedFvs)
                            
                            mutVarsToCapture = foldMap (\c -> Array.cons ("&" <> c.doneVar) (map (\p -> "&" <> c.varPrefix <> p) c.params)) loopCtxs
                            useVarsInner = mutVarsToCapture <> useVarsLoopRaw
                            useVarsOuter = useVarsLoopRaw
                            
                            innerLoopInit = Array.mapWithIndex (\i p -> PhpAssign p (PhpVar (fromMaybe "" (Array.index loopVars i)))) fn.args
                            innerFuncBody = Array.cons (PhpAssign ctx.doneVar (PhpRaw "true")) (innerLoopInit <> resBodyMut.stmts <> [ PhpReturn resBodyMut.expr ])
                            innerFunc = PhpAssign ctx.loopFuncVar (PhpFunction useVarsInner fn.args innerFuncBody)
                            
                            whileLoop = PhpWhile (PhpBinOp "===" (PhpVar ctx.doneVar) (PhpBoolean false))
                              [ PhpAssign ctx.resultVar (PhpCall (PhpVar ctx.loopFuncVar) (map PhpVar loopVars)) ]
                              
                          in
                            { identifier: fn.ident, expression: PhpValueThunk fn.ident (PhpFunction useVarsOuter fn.args (initVarStmts <> [doneAssign, resAssign, innerFunc, whileLoop, PhpReturn (PhpVar ctx.resultVar)])) }
                      )
                      fns
                  in
                    fnWrapperStmts
                Nothing ->
                  Array.concatMap
                    ( \(Tuple (Ident name) expr) ->
                        let
                          res = translateExprImpl recVars Map.empty Map.empty (Just (modPrefix <> name)) [] false 0 expr
                        in
                          [ { identifier: modPrefix <> name, expression: PhpValueThunk (modPrefix <> name) (wrapInStmts [] res.stmts res.expr) } ]
                    )
                    group.bindings
            else
              Array.concatMap
                ( \(Tuple (Ident name) expr) ->
                    let
                      res = translateExprImpl [] Map.empty Map.empty (Just (modPrefix <> name)) [] false 0 expr
                    in
                      [ { identifier: modPrefix <> name, expression: PhpValueThunk (modPrefix <> name) (wrapInStmts [] res.stmts res.expr) } ]
                )
                group.bindings
      )
      tcoBindings

  in
    { namespace: String.split (Pattern ".") (unwrap mod.name), decls, imports }

dedupArgs :: Array String -> Array String
dedupArgs args = Array.mapWithIndex
  ( \idx name ->
      let
        isShadowed = isJust (Array.findIndex (\x -> x == name) (Array.drop (idx + 1) args))
      in
        if isShadowed || name == "__unused" || name == "$__unused" || name == "_" then name <> "_" <> show idx
        else name
  )
  args

extractUncurriedAbs :: TcoExpr -> Maybe { args :: Array String, body :: TcoExpr, fvs :: Set String }
extractUncurriedAbs tcoExpr@(TcoExpr _ syntax) = case syntax of
  UncurriedAbs args body ->
    Just { args: map (\(Tuple mbI lvl) -> localId mbI lvl) args, body, fvs: freeVars tcoExpr }
  Abs args body ->
    Just { args: map (\(Tuple mbI lvl) -> localId mbI lvl) (toArray args), body, fvs: freeVars tcoExpr }
  _ -> Nothing