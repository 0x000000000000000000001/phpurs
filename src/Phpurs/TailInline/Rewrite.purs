-- | Return simplification and local renaming for tail inlining.
module Phpurs.TailInline.Rewrite
  ( compactCopies
  , mapLocals
  , rename
  , simplifyReturns
  ) where

import Prelude

import Data.Array as Array
import Data.Foldable (foldMap, foldr)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.String as String
import Data.Tuple (Tuple(..))
import Phpurs.PhpAst (PhpExpr(..))
import Phpurs.PhpAst.Traversal (mapBlocks)
import Phpurs.TailInline.Proof (capturedNames, nodes, readNames, returnLabels)

-- Thread only continuations that return one local. This turns generated result
-- assignments/gotos into genuine tail positions without moving expressions.
simplifyReturns :: Array PhpExpr -> Array PhpExpr
simplifyReturns original = clean 4 (settle 16 original)
  where
  captured = foldMap capturedNames original
  settle 0 xs = xs
  settle n xs =
    let
      labels = returnLabels xs
      jump (PhpGoto l) = case Map.lookup l labels of
        Just v -> PhpReturn (PhpVar v)
        Nothing -> PhpGoto l
      jump e = e
      go xs' = pairs (map (jump <<< mapBlocks go) xs')
      pairs xs' = case Array.uncons xs' of
        Just { head: PhpAssign a v, tail } -> case Array.uncons tail of
          Just { head: PhpReturn (PhpVar b), tail: rest } | a == b && not (Array.elem a captured) ->
            [ PhpReturn v ] <> pairs rest
          _ -> [ PhpAssign a v ] <> pairs tail
        Just { head, tail } -> [ head ] <> pairs tail
        Nothing -> []
      next = go xs
    in
      if next == xs then xs else settle (n - 1) next

  clean 0 xs = xs
  clean n xs =
    let
      ns = Array.concatMap nodes xs
      reads = foldMap readNames xs
      jumps = Array.mapMaybe
        ( case _ of
            PhpGoto l -> Just l
            _ -> Nothing
        )
        ns
      keep = case _ of
        PhpAssign v PhpNull -> Array.elem v reads
        PhpAssign v (PhpRaw "null") -> Array.elem v reads
        PhpLabel l -> Array.elem l jumps
        _ -> true
      isLabel (PhpLabel _) = true
      isLabel _ = false
      dead block = case Array.uncons block of
        Just { head: head@(PhpReturn _), tail } -> [ head ] <> dead
          (Array.dropWhile (not <<< isLabel) tail)
        Just { head: head@(PhpThrow _), tail } -> [ head ] <> dead
          (Array.dropWhile (not <<< isLabel) tail)
        Just { head, tail } -> [ head ] <> dead tail
        Nothing -> []
      go block = dead (map (mapBlocks go) (Array.filter keep block))
      next = go xs
    in
      if next == xs then xs else clean (n - 1) next

-- Leaf bodies cannot recurse, invoke FFI/callbacks, loop or construct closures.
-- Typed PHP parameters/returns are excluded separately: bypassing their runtime
-- coercions/checks would require additional proof at the call site.
mapLocals :: (String -> PhpExpr) -> (String -> String) -> PhpExpr -> PhpExpr
mapLocals readName writeName = go
  where
  go = case _ of
    PhpVar v -> readName v
    PhpAssign v e -> PhpAssign (writeName v) (go e)
    PhpArray xs -> PhpArray (map go xs)
    PhpAssocArray xs -> PhpAssocArray (map (\x -> x { value = go x.value }) xs)
    PhpPropertyAccess e p -> PhpPropertyAccess (go e) p
    PhpRecordAccess e p -> PhpRecordAccess (go e) p
    PhpArrayIndex e ix -> PhpArrayIndex (go e) (go ix)
    PhpIf c t e -> PhpIf (go c) (map go t) (map go e)
    PhpMatch s cs d -> PhpMatch (go s) (map (\c -> { val: go c.val, body: go c.body }) cs) (go d)
    PhpThrow e -> PhpThrow (go e)
    PhpTernary c t e -> PhpTernary (go c) (go t) (go e)
    PhpReturn e -> PhpReturn (go e)
    PhpBinOp op a b -> PhpBinOp op (go a) (go b)
    PhpNew cls args -> PhpNew cls (map go args)
    PhpSwitch s cs d -> PhpSwitch (go s)
      (map (\c -> { matchCases: map go c.matchCases, stmts: map go c.stmts }) cs)
      (map (map go) d)
    PhpInstanceOf e cls -> PhpInstanceOf (go e) cls
    e -> e

rename :: String -> PhpExpr -> PhpExpr
rename prefix = mapLocals (\v -> PhpVar (prefix <> v)) (append prefix)

-- Inline a single-use local/ADT-field read immediately before a return. Only
-- leaf bodies reach this pass, so there are no callbacks or property writes.
-- Work backwards to preserve dependencies between successive assignments.
compactCopies :: Array PhpExpr -> Array PhpExpr
compactCopies block = foldr step [] (map (mapBlocks compactCopies) block)
  where
  copy = case _ of
    PhpVar _ -> true
    PhpPropertyAccess e _ -> copy e
    PhpInt _ -> true
    PhpNumber _ -> true
    PhpString _ -> true
    PhpBoolean _ -> true
    _ -> false
  step stmt rest = case stmt, Array.uncons rest of
    PhpAssign name value, Just { head: PhpReturn e, tail }
      | copy value && Array.length (Array.filter (eq name) (readNames e)) == 1 ->
          [ PhpReturn (mapLocals (\v -> if v == name then value else PhpVar v) identity e) ] <> tail
    _, _ -> [ stmt ] <> rest

