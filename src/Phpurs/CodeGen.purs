-- | Lower optimized, typed modules to PHP: prepare private workers, analyze
-- | tail calls, translate expressions and declarations, then run PHP AST passes.
module Phpurs.CodeGen
  ( module Phpurs.CodeGen.Context
  , translate
  , translateWithCallbacks
  , translateWithContracts
  , translateExpr
  ) where

import Prelude

import Control.Monad.ST as ST
import Control.Monad.ST.Ref as STRef
import Data.Array as Array
import Data.Array.NonEmpty (toArray)
import Data.Array.ST as STArray
import Data.Foldable (foldMap, foldl)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Newtype (unwrap)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.Tuple (Tuple(..))
import Phpurs.ArrayRefs as ArrayRefs
import Phpurs.ArrayCallbacks as ArrayCallbacks
import Phpurs.CallbackSpecialization as CallbackSpecialization
import Phpurs.CodeGen.Context (LoopContext, TranslatedValues, TranslationContext, TranslationResult, initialContext, valueContext)
import Phpurs.CodeGen.Bindings (AnalyzedBindingGroup, analyzeBindings, extractUncurriedAbs)
import Phpurs.CodeGen.Effects (executeIfOpaque, isEffectNode)
import Phpurs.CodeGen.Operators (isLogicalShortCircuit, translateOperator1, translateOperator2)
import Phpurs.CodeGen.Tco (containsNestedLoop, findTailTarget, translateTailJump, wrapLoopBody)
import Phpurs.CodeGen.Types (exprTypeToPhpType, extractFuncType, getRetType, remainingArity, zipArgsWithTypes)
import Phpurs.CompactLoops as CompactLoops
import Phpurs.CopyCleanup as CopyCleanup
import Phpurs.EnumRegions as EnumRegions
import Phpurs.FreeVars (freeVars)
import Phpurs.ForeignTraversals (Traversals)
import Phpurs.NativeCallbacks (Callbacks)
import Phpurs.NullableConstructors as Nullable
import Phpurs.PartialBindings as PartialBindings
import Phpurs.PhpAst (PhpDecl, PhpExpr(..), PhpFile)
import Phpurs.RuntimeNames as RuntimeNames
import Phpurs.StateFusion as StateFusion
import Phpurs.TailInline as TailInline
import Phpurs.ThunkFusion as ThunkFusion
import PureScript.Backend.Optimizer.Codegen.Tco (TcoAnalysis(..), TcoExpr(..), TcoRef(..), TcoUsage(..), tcoAnalysisOf)
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident(..), Literal(..), ModuleName(..), Prop(..), Qualified(..))
import PureScript.Backend.Optimizer.FreeVars (localId)
import PureScript.Backend.Optimizer.Syntax (BackendAccessor(..), BackendEffect(..), BackendOperator(..), BackendSyntax(..), Pair(..))
import PureScript.Backend.Optimizer.Syntax as Syn

translateValue :: TranslationContext -> Int -> TcoExpr -> TranslationResult
translateValue ctx = translateExpr (valueContext ctx)

-- | Lower sibling operands in order, sharing a single temporary counter.
translateValues :: TranslationContext -> Int -> Array TcoExpr -> TranslatedValues
translateValues ctx nextId values = ST.run do
  stmts <- STArray.new
  exprs <- STArray.new
  counter <- STRef.new nextId
  ST.foreach values \expr -> do
    current <- STRef.read counter
    let result = translateValue ctx current expr
    -- Append only the new statements, without recopying the accumulated prefix
    -- or spreading a potentially large statement array into call arguments.
    ST.foreach result.stmts \stmt -> void $ STArray.push stmt stmts
    void $ STArray.push result.expr exprs
    void $ STRef.write result.nextId counter
  -- Both arrays are fresh and remain private until construction has finished.
  finalStmts <- STArray.unsafeFreeze stmts
  finalExprs <- STArray.unsafeFreeze exprs
  finalId <- STRef.read counter
  pure { stmts: finalStmts, exprs: finalExprs, nextId: finalId }

renameLocal :: TranslationContext -> String -> String
renameLocal ctx name = fromMaybe name (Map.lookup name ctx.boundVars)

-- | Recursive closures capture the renamed binding by reference so they see
-- | its eventual initialization. Ordinary captures keep PHP's value semantics.
captureVariable :: TranslationContext -> String -> String
captureVariable ctx name = if Array.elem name ctx.recursiveVars then "&" <> name else name

closureCaptures :: TranslationContext -> TcoExpr -> Array String
closureCaptures ctx expr = map (captureVariable ctx <<< renameLocal ctx) (Array.fromFoldable (freeVars expr))

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

recordFields :: ExprType -> Maybe (Array (Tuple String ExprType))
recordFields = case _ of
  Record (Row fields Nothing) -> Just fields
  _ -> Nothing

stripRoot :: TcoExpr -> TcoExpr
stripRoot tcoExpr@(TcoExpr _ syntax) = case syntax of
  Typed _ inner -> stripRoot inner
  Syn.TypeApp inner _ -> stripRoot inner
  _ -> tcoExpr

sameRoot :: TcoExpr -> TcoExpr -> Boolean
sameRoot left right =
  let
    TcoExpr _ a = stripRoot left
    TcoExpr _ b = stripRoot right
  in case a, b of
    Local ident level, Local ident2 level2 -> localId ident level == localId ident2 level2
    Var (Qualified moduleName ident), Var (Qualified moduleName2 ident2) -> moduleName == moduleName2 && ident == ident2
    _, _ -> false

type UpdatePath = { root :: TcoExpr, path :: Array String }

updatePath :: TcoExpr -> Maybe UpdatePath
updatePath tcoExpr@(TcoExpr _ syntax) = case syntax of
  Typed _ inner -> updatePath inner
  Syn.TypeApp inner _ -> updatePath inner
  -- A global read may be a demand-time FFI getter and must still be evaluated
  -- on the original path. Only local roots are proven side-effect free.
  Local _ _ -> Just { root: tcoExpr, path: [] }
  Var _ -> Nothing
  Accessor inner (GetProp key) ->
    (\found -> found { path = found.path <> [ key ] }) <$> updatePath inner
  _ -> Nothing

nestedUpdate :: ExprType -> TcoExpr -> Maybe { base :: TcoExpr, props :: Array (Prop TcoExpr) }
nestedUpdate fieldType (TcoExpr _ syntax) = case syntax of
  Typed _ inner -> nestedUpdate fieldType inner
  Syn.TypeApp inner _ -> nestedUpdate fieldType inner
  Update base props -> case recordFields fieldType of
    Just _ -> Just { base, props }
    Nothing -> Nothing
  _ -> Nothing

-- | A closed record whose every field is updated can be built directly. The
-- | old object is never mutated; nested updates must target the expected field
-- | of the same root. Open rows, partial updates and ambiguous paths keep the
-- | existing clone path.
tryFusedUpdate
  :: TranslationContext
  -> Int
  -> UpdatePath
  -> Array (Tuple String ExprType)
  -> Array (Prop TcoExpr)
  -> Maybe TranslationResult
tryFusedUpdate ctx nextId path fields props =
  let
    labels = map (\(Tuple label _) -> label) fields
    keys = map (\(Prop key _) -> key) props
    validFields =
      not (Array.null labels)
        && not (Array.null keys)
        && Array.length keys == Array.length labels
        && Array.sort keys == Array.sort labels
    fieldTypes = Map.fromFoldable fields
    expectedPath key = path.path <> [ key ]
    step Nothing _ = Nothing
    step (Just acc) (Prop key value) = case Map.lookup key fieldTypes of
      Nothing -> Nothing
      Just fieldType -> case nestedUpdate fieldType value of
        Just nested -> case updatePath nested.base of
          Just nestedPath
            | sameRoot path.root nestedPath.root && nestedPath.path == expectedPath key ->
                case recordFields fieldType of
                  Just nestedFields -> case tryFusedUpdate ctx acc.nextId nestedPath nestedFields nested.props of
                    Just result ->
                      Just { stmts: acc.stmts <> result.stmts
                           , values: Map.insert key result.expr acc.values
                           , nextId: result.nextId
                           }
                    Nothing -> plainValue acc key value
                  Nothing -> plainValue acc key value
          _ -> plainValue acc key value
        Nothing -> plainValue acc key value
    plainValue acc key value =
      let result = translateValue ctx acc.nextId value
      in Just { stmts: acc.stmts <> result.stmts
              , values: Map.insert key result.expr acc.values
              , nextId: result.nextId
              }
    initial = { stmts: [], values: Map.empty, nextId }
  in
    if not validFields then Nothing
    else case Array.foldl step (Just initial) props of
      Nothing -> Nothing
      Just built ->
        Just
          { stmts: built.stmts
          , expr: PhpAssocArray (map (\(Tuple label _) ->
              { key: label
              , value: fromMaybe (PhpNull) (Map.lookup label built.values)
              }) fields)
          , nextId: built.nextId
          }

translateTyped :: TranslationContext -> Int -> ExprType -> TcoExpr -> TranslationResult
translateTyped ctx nextId ty tcoExpr@(TcoExpr _ syntax) = case syntax of
  Typed innerTy inner ->
    let selected = case recordFields innerTy of
          Just _ -> innerTy
          Nothing -> ty
    in translateTyped ctx nextId selected inner
  Update base props -> case recordFields ty of
    Just fields -> case updatePath base of
      Just path -> case tryFusedUpdate ctx nextId path fields props of
        Just result -> result
        Nothing -> translateExpr ctx nextId tcoExpr
      Nothing -> translateExpr ctx nextId tcoExpr
    Nothing -> translateExpr ctx nextId tcoExpr
  _ -> translateExpr ctx nextId tcoExpr

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
        let result = translateValues ctx nextId arr
        in { stmts: result.stmts, expr: PhpArray result.exprs, nextId: result.nextId }
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

  Var (Qualified moduleName (Ident name)) ->
    { stmts: []
    , expr: PhpGlobalVar (map (\(ModuleName m) -> String.split (Pattern ".") m) moduleName) name
    , nextId
    }

  Local ident level ->
    { stmts: [], expr: PhpVar (renameLocal ctx (localId ident level)), nextId }

  App fn args ->
    let
      Tuple flatFn flatArgs = flattenApp tcoExpr
      tailTarget = case flatFn of
        TcoExpr _ (Local ident level) ->
          findTailTarget ctx (renameLocal ctx (localId ident level))
        TcoExpr _ (Var (Qualified mbMod (Ident name))) ->
          let fullName = fromMaybe "" (map (\(ModuleName m) -> String.joinWith "_" (String.split (Pattern ".") m) <> "_") mbMod) <> name
          in findTailTarget ctx fullName
        _ -> Nothing
    in case tailTarget of
      Just loop -> translateTailJump loop (translateValues ctx nextId flatArgs)
      Nothing ->
        let
          resFn = translateValue ctx nextId fn
          resArgs = translateValues ctx resFn.nextId (toArray args)
          curriedCall = foldl (\acc expr -> PhpCall acc [ expr ]) resFn.expr resArgs.exprs
        in
          { stmts: resFn.stmts <> resArgs.stmts, expr: curriedCall, nextId: resArgs.nextId }

  UncurriedApp fn args ->
    let
      resFn = translateValue ctx nextId fn
      resArgs = translateValues ctx resFn.nextId args
      tailTarget = case resFn.expr of
        PhpGlobalVar mbMod name ->
          let fullName = fromMaybe "" (map (\m -> String.joinWith "_" m <> "_") mbMod) <> name
          in findTailTarget ctx fullName
        PhpVar name -> findTailTarget ctx name
        _ -> Nothing
    in case tailTarget of
      Just loop -> translateTailJump loop resArgs
      Nothing ->
        { stmts: resFn.stmts <> resArgs.stmts, expr: PhpCall resFn.expr resArgs.exprs, nextId: resArgs.nextId }

  UncurriedEffectApp fn args ->
    let
      resFn = translateValue ctx nextId fn
      resArgs = translateValues ctx resFn.nextId args
    in
      { stmts: resFn.stmts <> resArgs.stmts, expr: PhpCall resFn.expr resArgs.exprs, nextId: resArgs.nextId }

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
      -- Saturating an EffectFn executes its body once. Explicit effect nodes
      -- have already been lowered to statements and a result; that result may
      -- itself be an action or canceler and must not be executed here.
      bodyExpr = executeIfOpaque body resBody.expr
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
      bindings = toArray binds
      oldNewPairs = map
        ( \(Tuple ident _) ->
            let
              oldName = localId (Just ident) lvl
            in
              { oldName, newName: oldName <> "_" <> show nextId }
        )
        bindings
      newBound = foldl (\acc pair -> Map.insert pair.oldName pair.newName acc) bound oldNewPairs
      recCtx = ctx { recursiveVars = recVars <> map _.newName oldNewPairs, boundVars = newBound }
      initStmts = map (\pair -> PhpAssign pair.newName (PhpNull)) oldNewPairs
      -- Loop lowering handles a single recursive function. Match that shape
      -- directly rather than constructing and searching a singleton loop table.
      loopBinding = case bindings of
        [ Tuple ident value ] | (unwrap (tcoAnalysisOf tcoExpr)).role.isLoop ->
          map (\fn -> { name: renameLocal recCtx (localId (Just ident) lvl), value, fn }) (extractUncurriedAbs value)
        _ -> Nothing
    in case loopBinding of
      Just { name, value, fn } ->
        let
          loop =
            { ident: name
            , params: fn.args
            , varPrefix: "__tco_var_" <> name <> "_" <> show nextId <> "_"
            , labelName: "tco_loop_" <> name <> "_" <> show nextId
            , direct: ctx.allowDirect && Array.null loopCtx && not (containsNestedLoop fn.body)
            }
          resFnBody = translateExpr (recCtx { loops = [ loop ], isTail = true, inEffectBlock = false }) nextId fn.body
          mappedFvs = Array.filter (\v -> not (Array.elem v fn.args)) (map (renameLocal recCtx) fn.fvs)
          captures = Array.nub (map (captureVariable recCtx) mappedFvs)
          outerLoopCaptures = foldMap
            (\outer -> map (\param -> "&" <> (if outer.direct then param else outer.varPrefix <> param)) outer.params)
            loopCtx
          types = extractFuncType value
          params = zipArgsWithTypes fn.args types
          returnType = getRetType (Array.length fn.args) types
          wrapper = PhpAssign name (PhpFunction (outerLoopCaptures <> captures) params returnType (wrapLoopBody loop resFnBody))
          resBody = translateExpr recCtx (nextId + 1) body
        in
          { stmts: initStmts <> [ wrapper ] <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

      Nothing ->
        let
          accBinds = foldl
            ( \acc (Tuple ident val) ->
                let
                  newName = renameLocal recCtx (localId (Just ident) lvl)
                  res = translateValue recCtx acc.nextId val
                in
                  { stmts: acc.stmts <> res.stmts <> [ PhpAssign newName res.expr ], nextId: res.nextId }
            )
            { stmts: initStmts, nextId: nextId + 1 }
            bindings
          resBody = translateExpr recCtx accBinds.nextId body
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
              condWrapped = wrapInStmts (map (renameLocal ctx) (Array.fromFoldable (freeVars condExpr))) resCond.stmts resCond.expr
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
        { stmts: [ PhpAssign tmpVar (PhpNull) ] <> accPairs.stmts <> finalDef, expr: PhpVar tmpVar, nextId: accPairs.nextId }

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
      accArgs = translateValues ctx nextId (map (\(Tuple _ val) -> val) args)
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
      singletonKey = "__phpurs_data0_" <> ctx.moduleName <> "_" <> safeCtorNameStr
      singletonBody = PhpBinOp "??=" (PhpRaw ("$GLOBALS['" <> singletonKey <> "']")) body
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
      in { stmts: res.stmts, expr: PhpCall (PhpRaw (RuntimeNames.qualified RuntimeNames.refNew)) [ res.expr ], nextId: res.nextId }
    EffectRefRead ref ->
      let res = translateValue ctx nextId ref
      in { stmts: res.stmts, expr: PhpCall (PhpRaw (RuntimeNames.qualified RuntimeNames.refRead)) [ res.expr ], nextId: res.nextId }
    EffectRefWrite ref val ->
      let
        resRef = translateValue ctx nextId ref
        resVal = translateValue ctx resRef.nextId val
      in
        { stmts: resRef.stmts <> resVal.stmts
        , expr: PhpCall (PhpRaw (RuntimeNames.qualified RuntimeNames.refWrite)) [ resRef.expr, resVal.expr ]
        , nextId: resVal.nextId
        }
  PrimUndefined -> { stmts: [], expr: PhpNull, nextId }
  Syn.TypeApp a _ -> translateExpr ctx nextId a
  Fail msg -> { stmts: [ PhpThrow (PhpRaw ("\"" <> msg <> " at \" . __FILE__ . \":\" . __LINE__")) ], expr: PhpNull, nextId }
  Typed ty a -> translateTyped ctx nextId ty a

-- | Main translation function.
-- | Takes module imports and the optimizer's `BackendModule`, performs local TCO
-- | analysis and returns a fully constructed `PhpFile` ready for printing.
translate :: Array (Array String) -> BackendModule -> PhpFile
translate = translateWithCallbacks Set.empty

translateWithCallbacks :: Callbacks -> Array (Array String) -> BackendModule -> PhpFile
translateWithCallbacks callbacks = translateWithContracts callbacks Set.empty

translateWithContracts :: Callbacks -> Traversals -> Array (Array String) -> BackendModule -> PhpFile
translateWithContracts callbacks traversals imports input =
  let
    partialBindings = PartialBindings.optimize input
    fusion = ThunkFusion.optimize partialBindings.module_
    stateFusion = StateFusion.optimize fusion.module_
    specialized = CallbackSpecialization.optimize callbacks stateFusion.module_
    arrays = ArrayCallbacks.optimize traversals callbacks specialized.module_
    regions = EnumRegions.optimize arrays.module_
    mod = regions.module_
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

    tcoBindings = analyzeBindings mod
    decls = Array.concatMap (translateBindingGroup modNameStr (Set.isEmpty regions.privateConstructors)) tcoBindings
    moduleArities = Map.fromFoldable (Array.concatMap
      (\group -> map (\(Tuple (Ident name) expr) -> Tuple (modPrefix <> name) (remainingArity expr)) group.bindings)
      tcoBindings)

    privateNames = Set.map (\ident -> modPrefix <> unwrap ident) (Set.unions [ regions.privateNames, fusion.privateNames, stateFusion.privateNames, specialized.privateNames, partialBindings.privateNames ])
    checkedSignatures = Set.map (\ident -> modPrefix <> unwrap ident) (Set.union stateFusion.checkedDepths specialized.privateNames)
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
    -- State guards preserve the original builder's Int depth check. Their
    -- result is deliberately untyped, just like the original record field.
    -- Callback-specialized copies also retain the original fold signature.
    internalSignatures d = if Set.member d.identifier privateNames && not (Set.member d.identifier checkedSignatures) then case d.expression of
      PhpNativeFunction name args _ body -> d { expression = PhpNativeFunction name (map (\a -> a { type_ = "" }) args) "" body }
      _ -> d
      else d
    hideWorker d = if Set.member d.identifier privateNames then case d.expression of
      PhpNativeFunction name args ret body -> d { expression = PhpPrivateFunction name args ret body }
      _ -> d
      else d
    -- Keep the pass order explicit: later passes consume the forms and private
    -- layout proofs established by the earlier ones.
    phpFile = { namespace: String.split (Pattern ".") (unwrap mod.name), rawDecls, decls: map internalSignatures decls, imports, arities: Map.union arrays.arities moduleArities }
    inlined = TailInline.optimize phpFile
    privateWorkers = inlined { decls = map hideWorker inlined.decls }
    nullable = Nullable.lower nullableClasses privateWorkers
    cleaned = CopyCleanup.optimize { workers: regionWorkers, constructors: privateClasses } nullable
    owned = ArrayRefs.optimize arrayParams privateClasses cleaned
  in
    owned { decls = owned.decls <> arrays.workers }

translateBindingGroup :: String -> Boolean -> AnalyzedBindingGroup -> Array PhpDecl
translateBindingGroup moduleName allowDirect group = case group.bindings of
  [ Tuple (Ident name) expr ] | group.recursive ->
    let
      identifier = moduleName <> "_" <> name
      ctx = (initialContext moduleName) { allowDirect = allowDirect, recursiveVars = [ identifier ] }
    in
      case extractUncurriedAbs expr of
        Just fn ->
          let
            loop =
              { ident: identifier
              , params: fn.args
              , varPrefix: "__tco_var_" <> identifier <> "_"
              , labelName: "tco_loop_" <> identifier
              , direct: allowDirect && not (containsNestedLoop fn.body)
              }
            result = translateExpr (ctx { loops = [ loop ], isTail = true }) 0 fn.body
            types = extractFuncType expr
            params = zipArgsWithTypes fn.args types
            returnType = getRetType (Array.length fn.args) types
          in
            [ { identifier, expression: CompactLoops.optimize loop.labelName types (PhpNativeFunction identifier params returnType (wrapLoopBody loop result)) } ]
        Nothing -> translateBinding ctx identifier expr
  _ -> Array.concatMap
    (\(Tuple (Ident name) expr) -> translateBinding ((initialContext moduleName) { allowDirect = allowDirect }) (moduleName <> "_" <> name) expr)
    group.bindings

-- | A lambda becomes a native function. A function-valued expression is first
-- | initialized once, then exposed through a forwarding wrapper of its remaining
-- | arity. Plain values need only the global initialization.
translateBinding :: TranslationContext -> String -> TcoExpr -> Array PhpDecl
translateBinding ctx identifier expr = case extractUncurriedAbs expr of
  Just fn ->
    let
      result = translateExpr (ctx { isTail = true }) 0 fn.body
      types = extractFuncType expr
      params = zipArgsWithTypes fn.args types
      returnType = getRetType (Array.length fn.args) types
    in
      [ { identifier, expression: PhpNativeFunction identifier params returnType (result.stmts <> [ PhpReturn result.expr ]) } ]
  Nothing ->
    let
      result = translateExpr ctx 0 expr
      value = wrapInStmts [] result.stmts result.expr
      arity = remainingArity expr
    in
      if arity > 0 then
        let
          closureName = identifier <> "_closure"
          args = Array.mapWithIndex (\index _ -> "v_" <> show index) (Array.replicate arity unit)
          call = PhpCall (PhpGlobalVar Nothing closureName) (map PhpVar args)
          types = extractFuncType expr
          params = zipArgsWithTypes args types
          returnType = getRetType arity types
        in
          [ { identifier: closureName, expression: PhpGlobalAssign closureName value }
          , { identifier, expression: PhpNativeFunction identifier params returnType [ PhpReturn call ] }
          ]
      else
        [ { identifier, expression: PhpGlobalAssign identifier value } ]

totalUsagesOf :: TcoRef -> TcoAnalysis -> Int
totalUsagesOf ref (TcoAnalysis { usages }) = case Map.lookup ref usages of
  Just (TcoUsage { total }) -> total
  _ -> 0
