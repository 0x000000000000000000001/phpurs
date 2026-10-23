-- | Track one candidate owner's aliases and field reads through a function.
-- | This conservative scan is independent of call-site promotion and TCO rules.
module Phpurs.ArrayRefs.Ownership
  ( OwnershipContext
  , hasUnsafeUse
  ) where

import Prelude

import Data.Array as A
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Set (Set)
import Data.Set as Set
import Data.String as String
import Data.Tuple (Tuple(..))
import Phpurs.ArrayRefs.Support (callHead, fieldIndexOf, usesVar, varsIn)
import Phpurs.PhpAst (PhpExpr(..))

type OwnershipContext =
  { owner :: String
  , byRef :: Map String (Set Int)
  , candidates :: Map String (Set Int)
  , privateClasses :: Set String
  }

type ScanState =
  { bad :: Boolean
  , mutated :: Set Int
  , slots :: Map String Int
  }

-- | Sentinel field for "the whole node is consumed, every field may change".
allFields :: Int
allFields = -1

-- | True means the function may alias the owner or observe a mutated field.
-- | Missing evidence refuses promotion; this does not prove general uniqueness.
hasUnsafeUse :: OwnershipContext -> Array PhpExpr -> Boolean
hasUnsafeUse context body = (scanStatements context initial body).bad
  where
  initial = { bad: false, mutated: Set.empty, slots: Map.empty }

scanStatements :: OwnershipContext -> ScanState -> Array PhpExpr -> ScanState
scanStatements ctx = A.foldl (scanStatement ctx)

scanStatement :: OwnershipContext -> ScanState -> PhpExpr -> ScanState
scanStatement ctx st e = case e of
  PhpAssign name value ->
    let
      st1 = scanAssignValue ctx st name value
    in
      case value of
        PhpPropertyAccess (PhpVar v) f | v == ctx.owner -> case fieldIndexOf f of
          Just field -> st1 { slots = Map.insert name field st1.slots }
          Nothing -> st1
        PhpArrayIndex (PhpVar v) (PhpInt field) | v == ctx.owner -> st1
          { slots = Map.insert name field st1.slots }
        _ -> st1
  PhpIf c t el -> scanStatements ctx (scanStatements ctx (scanRead ctx st c) t) el
  PhpSwitch s cases d -> scanSwitch ctx st s cases d
  PhpWhile c b -> scanStatements ctx (scanRead ctx st c) b
  PhpReturn value -> scanReturn ctx st value
  _ -> scanExpression ctx st e

scanAssignValue :: OwnershipContext -> ScanState -> String -> PhpExpr -> ScanState
scanAssignValue ctx st name value
  | name == ctx.owner = case value of
      PhpVar c | String.take 10 c == "__tco_var_" -> st
      _ -> st { bad = true }
  | otherwise = case value of
      PhpVar v | v == ctx.owner && String.take 10 name == "__tco_var_" -> st
      PhpVar v | v == ctx.owner -> st { bad = true }
      _ -> scanExpression ctx st value

scanReturn :: OwnershipContext -> ScanState -> PhpExpr -> ScanState
scanReturn ctx st e = case e of
  PhpVar v | v == ctx.owner -> st
  PhpPropertyAccess (PhpVar v) _ | v == ctx.owner -> st { bad = true }
  PhpArrayIndex (PhpVar v) (PhpInt _) | v == ctx.owner -> st { bad = true }
  _ -> scanExpression ctx st e

-- | A read position may mention the owner directly: reading it cannot create
-- | an alias.
scanRead :: OwnershipContext -> ScanState -> PhpExpr -> ScanState
scanRead ctx st e = case e of
  PhpVar v | v == ctx.owner -> st
  _ -> scanExpression ctx st e

isOwnerVar :: OwnershipContext -> PhpExpr -> Boolean
isOwnerVar ctx e = case e of
  PhpVar v -> v == ctx.owner
  _ -> false

scanExpression :: OwnershipContext -> ScanState -> PhpExpr -> ScanState
scanExpression ctx st e = case e of
  PhpVar v
    | v == ctx.owner -> st { bad = true }
    | otherwise -> case Map.lookup v st.slots of
        Just field | Set.member allFields st.mutated || Set.member field st.mutated -> st
          { bad = true }
        _ -> st
  PhpPropertyAccess base _
    | isOwnerVar ctx base -> st
    | usesVar ctx.owner base -> st { bad = true }
    | otherwise -> scanExpression ctx st base
  PhpRecordAccess base _
    | isOwnerVar ctx base -> st
    | usesVar ctx.owner base -> st { bad = true }
    | otherwise -> scanExpression ctx st base
  PhpArrayIndex base idx
    | isOwnerVar ctx base -> st
    | usesVar ctx.owner base -> st { bad = true }
    | otherwise -> scanExpression ctx (scanRead ctx st idx) base
  PhpBinOp _ l r -> scanRead ctx (scanRead ctx st l) r
  PhpInstanceOf v _ -> scanRead ctx st v
  PhpTernary c t el -> scanRead ctx (scanExpression ctx (scanExpression ctx st t) el) c
  PhpIf c t el -> scanStatements ctx (scanStatements ctx (scanRead ctx st c) t) el
  PhpSwitch s cases d -> scanSwitch ctx st s cases d
  PhpWhile c b -> scanStatements ctx (scanRead ctx st c) b
  PhpAssign _ _ -> scanStatement ctx st e
  PhpAssignExpr lhs rhs ->
    if usesVar ctx.owner lhs || usesVar ctx.owner rhs then st { bad = true }
    else scanExpression ctx st rhs
  PhpReturn v -> scanReturn ctx st v
  PhpNew cls args ->
    if Set.member cls ctx.privateClasses then A.foldl (scanConstructionArg ctx) st args
    else if A.any (usesVar ctx.owner) args then st { bad = true }
    else A.foldl (scanExpression ctx) st args
  PhpDirectCall name args -> scanCall ctx st name args
  PhpCall _ _ -> scanCallExpr ctx st e
  PhpArray es ->
    if A.any (usesVar ctx.owner) es then st { bad = true }
    else A.foldl (scanExpression ctx) st es
  PhpAssocArray items ->
    if A.any (\item -> usesVar ctx.owner item.value) items then st { bad = true }
    else A.foldl (\acc item -> scanExpression ctx acc item.value) st items
  PhpFunction captures _ _ body -> scanClosure ctx st captures body
  PhpCompactFunction captures _ _ body -> scanClosure ctx st captures body
  PhpClone v ->
    if usesVar ctx.owner v then st { bad = true }
    else scanExpression ctx st v
  PhpGlobalAssign _ v ->
    if usesVar ctx.owner v then st { bad = true }
    else scanExpression ctx st v
  PhpThrow v -> scanExpression ctx st v
  PhpMatch s cases d ->
    let
      st1 = scanRead ctx st s
      st2 = A.foldl (\acc c -> scanExpression ctx acc c.body) st1 cases
    in
      scanExpression ctx st2 d
  PhpRaw raw ->
    let
      mentionsOwner = String.contains (String.Pattern ("$" <> ctx.owner)) raw
      mentionsSlot = A.any (\(Tuple name _) -> String.contains (String.Pattern ("$" <> name)) raw)
        (Map.toUnfoldable st.slots :: Array (Tuple String Int))
    in
      if mentionsOwner || mentionsSlot then st { bad = true } else st
  _ -> st

scanSwitch
  :: OwnershipContext
  -> ScanState
  -> PhpExpr
  -> Array { matchCases :: Array PhpExpr, stmts :: Array PhpExpr }
  -> Maybe (Array PhpExpr)
  -> ScanState
scanSwitch ctx st s cases d =
  let
    st1 = scanRead ctx st s
    st2 = A.foldl (\acc c -> scanStatements ctx acc c.stmts) st1 cases
  in
    case d of
      Just stmts -> scanStatements ctx st2 stmts
      Nothing -> st2

scanClosure :: OwnershipContext -> ScanState -> Array String -> Array PhpExpr -> ScanState
scanClosure ctx st captures body =
  let
    slotNames = map (\(Tuple name _) -> name)
      (Map.toUnfoldable st.slots :: Array (Tuple String Int))
    bodyVars = A.concatMap varsIn body
  in
    if
      A.elem ctx.owner captures || A.any (usesVar ctx.owner) body || A.any
        (\name -> A.elem name captures || A.elem name bodyVars)
        slotNames then st { bad = true }
    else st

-- | A value stored as a field of a freshly built private node stays inside the
-- | update; a nested private construction built from the owner's fields is
-- | refused because it would share them into a second structure the scan
-- | cannot follow.
scanConstructionArg :: OwnershipContext -> ScanState -> PhpExpr -> ScanState
scanConstructionArg ctx st a = case a of
  PhpVar v | v == ctx.owner -> st { bad = true }
  PhpPropertyAccess (PhpVar v) _ | v == ctx.owner -> st
  PhpArrayIndex (PhpVar v) (PhpInt _) | v == ctx.owner -> st
  _ ->
    if usesVar ctx.owner a then case callHead a of
      Just head -> scanCall ctx st head.target head.args
      Nothing -> st { bad = true }
    else scanExpression ctx st a

scanCallExpr :: OwnershipContext -> ScanState -> PhpExpr -> ScanState
scanCallExpr ctx st e = case callHead e of
  Just head -> scanCall ctx st head.target head.args
  Nothing -> case e of
    PhpCall f args ->
      let
        st1 = if usesVar ctx.owner f then st { bad = true } else scanExpression ctx st f
      in
        A.foldl
          (\acc a -> if usesVar ctx.owner a then acc { bad = true } else scanExpression ctx acc a)
          st1
          args
    _ -> st

scanCall :: OwnershipContext -> ScanState -> String -> Array PhpExpr -> ScanState
scanCall ctx st target args =
  A.foldl (\acc (Tuple i a) -> scanCallArg ctx acc target i a) st (A.mapWithIndex Tuple args)

scanCallArg :: OwnershipContext -> ScanState -> String -> Int -> PhpExpr -> ScanState
scanCallArg ctx st target i a = case a of
  PhpVar v | v == ctx.owner ->
    if Set.member i promoted then st { mutated = Set.insert allFields st.mutated }
    else st { bad = true }
  PhpPropertyAccess (PhpVar v) f | v == ctx.owner -> case fieldIndexOf f of
    Just field -> fieldArg field
    Nothing -> st { bad = true }
  PhpArrayIndex (PhpVar v) (PhpInt field) | v == ctx.owner -> fieldArg field
  _ ->
    if usesVar ctx.owner a then case callHead a of
      Just head -> scanCall ctx st head.target head.args
      Nothing -> st { bad = true }
    else scanExpression ctx st a
  where
  promoted = fromMaybe Set.empty (Map.lookup target ctx.byRef)

  fieldArg field =
    if Map.member target ctx.candidates then
      if Set.member i promoted then st { mutated = Set.insert field st.mutated } else st
    else st { bad = true }
