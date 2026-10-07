-- Specialize only explicitly contracted FFI traversals. The generated PHP
-- templates keep their index/foreach protocols, collections and native checks.
module Phpurs.ArrayCallbacks where

import Prelude

import Control.Monad.State (State, get, modify_, runState)
import Data.Array as A
import Data.Array.NonEmpty as NEA
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Phpurs.CallbackSpecialization (Binder, local, nativeCallback)
import Phpurs.CodeGen.Operators (translateOperator1, translateOperator2)
import Phpurs.ForeignTraversals (Traversal(..), Traversals)
import Phpurs.NativeCallbacks (Callbacks)
import Phpurs.PhpAst (PhpDecl, PhpExpr(..))
import Phpurs.Printer (printExpr, safeFuncName)
import Phpurs.ThunkFusion (bounded, freshPrefixFor, hasType, integer, peel, require)
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident(..), Literal(..), ModuleName(..), Qualified(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendOperator(..), BackendOperator1(..), BackendOperator2(..), BackendOperatorNum(..), BackendSyntax(..))

data Selection = FoldCallback (Qualified Ident) | FilterPredicate String
derive instance eqSelection :: Eq Selection
derive instance ordSelection :: Ord Selection

workerBudget :: Int
workerBudget = 32

qualified :: String -> String -> Qualified Ident
qualified mn ident = Qualified (Just (ModuleName mn)) (Ident ident)

-- Match only closures whose emitted parameter has no scalar PHP check. Keep
-- the closure itself as a worker argument for all non-Int element fallbacks.
predicate :: NeutralExpr -> Maybe String
predicate expr = do
  require (bounded 128 expr)
  Tuple binder body <- lambda expr
  printExpr Map.empty <$> boolean binder body
  where
  lambda (NeutralExpr (Typed (Func _ _) _)) = Nothing
  lambda (NeutralExpr (Typed _ inner)) = lambda inner
  lambda (NeutralExpr (Abs args body)) = case NEA.toArray args of
    [ binder ] -> Just (Tuple binder body)
    _ -> Nothing
  lambda _ = Nothing

  boolean binder value = case peel value of
    Lit (LitBoolean b) -> Just (PhpBoolean b)
    PrimOp (Op2 op@(OpIntOrd _) a b) -> translateOperator2 op <$> scalar binder a <*> scalar binder b
    PrimOp (Op2 op@OpBooleanAnd a b) -> translateOperator2 op <$> boolean binder a <*> boolean binder b
    PrimOp (Op2 op@OpBooleanOr a b) -> translateOperator2 op <$> boolean binder a <*> boolean binder b
    PrimOp (Op1 OpBooleanNot value') -> translateOperator1 OpBooleanNot <$> boolean binder value'
    _ -> Nothing

  -- No overflow-prone intermediate arithmetic or foreign calls. A nonzero
  -- literal divisor makes modulo total on the guarded PHP Int element.
  scalar :: Binder -> NeutralExpr -> Maybe PhpExpr
  scalar binder value | local binder value = Just (PhpVar "item")
  scalar _ value | Just n <- integer value = Just (PhpInt n)
  scalar binder value = case peel value of
    PrimOp (Op2 op@(OpIntNum OpMod) item divisor) -> do
      require (local binder item)
      n <- integer divisor
      require (n /= 0)
      pure (translateOperator2 op (PhpVar "item") (PhpInt n))
    _ -> Nothing

nativeName :: Qualified Ident -> String
nativeName (Qualified (Just (ModuleName mn)) (Ident ident)) =
  let parts = String.split (Pattern ".") mn
  in "\\" <> String.joinWith "\\" parts <> "\\" <> safeFuncName (String.joinWith "_" parts <> "_" <> ident)
nativeName (Qualified Nothing (Ident ident)) = safeFuncName ident

-- App, not UncurriedApp: the printer saturates known private arities without
-- needing an exported $GLOBALS entry. Original arguments are evaluated once.
applyWorker :: ModuleName -> Ident -> Array NeutralExpr -> NeutralExpr
applyWorker mn ident args = case NEA.fromArray args of
  Just inputs -> NeutralExpr (App (NeutralExpr (Var (Qualified (Just mn) ident))) inputs)
  Nothing -> NeutralExpr (Var (Qualified (Just mn) ident))

type ScanState = { fuel :: Int, selected :: Map Selection Ident }

scan :: Traversals -> Callbacks -> ModuleName -> String -> NeutralExpr -> State ScanState NeutralExpr
scan contracts callbacks mn prefix expr@(NeutralExpr syntax) = do
  state <- get
  if state.fuel <= 0 then pure expr
  else do
    modify_ (\s -> s { fuel = s.fuel - 1 })
    case candidate of
      Just { key, args } -> case Map.lookup key state.selected of
        Just ident -> applyWorker mn ident <$> traverse recur args
        Nothing | Map.size state.selected < workerBudget -> do
          let ident = Ident (prefix <> show (Map.size state.selected))
          modify_ (\s -> s { selected = Map.insert key ident s.selected })
          applyWorker mn ident <$> traverse recur args
        _ -> NeutralExpr <$> traverse recur syntax
      _ -> NeutralExpr <$> traverse recur syntax
  where
  recur = scan contracts callbacks mn prefix
  candidate = case syntax of
    App callee inputs | Set.member FoldlArray contracts -> case peel callee, NEA.toArray inputs of
      Var target, args@[ callback, initial, _ ] | target == qualified "Data.Foldable" "foldlArray" -> do
        name <- case peel callback of
          Var name@(Qualified (Just _) _) | Set.member name callbacks -> Just name
          _ -> Nothing
        require (hasType Int initial)
        _ <- integer initial
        pure { key: FoldCallback name, args }
      _, _ -> Nothing
    UncurriedApp callee args@[ callback, _ ] | Set.member FilterImpl contracts -> case peel callee of
      Var target | target == qualified "Data.Array" "filterImpl" -> do
        code <- predicate callback
        pure { key: FilterPredicate code, args }
      _ -> Nothing
    _ -> Nothing

-- These contracted templates are emitted after PHP AST optimizations. Keeping
-- foreach as one fixed template avoids exposing an incomplete AST traversal to
-- ownership/inlining passes. Only printer-rendered, closed predicates enter it.
worker :: String -> Selection -> PhpDecl
worker name selection = { identifier: name, expression: PhpPrivateFunction name params "" body }
  where
  arg value = { name: value, type_: "" }
  callback = PhpVar "callback"
  xs = PhpVar "xs"
  params = map arg case selection of
    FoldCallback _ -> [ "callback", "initial", "xs" ]
    FilterPredicate _ -> [ "callback", "xs" ]
  body = case selection of
    FoldCallback target ->
      [ PhpIf (PhpRaw "(!\\is_array($xs) || !\\array_is_list($xs))")
          [ PhpReturn (PhpCall (PhpRaw (nativeName (qualified "Data.Foldable" "foldlArray"))) [ callback, PhpVar "initial", xs ]) ] []
      , PhpAssign "acc" (PhpVar "initial")
      , PhpAssign "i" (PhpInt 0)
      , PhpAssign "len" (PhpCall (PhpRaw "\\count") [ xs ])
      , PhpWhile (PhpBinOp "<" (PhpVar "i") (PhpVar "len"))
          [ PhpAssign "acc" (PhpCall (PhpRaw (nativeName target)) [ PhpVar "acc", PhpArrayIndex xs (PhpVar "i") ])
          , PhpAssign "i" (PhpBinOp "+" (PhpVar "i") (PhpInt 1)) ]
      , PhpReturn (PhpVar "acc") ]
    FilterPredicate code ->
      [ PhpIf (PhpRaw "(!\\is_array($xs))")
          [ PhpReturn (PhpCall (PhpGlobalVar (Just [ "Data", "Array" ]) "filterImpl") [ callback, xs ]) ] []
      , PhpAssign "result" (PhpArray [])
      , PhpRaw ("foreach ($xs as $item) {\n  if (\\is_int($item) ? " <> code <> " : $callback($item)) $result[] = $item;\n}")
      , PhpReturn (PhpVar "result") ]

optimize :: Traversals -> Callbacks -> BackendModule -> { module_ :: BackendModule, workers :: Array PhpDecl, arities :: Map String Int }
optimize contracts foreignCallbacks mod
  | Set.isEmpty contracts || A.length mod.bindings > 256 || A.any (\g -> A.length g.bindings > 64) mod.bindings || A.length mod.dataDecls > 128 =
      { module_: mod, workers: [], arities: Map.empty }
  | otherwise =
      let
        callbacks = Set.union foreignCallbacks (Set.fromFoldable (A.concatMap (\g -> if g.recursive then [] else A.mapMaybe
          (\(Tuple ident expr) -> if nativeCallback expr then Just (Qualified (Just mod.name) ident) else Nothing) g.bindings) mod.bindings))
        prefix = freshPrefixFor "__phpurs_arraycb_" mod
        Tuple bindings state = runState
          (traverse (\g -> do
            bs <- traverse (\(Tuple ident expr) -> Tuple ident <$> if bounded 8192 expr then scan contracts callbacks mod.name prefix expr else pure expr) g.bindings
            pure (g { bindings = bs })) mod.bindings) { fuel: 32768, selected: Map.empty }
        fullName ident = String.joinWith "_" (String.split (Pattern ".") (unwrap mod.name)) <> "_" <> unwrap ident
        selected = Map.toUnfoldable state.selected :: Array (Tuple Selection Ident)
      in
        { module_: mod { bindings = bindings }
        , workers: map (\(Tuple key ident) -> worker (fullName ident) key) selected
        , arities: Map.fromFoldable (map (\(Tuple key ident) -> Tuple (fullName ident) (case key of
            FoldCallback _ -> 3
            FilterPredicate _ -> 2)) selected)
        }
