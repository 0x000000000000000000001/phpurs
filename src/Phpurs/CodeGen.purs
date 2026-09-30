-- | Lower optimized, typed modules to PHP: prepare private workers, analyze
-- | tail calls, translate expressions and declarations, then run PHP AST passes.
module Phpurs.CodeGen
  ( LoopContext
  , TranslationContext
  , TranslationResult
  , initialContext
  , translate
  , translateExpr
  ) where

import Prelude

import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..), Level(..), Pair(..), BackendAccessor(..), BackendOperator(..), BackendEffect(..))
import PureScript.Backend.Optimizer.Syntax as Syn
import PureScript.Backend.Optimizer.Codegen.Tco as Tco
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..), tcoAnalysisOf, TcoRef(..), TcoUsage(..), TcoAnalysis(..))
import PureScript.Backend.Optimizer.CoreFn (Qualified(..), Ident(..), ModuleName(..), Literal(..), Prop(..), ExprType(..))
import PureScript.Backend.Optimizer.Convert (BackendModule)
import Phpurs.PhpAst (PhpDecl, PhpExpr(..), PhpFile)
import Phpurs.CodeGen.Operators (isLogicalShortCircuit, translateOperator1, translateOperator2)
import Phpurs.CodeGen.Types (exprTypeToPhpType, extractFuncType, getRetType, remainingArity, zipArgsWithTypes)
import Phpurs.TailInline as TailInline
import Phpurs.CompactLoops as CompactLoops
import Phpurs.EnumRegions as EnumRegions
import Phpurs.ThunkFusion as ThunkFusion
import Phpurs.PartialBindings as PartialBindings
import Phpurs.NullableConstructors as Nullable
import Phpurs.ArrayRefs as ArrayRefs
import Phpurs.CopyCleanup as CopyCleanup
import PureScript.Backend.Optimizer.FreeVars (freeVars, localId)
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Array.NonEmpty (toArray, fromArray)
import Data.Tuple (Tuple(..))
import Data.Array as Array
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.Foldable (all, foldl, foldMap)
import Data.Traversable (traverse)
import Data.Newtype (unwrap)
import Data.Map as Map
import Data.Map (Map)
import Data.Set as Set

-- | Statements must execute before the result expression is consumed. `nextId`
-- | is threaded through siblings so their live temporaries cannot collide.
type TranslationResult = { stmts :: Array PhpExpr, expr :: PhpExpr, nextId :: Int }

type LoopContext = { ident :: String, params :: Array String, varPrefix :: String, labelName :: String }

-- | Lexical scope and evaluation position for one expression. A value operand
-- | starts outside the enclosing tail/effect position; binding bodies inherit it.
type TranslationContext =
  { moduleName :: String
  , recursiveVars :: Array String
  , boundVars :: Map String String
  , loops :: Array LoopContext
  , isTail :: Boolean
  , inEffectBlock :: Boolean
  }

initialContext :: String -> TranslationContext
initialContext moduleName =
  { moduleName
  , recursiveVars: []
  , boundVars: Map.empty
  , loops: []
  , isTail: false
  , inEffectBlock: false
  }

valueContext :: TranslationContext -> TranslationContext
valueContext ctx = ctx { loops = [], isTail = false, inEffectBlock = false }

translateValue :: TranslationContext -> Int -> TcoExpr -> TranslationResult
translateValue ctx = translateExpr (valueContext ctx)

-- | Recursive closures capture the renamed binding by reference so they see
-- | its eventual initialization. Ordinary captures keep PHP's value semantics.
closureCaptures :: TranslationContext -> TcoExpr -> Array String
closureCaptures ctx expr = map capture (Array.fromFoldable (freeVars expr))
  where
  capture name =
    let renamed = fromMaybe name (Map.lookup name ctx.boundVars)
    in if Array.elem renamed ctx.recursiveVars then "&" <> renamed else renamed

-- Terminal alternatives may share these slots: evaluating the binding itself
-- cannot introduce statements or capture a reference to the slot being written.
isSimpleBindingValue :: TcoExpr -> Boolean
isSimpleBindingValue (TcoExpr _ expr) = case expr of
  Local _ _ -> true
  Accessor value _ -> isSimpleBindingValue value
  Lit (LitInt _) -> true
  Lit (LitNumber _) -> true
  Lit (LitString _) -> true
  Lit (LitChar _) -> true
  Lit (LitBoolean _) -> true
  Typed _ value -> isSimpleBindingValue value
  Syn.TypeApp value _ -> isSimpleBindingValue value
  _ -> false

wrapInStmts :: Array String -> Array PhpExpr -> PhpExpr -> PhpExpr
wrapInStmts _ [] expr = expr
wrapInStmts captures stmts expr = PhpCall (PhpFunction captures [] "" (stmts <> [ PhpReturn expr ])) []

flattenApp :: TcoExpr -> Tuple TcoExpr (Array TcoExpr)
flattenApp tcoExpr@(TcoExpr _ syntax) = case syntax of
  App fn args ->
    let
      Tuple innerFn innerArgs = flattenApp fn
    in
      Tuple innerFn (innerArgs <> toArray args)
  Typed _ inner -> flattenApp inner
  Syn.TypeApp inner _ -> flattenApp inner
  _ -> Tuple tcoExpr []

translateExpr :: TranslationContext -> Int -> TcoExpr -> TranslationResult
translateExpr ctx nextId tcoExpr
  | isEffectNode tcoExpr && not ctx.inEffectBlock =
      let
        res = translateExpr (ctx { isTail = false, inEffectBlock = true }) nextId tcoExpr
        useVars = Array.nub (closureCaptures ctx tcoExpr)
      in
        { stmts: [], expr: PhpFunction useVars [] "" (res.stmts <> [ PhpReturn res.expr ]), nextId: res.nextId }
  | otherwise = translateSyntax ctx nextId tcoExpr

translateSyntax :: TranslationContext -> Int -> TcoExpr -> TranslationResult
translateSyntax ctx@{ moduleName: modNameStr, recursiveVars: recVars, boundVars: bound, loops: loopCtx, isTail } nextId tcoExpr@(TcoExpr _ syntax) = case syntax of
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
                  res = translateValue ctx a.nextId expr
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
                  res = translateValue ctx a.nextId val
                in
                  { stmts: a.stmts <> res.stmts, exprs: Array.snoc a.exprs { key, value: res.expr }, nextId: res.nextId }
            )
            { stmts: [], exprs: [], nextId }
            rec
        in
          { stmts: acc.stmts, expr: PhpAssocArray acc.exprs, nextId: acc.nextId }

  Var qi -> { stmts: [], expr: PhpGlobalVar (case qi of (Qualified mbMod _) -> map (\(ModuleName m) -> String.split (Pattern ".") m) mbMod) (case qi of (Qualified _ (Ident i)) -> i), nextId }

  Local ident level ->
    let
      v = localId ident level
    in
      { stmts: [], expr: PhpVar (fromMaybe v (Map.lookup v bound)), nextId }

  App fn args ->
    let
      resFn = translateValue ctx nextId fn
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
              argRes = translateValue ctx acc.nextId arg
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
                  argRes = translateValue ctx acc.nextId arg
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
      resFn = translateValue ctx nextId fn
      
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
              argRes = translateValue ctx acc.nextId arg
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
      resFn = translateValue ctx nextId fn
      accArgs = foldl
        ( \acc arg@(TcoExpr _ _) ->
            let
              argRes = translateValue ctx acc.nextId arg
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
      useVars = closureCaptures ctx tcoExpr
      
      resBody = translateExpr ((valueContext ctx) { isTail = true }) nextId body
      types = extractFuncType tcoExpr
      argsWithTypes = zipArgsWithTypes argsArray types
      retType = getRetType (Array.length argsArray) types
    in
      { stmts: [], expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }

  UncurriedAbs args body ->
    let
      argsArray = map (\(Tuple mbI lvl) -> localId mbI lvl) args
      useVars = closureCaptures ctx tcoExpr
      
      resBody = translateExpr ((valueContext ctx) { isTail = true }) nextId body
      types = extractFuncType tcoExpr
      argsWithTypes = zipArgsWithTypes argsArray types
      retType = getRetType (Array.length argsArray) types
    in
      { stmts: [], expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }

  UncurriedEffectAbs args body ->
    let
      argsArray = map (\(Tuple mbI lvl) -> localId mbI lvl) args
      useVars = closureCaptures ctx tcoExpr
      resBody = translateExpr ((valueContext ctx) { inEffectBlock = true }) nextId body
      types = extractFuncType tcoExpr
      argsWithTypes = zipArgsWithTypes argsArray types
      retType = getRetType (Array.length argsArray) types
      -- An uncurried effect function performs its effect when saturated, like
      -- the FFI EffectFn it represents; the effect value may still be deferred.
      bodyExpr = PhpCall (PhpRaw "phpurs_execute_effect") [ resBody.expr ]
    in
      { stmts: [], expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn bodyExpr ]), nextId: resBody.nextId }

  Accessor e acc ->
    let
      res = translateValue ctx nextId e
    in
      case acc of
        GetProp prop -> { stmts: res.stmts, expr: PhpRecordAccess res.expr prop, nextId: res.nextId }
        GetIndex idx -> { stmts: res.stmts, expr: PhpArrayIndex res.expr (PhpInt idx), nextId: res.nextId }
        GetCtorField _ _ _ _ prop _ -> { stmts: res.stmts, expr: PhpPropertyAccess res.expr prop, nextId: res.nextId }

  Let ident level val body ->
    if totalUsagesOf (TcoLocal ident level) (tcoAnalysisOf body) == 0 then
      translateExpr ctx nextId body
    else
      let
        oldVarName = localId ident level
        -- Non-tail siblings (e.g. two call arguments) keep unique names because
        -- their statements run before either result expression is consumed.
        -- The dedicated suffix also separates slots from parameters/captures.
        varName = case ident of
          Nothing | isTail && Array.null loopCtx && not (Map.member oldVarName bound) && isSimpleBindingValue val ->
            oldVarName <> "_slot"
          _ -> oldVarName <> "_" <> show nextId
        resVal = translateValue ctx nextId val
        newBound = Map.insert oldVarName varName bound
        resBody = translateExpr (ctx { boundVars = newBound }) (resVal.nextId + 1) body
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
                  
                  resBodyMut = translateExpr
                    (ctx { recursiveVars = combinedRecVars, boundVars = newBound, loops = combinedLoopCtx, isTail = true, inEffectBlock = false })
                    nextId fn.body
                  
                  mappedFvs = Array.filter (\v -> not (Array.elem v fn.args)) (map (\v -> fromMaybe v (Map.lookup v newBound)) fn.fvs)
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
            
          resBodyOuter = translateExpr (ctx { recursiveVars = combinedRecVars, boundVars = newBound }) (nextId + 1) body
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
                  res = translateValue (ctx { recursiveVars = combinedRecVars, boundVars = newBound }) acc.nextId val
                in
                  { stmts: acc.stmts <> res.stmts <> [ PhpAssign newName res.expr ], nextId: res.nextId }
            )
            { stmts: initStmts, nextId: nextId + 1 }
            (toArray binds)
          resBody = translateExpr (ctx { recursiveVars = combinedRecVars, boundVars = newBound }) accBinds.nextId body
        in
          { stmts: accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

  EffectBind ident level val body ->
    let
      oldVarName = localId ident level
      varName = oldVarName <> "_" <> show nextId
      resVal = translateExpr ((valueContext ctx) { inEffectBlock = true }) nextId val
      newBound = Map.insert oldVarName varName bound
      resBody = translateExpr (ctx { boundVars = newBound, inEffectBlock = true }) (resVal.nextId + 1) body
      valExpr = executeIfOpaque val resVal.expr
      bodyExpr = executeIfOpaque body resBody.expr
    in
      { stmts: resVal.stmts <> [ PhpAssign varName valExpr ] <> resBody.stmts, expr: bodyExpr, nextId: resBody.nextId }

  EffectPure e -> translateExpr (ctx { inEffectBlock = false }) nextId e

  EffectDefer e ->
    let
      res = translateExpr ((valueContext ctx) { inEffectBlock = true }) nextId e
      useVars = closureCaptures ctx tcoExpr
    in
      { stmts: [], expr: PhpFunction useVars [] "" (res.stmts <> [ PhpReturn res.expr ]), nextId: res.nextId }

  Branch pairs def -> 
    let
      resDef = translateExpr ctx nextId def
      tmpVar = "__t" <> show resDef.nextId
      labelName = "end_branch_" <> show resDef.nextId
      accPairs = foldl
        ( \acc (Pair condExpr@(TcoExpr _ _cond) bodyExpr@(TcoExpr _ _body)) ->
            let
              resCond = translateValue ctx acc.nextId condExpr
              resBody = translateExpr ctx resCond.nextId bodyExpr
              condWrapped = wrapInStmts (map (\v -> fromMaybe v (Map.lookup v bound)) (Array.fromFoldable (freeVars condExpr))) resCond.stmts resCond.expr
              ifNode = PhpIf condWrapped (resBody.stmts <> [ PhpAssign tmpVar resBody.expr, PhpGoto labelName ]) []
            in
              { stmts: acc.stmts <> [ifNode], nextId: resBody.nextId }
        )
        { stmts: [], nextId: resDef.nextId + 1 }
        (toArray pairs)

      finalDef = resDef.stmts <> [ PhpAssign tmpVar resDef.expr, PhpLabel labelName ]
      
      extractMatch :: Array PhpExpr -> Array PhpExpr -> Maybe PhpExpr
      extractMatch ifNodes defStmts = case Array.uncons ifNodes of
        Nothing -> Nothing
        Just { head: PhpIf (PhpBinOp "===" subj val) [PhpAssign tVar body, PhpGoto _] [], tail } | tVar == tmpVar ->
            let
              checkTail :: Array PhpExpr -> Array { val :: PhpExpr, body :: PhpExpr } -> Maybe (Array { val :: PhpExpr, body :: PhpExpr })
              checkTail rest acc = case Array.uncons rest of
                Nothing -> Just acc
                Just { head: PhpIf (PhpBinOp "===" s v) [PhpAssign tVar2 b, PhpGoto _] [], tail: t } | s == subj && tVar2 == tmpVar -> checkTail t (Array.snoc acc { val: v, body: b })
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
        { stmts: [ PhpAssign tmpVar (PhpRaw "null") ] <> accPairs.stmts <> finalDef, expr: PhpVar tmpVar, nextId: accPairs.nextId }

  Update e props ->
    let
      resE = translateValue ctx nextId e
      tmpVar = "__obj" <> show resE.nextId
      accProps = foldl
        ( \acc (Prop key val@(TcoExpr _ _)) ->
            let
              resVal = translateValue ctx acc.nextId val
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
              resVal = translateValue ctx acc.nextId val
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
        resE = translateValue ctx nextId e
      in
        { stmts: resE.stmts, expr: translateOperator1 op1 resE.expr, nextId: resE.nextId }
    Op2 op2 e1@(TcoExpr _ _) e2@(TcoExpr _ _) ->
      let
        res1 = translateValue ctx nextId e1
        res2 = translateValue ctx res1.nextId e2
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

  PrimEffect effect -> case effect of
    EffectRefNew val ->
      let res = translateValue ctx nextId val
      in { stmts: res.stmts, expr: PhpCall (PhpRaw "phpurs_ref_new") [ res.expr ], nextId: res.nextId }
    EffectRefRead ref ->
      let res = translateValue ctx nextId ref
      in { stmts: res.stmts, expr: PhpCall (PhpRaw "phpurs_ref_read") [ res.expr ], nextId: res.nextId }
    EffectRefWrite ref val ->
      let
        resRef = translateValue ctx nextId ref
        resVal = translateValue ctx resRef.nextId val
      in
        { stmts: resRef.stmts <> resVal.stmts
        , expr: PhpCall (PhpRaw "phpurs_ref_write") [ resRef.expr, resVal.expr ]
        , nextId: resVal.nextId
        }
  PrimUndefined -> { stmts: [], expr: PhpRaw "null", nextId }
  Syn.TypeApp a _ -> translateExpr ctx nextId a
  Fail msg -> { stmts: [ PhpThrow (PhpRaw ("\"" <> msg <> " at \" . __FILE__ . \":\" . __LINE__")) ], expr: PhpRaw "null", nextId }
  Typed _ a -> translateExpr ctx nextId a

-- | Initialize recursive dictionaries and functions before expressions which
-- | call into them (for example, Apply instances implemented with Monad.ap).
isSafeRecursiveInit :: ModuleName -> Array Ident -> TcoExpr -> Boolean
isSafeRecursiveInit currentModule group = go
  where
  go (TcoExpr _ expr) = case expr of
    Abs _ _ -> true
    UncurriedAbs _ _ -> true
    UncurriedEffectAbs _ _ -> true
    CtorDef _ _ _ _ -> true
    EffectBind _ _ _ _ -> true
    EffectPure _ -> true
    Var (Qualified (Just mn) ident) | mn == currentModule -> not (Array.elem ident group)
    Var _ -> true
    Lit lit -> all go lit
    Accessor a _ -> go a
    Update a props -> go a && all (\(Prop _ value) -> go value) props
    CtorSaturated _ _ _ _ values -> all (\(Tuple _ value) -> go value) values
    PrimOp op -> all go op
    Typed _ a -> go a
    Syn.TypeApp a _ -> go a
    _ -> false

-- | Main translation function.
-- | Takes the list of module imports and a `BackendModule` (containing `TcoExpr` bindings)
-- | and returns a fully constructed `PhpFile` ready for printing.
translate :: Array (Array String) -> BackendModule -> PhpFile
translate imports input =
  let
    partialBindings = PartialBindings.optimize input
    fusion = ThunkFusion.optimize partialBindings.module_
    regions = EnumRegions.optimize fusion.module_
    mod = regions.module_
    _startLog = if unwrap mod.name == "Phpurs.PhpAst" then unsafePerformEffect (Console.log "translate START") else unit
    modNameStr = String.replaceAll (Pattern ".") (Replacement "_") (unwrap mod.name)
    modPrefix = modNameStr <> "_"
    
    rawDecls = Array.concatMap (\decl ->
        Array.concatMap (\ctor ->
          let
            safeCtorName = String.replaceAll (Pattern "'") (Replacement "_prime_") ctor.name
            structName = modPrefix <> safeCtorName
            safeTagStr = String.replaceAll (Pattern "'") (Replacement "\\'") ctor.name
            isPrivate = Set.member ctor.name regions.privateConstructors
            -- Internal field types are proven before rewriting. PHP promoted
            -- property checks add a cost to each allocation without adding a
            -- check at a public boundary; keep those checks on public classes.
            argsStr = Array.mapWithIndex (\i typ -> "public " <> (if isPrivate then "" else exprTypeToPhpType typ) <> " $value" <> show i) ctor.fields
            -- Generated pattern matches use `instanceof`, never a tag read, and
            -- private constructors never cross an FFI or public boundary. The
            -- public `$tag` is part of the visible representation only.
            tagDecl = if isPrivate then "" else "public $tag = '" <> safeTagStr <> "'; "
            structDecl = "final class " <> structName <> " { " <> tagDecl <> "public function __construct(" <> String.joinWith ", " argsStr <> ") {} }"
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
            tcoBinds = map (\(Tuple k v) -> 
              let
                res = if modNameStr == "Phpurs_PhpAst" then trace ("Tco.analyze START for " <> unwrap k) \_ -> Tco.analyze env' v else Tco.analyze env' v
              in Tuple k (if modNameStr == "Phpurs_PhpAst" then trace ("Tco.analyze END for " <> unwrap k) \_ -> res else res)
            ) group.bindings
            orderedBinds =
              if group.recursive then
                let
                  groupIdents = map (\(Tuple ident _) -> ident) group.bindings
                  ready = Array.partition (\(Tuple _ expr) -> isSafeRecursiveInit mod.name groupIdents expr) tcoBinds
                in ready.yes <> ready.no
              else tcoBinds
          in
            Tuple env' (Array.snoc acc { recursive: group.recursive, bindings: orderedBinds })
      )
      (Tuple [] [])
      mod.bindings

    _declsLog = if modNameStr == "Phpurs_PhpAst" then unsafePerformEffect (Console.log "Tco.analyze finished all bindings") else unit
    decls = Array.concatMap
      ( \group ->
          let
            recVars = if group.recursive then map (\(Tuple (Ident name) _) -> modPrefix <> name) group.bindings else []
          in
            if group.recursive && Array.length group.bindings == 1 then
              let
                mutRecBinds = traverse (\(Tuple (Ident name) val) -> map (\abs -> { ident: modPrefix <> name, args: abs.args, body: abs.body, fvs: abs.fvs, originalVal: val }) (extractUncurriedAbs Map.empty val)) group.bindings
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
                            
                            resBodyMut = translateExprImpl_ modNameStr recVars Map.empty Map.empty Nothing loopCtxs true false 0 fn.body
                            
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
                             { identifier: fn.ident, expression: CompactLoops.optimize ctx.labelName types (PhpNativeFunction fn.ident argsWithTypes retType (initVarStmts <> innerFuncBody)) }
                      )
                      fns
                  in
                    fnWrapperStmts
                Nothing ->
                  Array.concatMap
                    ( \(Tuple (Ident name) expr) ->
                        case extractUncurriedAbs Map.empty expr of
                          Just fn ->
                             let res = translateExprImpl_ modNameStr recVars Map.empty Map.empty (Just (modPrefix <> name)) [] true false 0 fn.body
                                 types = extractFuncType expr
                                 argsWithTypes = zipArgsWithTypes fn.args types
                                 retType = getRetType (Array.length fn.args) types
                             in [ { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType (res.stmts <> [ PhpReturn res.expr ]) } ]
                          Nothing ->
                           let
                             res = translateExprImpl_ modNameStr recVars Map.empty Map.empty (Just (modPrefix <> name)) [] false false 0 expr
                             arity = max 0 (extractTypeArity expr - appliedArgs expr)
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
                      arity = max 0 (extractTypeArity expr - appliedArgs expr)
                    in
                      case extractUncurriedAbs Map.empty expr of
                        Just fn ->
                           let res = translateExprImpl_ modNameStr [] Map.empty Map.empty (Just (modPrefix <> name)) [] true false 0 fn.body
                               types = extractFuncType expr
                               argsWithTypes = zipArgsWithTypes fn.args types
                               retType = getRetType (Array.length fn.args) types
                           in [ { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType (res.stmts <> [ PhpReturn res.expr ]) } ]
                        Nothing ->
                           let
                             res = translateExprImpl_ modNameStr [] Map.empty Map.empty (Just (modPrefix <> name)) [] false false 0 expr
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
          Just (Tuple (modPrefix <> safeIdent ident) (max 0 (extractTypeArity tcoExpr - appliedArgs tcoExpr)))
        ) group.bindings
      ) tcoBindings)

    privateNames = Set.map (\ident -> modPrefix <> unwrap ident) (Set.unions [ regions.privateNames, fusion.privateNames, partialBindings.privateNames ])
    isArrayType = case _ of
      ADT name _ _ -> Set.member name regions.arrayLayouts
      _ -> false
    -- Private workers whose parameters hold packed arrays; ArrayRefs promotes
    -- them to references where every call site can hand over ownership.
    arrayParams = Map.fromFoldable (Array.mapMaybe (\(Tuple (Ident name) expr) ->
        if Set.member (modPrefix <> name) privateNames then
          case extractFuncType expr of
            Just { fArgs } ->
              let idxs = Array.mapMaybe identity (Array.mapWithIndex (\i ty -> if isArrayType ty then Just i else Nothing) fArgs)
              in if Array.null idxs then Nothing else Just (Tuple (modPrefix <> name) (Set.fromFoldable idxs))
            Nothing -> Nothing
        else Nothing) (Array.concatMap _.bindings tcoBindings))
    regionWorkers = Set.map (\ident -> modPrefix <> unwrap ident) regions.privateNames
    privateClasses = Set.map (\name ->
      "\\" <> String.replaceAll (Pattern ".") (Replacement "\\") (unwrap mod.name) <> "\\" <> modPrefix
        <> String.replaceAll (Pattern "'") (Replacement "_prime_") name) regions.privateConstructors
    nullableClasses = Map.fromFoldable (map (\(Tuple name empty) ->
      Tuple ("\\" <> String.replaceAll (Pattern ".") (Replacement "\\") (unwrap mod.name) <> "\\" <> modPrefix
        <> String.replaceAll (Pattern "'") (Replacement "_prime_") name) empty)
      (Map.toUnfoldable regions.nullableConstructors :: Array (Tuple String Boolean)))
    -- Every private call is proven saturated and its arguments are checked on
    -- the typed AST. Avoid adding dynamic scalar coercions that would prevent
    -- the existing terminal inliner from simplifying these internal workers.
    internalSignatures d = if Set.member d.identifier privateNames then case d.expression of
      PhpNativeFunction name args _ body -> d { expression = PhpNativeFunction name (map (\a -> a { type_ = "" }) args) "" body }
      _ -> d
      else d
    optimized = TailInline.optimize { namespace: String.split (Pattern ".") (unwrap mod.name), rawDecls, decls: map internalSignatures decls, imports, arities: moduleArities }
    hideWorker d = if Set.member d.identifier privateNames then case d.expression of
      PhpNativeFunction name args ret body -> d { expression = PhpPrivateFunction name args ret body }
      _ -> d
      else d
  in
    ArrayRefs.optimize arrayParams privateClasses
      (CopyCleanup.optimize { workers: regionWorkers, constructors: privateClasses }
        (Nullable.lower nullableClasses (optimized { decls = map hideWorker optimized.decls })))

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
totalUsagesOf :: TcoRef -> TcoAnalysis -> Int
totalUsagesOf ref (TcoAnalysis { usages }) = case Map.lookup ref usages of
  Just (TcoUsage { total }) -> total
  _ -> 0



extractUncurriedAbs :: Map String String -> TcoExpr -> Maybe { args :: Array String, body :: TcoExpr, fvs :: Array String }
extractUncurriedAbs bound tcoExpr@(TcoExpr _ syntax) = case syntax of
  UncurriedAbs args body ->
    Just { args: map (\(Tuple mbI lvl) -> localId mbI lvl) args, body, fvs: Array.fromFoldable (freeVars tcoExpr) }
  Abs args body ->
    let
      thisArgs = map (\(Tuple mbI lvl) -> localId mbI lvl) (toArray args)
    in case extractUncurriedAbs bound body of
      Just inner -> Just { args: thisArgs <> inner.args, body: inner.body, fvs: Array.nub (Array.fromFoldable (freeVars tcoExpr) <> inner.fvs) }
      Nothing -> Just { args: thisArgs, body, fvs: Array.fromFoldable (freeVars tcoExpr) }
  Typed _ inner -> extractUncurriedAbs bound inner
  _ -> Nothing

isEffectNode :: TcoExpr -> Boolean
isEffectNode (TcoExpr _ syntax) = case syntax of
  EffectBind _ _ _ _ -> true
  EffectPure _ -> true
  EffectDefer _ -> false
  PrimEffect _ -> true
  UncurriedEffectApp _ _ -> true
  Let _ _ _ body -> isEffectNode body
  LetRec _ _ body -> isEffectNode body
  _ -> false

executeIfOpaque :: TcoExpr -> PhpExpr -> PhpExpr
executeIfOpaque expr phpExpr =
  if isEffectNode expr then phpExpr
  else PhpCall (PhpRaw "phpurs_execute_effect") [ phpExpr ]

extractTypeArity :: TcoExpr -> Int
extractTypeArity (TcoExpr _ syntax) = case syntax of
  Typed (Func args _) _ -> Array.length args
  Typed _ inner -> extractTypeArity inner
  _ -> 0

-- | A partial application can retain the callee's pre-application type
-- | annotation, including a dictionary argument that the expression already
-- | applied. Subtract the arguments present in the expression so a generated
-- | wrapper matches the arity of the value it forwards to.
appliedArgs :: TcoExpr -> Int
appliedArgs (TcoExpr _ syntax) = case syntax of
  Typed _ inner -> appliedArgs inner
  App f args -> Array.length (toArray args) + appliedArgs f
  UncurriedApp _ args -> Array.length args
  Syn.TypeApp inner _ -> appliedArgs inner
  _ -> 0
