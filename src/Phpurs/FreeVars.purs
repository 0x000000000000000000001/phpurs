-- | Provides static analysis for variable scoping in PHP.
-- | Unlike JavaScript, PHP closures do not automatically capture variables from their parent scope.
-- | In PHP, variables must be explicitly captured using the `use ($x, &$y)` syntax.
-- | 
-- | This module traverses the `NeutralExpr` AST to compute the set of "free variables"
-- | (variables referenced inside an expression but not defined within it). This information
-- | is used during code generation to correctly generate the `use` clauses for closures.
module Phpurs.FreeVars where

import Prelude
import Data.Array as Array
import Data.Maybe (Maybe(..))
import Data.Tuple (Tuple(..))
import Data.Map (Map)
import Data.Map as Map
import Data.Set (Set)
import Data.Set as Set
import Data.Foldable (foldl, foldr)
import Data.Array.NonEmpty (toArray)

import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..), Pair(..), BackendAccessor(..), BackendOperator(..), BackendOperator1(..), BackendOperator2(..), Level(..))
import PureScript.Backend.Optimizer.Semantics (NeutralExpr(..))
import PureScript.Backend.Optimizer.CoreFn (Ident(..), Prop(..), Literal(..))

-- | Computes the set of free variables (by name) in a given `NeutralExpr`.
-- | The `bound` map keeps track of variables currently bound in the local scope
-- | (e.g. function arguments, let bindings). If an identifier is found that is NOT
-- | in the `bound` map, it is considered a free variable.
freeVars :: Map Int String -> NeutralExpr -> Set String
freeVars bound (NeutralExpr syntax) = case syntax of
  Var _ -> Set.empty
  Local (Just (Ident ident)) _ -> Set.singleton ident
  Local Nothing (Level l) ->
    case Map.lookup l bound of
      Just varName -> Set.singleton varName
      Nothing -> Set.singleton ("__local_var_" <> show l)
  Lit lit -> case lit of
    LitArray arr -> foldl (\acc e -> Set.union acc (freeVars bound e)) Set.empty arr
    LitRecord rec -> foldl (\acc (Prop _ e) -> Set.union acc (freeVars bound e)) Set.empty rec
    _ -> Set.empty
  App fn args -> 
    foldl (\acc e -> Set.union acc (freeVars bound e)) (freeVars bound fn) (toArray args)
  Abs args body ->
    let 
      argsSet = foldl (\acc (Tuple mbIdent (Level l)) -> case mbIdent of
        Just (Ident i) -> Set.insert i acc
        Nothing -> Set.insert ("__local_var_" <> show l) acc
      ) Set.empty (toArray args)
      bodyVars = freeVars bound body
    in Set.difference bodyVars argsSet
  UncurriedApp fn args ->
    foldl (\acc e -> Set.union acc (freeVars bound e)) (freeVars bound fn) args
  UncurriedAbs args body ->
    let 
      argsSet = foldl (\acc (Tuple mbIdent (Level l)) -> case mbIdent of
        Just (Ident i) -> Set.insert i acc
        Nothing -> Set.insert ("__local_var_" <> show l) acc
      ) Set.empty args
      bodyVars = freeVars bound body
    in Set.difference bodyVars argsSet
  UncurriedEffectApp fn args ->
    foldl (\acc e -> Set.union acc (freeVars bound e)) (freeVars bound fn) args
  UncurriedEffectAbs args body ->
    let 
      argsSet = foldl (\acc (Tuple mbIdent (Level l)) -> case mbIdent of
        Just (Ident i) -> Set.insert i acc
        Nothing -> Set.insert ("__local_var_" <> show l) acc
      ) Set.empty args
      bodyVars = freeVars bound body
    in Set.difference bodyVars argsSet
  Accessor e _ -> freeVars bound e
  Update e props ->
    foldl (\acc (Prop _ val) -> Set.union acc (freeVars bound val)) (freeVars bound e) props
  CtorSaturated _ _ _ _ args ->
    foldl (\acc (Tuple _ e) -> Set.union acc (freeVars bound e)) Set.empty args
  CtorDef _ _ _ _ -> Set.empty
  LetRec _ binds body ->
    let
      bindsSet = foldl (\acc (Tuple (Ident i) _) -> Set.insert i acc) Set.empty (toArray binds)
      bodyVars = freeVars bound body
      bindsVars = foldl (\acc (Tuple _ e) -> Set.union acc (freeVars bound e)) Set.empty (toArray binds)
    in Set.difference (Set.union bodyVars bindsVars) bindsSet
  Let (Just (Ident ident)) _ val body ->
    Set.union (freeVars bound val) (Set.difference (freeVars bound body) (Set.singleton ident))
  Let Nothing (Level l) val body ->
    Set.union (freeVars bound val) (Set.difference (freeVars bound body) (Set.singleton ("__local_var_" <> show l)))
  EffectBind (Just (Ident ident)) _ val body ->
    Set.union (freeVars bound val) (Set.difference (freeVars bound body) (Set.singleton ident))
  EffectBind Nothing (Level l) val body ->
    Set.union (freeVars bound val) (Set.difference (freeVars bound body) (Set.singleton ("__local_var_" <> show l)))
  EffectPure e -> freeVars bound e
  EffectDefer e -> freeVars bound e
  Branch pairs def ->
    let
      defVars = freeVars bound def
      pairsVars = foldl (\acc (Pair cond body) -> Set.union acc (Set.union (freeVars bound cond) (freeVars bound body))) Set.empty (toArray pairs)
    in Set.union defVars pairsVars
  PrimOp op -> case op of
    Op1 _ e -> freeVars bound e
    Op2 _ e1 e2 -> Set.union (freeVars bound e1) (freeVars bound e2)
  PrimEffect _ -> Set.empty
  PrimUndefined -> Set.empty
  Fail _ -> Set.empty

