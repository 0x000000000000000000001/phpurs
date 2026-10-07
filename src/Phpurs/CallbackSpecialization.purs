-- Specialize a proven list fold at a known native binary Int callback. Keep
-- the public fold, its callback expression and every native numeric check.
module Phpurs.CallbackSpecialization where

import Prelude

import Control.Monad.State (State, get, modify_, runState)
import Data.Array as A
import Data.Array.NonEmpty as NEA
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.Set (Set)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Phpurs.NativeCallbacks (Callbacks)
import Phpurs.ThunkFusion (bounded, freshPrefixFor, hasType, integer, peel, require)
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (ConstructorType(..), DataDecl, ExprType(..), Ident(..), ModuleName, ProperName(..), Qualified(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendAccessor(..), BackendOperator(..), BackendOperator1(..), BackendOperator2(..), BackendOperatorNum(..), BackendSyntax(..), Level, Pair(..))

type Binder = Tuple (Maybe Ident) Level
type Fold = { expr :: NeutralExpr, callback :: Binder, params :: Array Binder }
type Layout = { typeName :: String, empty :: Qualified Ident, cons :: Qualified Ident, consName :: String, head :: Int, tail :: Int }
type Selection = Tuple Ident (Qualified Ident)

nodeBudget :: Int
nodeBudget = 1024

workerBudget :: Int
workerBudget = 32

local :: Binder -> NeutralExpr -> Boolean
local binder expr = case peel expr of
  Local name level -> binder == Tuple name level
  _ -> false

distinct :: Array Binder -> Boolean
distinct args = A.length (A.nub (map (\(Tuple _ level) -> level) args)) == A.length args

-- Match the emitter's actual native lambda extraction, not just its type.
-- In particular a branch/let returning a function is not another native arg.
function :: NeutralExpr -> { params :: Array Binder, body :: NeutralExpr }
function (NeutralExpr (Typed _ inner)) = function inner
function (NeutralExpr (Abs args body)) =
  let rest = function body
  in rest { params = NEA.toArray args <> rest.params }
function expr = { params: [], body: expr }

nativeCallback :: NeutralExpr -> Boolean
nativeCallback expr = case proof of
  Just _ -> true
  Nothing -> false
  where
  proof = do
    require (bounded nodeBudget expr)
    case expr of
      -- The local arity table uses the outer Func's argument count. Unlike
      -- foreign wrappers, a nested function type is not flattened there.
      NeutralExpr (Typed (Func [ Int, Int ] Int) _) -> pure unit
      _ -> Nothing
    case function expr of
      { params: [ left, right ], body } -> do
        require (distinct [ left, right ])
        case peel body of
          PrimOp (Op2 (OpIntNum op) a b) ->
            require ((op == OpAdd || op == OpSubtract || op == OpMultiply) && local left a && local right b)
          _ -> Nothing
      _ -> Nothing

layoutFor :: ModuleName -> String -> DataDecl -> Maybe Layout
layoutFor mn fqn decl = do
  require (fqn == unwrap mn <> "." <> decl.name && A.length decl.constructors == 2)
  case decl.vars of
    [ variable ] -> do
      empty <- A.find (\c -> A.null c.fields) decl.constructors
      cons <- A.find (\c -> A.length c.fields == 2) decl.constructors
      require (empty.name /= cons.name)
      head <- A.findIndex (_ == TypeVar variable) cons.fields
      tail <- A.findIndex (_ == ADT fqn (String.split (Pattern ".") fqn) [ TypeVar variable ]) cons.fields
      require (head /= tail)
      pure { typeName: decl.name, empty: qualified empty.name, cons: qualified cons.name, consName: cons.name, head, tail }
    _ -> Nothing
  where
  qualified name = Qualified (Just mn) (Ident name)

-- A generic accumulator and element, with the same accumulator flowing back
-- from the callback. The concrete native callback supplies the Int invariant.
foldSignature :: ExprType -> Maybe { fqn :: String, acc :: String, item :: String }
foldSignature (Func
  [ Func [ TypeVar acc, TypeVar item ] (TypeVar next), TypeVar initial, ADT fqn path [ TypeVar element ] ]
  (TypeVar result)) = do
  require (acc /= item)
  require (next == acc && initial == acc && result == acc && element == item)
  require (path == String.split (Pattern ".") fqn)
  pure { fqn, acc, item }
foldSignature _ = Nothing

foldType :: NeutralExpr -> Maybe String
foldType (NeutralExpr (Typed (ForAll vars signature) _)) = do
  ty <- foldSignature signature
  require (A.length vars == 2 && A.elem ty.acc vars && A.elem ty.item vars)
  pure ty.fqn
foldType _ = Nothing

-- The emitter skips ForAll/other annotations until the first Func. Copies
-- need the same flat, unchecked generic signature there: without it the
-- printer has no saturated arity and would look up a private global export.
-- PBO may rename the inner type variables, so check the same relationships
-- rather than their spelling. The shallow shape match also bounds type reads.
nativeFoldType :: String -> NeutralExpr -> Boolean
nativeFoldType fqn (NeutralExpr (Typed ty@(Func _ _) _)) = case foldSignature ty of
  Just native -> native.fqn == fqn
  Nothing -> false
nativeFoldType fqn (NeutralExpr (Typed _ inner)) = nativeFoldType fqn inner
nativeFoldType _ _ = false

isTag :: Binder -> Qualified Ident -> NeutralExpr -> Boolean
isTag list ctor expr = case peel expr of
  PrimOp (Op1 (OpIsTag name) value) -> name == ctor && local list value
  _ -> false

field :: Layout -> Binder -> Int -> NeutralExpr -> Boolean
field layout list index expr = case peel expr of
  Accessor value accessor -> local list value && accessor ==
    GetCtorField layout.cons SumType (ProperName layout.typeName) (Ident layout.consName) ("value" <> show index) index
  _ -> false

proveFold :: BackendModule -> Ident -> NeutralExpr -> Maybe Fold
proveFold mod ident expr = do
  require (bounded nodeBudget expr)
  fqn <- foldType expr
  require (nativeFoldType fqn expr)
  layout <- A.findMap (layoutFor mod.name fqn) mod.dataDecls
  case function expr of
    { params: params@[ callback, acc, list ], body } -> do
      require (distinct params)
      case peel body of
        Branch branches fallback -> case NEA.toArray branches, peel fallback of
          [ Pair emptyTest base, Pair consTest next ], Fail _ -> do
            require (isTag list layout.empty emptyTest && local acc base && isTag list layout.cons consTest)
            case peel next of
              App callee inputs -> case peel callee, NEA.toArray inputs of
                Var name, [ sameCallback, nextAcc, rest ] -> do
                  require (name == Qualified (Just mod.name) ident && local callback sameCallback && field layout list layout.tail rest)
                  case peel nextAcc of
                    App fn args -> case NEA.toArray args of
                      [ value, item ] -> do
                        require (local callback fn && local acc value && field layout list layout.head item)
                        pure { expr, callback, params }
                      _ -> Nothing
                    _ -> Nothing
                _, _ -> Nothing
              _ -> Nothing
          _, _ -> Nothing
        _ -> Nothing
    _ -> Nothing

-- The fold's only nonrecursive callback use is the proven saturated site.
-- Keep callback forwarding, parameter order, branches and reads unchanged.
makeCopy :: Qualified Ident -> Qualified Ident -> Qualified Ident -> Fold -> NeutralExpr
makeCopy original worker callback proof = rewrite proof.expr
  where
  rewrite (NeutralExpr syntax) = NeutralExpr case syntax of
    App callee args | local proof.callback callee -> App (NeutralExpr (Var callback)) args
    Var name | name == original -> Var worker
    -- Delegate the impossible-constructor path to the original function. This
    -- preserves its generated failure location/message for malformed FFI data.
    Fail _ -> App (NeutralExpr (Var original)) (NEA.cons'
      (binder proof.callback) (map binder (A.drop 1 proof.params)))
    _ -> map rewrite syntax
  binder (Tuple name level) = NeutralExpr (Local name level)

type ScanState = { fuel :: Int, copies :: Map Selection Ident }

scan :: ModuleName -> String -> Map Ident Fold -> Callbacks -> NeutralExpr -> State ScanState NeutralExpr
scan mn prefix folds callbacks expr@(NeutralExpr syntax) = do
  state <- get
  if state.fuel <= 0 then pure expr
  else do
    modify_ (\s -> s { fuel = s.fuel - 1 })
    case candidate of
      Just { key, args } -> case Map.lookup key state.copies of
        Just worker -> apply worker <$> traverse (scan mn prefix folds callbacks) args
        Nothing | Map.size state.copies < workerBudget -> do
          let worker = Ident (prefix <> show (Map.size state.copies))
          modify_ (\s -> s { copies = Map.insert key worker s.copies })
          apply worker <$> traverse (scan mn prefix folds callbacks) args
        _ -> recur
      Nothing -> recur
  where
  recur = NeutralExpr <$> traverse (scan mn prefix folds callbacks) syntax
  apply worker args = NeutralExpr (App (NeutralExpr (Var (Qualified (Just mn) worker))) args)
  candidate = case syntax of
    App callee args -> case peel callee, NEA.toArray args of
      Var (Qualified (Just owner) fold), [ callback, initial, _ ] | owner == mn -> do
        _ <- Map.lookup fold folds
        target <- case peel callback of
          Var name | Set.member name callbacks -> Just name
          _ -> Nothing
        -- The first partial call checked the accumulator before reading the
        -- element. A literal Int, followed only by native Int-checked results,
        -- proves that this early check cannot throw or coerce at any iteration.
        require (hasType Int initial)
        _ <- integer initial
        pure { key: Tuple fold target, args }
      _, _ -> Nothing
    _ -> Nothing

optimize :: Callbacks -> BackendModule -> { module_ :: BackendModule, privateNames :: Set Ident }
optimize foreignCallbacks mod
  | A.length mod.bindings > 256 || A.any (\g -> A.length g.bindings > 64) mod.bindings || A.length mod.dataDecls > 128 =
      { module_: mod, privateNames: Set.empty }
  | otherwise =
      let
        callbacks = Set.union foreignCallbacks (Set.fromFoldable (A.concatMap (\g -> if g.recursive then [] else A.mapMaybe
          (\(Tuple ident expr) -> if nativeCallback expr then Just (Qualified (Just mod.name) ident) else Nothing) g.bindings) mod.bindings))
        folds = Map.fromFoldable (A.mapMaybe (\g -> case g.recursive, g.bindings of
          true, [ Tuple ident expr ] -> Tuple ident <$> proveFold mod ident expr
          _, _ -> Nothing) mod.bindings)
      in if Set.isEmpty callbacks || Map.isEmpty folds then { module_: mod, privateNames: Set.empty }
         else specialize mod folds callbacks

specialize :: BackendModule -> Map Ident Fold -> Callbacks -> { module_ :: BackendModule, privateNames :: Set Ident }
specialize mod folds callbacks =
  let
    prefix = freshPrefixFor "__phpurs_foldcb_" mod
    Tuple bindings selected = runState
      (traverse (\g -> do
        bs <- traverse (\(Tuple ident expr) -> Tuple ident <$> if bounded 8192 expr then scan mod.name prefix folds callbacks expr else pure expr) g.bindings
        pure (g { bindings = bs })) mod.bindings)
      { fuel: 32768, copies: Map.empty }
    copies = A.mapMaybe (\(Tuple (Tuple original callback) worker) -> do
      proof <- Map.lookup original folds
      pure { recursive: true, bindings: [ Tuple worker (makeCopy
        (Qualified (Just mod.name) original) (Qualified (Just mod.name) worker) callback proof) ] })
      (Map.toUnfoldable selected.copies :: Array (Tuple Selection Ident))
  in
    { module_: mod { bindings = bindings <> copies }
    , privateNames: Set.fromFoldable (Map.values selected.copies)
    }
