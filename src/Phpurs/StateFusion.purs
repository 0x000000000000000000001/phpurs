-- Immediately execute a proven State chain in a private scalar loop. The
-- record layout, helpers and callback bodies are evidence, never their names.
module Phpurs.StateFusion where

import Prelude

import Control.Monad.State (State, get, modify_, runState)
import Data.Array as A
import Data.Array.NonEmpty as NEA
import Data.Foldable (all, foldl)
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.Set (Set)
import Data.Set as Set
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Phpurs.ThunkFusion (Step, bounded, call, freshPrefixFor, hasType, int, integer, makeWorker, peel, require, typed)
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident(..), Literal(..), ModuleName(..), Prop(..), Qualified(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendAccessor(..), BackendOperator(..), BackendOperator2(..), BackendOperatorNum(..), BackendOperatorOrd(..), BackendSyntax(..), Level(..), Pair(..))

type Binder = Tuple (Maybe Ident) Level
type Layout = { value :: String, state :: String }
type Builder = { layout :: Layout, step :: Step }
type Locals = Set Binder
type Result = { module_ :: BackendModule, privateNames :: Set Ident, checkedDepths :: Set Ident }

nodeBudget :: Int
nodeBudget = 1024

workerBudget :: Int
workerBudget = 32

local :: Binder -> NeutralExpr -> Boolean
local binder expr = case peel expr of
  Local ident level -> binder == Tuple ident level
  _ -> false

distinct :: Array Binder -> Boolean
distinct binders = A.length (A.nub (map (\(Tuple _ level) -> level) binders)) == A.length binders

function :: NeutralExpr -> { params :: Array Binder, body :: NeutralExpr }
function expr = case peel expr of
  Abs args body -> let rest = function body
    in rest { params = NEA.toArray args <> rest.params }
  _ -> { params: [], body: expr }

-- Internal annotations may still contain a polymorphic variable around Unit.
-- Only the canonical value, never an arbitrary expression of type Unit, is safe.
canonicalUnit :: NeutralExpr -> Boolean
canonicalUnit expr = case peel expr of
  Var name -> name == Qualified (Just (ModuleName "Data.Unit")) (Ident "unit")
  _ -> false

record :: Layout -> NeutralExpr -> Maybe { value :: NeutralExpr, state :: NeutralExpr }
record layout expr = case peel expr of
  Lit (LitRecord props) -> do
    require (A.length props == 2 && layout.value /= layout.state)
    value <- A.findMap (\(Prop key val) -> if key == layout.value then Just val else Nothing) props
    state <- A.findMap (\(Prop key val) -> if key == layout.state then Just val else Nothing) props
    pure { value, state }
  _ -> Nothing

layoutOf :: NeutralExpr -> Maybe Layout
layoutOf (NeutralExpr (Typed (Func [ Int, Int ] (Record (Row fields Nothing))) _)) = do
  require (A.length fields == 2)
  value <- A.findMap (\(Tuple key ty) -> if ty == Unit then Just key else Nothing) fields
  state <- A.findMap (\(Tuple key ty) -> if ty == Int then Just key else Nothing) fields
  require (value /= state)
  pure { value, state }
layoutOf _ = Nothing

recordType :: Layout -> ExprType -> ExprType -> ExprType
recordType layout value state = Record (Row [ Tuple layout.value value, Tuple layout.state state ] Nothing)

-- A monomorphic helper could add a PHP scalar parameter/return check. Only
-- these genuinely polymorphic signatures may be erased by this first pass.
polymorphic :: NeutralExpr -> Maybe { variable :: ExprType, body :: ExprType }
polymorphic (NeutralExpr (Typed (ForAll [ variable ] body) _)) = Just { variable: TypeVar variable, body }
polymorphic _ = Nothing

-- Inspect the annotation the native emitter will actually use as well as the
-- declaration scheme. A specialized inner annotation must not hide a check.
uncheckedHelper :: NeutralExpr -> Boolean
uncheckedHelper (NeutralExpr (Typed (Func args result) _)) = all nonScalar args && case result of
  -- Helpers return records. Refuse a nested function result without traversing
  -- unbounded type metadata (the expression budget does not count types).
  Func _ _ -> false
  _ -> nonScalar result
  where
  nonScalar Int = false
  nonScalar Number = false
  nonScalar String = false
  nonScalar Boolean = false
  nonScalar _ = true
uncheckedHelper (NeutralExpr (Typed _ inner)) = uncheckedHelper inner
uncheckedHelper _ = true

callArgs :: Qualified Ident -> NeutralExpr -> Maybe (Array NeutralExpr)
callArgs name expr = case peel expr of
  App callee args -> case peel callee of
    Var actual | actual == name -> Just (NEA.toArray args)
    _ -> Nothing
  _ -> Nothing

-- All helpers must be ordinary, nonrecursive declarations in this module.
-- Neither FFI code nor a callback with an unknown body is executed by a proof.
helper :: ModuleName -> Map Ident NeutralExpr -> NeutralExpr -> Maybe NeutralExpr
helper moduleName helpers expr = case peel expr of
  Var (Qualified (Just mn) ident) | mn == moduleName -> Map.lookup ident helpers
  _ -> Nothing

proveGet :: Layout -> NeutralExpr -> Boolean
proveGet layout expr = case proof of
  Just _ -> true
  Nothing -> false
  where
  proof = do
    require (uncheckedHelper expr)
    signature <- polymorphic expr
    require (signature.body == Func [ signature.variable ] (recordType layout signature.variable signature.variable))
    case function expr of
      { params: [ state ], body } -> do
        fields <- record layout body
        require (local state fields.value && local state fields.state)
      _ -> Nothing

proveModify :: ModuleName -> Map Ident NeutralExpr -> Layout -> NeutralExpr -> Maybe Unit
proveModify moduleName helpers layout expr = do
  require (uncheckedHelper expr)
  signature <- polymorphic expr
  require (signature.body == Func [ Func [ signature.variable ] signature.variable, signature.variable ] (recordType layout Unit signature.variable))
  case function expr of
    { params: [ callback, state ], body } -> do
      require (distinct [ callback, state ])
      fields <- record layout body
      require (canonicalUnit fields.value)
      case peel fields.state of
        App callee args | NEA.length args == 1 && local callback callee ->
          case peel (NEA.head args) of
            -- Also accept a helper whose get/record projection was inlined.
            Local _ _ -> require (local state (NEA.head args))
            Accessor result (GetProp key) | key == layout.value -> case peel result of
              App getter inputs | NEA.length inputs == 1 && local state (NEA.head inputs) -> do
                getBody <- helper moduleName helpers getter
                require (proveGet layout getBody)
              _ -> Nothing
            _ -> Nothing
        _ -> Nothing
    _ -> Nothing

proveStep :: Binder -> NeutralExpr -> Maybe Step
proveStep depth expr = do
  require (hasType (Func [ Int ] Int) expr)
  case function expr of
    { params: [ value ], body } -> do
      require (distinct [ depth, value ])
      case peel body of
        PrimOp (Op2 (OpIntNum op) left right) -> do
          require (local value left && (op == OpAdd || op == OpSubtract || op == OpMultiply))
          amount <- integer right
          pure { op, amount }
        _ -> Nothing
    _ -> Nothing

proveBuilder :: ModuleName -> Map Ident NeutralExpr -> Ident -> NeutralExpr -> Maybe Builder
proveBuilder moduleName helpers ident expr = do
  require (bounded nodeBudget expr)
  layout <- layoutOf expr
  case function expr of
    { params: [ depth ], body } -> case peel body of
      Branch branches fallback | NEA.length branches == 1 -> do
        let Pair condition base = NEA.head branches
        case peel condition of
          PrimOp (Op2 (OpIntOrd OpEq) left right) -> require (local depth left && integer right == Just 0)
          _ -> Nothing
        case function base of
          { params: [ state ], body: baseBody } -> do
            require (distinct [ depth, state ])
            fields <- record layout baseBody
            require (canonicalUnit fields.value && local state fields.state)
          _ -> Nothing
        case peel fallback of
          Let name level prepared continuation -> do
            let bound = Tuple name level
            require (distinct [ depth, bound ])
            step <- case peel prepared of
              App callee args | NEA.length args == 1 -> do
                modifyBody <- helper moduleName helpers callee
                proveModify moduleName helpers layout modifyBody
                proveStep depth (NEA.head args)
              _ -> Nothing
            case function continuation of
              { params: [ state ], body: next } -> do
                require (distinct [ depth, bound, state ])
                inputs <- callArgs (Qualified (Just moduleName) ident) next
                case inputs of
                  [ nextDepth, nextState ] -> do
                    case peel nextDepth of
                      PrimOp (Op2 (OpIntNum OpSubtract) left right) -> require (local depth left && integer right == Just 1)
                      _ -> Nothing
                    case peel nextState of
                      Accessor result (GetProp key) | key == layout.state -> case peel result of
                        App callee args -> require (local bound callee && NEA.length args == 1 && local state (NEA.head args))
                        _ -> Nothing
                      _ -> Nothing
                    pure { layout, step }
                  _ -> Nothing
              _ -> Nothing
          _ -> Nothing
      _ -> Nothing
    _ -> Nothing

-- Restrict both inputs to literals or already evaluated lexical Int values.
-- This keeps argument order and excludes recursive/uninitialized captures.
scalar :: Locals -> NeutralExpr -> Boolean
scalar available expr = hasType Int expr && case peel expr of
  Lit (LitInt _) -> true
  Local name level -> Set.member (Tuple name level) available
  _ -> false

-- The original builder checks its first Int argument at the PHP boundary.
-- Retain that check on the guard, but add no Int check to the state/result:
-- repeated arithmetic can produce a PHP float after overflow.
makeGuard :: Qualified Ident -> Qualified Ident -> Layout -> NeutralExpr
makeGuard builder worker layout = typed (Func [ Int, Int ] Any) $ NeutralExpr $ Abs
  (NEA.cons' (Tuple (Just (Ident "depth")) (Level 0)) [ Tuple (Just (Ident "state")) (Level 1) ])
  (NeutralExpr (Branch (NEA.singleton (Pair nonNegative (call worker depth state))) fallback))
  where
  depth = typed Int (NeutralExpr (Local (Just (Ident "depth")) (Level 0)))
  state = typed Int (NeutralExpr (Local (Just (Ident "state")) (Level 1)))
  nonNegative = typed Boolean (NeutralExpr (PrimOp (Op2 (OpIntOrd OpGte) depth (int 0))))
  -- A nested application is deliberately not a candidate on a later scan.
  original = NeutralExpr (App (NeutralExpr (App (NeutralExpr (Var builder)) (NEA.singleton depth))) (NEA.singleton state))
  fallback = NeutralExpr (Accessor original (GetProp layout.state))

type ScanState = { fuel :: Int, workers :: Set Ident, guards :: Set Ident }

scan :: ModuleName -> String -> String -> Map Ident Builder -> Locals -> NeutralExpr -> State ScanState NeutralExpr
scan moduleName prefix guardPrefix builders available expr@(NeutralExpr syntax) = do
  state <- get
  if state.fuel <= 0 then pure expr
  else do
    modify_ (\s -> s { fuel = s.fuel - 1 })
    case candidate of
      Just { ident, depth, initial, dynamic } | Set.member ident state.workers || Set.size state.workers < workerBudget -> do
        modify_ (\s -> s
          { workers = Set.insert ident s.workers
          , guards = if dynamic then Set.insert ident s.guards else s.guards
          })
        let family = if dynamic then guardPrefix else prefix
        pure (typed Int (call (Qualified (Just moduleName) (Ident (family <> unwrap ident))) depth initial))
      _ -> NeutralExpr <$> case syntax of
        Abs args body -> Abs args <$> recur (Set.union available (Set.fromFoldable args)) body
        UncurriedAbs args body -> UncurriedAbs args <$> recur (Set.union available (Set.fromFoldable args)) body
        UncurriedEffectAbs args body -> UncurriedEffectAbs args <$> recur (Set.union available (Set.fromFoldable args)) body
        Let name level value body -> Let name level <$> recur available value <*> recur (Set.insert (Tuple name level) available) body
        EffectBind name level value body -> EffectBind name level <$> recur available value <*> recur (Set.insert (Tuple name level) available) body
        LetRec level bindings body ->
          let outside = foldl (\s (Tuple name _) -> Set.delete (Tuple (Just name) level) s) available bindings
          in LetRec level <$> traverse (traverse (recur outside)) bindings <*> recur outside body
        _ -> traverse (recur available) syntax
  where
  recur = scan moduleName prefix guardPrefix builders
  candidate = case syntax of
    Accessor result (GetProp key) -> case peel result of
      App callee args -> case peel callee, NEA.toArray args of
        Var (Qualified (Just mn) ident), [ depth, initial ] | mn == moduleName -> do
          builder <- Map.lookup ident builders
          require (key == builder.layout.state && scalar available depth && scalar available initial)
          dynamic <- case integer depth of
            Just n -> false <$ require (n >= 0)
            Nothing -> Just true
          pure { ident, depth, initial, dynamic }
        _, _ -> Nothing
      _ -> Nothing
    _ -> Nothing

optimize :: BackendModule -> Result
optimize mod
  | A.length mod.bindings > 256 || A.any (\g -> A.length g.bindings > 64) mod.bindings =
      { module_: mod, privateNames: Set.empty, checkedDepths: Set.empty }
  | otherwise =
      let
        helpers = Map.fromFoldable (A.concatMap (\g -> if g.recursive then [] else A.filter (\(Tuple _ e) -> bounded nodeBudget e) g.bindings) mod.bindings)
        builders = Map.fromFoldable (A.mapMaybe (\g -> case g.recursive, g.bindings of
          true, [ Tuple ident expr ] -> Tuple ident <$> proveBuilder mod.name helpers ident expr
          _, _ -> Nothing) mod.bindings)
      in if Map.isEmpty builders then unchanged else fuseModule mod builders
  where
  unchanged = { module_: mod, privateNames: Set.empty, checkedDepths: Set.empty }

fuseModule :: BackendModule -> Map Ident Builder -> Result
fuseModule mod builders =
  let
    prefix = freshPrefixFor "__phpurs_state_" mod
    guardPrefix = freshPrefixFor "__phpurs_runstate_" mod
    Tuple bindings selected = runState
      (traverse (\g -> do
        bs <- traverse (\(Tuple k e) -> Tuple k <$> if bounded 8192 e then scan mod.name prefix guardPrefix builders Set.empty e else pure e) g.bindings
        pure (g { bindings = bs })) mod.bindings)
      { fuel: 32768, workers: Set.empty, guards: Set.empty }
    renamed ident = Ident (prefix <> unwrap ident)
    guarded ident = Ident (guardPrefix <> unwrap ident)
    workers = A.mapMaybe (\ident -> do
      builder <- Map.lookup ident builders
      pure { recursive: true, bindings: [ Tuple (renamed ident) (makeWorker (Qualified (Just mod.name) (renamed ident)) builder.step) ] })
      (Set.toUnfoldable selected.workers :: Array Ident)
    guards = A.mapMaybe (\ident -> do
      builder <- Map.lookup ident builders
      pure { recursive: false, bindings: [ Tuple (guarded ident) (makeGuard (Qualified (Just mod.name) ident) (Qualified (Just mod.name) (renamed ident)) builder.layout) ] })
      (Set.toUnfoldable selected.guards :: Array Ident)
  in
    { module_: mod { bindings = bindings <> workers <> guards }
    , privateNames: Set.union (Set.map renamed selected.workers) (Set.map guarded selected.guards)
    , checkedDepths: Set.map guarded selected.guards
    }
