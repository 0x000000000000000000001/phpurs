-- Reuse a closure binding inside a closed, immediately consumed chain.
-- Types alone do not prove that partial application is pure or terminating:
-- prove the producer grammar and the scalar callback before copying any code.
module Phpurs.PartialBindings where

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
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Phpurs.EnumRegions (signature)
import Phpurs.ThunkFusion (bounded, hasType, integer, local, peel, require, typed)
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident(..), ModuleName, Qualified(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendOperator(..), BackendOperator2(..), BackendOperatorNum(..), BackendOperatorOrd(..), BackendSyntax(..), Level(..), Pair(..))

workerBudget :: Int
workerBudget = 32

unary :: ExprType
unary = Func [ Int ] Int

chain :: ExprType
chain = Func [ unary, Int ] Int

builderType :: ExprType
builderType = Func [ Int, unary, Int ] Int

-- All accepted producers have one actual argument and return a chain. Keeping
-- that runtime arity on the private copies allows direct calls without globals.
privateType :: ExprType
privateType = Func [ Int ] chain

type Binder = Tuple (Maybe Ident) Level

type Producer = { body :: NeutralExpr, dependencies :: Set Ident }

type Context = { name :: ModuleName, originals :: Map Ident NeutralExpr }

oneAbs :: NeutralExpr -> Maybe { binder :: Binder, body :: NeutralExpr }
oneAbs expr = case peel expr of
  Abs args body | NEA.length args == 1 -> Just { binder: NEA.head args, body }
  _ -> Nothing

level :: Binder -> Level
level (Tuple _ n) = n

ref :: ExprType -> Binder -> NeutralExpr
ref ty (Tuple name n) = typed ty (NeutralExpr (Local name n))

lambda :: ExprType -> Binder -> NeutralExpr -> NeutralExpr
lambda ty binder body = typed ty (NeutralExpr (Abs (NEA.singleton binder) body))

makeApp :: ExprType -> NeutralExpr -> Array NeutralExpr -> Maybe NeutralExpr
makeApp ty fn args = typed ty <<< NeutralExpr <<< App fn <$> NEA.fromArray args

callArgs :: NeutralExpr -> Maybe { fn :: NeutralExpr, args :: Array NeutralExpr }
callArgs expr = case peel expr of
  App fn args -> Just { fn, args: NEA.toArray args }
  _ -> Nothing

localCall :: Level -> Array Level -> NeutralExpr -> Boolean
localCall fn levels expr = case callArgs expr of
  Just call -> local fn call.fn && A.length levels == A.length call.args && all identity (A.zipWith local levels call.args)
  _ -> false

sameModuleVar :: Context -> NeutralExpr -> Maybe Ident
sameModuleVar ctx expr = case peel expr of
  Var (Qualified (Just mn) name) | mn == ctx.name -> Just name
  _ -> Nothing

-- A neutral producer must literally ignore its first argument and return the
-- second. Its generic TAST signature is checked before specializing the copy.
neutral :: Context -> NeutralExpr -> Maybe Unit
neutral ctx expr = do
  name <- sameModuleVar ctx expr
  original <- Map.lookup name ctx.originals
  require (bounded 128 original)
  sig <- signature original
  require (sig.args == [ Func [ sig.ret ] sig.ret, sig.ret ])
  first <- oneAbs original
  second <- oneAbs first.body
  require (level first.binder /= level second.binder && local (level second.binder) second.body)

-- Induction: construction first builds the shorter chain; binding its callback
-- only returns closures and never invokes that callback. Only this successor
-- shape moves a partial call. Compositions below merely preserve provenance.
proveSuccessor :: Context -> Ident -> NeutralExpr -> Maybe Producer
proveSuccessor ctx name expr = do
  require (bounded 512 expr && hasType builderType expr)
  outer <- oneAbs expr
  case peel outer.body of
    Branch branches fallback | NEA.length branches == 1 -> do
      let Pair condition base = NEA.head branches
      case peel condition of
        PrimOp (Op2 (OpIntOrd OpEq) a b) -> require (local (level outer.binder) a && integer b == Just 0)
        _ -> Nothing
      neutral ctx base
      case peel fallback of
        Let accName accLevel recurse rest -> do
          call <- callArgs recurse
          target <- sameModuleVar ctx call.fn
          require (target == name)
          case call.args of
            [ next ] -> case peel next of
              PrimOp (Op2 (OpIntNum OpSubtract) a b) -> require (local (level outer.binder) a && integer b == Just 1)
              _ -> Nothing
            _ -> Nothing
          fn <- oneAbs rest
          value <- oneAbs fn.body
          let levels = [ level outer.binder, accLevel, level fn.binder, level value.binder ]
          require (all (\n -> unwrap n >= 0 && unwrap n < 8192) levels)
          require (A.length (A.nub levels) == 4 && hasType chain rest && hasType unary fn.body && hasType Int value.body)
          forced <- callArgs value.body
          require (local (level fn.binder) forced.fn)
          case forced.args of
            [ inner ] -> require (localCall accLevel [ level fn.binder, level value.binder ] inner)
            _ -> Nothing
          let
            fresh = Tuple (Just (Ident "bound")) (Level (1 + foldl max 0 (map unwrap levels)))
            acc = ref chain (Tuple accName accLevel)
          bound <- makeApp unary acc [ ref unary fn.binder ]
          invoke <- makeApp Int (ref unary fresh) [ ref Int value.binder ]
          result <- makeApp Int (ref unary fn.binder) [ invoke ]
          let
            successor = lambda chain fn.binder (typed unary (NeutralExpr (Let (Just (Ident "bound")) (level fresh) bound
              (lambda unary value.binder result))))
            zero = lambda chain fn.binder (lambda unary value.binder (ref Int value.binder))
            body = lambda privateType outer.binder (typed chain (NeutralExpr (Branch
              (NEA.singleton (Pair condition zero))
              (typed chain (NeutralExpr (Let accName accLevel recurse successor))))))
          pure { body, dependencies: Set.singleton name }
        _ -> Nothing
    _ -> Nothing

-- Closed composition of two already proved chain factories. Calls, captures,
-- operand order and allocation of the composition closures stay in place.
proveComposition :: Context -> Map Ident Producer -> NeutralExpr -> Maybe Producer
proveComposition ctx known expr = do
  require (bounded 512 expr && hasType builderType expr)
  outer <- oneAbs expr
  case peel outer.body of
    Let _ leftLevel leftCall rest -> case peel rest of
      Let _ rightLevel rightCall inner -> do
        left <- dependency outer.binder leftCall
        right <- dependency outer.binder rightCall
        fn <- oneAbs inner
        value <- oneAbs fn.body
        require (A.length (A.nub [ level outer.binder, leftLevel, rightLevel, level fn.binder, level value.binder ]) == 5)
        require (hasType chain inner && hasType unary fn.body && hasType Int value.body)
        call <- callArgs value.body
        require (local leftLevel call.fn)
        case call.args of
          [ binding, input ] -> require (local (level value.binder) input && localCall rightLevel [ level fn.binder ] binding)
          _ -> Nothing
        pure { body: lambda privateType outer.binder outer.body, dependencies: Set.union left right }
      _ -> Nothing
    _ -> Nothing
  where
  dependency binder e = do
    call <- callArgs e
    target <- sameModuleVar ctx call.fn
    source <- Map.lookup target known
    case call.args of
      [ n ] -> require (local (level binder) n && hasType Int n)
      _ -> Nothing
    pure (Set.insert target source.dependencies)

-- The only consumer admitted here is a literal unary integer operation. No
-- unknown callback, FFI, captured value, throwing division, or escaping result.
scalarCallback :: NeutralExpr -> Boolean
scalarCallback expr = case candidate of
  Just _ -> true
  Nothing -> false
  where
  candidate = do
    require (hasType unary expr)
    fn <- oneAbs expr
    require (hasType Int fn.body)
    case peel fn.body of
      PrimOp (Op2 (OpIntNum op) a b) -> do
        require (op == OpAdd || op == OpSubtract || op == OpMultiply)
        require (local (level fn.binder) a)
        _ <- integer b
        pure unit
      _ -> Nothing

scalarInput :: NeutralExpr -> Boolean
scalarInput e = hasType Int e && case peel e of
  Local _ _ -> true
  Lit _ -> integer e /= Nothing
  _ -> false

rename :: ModuleName -> String -> Set Ident -> NeutralExpr -> NeutralExpr
rename mn prefix names (NeutralExpr syntax) = NeutralExpr case syntax of
  Var (Qualified (Just moduleName) name) | moduleName == mn && Set.member name names ->
    Var (Qualified (Just mn) (Ident (prefix <> unwrap name)))
  _ -> map (rename mn prefix names) syntax

type ScanState = { fuel :: Int, selected :: Set Ident }

scan :: Context -> String -> Map Ident Producer -> NeutralExpr -> State ScanState NeutralExpr
scan ctx prefix producers expr@(NeutralExpr syntax) = do
  state <- get
  if state.fuel <= 0 then pure expr
  else do
    modify_ (\s -> s { fuel = s.fuel - 1 })
    case candidate of
      Just names | Set.size (Set.union names state.selected) <= workerBudget -> do
        modify_ (\s -> s { selected = Set.union names s.selected })
        pure (rename ctx.name prefix names expr)
      _ -> NeutralExpr <$> traverse (scan ctx prefix producers) syntax
  where
  candidate = do
    require (hasType Int expr)
    call <- callArgs expr
    name <- sameModuleVar ctx call.fn
    producer <- Map.lookup name producers
    case call.args of
      [ count, callback, seed ] -> require (scalarInput count && scalarCallback callback && scalarInput seed)
      _ -> Nothing
    pure (Set.insert name producer.dependencies)

freshPrefix :: BackendModule -> String
freshPrefix mod = go 0
  where
  names = map (String.toLower <<< unwrap) (A.concatMap (map (\(Tuple k _) -> k) <<< _.bindings) mod.bindings)
    <> map String.toLower (A.concatMap (map _.name <<< _.constructors) mod.dataDecls)
    <> map (String.toLower <<< unwrap) (Set.toUnfoldable (Map.keys mod.foreign) :: Array Ident)
  go n = let prefix = "__phpurs_bind_" <> show n <> "_"
    in if A.any (String.contains (Pattern prefix)) names then go (n + 1) else prefix

optimize :: BackendModule -> { module_ :: BackendModule, privateNames :: Set Ident }
optimize mod
  | A.length mod.bindings > 256 || A.any (\g -> A.length g.bindings > 64) mod.bindings =
      { module_: mod, privateNames: Set.empty }
  | otherwise =
      let
        ctx = { name: mod.name, originals: Map.fromFoldable (A.concatMap _.bindings mod.bindings) }
        -- PBO emits dependencies before their consumers. Forward references and
        -- mutual recursion deliberately remain on the original path.
        discover known group = foldl (\acc (Tuple name expr) ->
          let proof = if group.recursive then case group.bindings of
                [ _ ] -> proveSuccessor ctx name expr
                _ -> Nothing
                else proveComposition ctx acc expr
          in case proof of
            Just producer | Map.size acc < workerBudget -> Map.insert name producer acc
            _ -> acc) known group.bindings
        producers = foldl discover Map.empty mod.bindings
        prefix = freshPrefix mod
        Tuple bindings final = runState (traverse (\g -> do
          bs <- traverse (\(Tuple name expr) -> Tuple name <$> if bounded 8192 expr then scan ctx prefix producers expr else pure expr) g.bindings
          pure (g { bindings = bs })) mod.bindings) { fuel: 32768, selected: Set.empty }
        copies = A.mapMaybe (\name -> do
          producer <- Map.lookup name producers
          pure { recursive: Set.member name producer.dependencies, bindings: [ Tuple (Ident (prefix <> unwrap name))
            (rename mod.name prefix final.selected producer.body) ] }) (Set.toUnfoldable final.selected :: Array Ident)
      in if Map.isEmpty producers then { module_: mod, privateNames: Set.empty }
        else { module_: mod { bindings = bindings <> copies }, privateNames: Set.map (\name -> Ident (prefix <> unwrap name)) final.selected }
