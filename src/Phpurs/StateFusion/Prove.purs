-- | Proof for an immediately executed State chain. The record layout, helpers
-- | and callback bodies are evidence, never their names.
module Phpurs.StateFusion.Prove (proveBuilder) where

import Prelude

import Data.Array as A
import Data.Array.NonEmpty as NEA
import Data.Foldable (all)
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Tuple (Tuple(..))
import Phpurs.Budget as Budget
import Phpurs.Optimize.NeutralExpr
  ( Binder
  , bounded
  , hasType
  , int
  , integer
  , isLocalBinder
  , peel
  , require
  , typed
  )
import Phpurs.Optimize.ScalarWorker (Step)
import Phpurs.StateFusion.Types (Builder, Layout)
import PureScript.Backend.Optimizer.CoreFn
  ( ExprType(..)
  , Ident(..)
  , Literal(..)
  , ModuleName(..)
  , Prop(..)
  , Qualified(..)
  )
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax
  ( BackendAccessor(..)
  , BackendOperator(..)
  , BackendOperator2(..)
  , BackendOperatorNum(..)
  , BackendOperatorOrd(..)
  , BackendSyntax(..)
  , Pair(..)
  )

distinct :: Array Binder -> Boolean
distinct binders = A.length (A.nub (map (\(Tuple _ level) -> level) binders)) == A.length binders

function :: NeutralExpr -> { params :: Array Binder, body :: NeutralExpr }
function expr = case peel expr of
  Abs args body ->
    let
      rest = function body
    in
      rest { params = NEA.toArray args <> rest.params }
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
recordType layout value state = Record
  (Row [ Tuple layout.value value, Tuple layout.state state ] Nothing)

-- A monomorphic helper could add a PHP scalar parameter/return check. Only
-- these genuinely polymorphic signatures may be erased by this first pass.
polymorphic :: NeutralExpr -> Maybe { variable :: ExprType, body :: ExprType }
polymorphic (NeutralExpr (Typed (ForAll [ variable ] body) _)) = Just
  { variable: TypeVar variable, body }
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
    require
      ( signature.body == Func [ signature.variable ]
          (recordType layout signature.variable signature.variable)
      )
    case function expr of
      { params: [ state ], body } -> do
        fields <- record layout body
        require (isLocalBinder state fields.value && isLocalBinder state fields.state)
      _ -> Nothing

proveModify :: ModuleName -> Map Ident NeutralExpr -> Layout -> NeutralExpr -> Maybe Unit
proveModify moduleName helpers layout expr = do
  require (uncheckedHelper expr)
  signature <- polymorphic expr
  require
    ( signature.body == Func [ Func [ signature.variable ] signature.variable, signature.variable ]
        (recordType layout Unit signature.variable)
    )
  case function expr of
    { params: [ callback, state ], body } -> do
      require (distinct [ callback, state ])
      fields <- record layout body
      require (canonicalUnit fields.value)
      case peel fields.state of
        App callee args | NEA.length args == 1 && isLocalBinder callback callee ->
          case peel (NEA.head args) of
            -- Also accept a helper whose get/record projection was inlined.
            Local _ _ -> require (isLocalBinder state (NEA.head args))
            Accessor result (GetProp key) | key == layout.value -> case peel result of
              App getter inputs | NEA.length inputs == 1 && isLocalBinder state (NEA.head inputs) ->
                do
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
          require
            (isLocalBinder value left && (op == OpAdd || op == OpSubtract || op == OpMultiply))
          amount <- integer right
          pure { op, amount }
        _ -> Nothing
    _ -> Nothing

proveBuilder :: ModuleName -> Map Ident NeutralExpr -> Ident -> NeutralExpr -> Maybe Builder
proveBuilder moduleName helpers ident expr = do
  require (bounded Budget.proofNodes expr)
  layout <- layoutOf expr
  case function expr of
    { params: [ depth ], body } -> case peel body of
      Branch branches fallback | NEA.length branches == 1 -> do
        let Pair condition base = NEA.head branches
        case peel condition of
          PrimOp (Op2 (OpIntOrd OpEq) left right) -> require
            (isLocalBinder depth left && integer right == Just 0)
          _ -> Nothing
        case function base of
          { params: [ state ], body: baseBody } -> do
            require (distinct [ depth, state ])
            fields <- record layout baseBody
            require (canonicalUnit fields.value && isLocalBinder state fields.state)
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
                      PrimOp (Op2 (OpIntNum OpSubtract) left right) -> require
                        (isLocalBinder depth left && integer right == Just 1)
                      _ -> Nothing
                    case peel nextState of
                      Accessor result (GetProp key) | key == layout.state -> case peel result of
                        App callee args -> require
                          ( isLocalBinder bound callee && NEA.length args == 1 && isLocalBinder
                              state
                              (NEA.head args)
                          )
                        _ -> Nothing
                      _ -> Nothing
                    pure { layout, step }
                  _ -> Nothing
              _ -> Nothing
          _ -> Nothing
      _ -> Nothing
    _ -> Nothing
