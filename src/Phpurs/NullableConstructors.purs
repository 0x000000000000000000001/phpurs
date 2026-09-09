-- Layout selection is consumed by the typed region proof. Lowering receives
-- only the fresh private constructor names emitted for those proven regions.
module Phpurs.NullableConstructors where

import Prelude

import Data.Array as A
import Data.Foldable (foldr)
import Data.List (List(..))
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Tuple (Tuple(..))
import Phpurs.PhpAst (PhpExpr(..), PhpFile)
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

-- Cover every expression form when lowering; keep children below in sync for
-- the budget walk. The scalar entry may be inside an Effect closure.
mapChildren :: (PhpExpr -> PhpExpr) -> PhpExpr -> PhpExpr
mapChildren f = case _ of
  PhpFunction caps args ret body -> PhpFunction caps args ret (map f body)
  PhpCompactFunction caps args ret body -> PhpCompactFunction caps args ret (map f body)
  PhpCompactLoop name args ret body -> PhpCompactLoop name args ret (map f body)
  PhpNativeFunction name args ret body -> PhpNativeFunction name args ret (map f body)
  PhpPrivateFunction name args ret body -> PhpPrivateFunction name args ret (map f body)
  PhpGlobalAssign name e -> PhpGlobalAssign name (f e)
  PhpVar name -> PhpVar name
  PhpGlobalVar ns name -> PhpGlobalVar ns name
  PhpDirectCall name args -> PhpDirectCall name (map f args)
  PhpCall e args -> PhpCall (f e) (map f args)
  PhpInt n -> PhpInt n
  PhpNumber n -> PhpNumber n
  PhpString s -> PhpString s
  PhpBoolean b -> PhpBoolean b
  PhpArray xs -> PhpArray (map f xs)
  PhpAssocArray xs -> PhpAssocArray (map (\x -> x { value = f x.value }) xs)
  PhpPropertyAccess e key -> PhpPropertyAccess (f e) key
  PhpRecordAccess e key -> PhpRecordAccess (f e) key
  PhpArrayIndex e ix -> PhpArrayIndex (f e) (f ix)
  PhpAssign name e -> PhpAssign name (f e)
  PhpAssignExpr a b -> PhpAssignExpr (f a) (f b)
  PhpIf c a b -> PhpIf (f c) (map f a) (map f b)
  PhpMatch e cases fallback -> PhpMatch (f e) (map (\c -> { val: f c.val, body: f c.body }) cases) (f fallback)
  PhpThrow e -> PhpThrow (f e)
  PhpTernary c a b -> PhpTernary (f c) (f a) (f b)
  PhpReturn e -> PhpReturn (f e)
  PhpBinOp op a b -> PhpBinOp op (f a) (f b)
  PhpWhile c body -> PhpWhile (f c) (map f body)
  PhpContinue -> PhpContinue
  PhpRaw s -> PhpRaw s
  PhpNew name args -> PhpNew name (map f args)
  PhpClone e -> PhpClone (f e)
  PhpSwitch e cases fallback -> PhpSwitch (f e)
    (map (\c -> { matchCases: map f c.matchCases, stmts: map f c.stmts }) cases) (map (map f) fallback)
  PhpGoto name -> PhpGoto name
  PhpLabel name -> PhpLabel name
  PhpInstanceOf e name -> PhpInstanceOf (f e) name

children :: PhpExpr -> Array PhpExpr
children = case _ of
  PhpFunction _ _ _ body -> body
  PhpCompactFunction _ _ _ body -> body
  PhpCompactLoop _ _ _ body -> body
  PhpNativeFunction _ _ _ body -> body
  PhpPrivateFunction _ _ _ body -> body
  PhpGlobalAssign _ e -> [ e ]
  PhpCall f args -> [ f ] <> args
  PhpDirectCall _ args -> args
  PhpArray xs -> xs
  PhpAssocArray xs -> map _.value xs
  PhpPropertyAccess e _ -> [ e ]
  PhpRecordAccess e _ -> [ e ]
  PhpArrayIndex e ix -> [ e, ix ]
  PhpAssign _ e -> [ e ]
  PhpAssignExpr a b -> [ a, b ]
  PhpIf c a b -> [ c ] <> a <> b
  PhpMatch e cases fallback -> [ e, fallback ] <> A.concatMap (\c -> [ c.val, c.body ]) cases
  PhpThrow e -> [ e ]
  PhpTernary c a b -> [ c, a, b ]
  PhpReturn e -> [ e ]
  PhpBinOp _ a b -> [ a, b ]
  PhpWhile c body -> [ c ] <> body
  PhpNew _ args -> args
  PhpClone e -> [ e ]
  PhpSwitch e cases fallback -> [ e ] <> A.concatMap (\c -> c.matchCases <> c.stmts) cases <> fromMaybe [] fallback
  PhpInstanceOf e _ -> [ e ]
  _ -> []

-- This decision is atomic for the whole module. Skipping just one oversized
-- worker could mix object E and null within the same private call graph.
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
