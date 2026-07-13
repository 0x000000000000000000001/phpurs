module Phpurs.FreeVars where

import Prelude
import Data.Array as Array
import Data.Maybe (Maybe(..))
import Data.Tuple (Tuple(..))
import Data.Set (Set)
import Data.Set as Set
import Data.Foldable (foldl, foldr)
import Data.Array.NonEmpty (toArray)

import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..), Pair(..), BackendAccessor(..), BackendOperator(..), BackendOperator1(..), BackendOperator2(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.CoreFn (Ident(..), Prop(..), Literal(..))

freeVars :: NeutralExpr -> Set String
freeVars (NeutralExpr syntax) = case syntax of
  Var _ -> Set.empty
  Local (Just (Ident ident)) _ -> Set.singleton ident
  Local Nothing _ -> Set.empty
  Lit lit -> case lit of
    LitArray arr -> foldl (\acc e -> Set.union acc (freeVars e)) Set.empty arr
    LitRecord rec -> foldl (\acc (Prop _ e) -> Set.union acc (freeVars e)) Set.empty rec
    _ -> Set.empty
  App fn args -> 
    foldl (\acc e -> Set.union acc (freeVars e)) (freeVars fn) (toArray args)
  Abs args body ->
    let 
      argsSet = foldl (\acc (Tuple mbIdent _) -> case mbIdent of
        Just (Ident i) -> Set.insert i acc
        Nothing -> acc
      ) Set.empty (toArray args)
      bodyVars = freeVars body
    in Set.difference bodyVars argsSet
  UncurriedApp fn args ->
    foldl (\acc e -> Set.union acc (freeVars e)) (freeVars fn) args
  UncurriedAbs args body ->
    let 
      argsSet = foldl (\acc (Tuple mbIdent _) -> case mbIdent of
        Just (Ident i) -> Set.insert i acc
        Nothing -> acc
      ) Set.empty args
      bodyVars = freeVars body
    in Set.difference bodyVars argsSet
  UncurriedEffectApp fn args ->
    foldl (\acc e -> Set.union acc (freeVars e)) (freeVars fn) args
  UncurriedEffectAbs args body ->
    let 
      argsSet = foldl (\acc (Tuple mbIdent _) -> case mbIdent of
        Just (Ident i) -> Set.insert i acc
        Nothing -> acc
      ) Set.empty args
      bodyVars = freeVars body
    in Set.difference bodyVars argsSet
  Accessor e _ -> freeVars e
  Update e props ->
    foldl (\acc (Prop _ val) -> Set.union acc (freeVars val)) (freeVars e) props
  CtorSaturated _ _ _ _ args ->
    foldl (\acc (Tuple _ e) -> Set.union acc (freeVars e)) Set.empty args
  CtorDef _ _ _ _ -> Set.empty
  LetRec _ binds body ->
    let
      bindsSet = foldl (\acc (Tuple (Ident i) _) -> Set.insert i acc) Set.empty (toArray binds)
      bodyVars = freeVars body
      bindsVars = foldl (\acc (Tuple _ e) -> Set.union acc (freeVars e)) Set.empty (toArray binds)
    in Set.difference (Set.union bodyVars bindsVars) bindsSet
  Let (Just (Ident ident)) _ val body ->
    Set.union (freeVars val) (Set.difference (freeVars body) (Set.singleton ident))
  Let Nothing _ val body ->
    Set.union (freeVars val) (freeVars body)
  EffectBind (Just (Ident ident)) _ val body ->
    Set.union (freeVars val) (Set.difference (freeVars body) (Set.singleton ident))
  EffectBind Nothing _ val body ->
    Set.union (freeVars val) (freeVars body)
  EffectPure e -> freeVars e
  EffectDefer e -> freeVars e
  Branch pairs def ->
    let
      defVars = freeVars def
      pairsVars = foldl (\acc (Pair cond body) -> Set.union acc (Set.union (freeVars cond) (freeVars body))) Set.empty (toArray pairs)
    in Set.union defVars pairsVars
  PrimOp op -> case op of
    Op1 _ e -> freeVars e
    Op2 _ e1 e2 -> Set.union (freeVars e1) (freeVars e2)
  PrimEffect _ -> Set.empty
  PrimUndefined -> Set.empty
  Fail _ -> Set.empty

