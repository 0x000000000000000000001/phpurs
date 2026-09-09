-- Local copy elimination after terminal inlining. The caller supplies the
-- workers and constructors of closed regions proved on the typed AST.
module Phpurs.CopyCleanup (optimize, nodeBudget, depthBudget, widthBudget) where

import Prelude

import Data.Array as A
import Data.Foldable (all, any, foldl, foldr)
import Data.List (List(..))
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Set (Set)
import Data.Set as Set
import Data.String as String
import Data.Tuple (Tuple(..))
import Phpurs.PhpAst (PhpExpr(..), PhpFile)
import Phpurs.TailInline (simplifyReturns)

nodeBudget :: Int
nodeBudget = 8192

depthBudget :: Int
depthBudget = 128

widthBudget :: Int
widthBudget = 512

-- Decline the whole worker on opaque code, nested functions, mutations through
-- aliases, or dynamic callbacks. This also protects return-threading from
-- references captured by a closure. New AST forms must opt in explicitly.
children :: PhpExpr -> Maybe (Array PhpExpr)
children = case _ of
  PhpVar _ -> Just []
  PhpGlobalVar _ _ -> Just []
  PhpInt _ -> Just []
  PhpNumber _ -> Just []
  PhpString _ -> Just []
  PhpBoolean _ -> Just []
  PhpRaw "null" -> Just []
  PhpRaw "" -> Just []
  -- This exact CodeGen diagnostic reads no locals and executes no user code.
  PhpRaw "\"Failed pattern match at \" . __FILE__ . \":\" . __LINE__" -> Just []
  PhpAssign _ e -> Just [e]
  PhpPropertyAccess e _ -> Just [e]
  PhpBinOp _ a b -> Just [a, b]
  PhpInstanceOf e _ -> Just [e]
  PhpNew _ args -> Just args
  PhpCall f@(PhpGlobalVar _ _) args -> Just ([f] <> args)
  -- Saturated curried calls remain nested until Printer flattens them. The
  -- worklist checks every inner call, eventually requiring a global target.
  PhpCall f@(PhpCall _ _) args -> Just ([f] <> args)
  PhpDirectCall _ args -> Just args
  PhpIf c t e -> Just ([c] <> t <> e)
  PhpSwitch s cs d -> Just ([s] <> A.concatMap (\c -> c.matchCases <> c.stmts) cs <> fromMaybe [] d)
  PhpWhile c b -> Just ([c] <> b)
  PhpContinue -> Just []
  PhpReturn e -> Just [e]
  PhpThrow e -> Just [e]
  PhpLabel _ -> Just []
  PhpGoto _ -> Just []
  _ -> Nothing

-- Check size/depth before recursive rewriting, including nodes in conditions
-- and call arguments. A failed budget leaves this worker byte-for-byte intact.
inspect :: Array PhpExpr -> Maybe (Array PhpExpr)
inspect body | A.length body > widthBudget = Nothing
inspect body = go 0 Nil (push 0 body Nil)
  where
  push depth xs rest = foldr (\e more -> Cons (Tuple depth e) more) rest xs
  go _ result Nil = Just (A.fromFoldable result)
  go count result (Cons (Tuple depth e) rest)
    | count >= nodeBudget || depth > depthBudget = Nothing
    | otherwise = case children e of
        Just cs | A.length cs <= widthBudget -> go (count + 1) (Cons e result) (push (depth + 1) cs rest)
        _ -> Nothing

mapBlocks :: (Array PhpExpr -> Array PhpExpr) -> PhpExpr -> PhpExpr
mapBlocks f = case _ of
  PhpIf c t e -> PhpIf c (f t) (f e)
  PhpSwitch s cs d -> PhpSwitch s (map (\c -> c { stmts = f c.stmts }) cs) (map f d)
  PhpWhile c b -> PhpWhile c (f b)
  e -> e

-- Generated constructors have no user code/destructors; field reads refer to
-- their declared native properties. No callback/call can change a source local
-- between the eliminated copy and its use in the terminal expression.
pureResult :: Set String -> PhpExpr -> Boolean
pureResult constructors = go
  where
  go = case _ of
    PhpVar _ -> true
    PhpInt _ -> true
    PhpNumber _ -> true
    PhpString _ -> true
    PhpBoolean _ -> true
    PhpRaw "null" -> true
    PhpPropertyAccess e _ -> go e
    PhpBinOp _ a b -> go a && go b
    PhpNew name args -> Set.member name constructors && all go args
    _ -> false

-- This traversal is only used after pureResult, never across a scope boundary.
replaceLocal :: String -> String -> PhpExpr -> PhpExpr
replaceLocal name source = go
  where
  go = case _ of
    PhpVar v -> PhpVar (if v == name then source else v)
    PhpPropertyAccess e p -> PhpPropertyAccess (go e) p
    PhpBinOp op a b -> PhpBinOp op (go a) (go b)
    PhpNew cls args -> PhpNew cls (map go args)
    e -> e

mentions :: String -> PhpExpr -> Boolean
mentions name (PhpVar v) = name == v
mentions name e = any (mentions name) (fromMaybe [] (children e))

terminalCopies :: Set String -> Array PhpExpr -> Array PhpExpr
terminalCopies constructors = go
  where
  go body = foldr step [] (map (mapBlocks go) body)
  step stmt rest = case stmt, A.uncons rest of
    PhpAssign name (PhpVar source), Just { head: PhpReturn e, tail }
      | pureResult constructors e && mentions name e ->
          [PhpReturn (replaceLocal name source e)] <> tail
    _, _ -> [stmt] <> rest

-- Recognize an entry round-trip by its use/definition counts, not its generated
-- name. Real loop backedges/labels, permutations, later uses and captures keep
-- their original simultaneous parameter updates.
entryCopies :: Array String -> Array PhpExpr -> Array PhpExpr
entryCopies args body =
  let
    copy = case _ of
      PhpAssign tmp (PhpVar arg) -> A.elem arg args && not (A.elem tmp args)
      _ -> false
    initial = A.takeWhile copy body
    pairs = A.mapMaybe (case _ of
      PhpAssign tmp (PhpVar arg) -> Just (Tuple tmp arg)
      _ -> Nothing) initial
    n = A.length pairs
    reversed = map (\(Tuple tmp arg) -> PhpAssign arg (PhpVar tmp)) pairs
    rest = A.drop n body
    unique = Set.size (Set.fromFoldable (map (\(Tuple tmp _) -> tmp) pairs)) == n
      && Set.size (Set.fromFoldable (map (\(Tuple _ arg) -> arg) pairs)) == n
    counts ns = foldl (\acc e -> case e of
      PhpVar v -> Map.insertWith (+) v 1 acc
      PhpAssign v _ -> Map.insertWith (+) v 1 acc
      _ -> acc) Map.empty ns
  in case inspect body of
    Just ns | n > 0 && unique && A.take n rest == reversed ->
      let uses = counts ns
      in if all (\(Tuple tmp _) -> Map.lookup tmp uses == Just 2) pairs
        then A.drop n rest else body
    _ -> body

optimize :: { workers :: Set String, constructors :: Set String } -> PhpFile -> PhpFile
optimize proof file = file { decls = map clean file.decls }
  where
  clean d = case d.expression of
    PhpPrivateFunction name args ret body
      | Set.member name proof.workers && ret == ""
          && all (\a -> a.type_ == "" && String.take 1 a.name /= "&") args
      , Just _ <- inspect body ->
          let
            threaded = simplifyReturns body
            compact = terminalCopies proof.constructors threaded
            result = entryCopies (map _.name args) (simplifyReturns compact)
          in d { expression = PhpPrivateFunction name args ret result }
    _ -> d
