-- | Private-parameter promotion fixpoint.
module Phpurs.ArrayRefs.Fixpoint
  ( Worker
  , hasSharedConstruction
  , declShared
  , functionParts
  , setFunction
  , fixpoint
  , violations
  ) where

import Prelude

import Data.Array as A
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Set (Set)
import Data.Set as Set
import Data.Tuple (Tuple(..))
import Phpurs.ArrayRefs.CallSites (sitesIn)
import Phpurs.ArrayRefs.Decision (hasUnsafeOwnerUse, passable)
import Phpurs.ArrayRefs.Support (exprChildren)
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
