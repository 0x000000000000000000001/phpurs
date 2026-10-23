-- | Rewrite owned-node rebuilds into in-place field writes.
module Phpurs.ArrayRefs.Rebuild
  ( mutateRebuilds
  ) where

import Prelude

import Data.Array as Array
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Set (Set)
import Data.Set as Set
import Data.Tuple (Tuple(..))
import Phpurs.ArrayRefs.Support (exprChildren, fieldAccessor)
import Phpurs.PhpAst (PhpExpr(..))
import Phpurs.PhpAst.Traversal (mapBlocks)

-- | Unique local assignments of a body; an ambiguous local maps to Nothing.
type Assigns = Map String (Maybe PhpExpr)

collectAssigns :: Array PhpExpr -> Assigns
collectAssigns statements = Array.foldl collect Map.empty (allStatements statements)
  where
  allStatements ss = Array.concatMap statementAndChildren ss
  statementAndChildren st = Array.cons st case st of
    PhpIf _ t e -> allStatements t <> allStatements e
    PhpSwitch _ cases d -> Array.concatMap (\cs -> allStatements cs.stmts) cases <> fromMaybe [] d
    PhpWhile _ body -> allStatements body
    _ -> []
  collect acc st = case st of
    PhpAssign name e -> Map.insert name
      ( case Map.lookup name acc of
          Nothing -> Just e
          Just _ -> Nothing
      )
      acc
    _ -> acc

-- | Rebuilds of an owned node from its own fields become in-place updates.
-- | A rebuild whose fields are all unchanged simply returns the node.
mutateRebuilds :: Set String -> Array String -> Array PhpExpr -> Array PhpExpr
mutateRebuilds privateClasses promoted body = rewriteList assigns body
  where
  assigns = collectAssigns body

  rewriteList :: Assigns -> Array PhpExpr -> Array PhpExpr
  rewriteList table ss = Array.concatMap (rewriteOne table) ss

  rewriteOne table st = case st of
    PhpAssign res (PhpNew cls args) | Set.member cls privateClasses ->
      case findMutation table args of
        Just m -> m.writes <> [ PhpAssign res (PhpVar m.node) ]
        Nothing -> [ st ]
    PhpReturn (PhpNew cls args) | Set.member cls privateClasses ->
      case findMutation table args of
        Just m -> m.writes <> [ PhpReturn (PhpVar m.node) ]
        Nothing -> [ st ]
    other -> [ mapBlocks (rewriteList table) other ]

  findMutation table args = Array.head (Array.mapMaybe (tryParam table args) promoted)

  tryParam table args p =
    let
      entries = Array.mapWithIndex
        ( \j arg -> case arg of
            PhpVar v -> case Map.lookup v table of
              Just (Just e) -> Just (Tuple j e)
              _ -> Nothing
            _ -> Nothing
        )
        args
      pairs = Array.mapMaybe identity entries
    in
      if Array.length pairs /= Array.length args then Nothing
      else if Array.all (\(Tuple j e) -> hasAccessor p j e && not (otherAccessor p j e)) pairs then
        let
          writes = Array.mapMaybe
            ( \(Tuple j e) ->
                if e == fieldAccessor p j then Nothing
                else Just (PhpAssignExpr (fieldAccessor p j) (localAt args j))
            )
            pairs
        in
          Just { node: p, writes }
      else Nothing

  hasAccessor p j e = e == fieldAccessor p j || Array.any (hasAccessor p j) (exprChildren e)

  -- A field position may only be sourced by its own field, so a rebuild can
  -- never move one child into a different slot.
  otherAccessor p j e = case e of
    PhpPropertyAccess (PhpVar q) f | q == p -> f /= ("value" <> show j)
    PhpArrayIndex (PhpVar q) (PhpInt f) | q == p -> f /= j
    _ -> Array.any (otherAccessor p j) (exprChildren e)

  localAt args j = case Array.index args j of
    Just (PhpVar v) -> PhpVar v
    _ -> PhpNull
