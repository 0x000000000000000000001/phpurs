-- Layout selection is consumed by the typed region proof. Lowering receives
-- only the fresh private constructor names emitted for those proven regions.
module Phpurs.NullableConstructors (Layout, layout, lower, nodeBudget, depthBudget) where

import Prelude

import Data.Array as A
import Data.Foldable (foldr)
import Data.List (List(..))
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Tuple (Tuple(..))
import Phpurs.PhpAst (PhpExpr(..), PhpFile)
import Phpurs.PhpAst.Traversal (children, mapChildren)
import PureScript.Backend.Optimizer.CoreFn (DataDecl)

type Layout = { empty :: String, boxed :: String }

-- The payload constructor stays boxed, even if its fields are themselves
-- nullable. Multiple empty/payload constructors cannot use this binary test.
-- Field types and all uses are checked by EnumRegions before names are issued.
layout :: DataDecl -> Maybe Layout
layout d | not (A.null d.vars) = Nothing
layout d = case d.constructors of
  [ a, b ] | A.null a.fields && not (A.null b.fields) -> Just { empty: a.name, boxed: b.name }
  [ a, b ] | A.null b.fields && not (A.null a.fields) -> Just { empty: b.name, boxed: a.name }
  _ -> Nothing

nodeBudget :: Int
nodeBudget = 131072

depthBudget :: Int
depthBudget = 256

-- This decision is atomic for the whole module. Skipping just one oversized
-- worker could mix object E and null within the same private call graph.
-- The traversal includes function bodies: the scalar entry may be an Effect.
bounded :: Array PhpExpr -> Boolean
bounded exprs = go 0 (foldr (\e xs -> Cons (Tuple 0 e) xs) Nil exprs)
  where
  go count _ | count > nodeBudget = false
  go _ Nil = true
  go count (Cons (Tuple depth e) rest) =
    let cs = children e
    in if depth > depthBudget || A.length cs > 8192 then false
      else go (count + 1) (foldr (\child xs -> Cons (Tuple (depth + 1) child) xs) rest cs)

-- True denotes the empty constructor; false denotes its boxed alternative.
-- No untyped expression is inferred to be nullable here: the map is the proof.
lower :: Map String Boolean -> PhpFile -> PhpFile
lower names file
  | Map.isEmpty names || not (bounded (map _.expression file.decls)) = file
  | otherwise = file { decls = map (\d -> d { expression = go d.expression }) file.decls }
  where
  go expr = case mapChildren go expr of
    PhpNew name [] | Map.lookup name names == Just true -> PhpRaw "null"
    PhpInstanceOf value name | Just empty <- Map.lookup name names ->
      PhpBinOp (if empty then "===" else "!==") value (PhpRaw "null")
    other -> other
