-- | Find call-site and TCO transfers, then solve private-parameter promotion.
-- | Per-owner alias and mutation checks live in ArrayRefs.Ownership.
module Phpurs.ArrayRefs.Analysis
  ( Call
  , Worker
  , callsIn
  , fixpoint
  , functionParts
  , hasSharedConstruction
  , paramIndex
  , setFunction
  , tcoCarrier
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
import Phpurs.ArrayRefs.Ownership as Ownership
import Phpurs.ArrayRefs.Support (callHead, exprChildren, fieldIndexOf, usesVar, varsIn)
import Phpurs.PhpAst (PhpDecl, PhpExpr(..))

type Worker =
  { args :: Array { name :: String, type_ :: String }
  , body :: Array PhpExpr
  }

-- | A private constructor built with the same child twice creates a DAG
-- | inside one version: an in-place update reached through one field would
-- | also change the other. The pass disables itself for the whole file when
-- | any construction repeats a variable or field read.
hasSharedConstruction :: Set String -> Array PhpDecl -> Boolean
hasSharedConstruction privateClasses decls = A.any (declShared privateClasses) decls

declShared :: Set String -> PhpDecl -> Boolean
declShared privateClasses decl = go decl.expression
  where
  go e = case e of
    PhpNew cls args | Set.member cls privateClasses -> hasDuplicate (A.filter retainedChild args) ||
      A.any go args
    _ -> A.any go (exprChildren e)

  retainedChild = case _ of
    PhpVar _ -> true
    PhpPropertyAccess (PhpVar _) _ -> true
    PhpArrayIndex (PhpVar _) (PhpInt _) -> true
    _ -> false

  hasDuplicate xs = case A.head xs of
    Nothing -> false
    Just x -> A.elem x (A.drop 1 xs) || hasDuplicate (A.drop 1 xs)

functionParts :: PhpExpr -> Maybe Worker
functionParts = case _ of
  PhpGlobalAssign _ inner -> functionParts inner
  PhpCall (PhpFunction _ args _ body) _ -> Just { args, body }
  PhpCall (PhpCompactFunction _ args _ body) _ -> Just { args, body }
  PhpFunction _ args _ body -> Just { args, body }
  PhpCompactFunction _ args _ body -> Just { args, body }
  PhpCompactLoop _ args _ body -> Just { args, body }
  PhpNativeFunction _ args _ body -> Just { args, body }
  PhpPrivateFunction _ args _ body -> Just { args, body }
  _ -> Nothing

setFunction :: PhpExpr -> Array { name :: String, type_ :: String } -> Array PhpExpr -> PhpExpr
setFunction e args body = case e of
  PhpFunction c _ ret _ -> PhpFunction c args ret body
  PhpCompactFunction c _ ret _ -> PhpCompactFunction c args ret body
  PhpCompactLoop name _ ret _ -> PhpCompactLoop name args ret body
  PhpNativeFunction name _ ret _ -> PhpNativeFunction name args ret body
  PhpPrivateFunction name _ ret _ -> PhpPrivateFunction name args ret body
  _ -> e

type Call = { target :: String, args :: Array PhpExpr }

callsIn :: PhpExpr -> Array Call
callsIn e = case e of
  PhpDirectCall target args -> A.cons { target, args } (A.concatMap callsIn args)
  PhpCall f args -> case callHead f of
    Just head -> A.cons { target: head.target, args: head.args <> args }
      (A.concatMap callsIn (A.cons f args))
    Nothing -> A.concatMap callsIn (A.cons f args)
  _ -> A.concatMap callsIn (exprChildren e)

paramIndex :: Array String -> String -> Maybe Int
paramIndex names name = A.findIndex (_ == name) names

-- Other-field reads of the parent stay allowed; re-reading the mutated field
-- or aliasing the parent would observe the update and is refused.
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
statementsIn :: Array PhpExpr -> Array PhpExpr
statementsIn ss = A.concatMap stmtsOf ss
  where
  stmtsOf st = A.cons st case st of
    PhpIf _ t e -> statementsIn t <> statementsIn e
    PhpSwitch _ cases d -> A.concatMap (\c -> statementsIn c.stmts) cases <> statementsIn
      (fromMaybe [] d)
    PhpWhile _ b -> statementsIn b
    _ -> []

type Site = { statement :: Int, call :: Call, tail :: Array PhpExpr }

-- | A call's continuation inside its innermost statement list, stopping at a
-- | goto: in generated code that jumps to a branch merge point which reads
-- | locals, never the mutated field. A branch without a goto falls through,
-- | so the enclosing tail is appended.
branchTail :: Array PhpExpr -> Array PhpExpr -> Array PhpExpr
branchTail tail outerTail = case A.findIndex isTerminator tail of
  Just t -> A.take (t + 1) tail
  Nothing -> case A.findIndex isGoto tail of
    Just _ -> tail
    Nothing -> tail <> outerTail

isTerminator :: PhpExpr -> Boolean
isTerminator = case _ of
  PhpReturn _ -> true
  PhpThrow _ -> true
  _ -> false

isGoto :: PhpExpr -> Boolean
isGoto = case _ of
  PhpGoto _ -> true
  _ -> false

sitesIn :: Array PhpExpr -> Array Site
sitesIn body = A.concatMap (\(Tuple i st) -> sitesOf i (branchTail (A.drop (i + 1) body) []) st)
  (A.mapWithIndex Tuple body)
  where
  sitesOf topIndex rest st = case st of
    PhpIf _ t e -> sitesOfList topIndex rest t <> sitesOfList topIndex rest e
    PhpSwitch _ cases d -> A.concatMap (sitesOfList topIndex rest) (map _.stmts cases) <>
      sitesOfList topIndex rest (fromMaybe [] d)
    PhpWhile _ b -> sitesOfList topIndex rest b
    PhpReturn _ -> map (\c -> { statement: topIndex, call: c, tail: [] }) (callsIn st)
    PhpThrow _ -> map (\c -> { statement: topIndex, call: c, tail: [] }) (callsIn st)
    _ -> map (\c -> { statement: topIndex, call: c, tail: rest }) (callsIn st)

  sitesOfList topIndex outerTail stmts =
    A.concatMap (\(Tuple j b) -> sitesOf topIndex (branchTail (A.drop (j + 1) stmts) outerTail) b)
      (A.mapWithIndex Tuple stmts)

fixpoint :: Map String (Set Int) -> Set String -> Array PhpDecl -> Map String (Set Int)
fixpoint candidates privateClasses decls = go candidates
  where
  go current =
    let
      next = A.foldl (violations candidates privateClasses current) current decls
    in
      if next == current then current else go next

violations
  :: Map String (Set Int)
  -> Set String
  -> Map String (Set Int)
  -> Map String (Set Int)
  -> PhpDecl
  -> Map String (Set Int)
violations candidates privateClasses current acc decl = case functionParts decl.expression of
  Just fn ->
    let
      ours = fromMaybe Set.empty (Map.lookup decl.identifier current)
      ownNames = map _.name fn.args
      ctx =
        { byRef: current
        , candidates
        , privateClasses
        , ownNames
        , ownByRef: ours
        , body: fn.body
        }
      bad = A.concatMap
        ( \site ->
            A.mapMaybe
              ( \ix -> case A.index site.call.args ix of
                  Just arg
                    | not (passable ctx site.tail site.statement arg) -> Just
                        (Tuple site.call.target ix)
                  _ -> Nothing
              )
              ( Set.toUnfoldable (fromMaybe Set.empty (Map.lookup site.call.target current))
                  :: Array Int
              )
        )
        (sitesIn fn.body)
      acc1 = A.foldl (\m (Tuple target ix) -> Map.update (Just <<< Set.delete ix) target m) acc bad
      escaped = A.mapMaybe
        ( \(Tuple ix name) ->
            if Set.member ix ours && hasUnsafeOwnerUse ctx name then Just (Tuple decl.identifier ix)
            else Nothing
        )
        (A.mapWithIndex Tuple ownNames)
    in
      A.foldl (\m (Tuple target ix) -> Map.update (Just <<< Set.delete ix) target m) acc1 escaped
  Nothing -> acc
