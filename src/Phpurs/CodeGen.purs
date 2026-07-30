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
import PureScript.Backend.Optimizer.CoreFn (Qualified(..), Ident(..), ModuleName(..), Literal(..), Prop(..), ExprType(..))
import PureScript.Backend.Optimizer.Convert (BackendModule)
import Phpurs.PhpAst (PhpExpr(..), PhpFile)
import PureScript.Backend.Optimizer.FreeVars (freeVars, localId)
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
wrapInStmts captures stmts expr = PhpCall (PhpFunction captures [] "" (stmts <> [ PhpReturn expr ])) []

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
translateOperator1 (OpIsTag (Qualified mbMod (Ident tag))) e =
  let
    safeTag = String.replaceAll (Pattern "'") (Replacement "_prime_") tag
    absClass = case mbMod of
      Just (ModuleName m) -> "\\" <> String.replaceAll (Pattern ".") (Replacement "\\") m <> "\\" <> String.replaceAll (Pattern ".") (Replacement "_") m <> "_" <> safeTag
      Nothing -> safeTag -- Should not happen for fully qualified tags
  in
    PhpInstanceOf e absClass

translateOperator2 :: BackendOperator2 -> PhpExpr -> PhpExpr -> PhpExpr
translateOperator2 OpArrayIndex arr ix = PhpArrayIndex arr ix
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


type LoopCtx = { ident :: String, params :: Array String, varPrefix :: String, labelName :: String }

flattenApp :: TcoExpr -> Tuple (TcoExpr) (Array TcoExpr)
flattenApp tcoExpr@(TcoExpr _ syntax) = case syntax of
  App fn args ->
    let
      Tuple innerFn innerArgs = flattenApp fn
    in
      Tuple innerFn (innerArgs <> toArray args)
  _ -> Tuple tcoExpr []

translateExprImpl :: String -> Array String -> Map String String -> Map String String -> Maybe String -> Array LoopCtx -> Boolean -> Int -> TcoExpr -> TranslationRes
translateExprImpl modNameStr recVars namedBound bound _currentBindingName loopCtx isTail nextId tcoExpr@(TcoExpr _tcoAnalysis syntax) = case syntax of
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
                  res = translateExprImpl modNameStr recVars namedBound bound Nothing [] false a.nextId expr
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
                  res = translateExprImpl modNameStr recVars namedBound bound Nothing [] false a.nextId val
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
      resFn = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId fn
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
              argRes = translateExprImpl modNameStr recVars namedBound bound Nothing [] false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
        )
        { stmts: resFn.stmts, exprs: [], nextId: resFn.nextId }
        argsArr

    in case isTailCallTo of
      Just index ->
        let
          targetCtx = fromMaybe { ident: "", params: [], varPrefix: "", labelName: "" } (Array.index loopCtx index)
          
          flatAccFinal = foldl
            ( \acc arg@(TcoExpr _ _) ->
                let
                  argRes = translateExprImpl modNameStr recVars namedBound bound Nothing [] false acc.nextId arg
                in
                  { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
            )
            { stmts: [], exprs: [], nextId: nextId }
            flatArgs
            
          tcoStmts = Array.mapWithIndex (\i e -> PhpAssign ("__tco_" <> show (flatAccFinal.nextId + i)) e) flatAccFinal.exprs
          assignStmts = Array.mapWithIndex (\i _ -> PhpAssign (targetCtx.varPrefix <> (fromMaybe "" (Array.index targetCtx.params i))) (PhpVar ("__tco_" <> show (flatAccFinal.nextId + i)))) flatAccFinal.exprs
          
          finalStmts = flatAccFinal.stmts <> tcoStmts <> assignStmts <> [ PhpGoto targetCtx.labelName ]
        in { stmts: finalStmts, expr: PhpRaw "null", nextId: flatAccFinal.nextId + Array.length flatArgs }
      Nothing -> 
        let curriedCall = foldl (\acc e -> PhpCall acc [e]) resFn.expr accFinal.exprs
        in { stmts: accFinal.stmts, expr: curriedCall, nextId: accFinal.nextId }

  UncurriedApp fn args ->
    let
      resFn = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId fn
      
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
              argRes = translateExprImpl modNameStr recVars namedBound bound Nothing [] false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
        )
        { stmts: [], exprs: [], nextId: resFn.nextId }
        args
        
    in case isTailCallTo of
      Just index ->
        let
          targetCtx = fromMaybe { ident: "", params: [], varPrefix: "", labelName: "" } (Array.index loopCtx index)
          tcoStmts = Array.mapWithIndex (\i e -> PhpAssign ("__tco_" <> show (accArgs.nextId + i)) e) accArgs.exprs
          assignStmts = Array.mapWithIndex (\i _ -> PhpAssign (targetCtx.varPrefix <> (fromMaybe "" (Array.index targetCtx.params i))) (PhpVar ("__tco_" <> show (accArgs.nextId + i)))) accArgs.exprs
          
          finalStmts = accArgs.stmts <> tcoStmts <> assignStmts <> [ PhpGoto targetCtx.labelName ]
        in { stmts: finalStmts, expr: PhpRaw "null", nextId: accArgs.nextId + Array.length args }
      Nothing ->
        { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }

  UncurriedEffectApp fn args ->
    let
      resFn = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId fn
      accArgs = foldl
        ( \acc arg@(TcoExpr _ _) ->
            let
              argRes = translateExprImpl modNameStr recVars namedBound bound Nothing [] false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
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
      
      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] true nextId body
      types = extractFuncType tcoExpr
      argsWithTypes = zipArgsWithTypes argsArray types
      retType = getRetType (Array.length argsArray) types
    in
      { stmts: [], expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }

  UncurriedAbs args body ->
    let
      argsArray = map (\(Tuple mbI lvl) -> localId mbI lvl) args
      fvs = freeVars tcoExpr
      useVars = map (\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] true nextId body
      types = extractFuncType tcoExpr
      argsWithTypes = zipArgsWithTypes argsArray types
      retType = getRetType (Array.length argsArray) types
    in
      { stmts: [], expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }

  UncurriedEffectAbs args body ->
    let
      argsArray = map (\(Tuple mbI lvl) -> localId mbI lvl) args
      fvs = freeVars tcoExpr
      useVars = map (\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId body
      types = extractFuncType tcoExpr
      argsWithTypes = zipArgsWithTypes argsArray types
      retType = getRetType (Array.length argsArray) types
    in
      { stmts: [], expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }

  Accessor e acc ->
    let
      res = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId e
    in
      case acc of
        GetProp prop -> { stmts: res.stmts, expr: PhpRecordAccess res.expr prop, nextId: res.nextId }
        GetIndex idx -> { stmts: res.stmts, expr: PhpArrayIndex res.expr (PhpInt idx), nextId: res.nextId }
        GetCtorField _ _ _ _ prop _ -> { stmts: res.stmts, expr: PhpPropertyAccess res.expr prop, nextId: res.nextId }

  Let (Just (Ident i)) (Level l) val body ->
    let
      oldVarName = localId (Just (Ident i)) (Level l)
      varName = oldVarName <> "_" <> show nextId
      resVal = translateExprImpl modNameStr recVars namedBound bound (Just varName) [] false nextId val
      newBound = Map.insert oldVarName varName bound
      resBody = translateExprImpl modNameStr recVars namedBound newBound Nothing loopCtx isTail (resVal.nextId + 1) body
    in
      { stmts: resVal.stmts <> [ PhpAssign varName resVal.expr ] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  Let Nothing (Level l) val body ->
    let
      oldVarName = localId Nothing (Level l)
      varName = oldVarName <> "_" <> show nextId
      resVal = translateExprImpl modNameStr recVars namedBound bound (Just varName) [] false nextId val
      newBound = Map.insert oldVarName varName bound
      resBody = translateExprImpl modNameStr recVars namedBound newBound Nothing loopCtx isTail (resVal.nextId + 1) body
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
            Just abs -> Just { ident: localId (Just ident) lvl, args: abs.args, body: abs.body, fvs: abs.fvs, originalVal: val }
            Nothing -> Nothing
        ) (toArray binds)
      else Nothing
    in case mutRecBinds of
      Just fns ->
        let
          initStmts = map (\pair -> PhpAssign pair.newName (PhpRaw "null")) oldNewPairs
          
          loopCtxs = map (\fn ->
            let newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
            in { ident: newName, params: fn.args, varPrefix: "__tco_var_" <> newName <> "_" <> show nextId <> "_", labelName: "tco_loop_" <> newName <> "_" <> show nextId }
          ) fns
          
          combinedLoopCtx = loopCtxs
          
          fnWrapperStmts = map
            ( \fn ->
                let
                  newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
                  ctx = fromMaybe { ident: "", params: [], varPrefix: "", labelName: "" } (Array.find (\c -> c.ident == newName) loopCtxs)
                  
                  loopVars = map (\p -> ctx.varPrefix <> p) fn.args
                  
                  initVarStmts = Array.mapWithIndex (\i p -> PhpAssign (fromMaybe "" (Array.index loopVars i)) (PhpVar p)) fn.args
                  
                  resBodyMut = translateExprImpl modNameStr combinedRecVars namedBound newBound Nothing combinedLoopCtx true nextId fn.body
                  
                  mappedFvs = map (\v -> fromMaybe v (Map.lookup v newBound)) (Array.fromFoldable fn.fvs)
                  useVarsLoop = Array.nub (map (\mapped -> if Array.elem mapped combinedRecVars then "&" <> mapped else mapped) mappedFvs)
                  
                  mutVarsToCaptureOuter = foldMap (\c -> map (\p -> "&" <> c.varPrefix <> p) c.params) loopCtx
                  useVarsOuter = mutVarsToCaptureOuter <> useVarsLoop
                  
                  innerLoopInit = Array.mapWithIndex (\i p -> PhpAssign p (PhpVar (fromMaybe "" (Array.index loopVars i)))) fn.args
                  innerFuncBody = [ PhpLabel ctx.labelName ] <> innerLoopInit <> resBodyMut.stmts <> [ PhpReturn resBodyMut.expr ]
                  
                in
                  let
                    types = extractFuncType fn.originalVal
                    argsWithTypes = zipArgsWithTypes fn.args types
                    retType = getRetType (Array.length fn.args) types
                  in PhpAssign newName (PhpFunction useVarsOuter argsWithTypes retType (initVarStmts <> innerFuncBody))
            )
            fns
            
          resBodyOuter = translateExprImpl modNameStr combinedRecVars namedBound newBound Nothing loopCtx isTail (nextId + 1) body
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
                  res = translateExprImpl modNameStr combinedRecVars namedBound newBound (Just newName) [] false acc.nextId val
                in
                  { stmts: acc.stmts <> res.stmts <> [ PhpAssign newName res.expr ], nextId: res.nextId }
            )
            { stmts: initStmts, nextId: nextId + 1 }
            (toArray binds)
          resBody = translateExprImpl modNameStr combinedRecVars namedBound newBound Nothing loopCtx isTail accBinds.nextId body
        in
          { stmts: accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  EffectBind (Just (Ident i)) (Level l) val body ->
    let
      oldVarName = localId (Just (Ident i)) (Level l)
      varName = oldVarName <> "_" <> show nextId
      resVal = translateExprImpl modNameStr recVars namedBound bound (Just varName) [] false nextId val
      newBound = Map.insert oldVarName varName bound
      resBody = translateExprImpl modNameStr recVars namedBound newBound Nothing loopCtx isTail (resVal.nextId + 1) body
    in
      { stmts: resVal.stmts <> [ PhpAssign varName resVal.expr ] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  EffectBind Nothing (Level l) val body ->
    let
      oldVarName = localId Nothing (Level l)
      varName = oldVarName <> "_" <> show nextId
      resVal = translateExprImpl modNameStr recVars namedBound bound (Just varName) [] false nextId val
      newBound = Map.insert oldVarName varName bound
      resBody = translateExprImpl modNameStr recVars namedBound newBound Nothing loopCtx isTail (resVal.nextId + 1) body
    in
      { stmts: resVal.stmts <> [ PhpAssign varName resVal.expr ] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  EffectPure e -> translateExprImpl modNameStr recVars namedBound bound Nothing loopCtx isTail nextId e

  EffectDefer e ->
    let
      res = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId e
      fvs = freeVars tcoExpr
      useVars = map (\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
    in
      { stmts: [], expr: PhpFunction useVars [] "" (res.stmts <> [ PhpReturn res.expr ]), nextId: res.nextId }

  Branch pairs def -> 
    let
      resDef = translateExprImpl modNameStr recVars namedBound bound Nothing loopCtx isTail nextId def
      tmpVar = "__t" <> show resDef.nextId
      labelName = "end_branch_" <> show resDef.nextId
      accPairs = foldl
        ( \acc (Pair condExpr@(TcoExpr _ _cond) bodyExpr@(TcoExpr _ _body)) ->
            let
              resCond = translateExprImpl modNameStr recVars namedBound bound Nothing [] false acc.nextId condExpr
              resBody = translateExprImpl modNameStr recVars namedBound bound Nothing loopCtx isTail resCond.nextId bodyExpr
              condWrapped = wrapInStmts (map (\v -> fromMaybe v (Map.lookup v bound)) (Array.fromFoldable (freeVars condExpr))) resCond.stmts resCond.expr
              ifNode = PhpIf condWrapped (resBody.stmts <> [ PhpAssign tmpVar resBody.expr, PhpRaw ("goto " <> labelName <> ";") ]) []
            in
              { stmts: acc.stmts <> [ifNode], nextId: resBody.nextId }
        )
        { stmts: [], nextId: resDef.nextId + 1 }
        (toArray pairs)

      finalDef = resDef.stmts <> [ PhpAssign tmpVar resDef.expr, PhpRaw (labelName <> ":") ]
      
      extractMatch :: Array PhpExpr -> Array PhpExpr -> Maybe PhpExpr
      extractMatch ifNodes defStmts = case Array.uncons ifNodes of
        Nothing -> Nothing
        Just { head: PhpIf (PhpBinOp "===" subj val) [PhpAssign tVar body, PhpRaw _] [], tail } | tVar == tmpVar ->
            let
              checkTail :: Array PhpExpr -> Array { val :: PhpExpr, body :: PhpExpr } -> Maybe (Array { val :: PhpExpr, body :: PhpExpr })
              checkTail rest acc = case Array.uncons rest of
                Nothing -> Just acc
                Just { head: PhpIf (PhpBinOp "===" s v) [PhpAssign tVar2 b, PhpRaw _] [], tail: t } | s == subj && tVar2 == tmpVar -> checkTail t (Array.snoc acc { val: v, body: b })
                _ -> Nothing
            in case checkTail tail [{ val, body }] of
              Just validCases ->
                if Array.length defStmts == 2 then
                  case Array.index defStmts 0 of
                    Just (PhpAssign tVar3 defExpr) | tVar3 == tmpVar -> Just (PhpMatch subj validCases defExpr)
                    _ -> Nothing
                else Nothing
              Nothing -> Nothing
        _ -> Nothing
        
    in case extractMatch accPairs.stmts finalDef of
      Just matchExpr -> { stmts: [], expr: matchExpr, nextId: accPairs.nextId }
      Nothing ->
        { stmts: [ PhpRaw ("$" <> tmpVar <> " = null;") ] <> accPairs.stmts <> finalDef, expr: PhpVar tmpVar, nextId: accPairs.nextId }

  Update e props ->
    let
      resE = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId e
      tmpVar = "__obj" <> show resE.nextId
      accProps = foldl
        ( \acc (Prop key val@(TcoExpr _ _)) ->
            let
              resVal = translateExprImpl modNameStr recVars namedBound bound Nothing [] false acc.nextId val
            in
              { stmts: acc.stmts <> resVal.stmts <> [ PhpAssignExpr (PhpRecordAccess (PhpVar tmpVar) key) resVal.expr ], nextId: resVal.nextId }
        )
        { stmts: [], nextId: resE.nextId + 1 }
        props
    in
      { stmts: resE.stmts <> [ PhpAssign tmpVar (PhpClone resE.expr) ] <> accProps.stmts, expr: PhpVar tmpVar, nextId: accProps.nextId }

  CtorSaturated (Qualified mbMod _) _ _ (Ident ctorName) args ->
    let
      safeCtorName = String.replaceAll (Pattern "'") (Replacement "_prime_") ctorName
      absClass = case mbMod of
        Just (ModuleName m) -> "\\" <> String.replaceAll (Pattern ".") (Replacement "\\") m <> "\\" <> String.replaceAll (Pattern ".") (Replacement "_") m <> "_" <> safeCtorName
        Nothing -> "\\" <> String.replaceAll (Pattern "_") (Replacement "\\") modNameStr <> "\\" <> modNameStr <> "_" <> safeCtorName
      accArgs = foldl
        ( \acc (Tuple _ val@(TcoExpr _ _)) ->
            let
              resVal = translateExprImpl modNameStr recVars namedBound bound Nothing [] false acc.nextId val
            in
              { stmts: acc.stmts <> resVal.stmts, exprs: Array.snoc acc.exprs resVal.expr, nextId: resVal.nextId }
        )
        { stmts: [], exprs: [], nextId }
        args
      body = PhpNew absClass accArgs.exprs
    in
      { stmts: accArgs.stmts, expr: body, nextId: accArgs.nextId }

  CtorDef _ _ (Ident ctorName) fields ->
    let
      safeCtorName = String.replaceAll (Pattern "'") (Replacement "_prime_") ctorName
      absClass = "\\" <> String.replaceAll (Pattern "_") (Replacement "\\") modNameStr <> "\\" <> modNameStr <> "_" <> safeCtorName
      numFields = Array.length fields
      body = PhpNew absClass (map PhpVar fields)
      safeCtorNameStr = String.replaceAll (Pattern "'") (Replacement "\\'") ctorName
      singletonBody = PhpBinOp "??=" (PhpRaw ("$GLOBALS['__phpurs_data0_" <> safeCtorNameStr <> "']")) body
    in
      if numFields == 0 then { stmts: [], expr: singletonBody, nextId } else { stmts: [], expr: PhpFunction [] (map (\n -> { name: n, type_: "" }) fields) "" [ PhpReturn body ], nextId }

  PrimOp op -> case op of
    Op1 op1 e@(TcoExpr _ _) ->
      let
        resE = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId e
      in
        { stmts: resE.stmts, expr: translateOperator1 op1 resE.expr, nextId: resE.nextId }
    Op2 op2 e1@(TcoExpr _ _) e2@(TcoExpr _ _) ->
      let
        res1 = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId e1
        res2 = translateExprImpl modNameStr recVars namedBound bound Nothing [] false res1.nextId e2
      in
        { stmts: res1.stmts <> res2.stmts, expr: translateOperator2 op2 res1.expr res2.expr, nextId: res2.nextId }

  PrimEffect _ -> { stmts: [], expr: PhpString "TODO_PrimEffect", nextId }
  PrimUndefined -> { stmts: [], expr: PhpRaw "null", nextId }
  Fail msg -> { stmts: [ PhpThrow (PhpRaw ("\"" <> msg <> " at \" . __FILE__ . \":\" . __LINE__")) ], expr: PhpRaw "null", nextId }
  Typed _ a -> translateExprImpl modNameStr recVars namedBound bound _currentBindingName loopCtx isTail nextId a
unwrapExpr :: TcoExpr -> BackendSyntax TcoExpr
unwrapExpr (TcoExpr _ e) = e


extractFuncType :: TcoExpr -> Maybe { fArgs :: Array ExprType, fRet :: ExprType }
extractFuncType (TcoExpr _ (Typed ty inner)) =
  let
    flattenFuncType acc (Func args ret) = flattenFuncType (acc <> args) ret
    flattenFuncType acc ret = { fArgs: acc, fRet: ret }
    
    getFunc (Func a r) = Just (flattenFuncType a r)
    getFunc _ = extractFuncType inner
  in getFunc ty
extractFuncType _ = Nothing

getExprType :: TcoExpr -> ExprType
getExprType (TcoExpr _ syn) = case syn of
  Typed ty _ -> ty
  _ -> Any

zipArgsWithTypes :: Array String -> Maybe { fArgs :: Array ExprType, fRet :: ExprType } -> Array { name :: String, type_ :: String }
zipArgsWithTypes names mbTypes =
  case mbTypes of
    Just { fArgs } ->
      Array.mapWithIndex (\i name ->
        let t = fromMaybe Any (Array.index fArgs i)
        in { name: name, type_: exprTypeToPhpType t }
      ) names
    Nothing ->
      map (\name -> { name: name, type_: "" }) names

getRetType :: Int -> Maybe { fArgs :: Array ExprType, fRet :: ExprType } -> String
getRetType arity mbTypes =
  case mbTypes of
    Just { fArgs, fRet } ->
      if arity < Array.length fArgs then "" else exprTypeToPhpType fRet
    Nothing -> ""

exprTypeToPhpType :: ExprType -> String
exprTypeToPhpType = case _ of
  Int -> "int"
  Number -> "float"
  String -> "string"
  Boolean -> "bool"
  Array _ -> ""
  Func _ _ -> ""
  _ -> ""

-- | Main translation function.
-- | Takes the list of module imports and a `BackendModule` (containing `TcoExpr` bindings)
-- | and returns a fully constructed `PhpFile` ready for printing.
translate :: Array (Array String) -> BackendModule -> PhpFile
translate imports mod =
  let
    modNameStr = String.replaceAll (Pattern ".") (Replacement "_") (unwrap mod.name)
    modPrefix = modNameStr <> "_"
    
    rawDecls = Array.concatMap (\decl ->
        Array.concatMap (\ctor ->
          let
            safeCtorName = String.replaceAll (Pattern "'") (Replacement "_prime_") ctor.constructorName
            structName = modPrefix <> safeCtorName
            safeTagStr = String.replaceAll (Pattern "'") (Replacement "\\'") ctor.constructorName
            argsStr = Array.mapWithIndex (\i typ -> "public " <> exprTypeToPhpType typ <> " $value" <> show i) ctor.fieldTypes
            structDecl = "final class " <> structName <> " { public $tag = '" <> safeTagStr <> "'; public function __construct(" <> String.joinWith ", " argsStr <> ") {} }"
          in
            [ structDecl ]
        ) decl.constructors
      ) mod.dataDecls

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
                mutRecBinds = traverse (\(Tuple (Ident name) val) -> map (\abs -> { ident: modPrefix <> name, args: abs.args, body: abs.body, fvs: abs.fvs, originalVal: val }) (extractUncurriedAbs val)) group.bindings
              in case mutRecBinds of
                Just fns ->
                  let
                    loopCtxs = map (\fn ->
                      { ident: fn.ident, params: fn.args, varPrefix: "__tco_var_" <> fn.ident <> "_", labelName: "tco_loop_" <> fn.ident }
                    ) fns
                    
                    fnWrapperStmts = map
                      ( \fn ->
                          let
                            ctx = fromMaybe { ident: "", params: [], varPrefix: "", labelName: "" } (Array.find (\c -> c.ident == fn.ident) loopCtxs)
                            loopVars = map (\p -> ctx.varPrefix <> p) fn.args
                            initVarStmts = Array.mapWithIndex (\i p -> PhpAssign (fromMaybe "" (Array.index loopVars i)) (PhpVar p)) fn.args
                            
                            resBodyMut = translateExprImpl modNameStr recVars Map.empty Map.empty Nothing loopCtxs true 0 fn.body
                            
                            mappedFvs = map (\v -> v) (Array.fromFoldable fn.fvs)
                            useVarsOuter = Array.nub (map (\mapped -> if Array.elem mapped recVars then "&" <> mapped else mapped) mappedFvs)
                            
                            innerLoopInit = Array.mapWithIndex (\i p -> PhpAssign p (PhpVar (fromMaybe "" (Array.index loopVars i)))) fn.args
                            innerFuncBody = [ PhpLabel ctx.labelName ] <> innerLoopInit <> resBodyMut.stmts <> [ PhpReturn resBodyMut.expr ]
                              
                          in
                             let
                               types = extractFuncType fn.originalVal
                               argsWithTypes = zipArgsWithTypes fn.args types
                               retType = getRetType (Array.length fn.args) types
                             in
                             { identifier: fn.ident, expression: PhpNativeFunction fn.ident argsWithTypes retType (initVarStmts <> innerFuncBody) }
                      )
                      fns
                  in
                    fnWrapperStmts
                Nothing ->
                  Array.concatMap
                    ( \(Tuple (Ident name) expr) ->
                        case extractUncurriedAbs expr of
                          Just fn ->
                             let res = translateExprImpl modNameStr recVars Map.empty Map.empty (Just (modPrefix <> name)) [] true 0 fn.body
                                 types = extractFuncType expr
                                 argsWithTypes = zipArgsWithTypes fn.args types
                                 retType = getRetType (Array.length fn.args) types
                             in [ { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType (res.stmts <> [ PhpReturn res.expr ]) } ]
                          Nothing ->
                           let
                             res = translateExprImpl modNameStr recVars Map.empty Map.empty (Just (modPrefix <> name)) [] false 0 expr
                             arity = extractTypeArity expr
                           in
                             if arity > 0 then
                               let
                                 closureName = modPrefix <> name <> "_closure"
                                 args = Array.mapWithIndex (\i _ -> "v_" <> show i) (Array.replicate arity unit)
                                 callExpr = PhpCall (PhpGlobalVar Nothing closureName) (map PhpVar args)
                                 types = extractFuncType expr
                                 argsWithTypes = zipArgsWithTypes args types
                                 retType = getRetType arity types
                                 nativeFunc = { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType [ PhpReturn callExpr ] }
                                 closureAssign = { identifier: closureName, expression: PhpGlobalAssign closureName (wrapInStmts [] res.stmts res.expr) }
                               in
                                 [ closureAssign, nativeFunc ]
                             else
                               [ { identifier: modPrefix <> name, expression: PhpGlobalAssign (modPrefix <> name) (wrapInStmts [] res.stmts res.expr) } ]
                    )
                    group.bindings
            else
              Array.concatMap
                ( \(Tuple (Ident name) expr) ->
                    let
                      arity = extractTypeArity expr
                    in
                      case extractUncurriedAbs expr of
                        Just fn ->
                           let res = translateExprImpl modNameStr [] Map.empty Map.empty (Just (modPrefix <> name)) [] false 0 fn.body
                               types = extractFuncType expr
                               argsWithTypes = zipArgsWithTypes fn.args types
                               retType = getRetType (Array.length fn.args) types
                           in [ { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType (res.stmts <> [ PhpReturn res.expr ]) } ]
                        Nothing ->
                           let
                             res = translateExprImpl modNameStr [] Map.empty Map.empty (Just (modPrefix <> name)) [] false 0 expr
                           in
                             if arity > 0 then
                               let
                                 closureName = modPrefix <> name <> "_closure"
                                 args = Array.mapWithIndex (\i _ -> "v_" <> show i) (Array.replicate arity unit)
                                 callExpr = PhpCall (PhpGlobalVar Nothing closureName) (map PhpVar args)
                                 types = extractFuncType expr
                                 argsWithTypes = zipArgsWithTypes args types
                                 retType = getRetType arity types
                                 nativeFunc = { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType [ PhpReturn callExpr ] }
                                 closureAssign = { identifier: closureName, expression: PhpGlobalAssign closureName (wrapInStmts [] res.stmts res.expr) }
                               in
                                 [ closureAssign, nativeFunc ]
                             else
                               [ { identifier: modPrefix <> name, expression: PhpGlobalAssign (modPrefix <> name) (wrapInStmts [] res.stmts res.expr) } ]
                )
                group.bindings
      )
      tcoBindings

    moduleArities = Map.fromFoldable (Array.concatMap (\group -> 
        Array.mapMaybe (\(Tuple ident tcoExpr) -> 
          Just (Tuple (modPrefix <> safeIdent ident) (extractTypeArity tcoExpr))
        ) group.bindings
      ) tcoBindings)

  in
    { namespace: String.split (Pattern ".") (unwrap mod.name), rawDecls, decls, imports, arities: moduleArities }

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
    let
      thisArgs = map (\(Tuple mbI lvl) -> localId mbI lvl) (toArray args)
    in case extractUncurriedAbs body of
      Just inner -> Just { args: thisArgs <> inner.args, body: inner.body, fvs: Set.union (freeVars tcoExpr) inner.fvs }
      Nothing -> Just { args: thisArgs, body, fvs: freeVars tcoExpr }
  Typed _ inner -> extractUncurriedAbs inner
  _ -> Nothing

extractTypeArity :: TcoExpr -> Int
extractTypeArity (TcoExpr _ syntax) = case syntax of
  Typed (Func args _) _ -> Array.length args
  Typed _ inner -> extractTypeArity inner
  _ -> 0