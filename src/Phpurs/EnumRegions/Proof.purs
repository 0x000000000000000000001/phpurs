-- | Proof that a closed scalar region can use private layouts. Types, uses and
-- | call sites are checked on the typed optimizer AST.
module Phpurs.EnumRegions.Proof (compactLayout, infer, isEnum, scalar) where

import Prelude

import Control.Monad.State (get, modify_)
import Control.Monad.Trans.Class (lift)
import Data.Array as A
import Data.Array.NonEmpty as NEA
import Data.Foldable (all, foldl, traverse_)
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), isJust)
import Data.Set (Set)
import Data.Set as Set
import Data.Tuple (Tuple(..), snd)
import Phpurs.Budget as Budget
import Phpurs.EnumRegions.Types (Check, CheckState, Constructor, Context, initial, regionNodeCount)
import Phpurs.NullableConstructors as Nullable
import Phpurs.Optimize.NeutralExpr (peel, signature)
import PureScript.Backend.Optimizer.CoreFn
  ( ConstructorType(..)
  , DataDecl
  , ExprType(..)
  , Ident
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
  , BackendSyntax(..)
  , Level
  , Pair(..)
  )
import PureScript.Backend.Optimizer.Syntax as Syn

requireCheck :: Boolean -> Check Unit
requireCheck b = lift if b then Just unit else Nothing

tick :: Check Unit
tick = do
  s <- get
  requireCheck (s.fuel > 0)
  modify_ (\v -> v { fuel = v.fuel - 1 })

scalar :: ExprType -> Boolean
scalar = case _ of
  Int -> true
  Number -> true
  String -> true
  Char -> true
  Boolean -> true
  _ -> false

isEnum :: DataDecl -> Boolean
isEnum d = A.null d.vars && A.length d.constructors >= 2 && all (A.null <<< _.fields) d.constructors

compactLayout :: DataDecl -> Boolean
compactLayout d = isEnum d || isJust (Nullable.layout d)

-- Only closed, monomorphic local layouts with scalar/ADT fields are admitted.
-- Function fields, records, unknown types and open polymorphism fail closed.
valueType :: Context -> ExprType -> Check Unit
valueType _ ty | scalar ty = pure unit
valueType ctx (ADT name _ args) = do
  requireCheck (A.null args)
  d <- lift (Map.lookup name ctx.layouts)
  requireCheck (A.null d.vars)
  s <- get
  unless (Set.member name s.layouts) do
    tick
    modify_
      ( \v -> v
          { layouts = Set.insert name v.layouts
          , usesCompactLayout = v.usesCompactLayout || compactLayout d
          }
      )
    traverse_ (traverse_ (valueType ctx) <<< _.fields) d.constructors
valueType _ _ = requireCheck false

functionBody :: NeutralExpr -> { params :: Array (Tuple (Maybe Ident) Level), body :: NeutralExpr }
functionBody expr = case peel expr of
  Abs args body -> let rest = functionBody body in rest { params = NEA.toArray args <> rest.params }
  UncurriedAbs args body -> let rest = functionBody body in rest { params = args <> rest.params }
  _ -> { params: [], body: expr }

constructor :: Context -> Qualified Ident -> Check Constructor
constructor ctx q = do
  c <- lift (Map.lookup q ctx.constructors)
  valueType ctx c.type_
  pure c

worker :: Context -> Qualified Ident -> Check { args :: Array ExprType, ret :: ExprType }
worker ctx (Qualified mn ident) = do
  requireCheck (mn == Just ctx.name)
  expr <- lift (Map.lookup ident ctx.bindings)
  sig <- lift (signature expr)
  traverse_ (valueType ctx) (sig.args <> [ sig.ret ])
  let fn = functionBody expr
  requireCheck (not (A.null sig.args) && A.length sig.args == A.length fn.params)
  s <- get
  unless (Set.member ident s.workers) do
    requireCheck (Set.size s.workers < Budget.workerLimit)
    requireCheck (regionNodeCount (Budget.regionNodes + 1) expr <= s.fuel)
    modify_ (\v -> v { workers = Set.insert ident v.workers })
    let
      env = Map.fromFoldable (A.zipWith (\(Tuple _ level) ty -> Tuple level ty) fn.params sig.args)
    result <- infer ctx env false (Just sig.ret) fn.body
    requireCheck (result == sig.ret)
  pure sig

-- Local variables not bound inside the region are its inputs. They must have
-- a scalar type established by the TAST; an ADT from a public caller is refused.
infer :: Context -> Map Level ExprType -> Boolean -> Maybe ExprType -> NeutralExpr -> Check ExprType
infer ctx env freeScalar expected (NeutralExpr syntax) = do
  tick
  state <- get
  requireCheck (state.depth < Budget.inferDepth)
  modify_ (\v -> v { depth = v.depth + 1 })
  ty <- case syntax of
    Typed ty inner -> do
      valueType ctx ty
      result <- infer ctx env freeScalar (Just ty) inner
      requireCheck (result == ty)
      pure ty
    Syn.TypeApp inner _ -> infer ctx env freeScalar expected inner
    Local _ level -> case Map.lookup level env of
      Just ty -> pure ty
      Nothing -> do
        ty <- lift expected
        requireCheck (freeScalar && scalar ty)
        pure ty
    Lit (LitInt _) -> pure Int
    Lit (LitNumber _) -> pure Number
    Lit (LitString _) -> pure String
    Lit (LitChar _) -> pure Char
    Lit (LitBoolean _) -> pure Boolean
    Var q -> do
      c <- constructor ctx q
      requireCheck (A.null c.fields)
      pure c.type_
    CtorSaturated q _ _ _ fields -> do
      c <- constructor ctx q
      checkArgs c.fields (map snd fields)
      pure c.type_
    Accessor value (GetCtorField q _ _ _ _ index) -> do
      c <- constructor ctx q
      checkAs c.type_ value
      lift (A.index c.fields index)
    App fn args -> call fn (NEA.toArray args)
    UncurriedApp fn args -> call fn args
    Let _ level value body -> do
      ty <- infer ctx env freeScalar Nothing value
      infer ctx (Map.insert level ty env) freeScalar expected body
    Branch branches fallback -> do
      let Pair cond first = NEA.head branches
      checkAs Boolean cond
      result <- infer ctx env freeScalar expected first
      traverse_ (\(Pair c b) -> checkAs Boolean c *> checkAs result b)
        (A.drop 1 (NEA.toArray branches))
      checkAs result fallback
      pure result
    PrimOp (Op1 (OpIsTag q) value) -> do
      c <- constructor ctx q
      checkAs c.type_ value
      pure Boolean
    PrimOp (Op1 op value) -> case op of
      OpBooleanNot -> checkAs Boolean value $> Boolean
      OpIntBitNot -> checkAs Int value $> Int
      OpIntNegate -> checkAs Int value $> Int
      OpNumberNegate -> checkAs Number value $> Number
      _ -> lift Nothing
    PrimOp (Op2 op left right) -> do
      Tuple arg result <- lift (binaryTypes op)
      checkAs arg left
      checkAs arg right
      pure result
    Fail _ -> lift expected
    _ -> lift Nothing
  traverse_ (\wanted -> requireCheck (wanted == ty)) expected
  modify_ (\v -> v { depth = v.depth - 1 })
  pure ty
  where
  checkAs ty expr = do
    result <- infer ctx env freeScalar (Just ty) expr
    requireCheck (result == ty)
  checkArgs types args = do
    requireCheck (A.length types == A.length args)
    traverse_ (\(Tuple ty expr) -> checkAs ty expr) (A.zip types args)
  call fn args = case peel fn of
    Var q -> do
      sig <- worker ctx q
      checkArgs sig.args args
      pure sig.ret
    _ -> lift Nothing

binaryTypes :: BackendOperator2 -> Maybe (Tuple ExprType ExprType)
binaryTypes = case _ of
  OpBooleanAnd -> Just (Tuple Boolean Boolean)
  OpBooleanOr -> Just (Tuple Boolean Boolean)
  OpBooleanOrd _ -> Just (Tuple Boolean Boolean)
  OpCharOrd _ -> Just (Tuple Char Boolean)
  OpIntBitAnd -> ints
  OpIntBitOr -> ints
  OpIntBitXor -> ints
  OpIntBitShiftLeft -> ints
  OpIntBitShiftRight -> ints
  OpIntBitZeroFillShiftRight -> ints
  OpIntNum _ -> ints
  OpIntOrd _ -> Just (Tuple Int Boolean)
  OpNumberNum _ -> Just (Tuple Number Number)
  OpNumberOrd _ -> Just (Tuple Number Boolean)
  OpStringAppend -> Just (Tuple String String)
  OpStringOrd _ -> Just (Tuple String Boolean)
  _ -> Nothing
  where
  ints = Just (Tuple Int Int)
