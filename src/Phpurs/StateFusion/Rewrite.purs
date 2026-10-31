-- | Apply a proven State chain: private scalar workers, guards and call-site
-- | rewrites.
module Phpurs.StateFusion.Rewrite (fuseModule) where

import Prelude

import Control.Monad.State (State, get, modify_, runState)
import Data.Array as A
import Data.Array.NonEmpty as NEA
import Data.Foldable (foldl)
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.Set (Set)
import Data.Set as Set
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Phpurs.Budget as Budget
import Phpurs.Optimize.Names (freshPrefixFor)
import Phpurs.Optimize.NeutralExpr (hasType, int, integer, peel, require, typed)
import Phpurs.Optimize.ScalarWorker (call, makeWorker)
import Phpurs.Pass (privateName, scanGroups)
import Phpurs.StateFusion.Types (Builder, Layout, Locals, Result)
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn
  ( ExprType(..)
  , Ident(..)
  , Literal(..)
  , ModuleName
  , Qualified(..)
  )
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.Syntax
  ( BackendAccessor(..)
  , BackendOperator(..)
  , BackendOperator2(..)
  , BackendOperatorOrd(..)
  , BackendSyntax(..)
  , Level(..)
  , Pair(..)
  )

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
  original = NeutralExpr
    ( App (NeutralExpr (App (NeutralExpr (Var builder)) (NEA.singleton depth)))
        (NEA.singleton state)
    )
  fallback = NeutralExpr (Accessor original (GetProp layout.state))

type ScanState = { fuel :: Int, workers :: Set Ident, guards :: Set Ident }

scan
  :: ModuleName
  -> String
  -> String
  -> Map Ident Builder
  -> Locals
  -> NeutralExpr
  -> State ScanState NeutralExpr
scan moduleName prefix guardPrefix builders available expr@(NeutralExpr syntax) = do
  state <- get
  if state.fuel <= 0 then pure expr
  else do
    modify_ (\s -> s { fuel = s.fuel - 1 })
    case candidate of
      Just { ident, depth, initial, dynamic }
        | Set.member ident state.workers || Set.size state.workers < Budget.workerLimit -> do
            modify_
              ( \s -> s
                  { workers = Set.insert ident s.workers
                  , guards = if dynamic then Set.insert ident s.guards else s.guards
                  }
              )
            let family = if dynamic then guardPrefix else prefix
            pure
              ( typed Int
                  ( call (Qualified (Just moduleName) (Ident (family <> unwrap ident))) depth
                      initial
                  )
              )
      _ -> NeutralExpr <$> case syntax of
        Abs args body -> Abs args <$> recur (Set.union available (Set.fromFoldable args)) body
        UncurriedAbs args body -> UncurriedAbs args <$> recur
          (Set.union available (Set.fromFoldable args))
          body
        UncurriedEffectAbs args body -> UncurriedEffectAbs args <$> recur
          (Set.union available (Set.fromFoldable args))
          body
        Let name level value body -> Let name level <$> recur available value <*> recur
          (Set.insert (Tuple name level) available)
          body
        EffectBind name level value body -> EffectBind name level <$> recur available value <*>
          recur (Set.insert (Tuple name level) available) body
        LetRec level bindings body ->
          let
            outside = foldl (\s (Tuple name _) -> Set.delete (Tuple (Just name) level) s) available
              bindings
          in
            LetRec level <$> traverse (traverse (recur outside)) bindings <*> recur outside body
        _ -> traverse (recur available) syntax
  where
  recur = scan moduleName prefix guardPrefix builders
  candidate = case syntax of
    Accessor result (GetProp key) -> case peel result of
      App callee args -> case peel callee, NEA.toArray args of
        Var (Qualified (Just mn) ident), [ depth, initial ] | mn == moduleName -> do
          builder <- Map.lookup ident builders
          require
            (key == builder.layout.state && scalar available depth && scalar available initial)
          dynamic <- case integer depth of
            Just n -> false <$ require (n >= 0)
            Nothing -> Just true
          pure { ident, depth, initial, dynamic }
        _, _ -> Nothing
      _ -> Nothing
    _ -> Nothing

fuseModule :: BackendModule -> Map Ident Builder -> Result
fuseModule mod builders =
  let
    prefix = freshPrefixFor "__phpurs_state_" mod
    guardPrefix = freshPrefixFor "__phpurs_runstate_" mod
    Tuple bindings selected = runState
      (scanGroups (scan mod.name prefix guardPrefix builders Set.empty) mod)
      { fuel: Budget.stateFuel, workers: Set.empty, guards: Set.empty }
    renamed = privateName prefix
    guarded = privateName guardPrefix
    workers = A.mapMaybe
      ( \ident -> do
          builder <- Map.lookup ident builders
          pure
            { recursive: true
            , bindings:
                [ Tuple (renamed ident)
                    (makeWorker (Qualified (Just mod.name) (renamed ident)) builder.step)
                ]
            }
      )
      (Set.toUnfoldable selected.workers :: Array Ident)
    guards = A.mapMaybe
      ( \ident -> do
          builder <- Map.lookup ident builders
          pure
            { recursive: false
            , bindings:
                [ Tuple (guarded ident)
                    ( makeGuard (Qualified (Just mod.name) ident)
                        (Qualified (Just mod.name) (renamed ident))
                        builder.layout
                    )
                ]
            }
      )
      (Set.toUnfoldable selected.guards :: Array Ident)
  in
    { module_: mod { bindings = bindings <> workers <> guards }
    , privateNames: Set.union (Set.map renamed selected.workers) (Set.map guarded selected.guards)
    , checkedDepths: Set.map guarded selected.guards
    }
