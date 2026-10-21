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
import Phpurs.ArrayRefs.Analysis (callsIn, fixpoint, functionParts, hasSharedConstruction, paramIndex, setFunction, tcoCarrier)
import Phpurs.ArrayRefs.Rebuild (mutateRebuilds)
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Set (Set)
import Data.Set as Set
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
