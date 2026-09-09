-- Fuse an immediately forced, closed chain into a scalar TCO worker. This is
-- deliberately narrower than a type-only escape analysis: a foreign callback
-- can observe the removed stack frames even with a Unit -> Int signature.
module Phpurs.ThunkFusion where

import Prelude

import Control.Monad.State (State, get, modify_, runState)
import Data.Array as A
import Data.Array.NonEmpty as NEA
import Data.Foldable (foldl, foldr)
import Data.List (List(..))
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
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident(..), Literal(..), ModuleName(..), Qualified(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendOperator(..), BackendOperator2(..), BackendOperatorNum(..), BackendOperatorOrd(..), BackendSyntax(..), Level(..), Pair(..))
import PureScript.Backend.Optimizer.Syntax as Syn

nodeBudget :: Int
nodeBudget = 1024

workerBudget :: Int
workerBudget = 32

thunkType :: ExprType
thunkType = Func [ Unit ] Int

type Step = { op :: BackendOperatorNum, amount :: Int }

require :: Boolean -> Maybe Unit
require true = Just unit
require false = Nothing

-- All recursive walks below run only after this stack-safe size/depth check.
bounded :: Int -> NeutralExpr -> Boolean
bounded cap expr = go 0 (Cons (Tuple 0 expr) Nil)
  where
  go count _ | count > cap = false
  go _ Nil = true
  go count (Cons (Tuple depth (NeutralExpr syntax)) rest)
    | depth > 96 || foldl (\n _ -> n + 1) 0 syntax > 64 = false
    | otherwise = go (count + 1) (foldr (\child acc -> Cons (Tuple (depth + 1) child) acc) rest syntax)

peel :: NeutralExpr -> BackendSyntax NeutralExpr
peel (NeutralExpr (Typed _ e)) = peel e
peel (NeutralExpr (Syn.TypeApp e _)) = peel e
peel (NeutralExpr syntax) = syntax

hasType :: ExprType -> NeutralExpr -> Boolean
hasType ty (NeutralExpr (Typed actual _)) = ty == actual
hasType ty (NeutralExpr (Syn.TypeApp e _)) = hasType ty e
hasType _ _ = false

paramsAndBody :: NeutralExpr -> { params :: Array Level, body :: NeutralExpr }
paramsAndBody expr = case peel expr of
  Abs args body -> let rest = paramsAndBody body
    in rest { params = map (\(Tuple _ level) -> level) (NEA.toArray args) <> rest.params }
  _ -> { params: [], body: expr }

local :: Level -> NeutralExpr -> Boolean
local level expr = case peel expr of
  Local _ actual -> level == actual
  _ -> false

integer :: NeutralExpr -> Maybe Int
integer expr = case peel expr of
  Lit (LitInt n) -> Just n
  _ -> Nothing

-- Only the canonical pure Unit value is erased. An arbitrary Unit expression
-- could evaluate foreign code, throw, or inspect the surrounding stack.
pureUnit :: NeutralExpr -> Boolean
pureUnit expr = hasType Unit expr && case peel expr of
  Var (Qualified (Just (ModuleName "Data.Unit")) (Ident "unit")) -> true
  _ -> false

isCall :: Qualified Ident -> NeutralExpr -> Maybe (Array NeutralExpr)
isCall name expr = case peel expr of
  App fn args -> case peel fn of
    Var actual | actual == name -> Just (NEA.toArray args)
    _ -> Nothing
  _ -> Nothing

-- Runtime arity is two; the third flattened TAST argument belongs to the
-- returned function. Both the signature and the actual binders are checked.
proveBuilder :: Qualified Ident -> NeutralExpr -> Maybe Step
proveBuilder name expr = do
  require (bounded nodeBudget expr)
  require (hasType (Func [ Int, thunkType, Unit ] Int) expr)
  let fn = paramsAndBody expr
  case fn.params, peel fn.body of
    [ depth, acc ], Branch branches fallback -> do
      require (depth /= acc && NEA.length branches == 1 && hasType thunkType fn.body)
      let Pair condition base = NEA.head branches
      case peel condition of
        PrimOp (Op2 (OpIntOrd OpEq) left right) -> do
          require (local depth left && integer right == Just 0)
        _ -> Nothing
      require (local acc base)
      args <- isCall name fallback
      case args of
        [ nextDepth, nextAcc ] -> do
          case peel nextDepth of
            PrimOp (Op2 (OpIntNum OpSubtract) left right) ->
              require (local depth left && integer right == Just 1)
            _ -> Nothing
          require (hasType thunkType nextAcc)
          let thunk = paramsAndBody nextAcc
          require (A.length thunk.params == 1 && not (A.elem depth thunk.params) && not (A.elem acc thunk.params))
          require (hasType Int thunk.body)
          case peel thunk.body of
            PrimOp (Op2 (OpIntNum op) forced amount) -> do
              require (op == OpAdd || op == OpSubtract || op == OpMultiply)
              case peel forced of
                App callee inputs -> require (local acc callee && NEA.length inputs == 1 && pureUnit (NEA.head inputs))
                _ -> Nothing
              n <- integer amount
              pure { op, amount: n }
            _ -> Nothing
        _ -> Nothing
    _, _ -> Nothing

-- A literal-returning seed has no captures, calls, exceptions or stack
-- observations. Merely knowing the seed's function type is insufficient.
seedValue :: NeutralExpr -> Maybe Int
seedValue expr = do
  require (hasType thunkType expr)
  let fn = paramsAndBody expr
  require (A.length fn.params == 1 && hasType Int fn.body)
  integer fn.body

typed :: ExprType -> NeutralExpr -> NeutralExpr
typed ty = NeutralExpr <<< Typed ty

int :: Int -> NeutralExpr
int = typed Int <<< NeutralExpr <<< Lit <<< LitInt

call :: Qualified Ident -> NeutralExpr -> NeutralExpr -> NeutralExpr
call name depth value = NeutralExpr (App (NeutralExpr (Var name)) (NEA.cons' depth [ value ]))

-- Keep one arithmetic operation per original thunk, including PHP's overflow
-- and floating-point rounding behavior. No multiplication by the chain length.
makeWorker :: Qualified Ident -> Step -> NeutralExpr
makeWorker name step = typed (Func [ Int, Int ] Int) $ NeutralExpr $ Abs
  (NEA.cons' (Tuple (Just (Ident "remaining")) (Level 0)) [ Tuple (Just (Ident "value")) (Level 1) ])
  (typed Int (NeutralExpr (Branch
    (NEA.singleton (Pair (binary (OpIntOrd OpEq) remaining (int 0)) value))
    (typed Int (call name
      (binary (OpIntNum OpSubtract) remaining (int 1))
      (binary (OpIntNum step.op) value (int step.amount)))))))
  where
  remaining = typed Int (NeutralExpr (Local (Just (Ident "remaining")) (Level 0)))
  value = typed Int (NeutralExpr (Local (Just (Ident "value")) (Level 1)))
  binary op left right = NeutralExpr (PrimOp (Op2 op left right))

type ScanState = { fuel :: Int, workers :: Set Ident }

scan :: ModuleName -> String -> Map Ident Step -> NeutralExpr -> State ScanState NeutralExpr
scan moduleName prefix builders expr@(NeutralExpr syntax) = do
  state <- get
  if state.fuel <= 0 then pure expr
  else do
    modify_ (\s -> s { fuel = s.fuel - 1 })
    case candidate of
      Just { ident, depth, seed } | Set.member ident state.workers || Set.size state.workers < workerBudget -> do
        modify_ (\s -> s { workers = Set.insert ident s.workers })
        pure (typed Int (call (Qualified (Just moduleName) (Ident (prefix <> unwrap ident))) (int depth) (int seed)))
      _ -> NeutralExpr <$> traverse (scan moduleName prefix builders) syntax
  where
  candidate = case syntax of
    App fn args -> case peel fn, NEA.toArray args of
      Var (Qualified (Just mn) ident), [ count, initial, input ] | mn == moduleName -> do
        _ <- Map.lookup ident builders
        depth <- integer count
        require (hasType Int count && depth >= 0 && pureUnit input)
        seed <- seedValue initial
        pure { ident, depth, seed }
      _, _ -> Nothing
    _ -> Nothing

freshPrefix :: BackendModule -> String
freshPrefix mod = go 0
  where
  names = map (String.toLower <<< unwrap) (A.concatMap (map (\(Tuple k _) -> k) <<< _.bindings) mod.bindings)
    <> map String.toLower (A.concatMap (map _.name <<< _.constructors) mod.dataDecls)
    <> map (String.toLower <<< unwrap) (Set.toUnfoldable (Map.keys mod.foreign) :: Array Ident)
  go n = let prefix = "__phpurs_fuse_" <> show n <> "_"
    in if A.any (String.contains (Pattern prefix)) names then go (n + 1) else prefix

optimize :: BackendModule -> { module_ :: BackendModule, privateNames :: Set Ident }
optimize mod
  | A.length mod.bindings > 256 || A.any (\g -> A.length g.bindings > 64) mod.bindings =
      { module_: mod, privateNames: Set.empty }
  | otherwise =
      let
        builders = Map.fromFoldable (A.mapMaybe (\g -> case g.recursive, g.bindings of
          true, [ Tuple ident expr ] -> Tuple ident <$> proveBuilder (Qualified (Just mod.name) ident) expr
          _, _ -> Nothing) mod.bindings)
      in if Map.isEmpty builders then { module_: mod, privateNames: Set.empty }
        else fuseModule mod builders

fuseModule :: BackendModule -> Map Ident Step -> { module_ :: BackendModule, privateNames :: Set Ident }
fuseModule mod builders =
  let
    prefix = freshPrefix mod
    Tuple bindings selected = runState
      (traverse (\g -> do
        bs <- traverse (\(Tuple k e) -> Tuple k <$> if bounded 8192 e then scan mod.name prefix builders e else pure e) g.bindings
        pure (g { bindings = bs })) mod.bindings)
      { fuel: 32768, workers: Set.empty }
    renamed ident = Ident (prefix <> unwrap ident)
    copies = A.mapMaybe (\ident -> do
      step <- Map.lookup ident builders
      let name = renamed ident
      pure { recursive: true, bindings: [ Tuple name (makeWorker (Qualified (Just mod.name) name) step) ] })
      (Set.toUnfoldable selected.workers :: Array Ident)
  in { module_: mod { bindings = bindings <> copies }, privateNames: Set.map renamed selected.workers }
