-- Closed scalar computations can use private ADT layouts. Work on the typed
-- optimizer AST so the proof and the rewritten call sites cannot drift apart.
module Phpurs.EnumRegions where

import Prelude

import Control.Monad.State (State, StateT, get, modify_, runState, runStateT)
import Control.Monad.Trans.Class (lift)
import Data.Array as A
import Data.Array.NonEmpty as NEA
import Data.Foldable (all, foldl, foldr, traverse_)
import Data.List (List(..))
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Newtype (unwrap)
import Data.Set (Set)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..), snd)
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (ConstructorType(..), DataDecl, ExprType(..), Ident(..), Literal(..), ModuleName, ProperName(..), Qualified(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendAccessor(..), BackendOperator(..), BackendOperator1(..), BackendOperator2(..), BackendOperatorOrd(..), BackendSyntax(..), Level, Pair(..))
import PureScript.Backend.Optimizer.Syntax as Syn

nodeBudget :: Int
nodeBudget = 8192

workerBudget :: Int
workerBudget = 32

type Context =
  { name :: ModuleName
  , bindings :: Map Ident NeutralExpr
  , layouts :: Map String DataDecl
  , constructors :: Map (Qualified Ident) Constructor
  }

type Constructor =
  { type_ :: ExprType, fields :: Array ExprType, tag :: Maybe Int
  , constructorType :: ConstructorType, typeName :: ProperName
  }

type CheckState = { fuel :: Int, depth :: Int, workers :: Set Ident, layouts :: Set String, usesEnum :: Boolean }
type Check a = StateT CheckState Maybe a

initial :: CheckState
initial = { fuel: nodeBudget, depth: 0, workers: Set.empty, layouts: Set.empty, usesEnum: false }

require :: Boolean -> Check Unit
require b = lift if b then Just unit else Nothing

tick :: Check Unit
tick = do
  s <- get
  require (s.fuel > 0)
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

-- Only closed, monomorphic local layouts with scalar/ADT fields are admitted.
-- Function fields, records, unknown types and open polymorphism fail closed.
valueType :: Context -> ExprType -> Check Unit
valueType _ ty | scalar ty = pure unit
valueType ctx (ADT name _ args) = do
  require (A.null args)
  d <- lift (Map.lookup name ctx.layouts)
  require (A.null d.vars)
  s <- get
  unless (Set.member name s.layouts) do
    tick
    modify_ (\v -> v { layouts = Set.insert name v.layouts, usesEnum = v.usesEnum || isEnum d })
    traverse_ (traverse_ (valueType ctx) <<< _.fields) d.constructors
valueType _ _ = require false

peel :: NeutralExpr -> BackendSyntax NeutralExpr
peel (NeutralExpr (Typed _ e)) = peel e
peel (NeutralExpr (Syn.TypeApp e _)) = peel e
peel (NeutralExpr s) = s

signature :: NeutralExpr -> Maybe { args :: Array ExprType, ret :: ExprType }
signature (NeutralExpr (Typed (Func args ret) _)) = Just { args, ret }
signature (NeutralExpr (Typed _ e)) = signature e
signature (NeutralExpr (Syn.TypeApp e _)) = signature e
signature _ = Nothing

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
  require (mn == Just ctx.name)
  expr <- lift (Map.lookup ident ctx.bindings)
  sig <- lift (signature expr)
  traverse_ (valueType ctx) (sig.args <> [ sig.ret ])
  let fn = functionBody expr
  require (not (A.null sig.args) && A.length sig.args == A.length fn.params)
  s <- get
  unless (Set.member ident s.workers) do
    require (Set.size s.workers < workerBudget)
    require (size (nodeBudget + 1) expr <= s.fuel)
    modify_ (\v -> v { workers = Set.insert ident v.workers })
    let env = Map.fromFoldable (A.zipWith (\(Tuple _ level) ty -> Tuple level ty) fn.params sig.args)
    result <- infer ctx env false (Just sig.ret) fn.body
    require (result == sig.ret)
  pure sig

-- Local variables not bound inside the region are its inputs. They must have
-- a scalar type established by the TAST; an ADT from a public caller is refused.
infer :: Context -> Map Level ExprType -> Boolean -> Maybe ExprType -> NeutralExpr -> Check ExprType
infer ctx env freeScalar expected (NeutralExpr syntax) = do
  tick
  state <- get
  require (state.depth < 128)
  modify_ (\v -> v { depth = v.depth + 1 })
  ty <- case syntax of
    Typed ty inner -> do
      valueType ctx ty
      result <- infer ctx env freeScalar (Just ty) inner
      require (result == ty)
      pure ty
    Syn.TypeApp inner _ -> infer ctx env freeScalar expected inner
    Local _ level -> case Map.lookup level env of
      Just ty -> pure ty
      Nothing -> do
        ty <- lift expected
        require (freeScalar && scalar ty)
        pure ty
    Lit (LitInt _) -> pure Int
    Lit (LitNumber _) -> pure Number
    Lit (LitString _) -> pure String
    Lit (LitChar _) -> pure Char
    Lit (LitBoolean _) -> pure Boolean
    Var q -> do
      c <- constructor ctx q
      require (A.null c.fields)
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
      traverse_ (\(Pair c b) -> checkAs Boolean c *> checkAs result b) (A.drop 1 (NEA.toArray branches))
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
  traverse_ (\wanted -> require (wanted == ty)) expected
  modify_ (\v -> v { depth = v.depth - 1 })
  pure ty
  where
  checkAs ty expr = do
    result <- infer ctx env freeScalar (Just ty) expr
    require (result == ty)
  checkArgs types args = do
    require (A.length types == A.length args)
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

context :: BackendModule -> Context
context mod =
  { name: mod.name
  , bindings: Map.fromFoldable (A.concatMap _.bindings mod.bindings)
  , layouts: Map.fromFoldable (map (\d -> Tuple (full d.name) d) mod.dataDecls)
  , constructors: Map.fromFoldable (A.concatMap (\d -> A.mapWithIndex (\i c ->
      Tuple (Qualified (Just mod.name) (Ident c.name))
        { type_: ADT (full d.name) (String.split (Pattern ".") (full d.name)) []
        , fields: c.fields
        , tag: if isEnum d then Just i else Nothing
        , constructorType: if A.length d.constructors == 1 then ProductType else SumType
        , typeName: ProperName d.name
        }) d.constructors) mod.dataDecls)
  }
  where
  full name = unwrap mod.name <> "." <> name

renameType :: forall r. Context -> String -> { workers :: Set Ident, layouts :: Set String | r } -> ExprType -> ExprType
renameType ctx prefix selected ty = case ty of
  ADT name _ args | Set.member name selected.layouts -> case Map.lookup name ctx.layouts of
    Just d | isEnum d -> Int
    Just d -> let full = unwrap ctx.name <> "." <> prefix <> d.name
      in ADT full (String.split (Pattern ".") full) (map recur args)
    _ -> ty
  Func args ret -> Func (map recur args) (recur ret)
  _ -> ty
  where
  recur = renameType ctx prefix selected

-- This same structural rewrite applies to a proven entry expression and to
-- copies of its complete call graph. Original workers/layouts are kept intact.
rewrite :: forall r. Context -> String -> { workers :: Set Ident, layouts :: Set String | r } -> NeutralExpr -> NeutralExpr
rewrite ctx prefix selected (NeutralExpr syntax) = NeutralExpr case map recur syntax of
  Typed ty inner -> Typed (renameType ctx prefix selected ty) inner
  Syn.TypeApp inner ty -> Syn.TypeApp inner (renameType ctx prefix selected ty)
  Var (Qualified mn ident) | mn == Just ctx.name && Set.member ident selected.workers ->
    Var (Qualified mn (Ident (prefix <> unwrap ident)))
  Var q -> case Map.lookup q ctx.constructors of
    Just c | A.null c.fields -> case c.tag of
      Just tag -> Lit (LitInt tag)
      _ -> let renamed@(Qualified _ ident) = renameCtor q
        in CtorSaturated renamed c.constructorType (renameProper c.typeName) ident []
    _ -> Var q
  CtorSaturated q ct pn ident fields -> case Map.lookup q ctx.constructors >>= _.tag of
    Just tag -> Lit (LitInt tag)
    _ -> CtorSaturated (renameCtor q) ct (renameProper pn) (renameIdent ident) fields
  PrimOp (Op1 (OpIsTag q) value) -> case Map.lookup q ctx.constructors >>= _.tag of
    Just tag -> PrimOp (Op2 (OpIntOrd OpEq) value (NeutralExpr (Lit (LitInt tag))))
    _ -> PrimOp (Op1 (OpIsTag (renameCtor q)) value)
  Accessor value (GetCtorField q ct pn ident field index) ->
    Accessor value (GetCtorField (renameCtor q) ct (renameProper pn) (renameIdent ident) field index)
  other -> other
  where
  recur = rewrite ctx prefix selected
  renameIdent (Ident name) = Ident (prefix <> name)
  renameProper (ProperName name) = ProperName (prefix <> name)
  renameCtor q@(Qualified mn ident) = case Map.lookup q ctx.constructors of
    Just { type_: ADT name _ _ } | Set.member name selected.layouts -> Qualified mn (renameIdent ident)
    _ -> q

type ScanState =
  { fuel :: Int, attempts :: Int, workers :: Set Ident, layouts :: Set String, copied :: Int }

-- Explicit work list: even a rejected deep/wide body must not exhaust the JS
-- stack before its budget can be checked. The same bound protects State walks.
size :: Int -> NeutralExpr -> Int
size cap expr = go 0 (Cons (Tuple 0 expr) Nil)
  where
  go count _ | count >= cap = cap
  go count Nil = count
  go count (Cons (Tuple depth (NeutralExpr syntax)) rest)
    | depth > 128 || foldl (\n _ -> n + 1) 0 syntax > 64 = cap
    | otherwise = go (count + 1) (foldr (\child acc -> Cons (Tuple (depth + 1) child) acc) rest syntax)

scan :: Context -> String -> NeutralExpr -> State ScanState NeutralExpr
scan ctx prefix expr@(NeutralExpr syntax) = do
  s <- get
  if s.fuel <= 0 then pure expr
  else do
    modify_ (\v -> v { fuel = v.fuel - 1 })
    let candidate = case syntax of
          Typed ty inner | scalar ty -> case peel inner of
            App _ _ -> true
            UncurriedApp _ _ -> true
            _ -> false
          _ -> false
    result <- if candidate && s.attempts > 0 then do
      modify_ (\v -> v { attempts = v.attempts - 1 })
      pure (runStateT (infer ctx Map.empty true Nothing expr) initial)
      else pure Nothing
    case result of
      Just (Tuple ty proof) | scalar ty && proof.usesEnum && not (Set.isEmpty proof.workers) -> do
        let added = Set.difference proof.workers s.workers
        let copied = s.copied + foldl (\n ident -> n + fromMaybe 0 (size (nodeBudget + 1) <$> Map.lookup ident ctx.bindings)) 0 added
        let workers = Set.union s.workers proof.workers
        let layouts = Set.union s.layouts proof.layouts
        if copied <= nodeBudget && Set.size workers <= workerBudget then do
          modify_ (\v -> v { workers = workers, layouts = layouts, copied = copied })
          pure (rewrite ctx prefix proof expr)
        else descend
      _ -> descend
  where
  descend = NeutralExpr <$> traverse (scan ctx prefix) syntax

freshPrefix :: BackendModule -> String
freshPrefix mod = go 0
  where
  names = map (String.toLower <<< unwrap) (A.concatMap (map (\(Tuple k _) -> k) <<< _.bindings) mod.bindings)
    <> map String.toLower (A.concatMap (map _.name <<< _.constructors) mod.dataDecls)
    <> map (String.toLower <<< unwrap) (Set.toUnfoldable (Map.keys mod.foreign) :: Array Ident)
  go n = let prefix = "__phpurs_enum_" <> show n <> "_"
    in if A.any (String.contains (Pattern prefix)) names then go (n + 1) else prefix

optimize :: BackendModule -> { module_ :: BackendModule, privateNames :: Set Ident, privateConstructors :: Set String }
optimize mod
  | not (A.any isEnum mod.dataDecls)
      || A.length mod.dataDecls > 64
      || A.length mod.bindings > 256
      || A.any (\d -> A.length d.constructors > 64 || A.any (\c -> A.length c.fields > 64) d.constructors) mod.dataDecls =
      { module_: mod, privateNames: Set.empty, privateConstructors: Set.empty }
  | otherwise =
      let
        ctx = context mod
        prefix = freshPrefix mod
        Tuple bindings selected = runState
          (traverse (\g -> do
            bs <- traverse (\(Tuple k e) -> Tuple k <$> if size (nodeBudget + 1) e <= nodeBudget then scan ctx prefix e else pure e) g.bindings
            pure (g { bindings = bs })) mod.bindings)
          { fuel: 32768, attempts: 64, workers: Set.empty, layouts: Set.empty, copied: 0 }
        renamed ident = Ident (prefix <> unwrap ident)
        copies = A.mapMaybe (\g ->
          let bs = A.mapMaybe (\(Tuple k e) -> if Set.member k selected.workers
                then Just (Tuple (renamed k) (rewrite ctx prefix selected e)) else Nothing) g.bindings
          in if A.null bs then Nothing else Just (g { bindings = bs })) mod.bindings
        layouts = A.mapMaybe (\d -> if Set.member (unwrap mod.name <> "." <> d.name) selected.layouts && not (isEnum d)
          then Just (d { name = prefix <> d.name, constructors = map (\c -> c { name = prefix <> c.name, fields = map (renameType ctx prefix selected) c.fields }) d.constructors })
          else Nothing) mod.dataDecls
      in
        { module_: mod { bindings = bindings <> copies, dataDecls = mod.dataDecls <> layouts }
        , privateNames: Set.map renamed selected.workers
        , privateConstructors: Set.fromFoldable (A.concatMap (map _.name <<< _.constructors) layouts)
        }
