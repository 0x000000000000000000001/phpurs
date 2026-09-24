-- Promote private worker parameters to PHP references when every call site
-- can hand over ownership of the argument: last use of an owned parameter, a
-- field of an owned parameter, or a consumed TCO loop register. A rebuild of
-- the owned node from its own fields (one field replaced by a recursive
-- result) then becomes an in-place update instead of a fresh allocation.
-- See `phpurs/audit/2026-09-24/rbtree-alloc/report.md` for the measurements
-- and the ownership argument.
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
optimize candidates privateClasses file = file { decls = map (rewriteDecl byRef) file.decls }
  where
  byRef = fixpoint candidates file.decls

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

passable :: Array String -> Set Int -> Array PhpExpr -> Array PhpExpr -> Int -> PhpExpr -> Boolean
passable ownNames ownByRef tail tcoStatements tcoIndex arg = case arg of
  PhpVar v ->
    ( case paramIndex ownNames v of
        Just ix -> Set.member ix ownByRef && not (A.any (usesVar v) tail)
        Nothing -> false
    )
      || isJust (tcoCarrier tcoStatements tcoIndex v)
  PhpPropertyAccess (PhpVar p) f -> case fieldIndexOf f of
    Just field ->
      case paramIndex ownNames p of
        Just ix -> Set.member ix ownByRef && parentReadsOk tail p field
        Nothing -> false
    Nothing -> false
  PhpArrayIndex (PhpVar p) (PhpInt field) ->
    case paramIndex ownNames p of
      Just ix -> Set.member ix ownByRef && parentReadsOk tail p field
      Nothing -> false
  _ -> false

fieldIndexOf :: String -> Maybe Int
fieldIndexOf f = case String.stripPrefix (String.Pattern "value") f of
  Just digits -> Int.fromString digits
  Nothing -> Nothing

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

fixpoint :: Map String (Set Int) -> Array PhpDecl -> Map String (Set Int)
fixpoint candidates decls = go candidates
  where
  go current =
    let
      next = A.foldl (violations current) current decls
    in
      if next == current then current else go next

violations :: Map String (Set Int) -> Map String (Set Int) -> PhpDecl -> Map String (Set Int)
violations current acc decl = case functionParts decl.expression of
  Just fn ->
    let
      ours = fromMaybe Set.empty (Map.lookup decl.identifier current)
      ownNames = map _.name fn.args
      bad = A.concatMap
        ( \site ->
            A.mapMaybe
              ( \ix -> case A.index site.call.args ix of
                  Just arg
                    | not (passable ownNames ours site.tail fn.body site.statement arg) -> Just (Tuple site.call.target ix)
                  _ -> Nothing
              )
              (Set.toUnfoldable (fromMaybe Set.empty (Map.lookup site.call.target current)) :: Array Int)
        )
        (sitesIn fn.body)
    in
      A.foldl (\m (Tuple target ix) -> Map.update (Just <<< Set.delete ix) target m) acc bad
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
      else if A.all (\(Tuple j e) -> hasAccessor p j e) pairs then
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
