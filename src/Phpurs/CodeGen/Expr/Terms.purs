-- | Expression-shaped translation rules: literals, variables, calls and
-- | closures.
module Phpurs.CodeGen.Expr.Terms
  ( translateAbs
  , translateAccessor
  , translateApp
  , translateLit
  , translateLocal
  , translateUncurriedAbs
  , translateUncurriedApp
  , translateUncurriedEffectAbs
  , translateUncurriedEffectApp
  , translateVar
  ) where

import Prelude

import Data.Array as Array
import Data.Array.NonEmpty (toArray)
import Data.Array.NonEmpty as NEA
import Data.Foldable (foldl)
import Data.Maybe (Maybe(..), fromMaybe)
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Data.Tuple (Tuple(..))
import Phpurs.CodeGen.Context (TranslationContext, TranslationResult, valueContext)
import Phpurs.CodeGen.Effects (executeIfOpaque)
import Phpurs.CodeGen.Expr.Support
  ( Translate
  , closureCaptures
  , flattenApp
  , renameLocal
  , translateValue
  , translateValues
  )
import Phpurs.CodeGen.Tco (findTailTarget, translateTailJump)
import Phpurs.CodeGen.Types (extractFuncType, getRetType, zipArgsWithTypes)
import Phpurs.PhpAst (PhpExpr(..))
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..))
import PureScript.Backend.Optimizer.CoreFn
  ( Ident(..)
  , Literal(..)
  , ModuleName(..)
  , Prop(..)
  , Qualified(..)
  )
import PureScript.Backend.Optimizer.FreeVars (localId)
import PureScript.Backend.Optimizer.Syntax
  ( BackendAccessor(..)
  , BackendSyntax(..)
  , Level
  )

translateLit :: Translate -> TranslationContext -> Int -> Literal TcoExpr -> TranslationResult
translateLit translate ctx nextId lit =
  case lit of
    LitInt i -> { stmts: [], expr: PhpInt i, nextId }
    LitNumber n -> { stmts: [], expr: PhpNumber n, nextId }
    LitString s -> { stmts: [], expr: PhpString s, nextId }
    LitChar c ->
      { stmts: [], expr: PhpString (String.singleton (String.codePointFromChar c)), nextId }
    LitBoolean b -> { stmts: [], expr: PhpBoolean b, nextId }
    LitArray arr ->
      let
        result = translateValues translate ctx nextId arr
      in
        { stmts: result.stmts, expr: PhpArray result.exprs, nextId: result.nextId }
    LitRecord rec ->
      let
        acc = foldl
          ( \a (Prop key val@(TcoExpr _ _)) ->
              let
                res = translateValue translate ctx a.nextId val
              in
                { stmts: a.stmts <> res.stmts
                , exprs: Array.snoc a.exprs { key, value: res.expr }
                , nextId: res.nextId
                }
          )
          { stmts: [], exprs: [], nextId }
          rec
      in
        { stmts: acc.stmts, expr: PhpAssocArray acc.exprs, nextId: acc.nextId }

translateVar :: Translate -> Int -> Maybe ModuleName -> String -> TranslationResult
translateVar _translate nextId moduleName name =
  { stmts: []
  , expr: PhpGlobalVar (map (\(ModuleName m) -> String.split (Pattern ".") m) moduleName) name
  , nextId
  }

translateLocal
  :: Translate -> TranslationContext -> Int -> Maybe Ident -> Level -> TranslationResult
translateLocal _translate ctx nextId ident level =
  { stmts: [], expr: PhpVar (renameLocal ctx (localId ident level)), nextId }

translateApp
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> TcoExpr
  -> NEA.NonEmptyArray TcoExpr
  -> TranslationResult
translateApp translate ctx nextId tcoExpr fn args =
  let
    Tuple flatFn flatArgs = flattenApp tcoExpr
    tailTarget = case flatFn of
      TcoExpr _ (Local ident level) ->
        findTailTarget ctx (renameLocal ctx (localId ident level))
      TcoExpr _ (Var (Qualified mbMod (Ident name))) ->
        let
          fullName =
            fromMaybe ""
              ( map (\(ModuleName m) -> String.joinWith "_" (String.split (Pattern ".") m) <> "_")
                  mbMod
              ) <> name
        in
          findTailTarget ctx fullName
      _ -> Nothing
  in
    case tailTarget of
      Just loop -> translateTailJump loop
        (translateValues translate ctx nextId flatArgs)
      Nothing ->
        let
          resFn = translateValue translate ctx nextId fn
          resArgs = translateValues translate ctx resFn.nextId (toArray args)
          curriedCall = foldl (\acc expr -> PhpCall acc [ expr ]) resFn.expr resArgs.exprs
        in
          { stmts: resFn.stmts <> resArgs.stmts, expr: curriedCall, nextId: resArgs.nextId }

translateUncurriedApp
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> Array TcoExpr
  -> TranslationResult
translateUncurriedApp translate ctx nextId fn args =
  let
    resFn = translateValue translate ctx nextId fn
    resArgs = translateValues translate ctx resFn.nextId args
    tailTarget = case resFn.expr of
      PhpGlobalVar mbMod name ->
        let
          fullName = fromMaybe "" (map (\m -> String.joinWith "_" m <> "_") mbMod) <> name
        in
          findTailTarget ctx fullName
      PhpVar name -> findTailTarget ctx name
      _ -> Nothing
  in
    case tailTarget of
      Just loop -> translateTailJump loop resArgs
      Nothing ->
        { stmts: resFn.stmts <> resArgs.stmts
        , expr: PhpCall resFn.expr resArgs.exprs
        , nextId: resArgs.nextId
        }

translateUncurriedEffectApp
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> Array TcoExpr
  -> TranslationResult
translateUncurriedEffectApp translate ctx nextId fn args =
  let
    resFn = translateValue translate ctx nextId fn
    resArgs = translateValues translate ctx resFn.nextId args
  in
    { stmts: resFn.stmts <> resArgs.stmts
    , expr: PhpCall resFn.expr resArgs.exprs
    , nextId: resArgs.nextId
    }

translateAbs
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> NEA.NonEmptyArray (Tuple (Maybe Ident) Level)
  -> TcoExpr
  -> TranslationResult
translateAbs translate ctx nextId tcoExpr args body =
  let
    argsArray = map (\(Tuple mbI lvl) -> localId mbI lvl) (toArray args)
    useVars = closureCaptures ctx tcoExpr

    resBody = translate ((valueContext ctx) { isTail = true }) nextId body
    types = extractFuncType tcoExpr
    argsWithTypes = zipArgsWithTypes argsArray types
    retType = getRetType (Array.length argsArray) types
  in
    { stmts: []
    , expr: PhpFunction useVars argsWithTypes retType
        (resBody.stmts <> [ PhpReturn resBody.expr ])
    , nextId: resBody.nextId
    }

translateUncurriedAbs
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> Array (Tuple (Maybe Ident) Level)
  -> TcoExpr
  -> TranslationResult
translateUncurriedAbs translate ctx nextId tcoExpr args body =
  let
    argsArray = map (\(Tuple mbI lvl) -> localId mbI lvl) args
    useVars = closureCaptures ctx tcoExpr

    resBody = translate ((valueContext ctx) { isTail = true }) nextId body
    types = extractFuncType tcoExpr
    argsWithTypes = zipArgsWithTypes argsArray types
    retType = getRetType (Array.length argsArray) types
  in
    { stmts: []
    , expr: PhpFunction useVars argsWithTypes retType
        (resBody.stmts <> [ PhpReturn resBody.expr ])
    , nextId: resBody.nextId
    }

translateUncurriedEffectAbs
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> Array (Tuple (Maybe Ident) Level)
  -> TcoExpr
  -> TranslationResult
translateUncurriedEffectAbs translate ctx nextId tcoExpr args body =
  let
    argsArray = map (\(Tuple mbI lvl) -> localId mbI lvl) args
    useVars = closureCaptures ctx tcoExpr
    resBody = translate ((valueContext ctx) { inEffectBlock = true }) nextId body
    types = extractFuncType tcoExpr
    argsWithTypes = zipArgsWithTypes argsArray types
    retType = getRetType (Array.length argsArray) types
    -- Saturating an EffectFn executes its body once. Explicit effect nodes
    -- have already been lowered to statements and a result; that result may
    -- itself be an action or canceler and must not be executed here.
    bodyExpr = executeIfOpaque body resBody.expr
  in
    { stmts: []
    , expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn bodyExpr ])
    , nextId: resBody.nextId
    }

translateAccessor
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> BackendAccessor
  -> TranslationResult
translateAccessor translate ctx nextId e acc =
  let
    res = translateValue translate ctx nextId e
  in
    case acc of
      GetProp prop ->
        { stmts: res.stmts, expr: PhpRecordAccess res.expr prop, nextId: res.nextId }
      GetIndex idx ->
        { stmts: res.stmts, expr: PhpArrayIndex res.expr (PhpInt idx), nextId: res.nextId }
      GetCtorField _ _ _ _ prop _ ->
        { stmts: res.stmts, expr: PhpPropertyAccess res.expr prop, nextId: res.nextId }

