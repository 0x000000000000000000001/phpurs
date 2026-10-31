-- | Apply a proven enum region: private layouts, constructor copies and call
-- | sites.
module Phpurs.EnumRegions.Rewrite (optimize) where

import Prelude

import Control.Monad.State (State, get, modify_, runState)
import Control.Monad.State.Trans (runStateT)
import Data.Array as A
import Data.Foldable (foldl, foldr)
import Data.List (List(..))
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe, isJust)
import Data.Newtype (unwrap)
import Data.Set (Set)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Phpurs.Budget as Budget
import Phpurs.EnumRegions.Proof (compactLayout, infer, isEnum, scalar)
import Phpurs.EnumRegions.Types (Constructor, Context, initial, regionNodeCount)
import Phpurs.NullableConstructors as Nullable
import Phpurs.Optimize.Names (freshPrefixFor)
import Phpurs.Optimize.NeutralExpr (peel, signature)
import Phpurs.Pass (privateName, scanGroupsWithin)
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn
  ( ConstructorType(..)
  , DataDecl
  , ExprType(..)
  , Ident(..)
  , Literal(..)
  , ModuleName
  , ProperName(..)
  , Qualified(..)
  )
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax
  ( BackendAccessor(..)
  , BackendOperator(..)
  , BackendOperator1(..)
  , BackendOperator2(..)
  , BackendOperatorOrd(..)
  , BackendSyntax(..)
  , Level
  , Pair(..)
  )
import PureScript.Backend.Optimizer.Syntax as Syn

context :: BackendModule -> Context
context mod =
  { name: mod.name
  , bindings: Map.fromFoldable (A.concatMap _.bindings mod.bindings)
  , layouts: Map.fromFoldable (map (\d -> Tuple (full d.name) d) mod.dataDecls)
  , constructors: Map.fromFoldable
      ( A.concatMap
          ( \d -> A.mapWithIndex
              ( \i c ->
                  Tuple (Qualified (Just mod.name) (Ident c.name))
                    { type_: ADT (full d.name) (String.split (Pattern ".") (full d.name)) []
                    , fields: c.fields
                    , tag: if isEnum d then Just i else Nothing
                    , constructorType: if A.length d.constructors == 1 then ProductType else SumType
                    , typeName: ProperName d.name
                    }
              )
              d.constructors
          )
          mod.dataDecls
      )
  }
  where
  full name = unwrap mod.name <> "." <> name

renameType
  :: forall r
   . Context
  -> String
  -> { workers :: Set Ident, layouts :: Set String | r }
  -> ExprType
  -> ExprType
renameType ctx prefix selected ty = case ty of
  ADT name _ args | Set.member name selected.layouts -> case Map.lookup name ctx.layouts of
    Just d | isEnum d -> Int
    Just d ->
      let
        full = unwrap ctx.name <> "." <> prefix <> d.name
      in
        ADT full (String.split (Pattern ".") full) (map recur args)
    _ -> ty
  Func args ret -> Func (map recur args) (recur ret)
  _ -> ty
  where
  recur = renameType ctx prefix selected

-- This same structural rewrite applies to a proven entry expression and to
-- copies of its complete call graph. Original workers/layouts are kept intact.
rewrite
  :: forall r
   . Context
  -> String
  -> { workers :: Set Ident, layouts :: Set String | r }
  -> NeutralExpr
  -> NeutralExpr
rewrite ctx prefix selected (NeutralExpr syntax) = NeutralExpr case map recur syntax of
  Typed ty inner -> Typed (renameType ctx prefix selected ty) inner
  Syn.TypeApp inner ty -> Syn.TypeApp inner (renameType ctx prefix selected ty)
  Var (Qualified mn ident) | mn == Just ctx.name && Set.member ident selected.workers ->
    Var (Qualified mn (Ident (prefix <> unwrap ident)))
  Var q -> case Map.lookup q ctx.constructors of
    Just c | A.null c.fields -> case c.tag of
      Just tag -> Lit (LitInt tag)
      _ ->
        let
          renamed@(Qualified _ ident) = renameCtor q
        in
          CtorSaturated renamed c.constructorType (renameProper c.typeName) ident []
    _ -> Var q
  CtorSaturated q ct pn ident fields -> case Map.lookup q ctx.constructors >>= _.tag of
    Just tag -> Lit (LitInt tag)
    _ -> CtorSaturated (renameCtor q) ct (renameProper pn) (renameIdent ident) fields
  PrimOp (Op1 (OpIsTag q) value) -> case Map.lookup q ctx.constructors >>= _.tag of
    Just tag -> PrimOp (Op2 (OpIntOrd OpEq) value (NeutralExpr (Lit (LitInt tag))))
    _ -> PrimOp (Op1 (OpIsTag (renameCtor q)) value)
  Accessor value (GetCtorField q ct pn ident field index) ->
    Accessor value
      (GetCtorField (renameCtor q) ct (renameProper pn) (renameIdent ident) field index)
  other -> other
  where
  recur = rewrite ctx prefix selected
  renameIdent (Ident name) = Ident (prefix <> name)
  renameProper (ProperName name) = ProperName (prefix <> name)
  renameCtor q@(Qualified mn ident) = case Map.lookup q ctx.constructors of
    Just { type_: ADT name _ _ } | Set.member name selected.layouts -> Qualified mn
      (renameIdent ident)
    _ -> q

type ScanState =
  { fuel :: Int, attempts :: Int, workers :: Set Ident, layouts :: Set String, copied :: Int }

-- Explicit work list: even a rejected deep/wide body must not exhaust the JS
-- stack before its budget can be checked. The same bound protects State walks.
-- A region may start at any saturated call to a local monomorphic worker that
-- returns a scalar. The optimizer does not always keep a `Typed` annotation on
-- such calls (for example as the direct argument of `EffectPure`), so the
-- candidate test consults the worker's signature instead of the annotation.
candidateCall :: Context -> NeutralExpr -> Boolean
candidateCall ctx fn = case peel fn of
  Var (Qualified (Just mn) (Ident ident)) | mn == ctx.name ->
    case Map.lookup (Ident ident) ctx.bindings >>= signature of
      Just sig -> scalar sig.ret
      Nothing -> false
  _ -> false

scan :: Context -> String -> NeutralExpr -> State ScanState NeutralExpr
scan ctx prefix expr@(NeutralExpr syntax) = do
  s <- get
  if s.fuel <= 0 then pure expr
  else do
    modify_ (\v -> v { fuel = v.fuel - 1 })
    let
      candidate = case syntax of
        App fn _ -> candidateCall ctx fn
        UncurriedApp fn _ -> candidateCall ctx fn
        _ -> false
    result <-
      if candidate && s.attempts > 0 then do
        modify_ (\v -> v { attempts = v.attempts - 1 })
        pure (runStateT (infer ctx Map.empty true Nothing expr) initial)
      else pure Nothing
    case result of
      Just (Tuple ty proof)
        | scalar ty && proof.usesCompactLayout && not (Set.isEmpty proof.workers) -> do
            let added = Set.difference proof.workers s.workers
            let
              copied = s.copied + foldl
                ( \n ident -> n + fromMaybe 0
                    (regionNodeCount (Budget.regionNodes + 1) <$> Map.lookup ident ctx.bindings)
                )
                0
                added
            let workers = Set.union s.workers proof.workers
            let layouts = Set.union s.layouts proof.layouts
            if copied <= Budget.regionNodes && Set.size workers <= Budget.workerLimit then do
              modify_ (\v -> v { workers = workers, layouts = layouts, copied = copied })
              pure (rewrite ctx prefix proof expr)
            else descend
      _ -> descend
  where
  descend = NeutralExpr <$> traverse (scan ctx prefix) syntax

-- | Constructor and field width admitted before layout analysis.
wideData :: DataDecl -> Boolean
wideData d =
  A.length d.constructors > Budget.constructorWidth
    || A.any (\c -> A.length c.fields > Budget.constructorWidth) d.constructors

-- | Region analysis uses its own node counter, smaller than the scan budget.
withinRegion :: NeutralExpr -> Boolean
withinRegion e = regionNodeCount (Budget.regionNodes + 1) e <= Budget.regionNodes

optimize
  :: BackendModule
  -> { module_ :: BackendModule
     , privateNames :: Set Ident
     , privateConstructors :: Set String
     , nullableConstructors :: Map String Boolean
     , arrayLayouts :: Set String
     }
optimize mod
  | not (A.any compactLayout mod.dataDecls)
      || A.length mod.dataDecls > Budget.regionDataDecls
      || A.length mod.bindings > Budget.moduleBindings
      || A.any wideData mod.dataDecls =
      { module_: mod
      , privateNames: Set.empty
      , privateConstructors: Set.empty
      , nullableConstructors: Map.empty
      , arrayLayouts: Set.empty
      }
  | otherwise =
      let
        ctx = context mod
        prefix = freshPrefixFor "__phpurs_enum_" mod
        Tuple bindings selected = runState
          (scanGroupsWithin withinRegion (scan ctx prefix) mod)
          { fuel: Budget.stateFuel
          , attempts: Budget.attempts
          , workers: Set.empty
          , layouts: Set.empty
          , copied: 0
          }
        renamed = privateName prefix
        copies = A.mapMaybe
          ( \g ->
              let
                bs = A.mapMaybe
                  ( \(Tuple k e) ->
                      if Set.member k selected.workers then Just
                        (Tuple (renamed k) (rewrite ctx prefix selected e))
                      else Nothing
                  )
                  g.bindings
              in
                if A.null bs then Nothing else Just (g { bindings = bs })
          )
          mod.bindings
        layouts = A.mapMaybe
          ( \d ->
              if Set.member (unwrap mod.name <> "." <> d.name) selected.layouts && not (isEnum d) then
                Just
                  ( d
                      { name = prefix <> d.name
                      , constructors = map
                          ( \c -> c
                              { name = prefix <> c.name
                              , fields = map (renameType ctx prefix selected) c.fields
                              }
                          )
                          d.constructors
                      }
                  )
              else Nothing
          )
          mod.dataDecls
        -- Private nullable-product layouts (objects). Their full names let
        -- CodeGen identify the worker parameters that may be updated in place.
        arrayLayouts = Set.fromFoldable
          ( A.mapMaybe
              ( \d -> case Nullable.layout d of
                  Just _ -> Just (unwrap mod.name <> "." <> d.name)
                  Nothing -> Nothing
              )
              layouts
          )
      in
        { module_: mod { bindings = bindings <> copies, dataDecls = mod.dataDecls <> layouts }
        , privateNames: Set.map renamed selected.workers
        , privateConstructors: Set.fromFoldable
            (A.concatMap (map _.name <<< _.constructors) layouts)
        -- These names belong only to copied layouts whose complete uses passed
        -- infer. The public layouts and arbitrary PHP values are never tagged.
        , nullableConstructors: Map.fromFoldable
            ( A.concatMap
                ( \d -> case Nullable.layout d of
                    Just l -> [ Tuple l.empty true, Tuple l.boxed false ]
                    Nothing -> []
                )
                layouts
            )
        , arrayLayouts
        }
