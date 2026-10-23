-- | Binding-group analysis: TCO environments, safe recursive initialization
-- | order and lambda flattening.
module Phpurs.CodeGen.Bindings
  ( AnalyzedBindingGroup
  , analyzeBindings
  , isSafeRecursiveInit
  , extractUncurriedAbs
  ) where

import Prelude

import Data.Array as Array
import Data.Array.NonEmpty (fromArray, toArray)
import Data.Foldable (all, foldl)
import Data.Maybe (Maybe(..))
import Data.Tuple (Tuple(..))
import Phpurs.FreeVars (freeVars)
import PureScript.Backend.Optimizer.Codegen.Tco as Tco
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (Ident, ModuleName, Prop(..), Qualified(..))
import PureScript.Backend.Optimizer.FreeVars (localId)
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..)) as Syn
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..))

type AnalyzedBindingGroup =
  { recursive :: Boolean
  , bindings :: Array (Tuple Ident Tco.TcoExpr)
  }

analyzeBindings :: BackendModule -> Array AnalyzedBindingGroup
analyzeBindings mod =
  let
    Tuple _ groups = foldl analyzeGroup (Tuple [] []) mod.bindings
  in
    groups
  where
  analyzeGroup (Tuple env groups) group =
    let
      nextEnv = case fromArray group.bindings of
        Just nonEmptyBindings | group.recursive -> Tco.topLevelTcoEnvGroup mod.name nonEmptyBindings
          <> env
        _ -> env
      bindings = map (\(Tuple ident expr) -> Tuple ident (Tco.analyze nextEnv expr)) group.bindings
      orderedBindings =
        if group.recursive then
          let
            groupIdents = map (\(Tuple ident _) -> ident) group.bindings
            ready = Array.partition
              (\(Tuple _ expr) -> isSafeRecursiveInit mod.name groupIdents expr)
              bindings
          in
            ready.yes <> ready.no
        else bindings
    in
      Tuple nextEnv (Array.snoc groups { recursive: group.recursive, bindings: orderedBindings })

-- | Initialize recursive dictionaries and functions before expressions which
-- | call into them (for example, Apply instances implemented with Monad.ap).
isSafeRecursiveInit :: ModuleName -> Array Ident -> Tco.TcoExpr -> Boolean
isSafeRecursiveInit currentModule group = go
  where
  go (Tco.TcoExpr _ expr) = case expr of
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
    -- Pure applications such as a composed `pure` implementation can also be
    -- initialized early when they are independent of the recursive group.
    -- Inspect callback bodies too: the callee may invoke them immediately.
    App _ _ -> all independent expr
    UncurriedApp _ _ -> all independent expr
    Typed _ a -> go a
    Syn.TypeApp a _ -> go a
    _ -> false

  independent (Tco.TcoExpr _ expr) = case expr of
    Var (Qualified (Just mn) ident) | mn == currentModule -> not (Array.elem ident group)
    _ -> all independent expr

extractUncurriedAbs
  :: Tco.TcoExpr -> Maybe { args :: Array String, body :: Tco.TcoExpr, fvs :: Array String }
extractUncurriedAbs tcoExpr@(Tco.TcoExpr _ syntax) = case syntax of
  UncurriedAbs args body ->
    Just
      { args: map (\(Tuple mbI lvl) -> localId mbI lvl) args
      , body
      , fvs: Array.fromFoldable (freeVars tcoExpr)
      }
  Abs args body ->
    let
      thisArgs = map (\(Tuple mbI lvl) -> localId mbI lvl) (toArray args)
    in
      case extractUncurriedAbs body of
        Just inner -> Just
          { args: thisArgs <> inner.args
          , body: inner.body
          , fvs: Array.nub (Array.fromFoldable (freeVars tcoExpr) <> inner.fvs)
          }
        Nothing -> Just { args: thisArgs, body, fvs: Array.fromFoldable (freeVars tcoExpr) }
  Typed _ inner -> extractUncurriedAbs inner
  _ -> Nothing
