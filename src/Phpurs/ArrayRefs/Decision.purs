-- | Per-site and per-owner safety decisions.
module Phpurs.ArrayRefs.Decision
  ( badUse
  , parentReadsOk
  , lastAssignBefore
  , tcoCarrier
  , isCarrierAssign
  , SiteCtx
  , passable
  , hasUnsafeOwnerUse
  , tcoSafe
  , findConsumedTmp
  , consumesReg
  , tcoBad
  , mentionsTco
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
import Phpurs.ArrayRefs.CallSites (paramIndex, statementsIn)
import Phpurs.ArrayRefs.Ownership as Ownership
import Phpurs.ArrayRefs.Support (callHead, exprChildren, fieldIndexOf, usesVar, varsIn)
import Phpurs.PhpAst (PhpExpr(..))

badUse :: String -> Int -> PhpExpr -> Boolean
badUse p field e = case e of
  PhpPropertyAccess (PhpVar q) f | q == p -> f == ("value" <> show field)
  PhpArrayIndex (PhpVar q) (PhpInt f) | q == p -> f == field
  PhpVar q -> q == p
  _ -> A.any (badUse p field) (exprChildren e)

parentReadsOk :: Array PhpExpr -> String -> Int -> Boolean
parentReadsOk tail p field = not (A.any (badUse p field) tail)

lastAssignBefore :: Array PhpExpr -> Int -> String -> Maybe { index :: Int, value :: PhpExpr }
lastAssignBefore statements k name =
  A.foldl
    ( \acc (Tuple i st) -> case st of
        PhpAssign n v | n == name -> Just { index: i, value: v }
        _ -> acc
    )
    Nothing
    (A.mapWithIndex Tuple (A.take k statements))

-- A TCO loop register is bound at the loop head from a carrier; the carrier
-- is rewritten with the call result right after the call and the register is
-- not read again, so the call consumes the value. Dropping the carrier before
-- the call leaves a single owner and enables the in-place update.

tcoCarrier :: Array PhpExpr -> Int -> String -> Maybe String
tcoCarrier statements k reg = do
  tmp <- case A.index statements k of
    Just (PhpAssign t (PhpDirectCall _ _)) -> Just t
    Just (PhpAssign t (PhpCall _ _)) -> Just t
    _ -> Nothing
  binding <- lastAssignBefore statements k reg
  carrier <- case binding.value of
    PhpVar c | String.take 10 c == "__tco_var_" -> Just c
    _ -> Nothing
  if A.any (usesVar carrier) (A.slice (binding.index + 1) k statements) then Nothing
  else do
    let after = A.drop (k + 1) statements
    idx <- A.findIndex (isCarrierAssign carrier tmp) after
    if idx > 3 then Nothing
    else if A.any (\st -> usesVar reg st || usesVar carrier st) (A.take idx after) then Nothing
    else Just carrier

isCarrierAssign :: String -> String -> PhpExpr -> Boolean
isCarrierAssign carrier tmp = case _ of
  PhpAssign c (PhpVar t) -> c == carrier && t == tmp
  _ -> false

-- | The function being inspected for ownership, plus the current promotion map
-- | and the private-layout candidates.

type SiteCtx =
  { byRef :: Map String (Set Int)
  , candidates :: Map String (Set Int)
  , privateClasses :: Set String
  , ownNames :: Array String
  , ownByRef :: Set Int
  , body :: Array PhpExpr
  }

-- | The caller hands `arg` over; the owner of the value must not be reachable
-- | from any other binding of the calling function.

passable :: SiteCtx -> Array PhpExpr -> Int -> PhpExpr -> Boolean
passable ctx tail tcoIndex arg = case arg of
  PhpVar v ->
    ( case paramIndex ctx.ownNames v of
        Just ix ->
          Set.member ix ctx.ownByRef
            && not (A.any (usesVar v) tail)
            && not (hasUnsafeOwnerUse ctx v)
        Nothing -> false
    )
      || case tcoCarrier ctx.body tcoIndex v of
        Just carrier -> tcoSafe ctx v carrier
        Nothing -> false
  PhpPropertyAccess (PhpVar p) f -> case fieldIndexOf f of
    Just field ->
      case paramIndex ctx.ownNames p of
        Just ix ->
          Set.member ix ctx.ownByRef
            && parentReadsOk tail p field
            && not (hasUnsafeOwnerUse ctx p)
        Nothing -> false
    Nothing -> false
  PhpArrayIndex (PhpVar p) (PhpInt field) ->
    case paramIndex ctx.ownNames p of
      Just ix ->
        Set.member ix ctx.ownByRef
          && parentReadsOk tail p field
          && not (hasUnsafeOwnerUse ctx p)
      Nothing -> false
  _ -> false

hasUnsafeOwnerUse :: SiteCtx -> String -> Boolean
hasUnsafeOwnerUse site owner = Ownership.hasUnsafeUse
  { owner
  , byRef: site.byRef
  , candidates: site.candidates
  , privateClasses: site.privateClasses
  }
  site.body

-- | TCO registers are only allowed to move through the loop pattern: entry
-- | carrier initialization, loop-head binding, the consuming call, the
-- | carrier writeback and the accumulator return. Anything else (an alias, a
-- | container, a capture, a foreign call, a raw statement) refuses promotion.

tcoSafe :: SiteCtx -> String -> String -> Boolean
tcoSafe ctx reg carrier =
  let
    mTmp = findConsumedTmp ctx reg carrier
  in
    not (A.any (tcoBad ctx reg carrier mTmp) (statementsIn ctx.body))

findConsumedTmp :: SiteCtx -> String -> String -> Maybe String
findConsumedTmp ctx reg carrier = A.head (A.mapMaybe go (statementsIn ctx.body))
  where
  go st = case st of
    PhpAssign name v | consumesReg ctx reg carrier v -> Just name
    _ -> Nothing

consumesReg :: SiteCtx -> String -> String -> PhpExpr -> Boolean
consumesReg ctx reg carrier v = case callHead v of
  Just head ->
    let
      promoted = fromMaybe Set.empty (Map.lookup head.target ctx.byRef)
      direct = A.length
        ( A.filter (\(Tuple i a) -> a == PhpVar reg && Set.member i promoted)
            (A.mapWithIndex Tuple head.args)
        )
      total = A.length (A.filter (_ == reg) (varsIn v))
    in
      direct > 0 && total == direct && not (A.elem carrier (varsIn v))
  Nothing -> false

tcoBad :: SiteCtx -> String -> String -> Maybe String -> PhpExpr -> Boolean
tcoBad ctx reg carrier mTmp e = case e of
  PhpAssign x v
    | x == carrier && v == PhpVar reg -> false
    | x == reg && v == PhpVar carrier -> false
    | x == carrier, PhpVar y <- v, Just y == mTmp -> false
    | x == "__res" && v == PhpVar reg -> false
    | Just x == mTmp -> not (consumesReg ctx reg carrier v)
    | otherwise -> mentionsTco reg carrier e
  PhpReturn v -> not (v == PhpVar reg) && mentionsTco reg carrier e
  PhpIf c t el ->
    mentionsTco reg carrier c
      || A.any (tcoBad ctx reg carrier mTmp) t
      || A.any (tcoBad ctx reg carrier mTmp) el
  PhpSwitch s cases d ->
    mentionsTco reg carrier s
      || A.any (\c -> A.any (tcoBad ctx reg carrier mTmp) c.stmts) cases
      || A.any (tcoBad ctx reg carrier mTmp) (fromMaybe [] d)
  PhpWhile c b -> mentionsTco reg carrier c || A.any (tcoBad ctx reg carrier mTmp) b
  _ -> mentionsTco reg carrier e

mentionsTco :: String -> String -> PhpExpr -> Boolean
mentionsTco reg carrier e =
  A.elem reg (varsIn e)
    || A.elem carrier (varsIn e)
    || case e of
      PhpRaw raw ->
        String.contains (String.Pattern ("$" <> reg)) raw
          || String.contains (String.Pattern ("$" <> carrier)) raw
      _ -> false

-- | All statements, including nested blocks, in textual order.
