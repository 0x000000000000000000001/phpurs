-- | Binding, effect and branch rules for expression translation.
module Phpurs.CodeGen.Expr.Statements
  ( translateBranch
  , translateEffectBind
  , translateEffectDefer
  , translateEffectPure
  , translateLet
  , translateLetRec
  ) where

import Prelude

import Data.Array as Array
import Data.Array.NonEmpty (toArray)
import Data.Array.NonEmpty as NEA
import Data.Foldable (foldMap, foldl)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.Tuple (Tuple(..))
import Phpurs.CodeGen.Bindings (extractUncurriedAbs)
import Phpurs.CodeGen.Context (TranslationContext, TranslationResult, valueContext)
import Phpurs.CodeGen.Effects (executeIfOpaque)
import Phpurs.CodeGen.Expr.Support
  ( Translate
  , captureVariable
  , closureCaptures
  , isSimpleBindingValue
  , renameLocal
  , totalUsagesOf
  , translateValue
  , wrapInStmts
  )
import Phpurs.CodeGen.Tco (containsNestedLoop, wrapLoopBody)
import Phpurs.FreeVars (freeVars)
import Phpurs.CodeGen.Types (extractFuncType, getRetType, zipArgsWithTypes)
import Phpurs.PhpAst (PhpExpr(..))
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..), TcoRef(..), tcoAnalysisOf)
import PureScript.Backend.Optimizer.CoreFn (Ident)
import PureScript.Backend.Optimizer.FreeVars (localId)
import PureScript.Backend.Optimizer.Syntax (Level, Pair(..))

translateLet
  :: Translate
  -> TranslationContext
  -> Int
  -> Maybe Ident
  -> Level
  -> TcoExpr
  -> TcoExpr
  -> TranslationResult
translateLet translate ctx nextId ident level val body =
  if totalUsagesOf (TcoLocal ident level) (tcoAnalysisOf body) == 0 then
    translate ctx nextId body
  else
    let
      oldVarName = localId ident level
      -- Non-tail siblings (e.g. two call arguments) keep unique names because
      -- their statements run before either result expression is consumed.
      -- The dedicated suffix also separates slots from parameters/captures.
      varName = case ident of
        Nothing
          | isTail && Array.null loopCtx && not (Map.member oldVarName bound) &&
              isSimpleBindingValue val ->
              oldVarName <> "_slot"
        _ -> oldVarName <> "_" <> show nextId
      resVal = translateValue translate ctx nextId val
      newBound = Map.insert oldVarName varName bound
      resBody = translate (ctx { boundVars = newBound }) (resVal.nextId + 1) body
    in
      { stmts: resVal.stmts <> [ PhpAssign varName resVal.expr ] <> resBody.stmts
      , expr: resBody.expr
      , nextId: resBody.nextId
      }
  where
  bound = ctx.boundVars
  loopCtx = ctx.loops
  isTail = ctx.isTail

translateLetRec
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> Level
  -> NEA.NonEmptyArray (Tuple Ident TcoExpr)
  -> TcoExpr
  -> TranslationResult
translateLetRec translate ctx nextId tcoExpr lvl binds body =
  let
    bindings = toArray binds :: Array (Tuple Ident TcoExpr)
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
        map (\fn -> { name: renameLocal recCtx (localId (Just ident) lvl), value, fn })
          (extractUncurriedAbs value)
      _ -> Nothing
  in
    case loopBinding of
      Just { name, value, fn } ->
        let
          loop =
            { ident: name
            , params: fn.args
            , varPrefix: "__tco_var_" <> name <> "_" <> show nextId <> "_"
            , labelName: "tco_loop_" <> name <> "_" <> show nextId
            , direct: ctx.allowDirect && Array.null loopCtx && not (containsNestedLoop fn.body)
            }
          resFnBody = translate
            (recCtx { loops = [ loop ], isTail = true, inEffectBlock = false })
            nextId
            fn.body
          mappedFvs = Array.filter (\v -> not (Array.elem v fn.args))
            (map (renameLocal recCtx) fn.fvs)
          captures = Array.nub (map (captureVariable recCtx) mappedFvs)
          outerLoopCaptures = foldMap
            ( \outer -> map
                (\param -> "&" <> (if outer.direct then param else outer.varPrefix <> param))
                outer.params
            )
            loopCtx
          types = extractFuncType value
          params = zipArgsWithTypes fn.args types
          returnType = getRetType (Array.length fn.args) types
          wrapper = PhpAssign name
            ( PhpFunction (outerLoopCaptures <> captures) params returnType
                (wrapLoopBody loop resFnBody)
            )
          resBody = translate recCtx (nextId + 1) body
        in
          { stmts: initStmts <> [ wrapper ] <> resBody.stmts
          , expr: resBody.expr
          , nextId: resBody.nextId
          }

      Nothing -> translateLetRecBindings translate recCtx lvl nextId initStmts bindings body
  where
  bound = ctx.boundVars
  loopCtx = ctx.loops
  recVars = ctx.recursiveVars

translateLetRecBindings
  :: Translate
  -> TranslationContext
  -> Level
  -> Int
  -> Array PhpExpr
  -> Array (Tuple Ident TcoExpr)
  -> TcoExpr
  -> TranslationResult
translateLetRecBindings translate recCtx lvl nextId initStmts bindings body =
  let
    accBinds = foldl
      ( \acc (Tuple ident val) ->
          let
            newName = renameLocal recCtx (localId (Just ident) lvl)
            res = translateValue translate recCtx acc.nextId val
          in
            { stmts: acc.stmts <> res.stmts <> [ PhpAssign newName res.expr ]
            , nextId: res.nextId
            }
      )
      { stmts: initStmts, nextId: nextId + 1 }
      bindings
    resBody = translate recCtx accBinds.nextId body
  in
    { stmts: accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

translateEffectBind
  :: Translate
  -> TranslationContext
  -> Int
  -> Maybe Ident
  -> Level
  -> TcoExpr
  -> TcoExpr
  -> TranslationResult
translateEffectBind translate ctx nextId ident level val body =
  let
    oldVarName = localId ident level
    varName = oldVarName <> "_" <> show nextId
    resVal = translate ((valueContext ctx) { inEffectBlock = true }) nextId val
    newBound = Map.insert oldVarName varName bound
    resBody = translate (ctx { boundVars = newBound, inEffectBlock = true })
      (resVal.nextId + 1)
      body
    valExpr = executeIfOpaque val resVal.expr
    bodyExpr = executeIfOpaque body resBody.expr
  in
    { stmts: resVal.stmts <> [ PhpAssign varName valExpr ] <> resBody.stmts
    , expr: bodyExpr
    , nextId: resBody.nextId
    }
  where
  bound = ctx.boundVars

translateEffectPure
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> TranslationResult
translateEffectPure translate ctx nextId e =
  translate (ctx { inEffectBlock = false }) nextId e

translateEffectDefer
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> TcoExpr
  -> TranslationResult
translateEffectDefer translate ctx nextId tcoExpr e =
  let
    res = translate ((valueContext ctx) { inEffectBlock = true }) nextId e
    useVars = closureCaptures ctx tcoExpr
  in
    { stmts: []
    , expr: PhpFunction useVars [] "" (res.stmts <> [ PhpReturn res.expr ])
    , nextId: res.nextId
    }

translateBranch
  :: Translate
  -> TranslationContext
  -> Int
  -> NEA.NonEmptyArray (Pair TcoExpr)
  -> TcoExpr
  -> TranslationResult
translateBranch translate ctx nextId pairs def =
  let
    resDef = translate ctx nextId def
    tmpVar = "__t" <> show resDef.nextId
    labelName = "end_branch_" <> show resDef.nextId
    accPairs = foldl
      ( \acc (Pair condExpr@(TcoExpr _ _cond) bodyExpr@(TcoExpr _ _body)) ->
          let
            resCond = translateValue translate ctx acc.nextId condExpr
            resBody = translate ctx resCond.nextId bodyExpr
            condWrapped = wrapInStmts
              (map (renameLocal ctx) (Array.fromFoldable (freeVars condExpr)))
              resCond.stmts
              resCond.expr
            ifNode = PhpIf condWrapped
              (resBody.stmts <> [ PhpAssign tmpVar resBody.expr, PhpGoto labelName ])
              []
          in
            { stmts: acc.stmts <> [ ifNode ], nextId: resBody.nextId }
      )
      { stmts: [], nextId: resDef.nextId + 1 }
      (toArray pairs)

    finalDef = resDef.stmts <> [ PhpAssign tmpVar resDef.expr, PhpLabel labelName ]

    extractMatch :: Array PhpExpr -> Array PhpExpr -> Maybe PhpExpr
    extractMatch ifNodes defStmts = case Array.uncons ifNodes of
      Nothing -> Nothing
      Just { head: PhpIf (PhpBinOp "===" subj val) [ PhpAssign tVar body, PhpGoto _ ] [], tail }
        | tVar == tmpVar ->
            let
              checkTail
                :: Array PhpExpr
                -> Array { val :: PhpExpr, body :: PhpExpr }
                -> Maybe (Array { val :: PhpExpr, body :: PhpExpr })
              checkTail rest acc = case Array.uncons rest of
                Nothing -> Just acc
                Just
                  { head: PhpIf (PhpBinOp "===" s v) [ PhpAssign tVar2 b, PhpGoto _ ] []
                  , tail: t
                  } | s == subj && tVar2 == tmpVar -> checkTail t
                  (Array.snoc acc { val: v, body: b })
                _ -> Nothing
            in
              case checkTail tail [ { val, body } ] of
                Just validCases ->
                  if Array.length defStmts == 2 then
                    case Array.index defStmts 0 of
                      Just (PhpAssign tVar3 defExpr) | tVar3 == tmpVar -> Just
                        (PhpMatch subj validCases defExpr)
                      _ -> Nothing
                  else Nothing
                Nothing -> Nothing
      _ -> Nothing

  in
    case extractMatch accPairs.stmts finalDef of
      Just matchExpr -> { stmts: [], expr: matchExpr, nextId: accPairs.nextId }
      Nothing ->
        { stmts: [ PhpAssign tmpVar (PhpNull) ] <> accPairs.stmts <> finalDef
        , expr: PhpVar tmpVar
        , nextId: accPairs.nextId
        }
