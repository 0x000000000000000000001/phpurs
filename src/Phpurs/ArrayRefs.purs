-- Promote private worker parameters to PHP references when every call site
-- can hand over ownership of the argument: last use of an owned parameter, a
-- field of an owned parameter, or a consumed TCO loop register. A rebuild of
-- the owned node from its own fields (one field replaced by a recursive
-- result) then becomes an in-place update instead of a fresh allocation.
--
-- In-place updates are only sound while no other live binding can observe the
-- mutated nodes, so promotion additionally requires that, inside the function
-- that hands the value over, the owner variable is never aliased or escaped:
-- no `$x = $owner`, no snapshot of `$owner->field` read after the same field
-- is consumed, no container, closure, global or raw-PHP retention, and no call
-- to a function outside the private-layout candidates. TCO registers must be
-- used only by the loop binding/call/writeback pattern. The function that owns
-- a promoted parameter is checked by the same scan on its own body, so a
-- callee cannot store or alias it either.
--
-- Assumption kept by the analysis: a local has one assignment, and candidate
-- functions do not retain their value parameters. Constructions that repeat a
-- child (internal DAG) disable the pass for the whole file.
-- See `phpurs/audit/2026-09-24/rbtree-alloc/report.md`.
module Phpurs.ArrayRefs (optimize) where

import Prelude

import Data.Array as A
import Data.Int as Int
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe, isJust)
import Data.Set (Set)
import Data.Set as Set
import Data.String as String
import Data.Tuple (Tuple(..))
import Phpurs.PhpAst (PhpDecl, PhpExpr(..), PhpFile)

optimize :: Map String (Set Int) -> Set String -> PhpFile -> PhpFile
optimize candidates privateClasses file =
  if hasSharedConstruction privateClasses file.decls then file
  else file { decls = map (rewriteDecl byRef) file.decls }
  where
  byRef = fixpoint candidates privateClasses file.decls

  rewriteDecl :: Map String (Set Int) -> PhpDecl -> PhpDecl
  rewriteDecl refs decl = case functionParts decl.expression of
    Just fn | Just ours <- Map.lookup decl.identifier refs, not (Set.isEmpty ours) ->
      let
        ownNames = map _.name fn.args
        promoted = A.mapMaybe identity (A.mapWithIndex (\i a -> if Set.member i ours then Just a.name else Nothing) fn.args)
        body' = mutateRebuilds privateClasses promoted fn.body
        body'' = rewriteBody refs ownNames ours body'
        args' = A.mapWithIndex (\i a -> if Set.member i ours then a { type_ = "&" } else a) fn.args
      in
        decl { expression = setFunction decl.expression args' body'' }
    _ -> decl

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
    PhpNew cls args | Set.member cls privateClasses -> hasDuplicate (A.filter retainedChild args) || A.any go args
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

exprChildren :: PhpExpr -> Array PhpExpr
exprChildren = case _ of
  PhpFunction _ _ _ body -> body
  PhpCompactFunction _ _ _ body -> body
  PhpCompactLoop _ _ _ body -> body
  PhpNativeFunction _ _ _ body -> body
  PhpPrivateFunction _ _ _ body -> body
  PhpGlobalAssign _ e -> [ e ]
  PhpDirectCall _ args -> args
  PhpCall f args -> A.cons f args
  PhpArray es -> es
  PhpAssocArray kvs -> map _.value kvs
  PhpPropertyAccess e _ -> [ e ]
  PhpRecordAccess e _ -> [ e ]
  PhpArrayIndex a b -> [ a, b ]
  PhpAssign _ e -> [ e ]
  PhpAssignExpr a b -> [ a, b ]
  PhpIf c t e -> A.cons c (t <> e)
  PhpMatch s cases d -> A.cons s (A.concatMap (\c -> [ c.val, c.body ]) cases <> [ d ])
  PhpThrow e -> [ e ]
  PhpTernary a b c -> [ a, b, c ]
  PhpReturn e -> [ e ]
  PhpBinOp _ a b -> [ a, b ]
  PhpWhile c body -> A.cons c body
  PhpNew _ args -> args
  PhpClone e -> [ e ]
  PhpSwitch s cases d -> A.cons s (A.concatMap (\c -> c.matchCases <> c.stmts) cases <> fromMaybe [] d)
  PhpInstanceOf e _ -> [ e ]
  _ -> []

varsIn :: PhpExpr -> Array String
varsIn e = case e of
  PhpVar v -> [ v ]
  PhpFunction captures _ _ _ -> captures <> A.concatMap varsIn (exprChildren e)
  PhpCompactFunction captures _ _ _ -> captures <> A.concatMap varsIn (exprChildren e)
  _ -> A.concatMap varsIn (exprChildren e)

usesVar :: String -> PhpExpr -> Boolean
usesVar name e = A.elem name (varsIn e)

type Call = { target :: String, args :: Array PhpExpr }

-- | Curried applications are nested calls: `((f a) b)` is one call with two
-- | arguments. Flatten them before checking passability.
callHead :: PhpExpr -> Maybe { target :: String, args :: Array PhpExpr }
callHead = case _ of
  PhpGlobalVar mbMod ident -> Just { target: modKey mbMod ident, args: [] }
  PhpCall f args -> case callHead f of
    Just head -> Just { target: head.target, args: head.args <> args }
    Nothing -> Nothing
  _ -> Nothing

callsIn :: PhpExpr -> Array Call
callsIn e = case e of
  PhpDirectCall target args -> A.cons { target, args } (A.concatMap callsIn args)
  PhpCall f args -> case callHead f of
    Just head -> A.cons { target: head.target, args: head.args <> args } (A.concatMap callsIn (A.cons f args))
    Nothing -> A.concatMap callsIn (A.cons f args)
  _ -> A.concatMap callsIn (exprChildren e)

modKey :: Maybe (Array String) -> String -> String
modKey mbMod ident = case mbMod of
  Just parts -> String.joinWith "_" parts <> "_" <> ident
  Nothing -> ident

paramIndex :: Array String -> String -> Maybe Int
paramIndex names name = A.findIndex (_ == name) names

fieldAccessor :: String -> Int -> PhpExpr
fieldAccessor p i = PhpPropertyAccess (PhpVar p) ("value" <> show i)

fieldIndexOf :: String -> Maybe Int
fieldIndexOf f = case String.stripPrefix (String.Pattern "value") f of
  Just digits -> Int.fromString digits
  Nothing -> Nothing

arrayAccessor :: String -> Int -> PhpExpr
arrayAccessor p i = PhpArrayIndex (PhpVar p) (PhpInt i)

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

type ScanCtx =
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

-- | The caller hands `arg` over; the owner of the value must not be reachable
-- | from any other binding of the calling function.
passable :: SiteCtx -> Array PhpExpr -> Int -> PhpExpr -> Boolean
passable ctx tail tcoIndex arg = case arg of
  PhpVar v ->
    ( case paramIndex ctx.ownNames v of
        Just ix ->
          Set.member ix ctx.ownByRef
            && not (A.any (usesVar v) tail)
            && not (scanOwnerBad ctx v)
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
            && not (scanOwnerBad ctx p)
        Nothing -> false
    Nothing -> false
  PhpArrayIndex (PhpVar p) (PhpInt field) ->
    case paramIndex ctx.ownNames p of
      Just ix ->
        Set.member ix ctx.ownByRef
          && parentReadsOk tail p field
          && not (scanOwnerBad ctx p)
      Nothing -> false
  _ -> false

scanCtxOf :: SiteCtx -> String -> ScanCtx
scanCtxOf site owner =
  { owner
  , byRef: site.byRef
  , candidates: site.candidates
  , privateClasses: site.privateClasses
  }

scanOwnerBad :: SiteCtx -> String -> Boolean
scanOwnerBad site owner = (A.foldl (scanStatement (scanCtxOf site owner)) initial site.body).bad
  where
  initial = { bad: false, mutated: Set.empty, slots: Map.empty }

scanStatements :: ScanCtx -> ScanState -> Array PhpExpr -> ScanState
scanStatements ctx = A.foldl (scanStatement ctx)

scanStatement :: ScanCtx -> ScanState -> PhpExpr -> ScanState
scanStatement ctx st e = case e of
  PhpAssign name value ->
    let
      st1 = scanAssignValue ctx st name value
    in
      case value of
        PhpPropertyAccess (PhpVar v) f | v == ctx.owner -> case fieldIndexOf f of
          Just field -> st1 { slots = Map.insert name field st1.slots }
          Nothing -> st1
        PhpArrayIndex (PhpVar v) (PhpInt field) | v == ctx.owner -> st1 { slots = Map.insert name field st1.slots }
        _ -> st1
  PhpIf c t el -> scanStatements ctx (scanStatements ctx (scanRead ctx st c) t) el
  PhpSwitch s cases d ->
    let
      st1 = scanRead ctx st s
      st2 = A.foldl (\acc c -> scanStatements ctx acc c.stmts) st1 cases
    in
      case d of
        Just stmts -> scanStatements ctx st2 stmts
        Nothing -> st2
  PhpWhile c b -> scanStatements ctx (scanRead ctx st c) b
  PhpReturn value -> scanReturn ctx st value
  _ -> scanExpression ctx st e

scanAssignValue :: ScanCtx -> ScanState -> String -> PhpExpr -> ScanState
scanAssignValue ctx st name value
  | name == ctx.owner = case value of
      PhpVar c | String.take 10 c == "__tco_var_" -> st
      _ -> st { bad = true }
  | otherwise = case value of
      PhpVar v | v == ctx.owner && String.take 10 name == "__tco_var_" -> st
      PhpVar v | v == ctx.owner -> st { bad = true }
      _ -> scanExpression ctx st value

scanReturn :: ScanCtx -> ScanState -> PhpExpr -> ScanState
scanReturn ctx st e = case e of
  PhpVar v | v == ctx.owner -> st
  PhpPropertyAccess (PhpVar v) _ | v == ctx.owner -> st { bad = true }
  PhpArrayIndex (PhpVar v) (PhpInt _) | v == ctx.owner -> st { bad = true }
  _ -> scanExpression ctx st e

-- | A read position may mention the owner directly: reading it cannot create
-- | an alias.
scanRead :: ScanCtx -> ScanState -> PhpExpr -> ScanState
scanRead ctx st e = case e of
  PhpVar v | v == ctx.owner -> st
  _ -> scanExpression ctx st e

isOwnerVar :: ScanCtx -> PhpExpr -> Boolean
isOwnerVar ctx e = case e of
  PhpVar v -> v == ctx.owner
  _ -> false

scanExpression :: ScanCtx -> ScanState -> PhpExpr -> ScanState
scanExpression ctx st e = case e of
  PhpVar v
    | v == ctx.owner -> st { bad = true }
    | otherwise -> case Map.lookup v st.slots of
        Just field | Set.member allFields st.mutated || Set.member field st.mutated -> st { bad = true }
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
      mentionsSlot = A.any (\(Tuple name _) -> String.contains (String.Pattern ("$" <> name)) raw) (Map.toUnfoldable st.slots :: Array (Tuple String Int))
    in
      if mentionsOwner || mentionsSlot then st { bad = true } else st
  _ -> st

scanSwitch :: ScanCtx -> ScanState -> PhpExpr -> Array { matchCases :: Array PhpExpr, stmts :: Array PhpExpr } -> Maybe (Array PhpExpr) -> ScanState
scanSwitch ctx st s cases d =
  let
    st1 = scanRead ctx st s
    st2 = A.foldl (\acc c -> scanStatements ctx acc c.stmts) st1 cases
  in
    case d of
      Just stmts -> scanStatements ctx st2 stmts
      Nothing -> st2

scanClosure :: ScanCtx -> ScanState -> Array String -> Array PhpExpr -> ScanState
scanClosure ctx st captures body =
  let
    slotNames = map (\(Tuple name _) -> name) (Map.toUnfoldable st.slots :: Array (Tuple String Int))
    bodyVars = A.concatMap varsIn body
  in
    if A.elem ctx.owner captures || A.any (usesVar ctx.owner) body || A.any (\name -> A.elem name captures || A.elem name bodyVars) slotNames
      then st { bad = true }
      else st

-- | A value stored as a field of a freshly built private node stays inside the
-- | update; a nested private construction built from the owner's fields is
-- | refused because it would share them into a second structure the scan
-- | cannot follow.
scanConstructionArg :: ScanCtx -> ScanState -> PhpExpr -> ScanState
scanConstructionArg ctx st a = case a of
  PhpVar v | v == ctx.owner -> st { bad = true }
  PhpPropertyAccess (PhpVar v) _ | v == ctx.owner -> st
  PhpArrayIndex (PhpVar v) (PhpInt _) | v == ctx.owner -> st
  _ ->
    if usesVar ctx.owner a then case callHead a of
      Just head -> scanCall ctx st head.target head.args
      Nothing -> st { bad = true }
    else scanExpression ctx st a

scanCallExpr :: ScanCtx -> ScanState -> PhpExpr -> ScanState
scanCallExpr ctx st e = case callHead e of
  Just head -> scanCall ctx st head.target head.args
  Nothing -> case e of
    PhpCall f args ->
      let
        st1 = if usesVar ctx.owner f then st { bad = true } else scanExpression ctx st f
      in
        A.foldl (\acc a -> if usesVar ctx.owner a then acc { bad = true } else scanExpression ctx acc a) st1 args
    _ -> st

scanCall :: ScanCtx -> ScanState -> String -> Array PhpExpr -> ScanState
scanCall ctx st target args =
  A.foldl (\acc (Tuple i a) -> scanCallArg ctx acc target i a) st (A.mapWithIndex Tuple args)

scanCallArg :: ScanCtx -> ScanState -> String -> Int -> PhpExpr -> ScanState
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
      direct = A.length (A.filter (\(Tuple i a) -> a == PhpVar reg && Set.member i promoted) (A.mapWithIndex Tuple head.args))
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
    PhpSwitch _ cases d -> A.concatMap (\c -> statementsIn c.stmts) cases <> statementsIn (fromMaybe [] d)
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
sitesIn body = A.concatMap (\(Tuple i st) -> sitesOf i (branchTail (A.drop (i + 1) body) []) st) (A.mapWithIndex Tuple body)
  where
  sitesOf topIndex rest st = case st of
    PhpIf _ t e -> sitesOfList topIndex rest t <> sitesOfList topIndex rest e
    PhpSwitch _ cases d -> A.concatMap (sitesOfList topIndex rest) (map _.stmts cases) <> sitesOfList topIndex rest (fromMaybe [] d)
    PhpWhile _ b -> sitesOfList topIndex rest b
    PhpReturn _ -> map (\c -> { statement: topIndex, call: c, tail: [] }) (callsIn st)
    PhpThrow _ -> map (\c -> { statement: topIndex, call: c, tail: [] }) (callsIn st)
    _ -> map (\c -> { statement: topIndex, call: c, tail: rest }) (callsIn st)

  sitesOfList topIndex outerTail stmts =
    A.concatMap (\(Tuple j b) -> sitesOf topIndex (branchTail (A.drop (j + 1) stmts) outerTail) b) (A.mapWithIndex Tuple stmts)

fixpoint :: Map String (Set Int) -> Set String -> Array PhpDecl -> Map String (Set Int)
fixpoint candidates privateClasses decls = go candidates
  where
  go current =
    let
      next = A.foldl (violations candidates privateClasses current) current decls
    in
      if next == current then current else go next

violations :: Map String (Set Int) -> Set String -> Map String (Set Int) -> Map String (Set Int) -> PhpDecl -> Map String (Set Int)
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
                    | not (passable ctx site.tail site.statement arg) -> Just (Tuple site.call.target ix)
                  _ -> Nothing
              )
              (Set.toUnfoldable (fromMaybe Set.empty (Map.lookup site.call.target current)) :: Array Int)
        )
        (sitesIn fn.body)
      acc1 = A.foldl (\m (Tuple target ix) -> Map.update (Just <<< Set.delete ix) target m) acc bad
      escaped = A.mapMaybe
        ( \(Tuple ix name) ->
            if Set.member ix ours && scanOwnerBad ctx name then Just (Tuple decl.identifier ix) else Nothing
        )
        (A.mapWithIndex Tuple ownNames)
    in
      A.foldl (\m (Tuple target ix) -> Map.update (Just <<< Set.delete ix) target m) acc1 escaped
  Nothing -> acc

-- | Unique local assignments of a body; an ambiguous local maps to Nothing.
type Assigns = Map String (Maybe PhpExpr)

collectAssigns :: Array PhpExpr -> Assigns
collectAssigns statements = A.foldl collect Map.empty (allStatements statements)
  where
  allStatements ss = A.concatMap statementAndChildren ss
  statementAndChildren st = A.cons st case st of
    PhpIf c t e -> allStatements t <> allStatements e
    PhpSwitch _ cases d -> A.concatMap (\cs -> allStatements cs.stmts) cases <> fromMaybe [] d
    PhpWhile _ body -> allStatements body
    _ -> []
  collect acc st = case st of
    PhpAssign name e -> Map.insert name (case Map.lookup name acc of
      Nothing -> Just e
      Just _ -> Nothing) acc
    _ -> acc

-- | Rebuilds of an owned node from its own fields become in-place updates.
-- | A rebuild whose fields are all unchanged simply returns the node.
mutateRebuilds :: Set String -> Array String -> Array PhpExpr -> Array PhpExpr
mutateRebuilds privateClasses promoted body = rewriteList assigns body
  where
  assigns = collectAssigns body

  rewriteList :: Assigns -> Array PhpExpr -> Array PhpExpr
  rewriteList table ss = A.concatMap (rewriteOne table) ss

  rewriteOne table st = case st of
    PhpAssign res (PhpNew cls args) | Set.member cls privateClasses ->
      case findMutation table args of
        Just m -> m.writes <> [ PhpAssign res (PhpVar m.node) ]
        Nothing -> [ st ]
    PhpReturn (PhpNew cls args) | Set.member cls privateClasses ->
      case findMutation table args of
        Just m -> m.writes <> [ PhpReturn (PhpVar m.node) ]
        Nothing -> [ st ]
    PhpIf c t e -> [ PhpIf c (rewriteList table t) (rewriteList table e) ]
    PhpSwitch s cases d -> [ PhpSwitch s (map (\cs -> cs { stmts = rewriteList table cs.stmts }) cases) (map (rewriteList table) d) ]
    PhpWhile c b -> [ PhpWhile c (rewriteList table b) ]
    _ -> [ st ]

  findMutation table args = A.head (A.mapMaybe (tryParam table args) promoted)

  tryParam table args p =
    let
      entries = A.mapWithIndex
        ( \j arg -> case arg of
            PhpVar v -> case Map.lookup v table of
              Just (Just e) -> Just (Tuple j e)
              _ -> Nothing
            _ -> Nothing
        )
        args
      pairs = A.mapMaybe identity entries
    in
      if A.length pairs /= A.length args then Nothing
      else if A.all (\(Tuple j e) -> hasAccessor p j e && not (otherAccessor p j e)) pairs then
        let
          writes = A.mapMaybe
            ( \(Tuple j e) ->
                if e == fieldAccessor p j then Nothing
                else Just (PhpAssignExpr (fieldAccessor p j) (localAt args j))
            )
            pairs
        in
          Just { node: p, writes }
      else Nothing

  hasAccessor p j e = e == fieldAccessor p j || A.any (hasAccessor p j) (exprChildren e)

  -- A field position may only be sourced by its own field, so a rebuild can
  -- never move one child into a different slot.
  otherAccessor p j e = case e of
    PhpPropertyAccess (PhpVar q) f | q == p -> f /= ("value" <> show j)
    PhpArrayIndex (PhpVar q) (PhpInt f) | q == p -> f /= j
    _ -> A.any (otherAccessor p j) (exprChildren e)

  localAt args j = case A.index args j of
    Just (PhpVar v) -> PhpVar v
    _ -> PhpRaw "null"

rewriteBody :: Map String (Set Int) -> Array String -> Set Int -> Array PhpExpr -> Array PhpExpr
rewriteBody byRef ownNames ownByRef body = A.concatMap (\(Tuple i st) -> step i st) (A.mapWithIndex Tuple body)
  where
  step i st =
    let
      unsets = A.concatMap
        ( \call -> case Map.lookup call.target byRef of
            Just targets ->
              A.mapMaybe
                ( \ix -> case A.index call.args ix of
                    Just (PhpVar reg)
                      | not (ownedParam ownNames ownByRef reg) ->
                          map (\carrier -> PhpRaw ("unset($" <> carrier <> ")")) (tcoCarrier body i reg)
                    _ -> Nothing
                )
                (Set.toUnfoldable targets :: Array Int)
            Nothing -> []
        )
        (callsIn st)
    in
      unsets <> [ st ]

ownedParam :: Array String -> Set Int -> String -> Boolean
ownedParam ownNames ownByRef reg = case paramIndex ownNames reg of
  Just ix -> Set.member ix ownByRef
  Nothing -> false
