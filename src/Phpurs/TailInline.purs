-- A single, bounded pass over native functions. Public entry points remain intact.
module Phpurs.TailInline (optimize, simplifyReturns, bodyBudget, callerBudget) where

import Prelude

import Data.Array as Array
import Data.Foldable (all, any, foldl, foldr, foldMap)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.String as String
import Data.Tuple (Tuple(..))
import Phpurs.PhpAst (PhpExpr(..), PhpFile)

-- Count expression nodes, not just statements; cap both each copied body and
-- the total added body/argument nodes per caller. No iterative expansion.
bodyBudget :: Int
bodyBudget = 1536

callerBudget :: Int
callerBudget = 3072

-- Expression traversal does not cross a closure boundary. Candidates containing
-- closures are excluded; callers' closures must keep their own locals and returns.
children :: PhpExpr -> Array PhpExpr
children = case _ of
  PhpCall f args -> [f] <> args
  PhpDirectCall _ args -> args
  PhpArray xs -> xs
  PhpAssocArray xs -> map _.value xs
  PhpPropertyAccess e _ -> [e]
  PhpRecordAccess e _ -> [e]
  PhpArrayIndex e ix -> [e, ix]
  PhpAssign _ e -> [e]
  PhpAssignExpr a b -> [a, b]
  PhpIf c t e -> [c] <> t <> e
  PhpMatch s cs d -> [s, d] <> Array.concatMap (\c -> [c.val, c.body]) cs
  PhpThrow e -> [e]
  PhpTernary c t e -> [c, t, e]
  PhpReturn e -> [e]
  PhpBinOp _ a b -> [a, b]
  PhpWhile c b -> [c] <> b
  PhpNew _ args -> args
  PhpClone e -> [e]
  PhpSwitch s cs d -> [s] <> Array.concatMap (\c -> c.matchCases <> c.stmts) cs <> fromMaybe [] d
  PhpInstanceOf e _ -> [e]
  _ -> []

nodes :: PhpExpr -> Array PhpExpr
nodes e = [e] <> Array.concatMap nodes (children e)

mapBlocks :: (Array PhpExpr -> Array PhpExpr) -> PhpExpr -> PhpExpr
mapBlocks f = case _ of
  PhpIf c t e -> PhpIf c (f t) (f e)
  PhpWhile c b -> PhpWhile c (f b)
  PhpSwitch s cs d -> PhpSwitch s (map (\c -> c { stmts = f c.stmts }) cs) (map f d)
  e -> e

returnLabels :: Array PhpExpr -> Map.Map String String
returnLabels xs =
  let
    here = Array.mapMaybe identity $ Array.zipWith pair xs (Array.drop 1 xs)
    pair (PhpLabel label) (PhpReturn (PhpVar name)) = Just (Tuple label name)
    pair _ _ = Nothing
    nested = foldl Map.union Map.empty $ map (case _ of
      PhpIf _ t e -> Map.union (returnLabels t) (returnLabels e)
      PhpWhile _ b -> returnLabels b
      PhpSwitch _ cs d -> foldl Map.union (returnLabels (fromMaybe [] d)) (map (returnLabels <<< _.stmts) cs)
      _ -> Map.empty) xs
  in Map.union (Map.fromFoldable here) nested

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
          Just { head: PhpReturn (PhpVar b), tail: rest } | a == b && not (Array.elem a captured) -> [PhpReturn v] <> pairs rest
          _ -> [PhpAssign a v] <> pairs tail
        Just { head, tail } -> [head] <> pairs tail
        Nothing -> []
      next = go xs
    in if next == xs then xs else settle (n - 1) next

  clean 0 xs = xs
  clean n xs =
    let
      ns = Array.concatMap nodes xs
      reads = foldMap readNames xs
      jumps = Array.mapMaybe (case _ of
        PhpGoto l -> Just l
        _ -> Nothing) ns
      keep = case _ of
        PhpAssign v (PhpRaw "null") -> Array.elem v reads
        PhpLabel l -> Array.elem l jumps
        _ -> true
      isLabel (PhpLabel _) = true
      isLabel _ = false
      dead block = case Array.uncons block of
        Just { head: head@(PhpReturn _), tail } -> [head] <> dead (Array.dropWhile (not <<< isLabel) tail)
        Just { head: head@(PhpThrow _), tail } -> [head] <> dead (Array.dropWhile (not <<< isLabel) tail)
        Just { head, tail } -> [head] <> dead tail
        Nothing -> []
      go block = dead (map (mapBlocks go) (Array.filter keep block))
      next = go xs
    in if next == xs then xs else clean (n - 1) next

-- Leaf bodies cannot recurse, invoke FFI/callbacks, loop or construct closures.
-- Typed PHP parameters/returns are excluded separately: bypassing their runtime
-- coercions/checks would require additional proof at the call site.
leafNode :: PhpExpr -> Boolean
leafNode = case _ of
  PhpVar _ -> true
  PhpGlobalVar _ _ -> true
  PhpInt _ -> true
  PhpNumber _ -> true
  PhpString _ -> true
  PhpBoolean _ -> true
  PhpArray _ -> true
  PhpAssocArray _ -> true
  PhpPropertyAccess _ _ -> true
  PhpRecordAccess _ _ -> true
  PhpArrayIndex _ _ -> true
  PhpAssign _ _ -> true
  PhpIf _ _ _ -> true
  PhpMatch _ _ _ -> true
  PhpThrow _ -> true
  PhpTernary _ _ _ -> true
  PhpReturn _ -> true
  PhpBinOp _ _ _ -> true
  PhpRaw "null" -> true
  PhpNew _ _ -> true
  PhpSwitch _ _ _ -> true
  PhpInstanceOf _ _ -> true
  _ -> false

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
    PhpSwitch s cs d -> PhpSwitch (go s) (map (\c -> { matchCases: map go c.matchCases, stmts: map go c.stmts }) cs) (map (map go) d)
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
          [PhpReturn (mapLocals (\v -> if v == name then value else PhpVar v) identity e)] <> tail
    _, _ -> [stmt] <> rest

flattenCall :: PhpExpr -> Tuple PhpExpr (Array PhpExpr)
flattenCall (PhpCall f args) | not (Array.null args) =
  let Tuple target previous = flattenCall f
  in Tuple target (previous <> args)
flattenCall e = Tuple e []

-- Include nested closures when choosing a fresh prefix, even though the pass
-- never rewrites their bodies. A caller may keep one in a local before the call.
localNames :: PhpExpr -> Array String
localNames = case _ of
  PhpVar v -> [v]
  PhpAssign v e -> [v] <> localNames e
  PhpFunction caps args _ body -> map captureName caps <> map _.name args <> foldMap localNames body
  e -> foldMap localNames (children e)

captureName :: String -> String
captureName s = if String.take 1 s == "&" then String.drop 1 s else s

readNames :: PhpExpr -> Array String
readNames = case _ of
  PhpVar v -> [v]
  PhpFunction caps _ _ body -> map captureName caps <> foldMap readNames body
  e -> foldMap readNames (children e)

-- Eliminating an assignment must not hide it from an escaping closure.
capturedNames :: PhpExpr -> Array String
capturedNames = case _ of
  PhpFunction caps _ _ body -> map captureName caps <> foldMap capturedNames body
  e -> foldMap capturedNames (children e)

-- An inlined body must leave the caller just as the original call did. Reject
-- fallthrough bodies instead of relying on PHP's implicit null return.
terminates :: Array PhpExpr -> Boolean
terminates = any case _ of
  PhpReturn _ -> true
  PhpThrow _ -> true
  PhpIf _ t e -> terminates t && terminates e
  PhpSwitch _ cs (Just d) -> all (terminates <<< _.stmts) cs && terminates d
  _ -> false

optimize :: PhpFile -> PhpFile
optimize file = file { decls = map optimizeDecl file.decls }
  where
  modulePrefix = String.joinWith "_" file.namespace <> "_"
  noCheck t = t == "" || t == "mixed"
  candidates = Map.fromFoldable $ Array.mapMaybe (\d -> case d.expression of
    -- genNativeCurry annotates only the first parameter; subsequent parameters
    -- stay untyped/optional for currying even when TAST supplies their types.
    PhpNativeFunction name args ret body | Just first <- Array.head args, noCheck first.type_ && noCheck ret ->
      let
        terminal = simplifyReturns body
        terminalNodes = Array.concatMap nodes terminal
        locals = map _.name args <> Array.mapMaybe (case _ of
          PhpAssign v _ -> Just v
          _ -> Nothing) terminalNodes
        isLeaf = all leafNode terminalNodes && terminates terminal
          && all (flip Array.elem locals) (foldMap readNames terminal)
        compact = if isLeaf then compactCopies terminal else terminal
        ns = Array.concatMap nodes compact
      in if isLeaf && Array.length ns <= bodyBudget
        then Just (Tuple name { args, body: compact, cost: Array.length ns + 2 * Array.length args })
        else Nothing
    _ -> Nothing) file.decls

  targetName = case _ of
    PhpGlobalVar (Just mod) ident | mod == file.namespace -> Just (modulePrefix <> ident)
    PhpGlobalVar Nothing ident -> Just ident
    _ -> Nothing

  optimizeDecl decl = case decl.expression of
    PhpNativeFunction name args ret body ->
      let
        names = map _.name args <> foldMap localNames body
        fresh n =
          let attempt = "__phpurs_inline_" <> show n <> "_"
          in if any (\v -> String.take (String.length attempt) v == attempt) names then fresh (n + 1) else attempt
        prefix = fresh 0
        -- Slots are shared between terminal sites of the same callee. No two
        -- such invocations can overlap within this PHP activation.
        indexed = Map.fromFoldable $ Array.mapWithIndex (\i (Tuple key value) -> Tuple key { args: value.args, body: value.body, cost: value.cost, prefix: prefix <> show i <> "_" }) (Map.toUnfoldable candidates :: Array _)
        visit budget xs = foldl step { stmts: [], remaining: budget, changed: false } xs
          where
          step acc stmt =
            let res = one acc.remaining stmt
            in { stmts: acc.stmts <> res.stmts, remaining: res.remaining, changed: acc.changed || res.changed }
          one available stmt = case stmt of
            PhpReturn call ->
              let Tuple target actual = flattenCall call
              in case targetName target >>= (\key -> if key == name then Nothing else Map.lookup key indexed) of
                Just fn | Array.length actual == Array.length fn.args && fn.cost <= available ->
                  { stmts: Array.zipWith (\arg value -> PhpAssign (fn.prefix <> arg.name) value) fn.args actual <> map (rename fn.prefix) fn.body
                  , remaining: available - fn.cost
                  , changed: true }
                _ -> unchanged available stmt
            PhpIf c t e ->
              let a = visit available t
                  b = visit a.remaining e
              in { stmts: [PhpIf c a.stmts b.stmts], remaining: b.remaining, changed: a.changed || b.changed }
            PhpWhile c b ->
              let r = visit available b
              in { stmts: [PhpWhile c r.stmts], remaining: r.remaining, changed: r.changed }
            PhpSwitch s cs d ->
              let
                cases = foldl (\acc c ->
                  let r = visit acc.remaining c.stmts
                  in { cases: Array.snoc acc.cases (c { stmts = r.stmts }), remaining: r.remaining, changed: acc.changed || r.changed }) { cases: [], remaining: available, changed: false } cs
                def = visit cases.remaining (fromMaybe [] d)
              in { stmts: [PhpSwitch s cases.cases (map (const def.stmts) d)], remaining: def.remaining, changed: cases.changed || def.changed }
            _ -> unchanged available stmt
          unchanged available stmt = { stmts: [stmt], remaining: available, changed: false }
        result = visit callerBudget (simplifyReturns body)
      in if result.changed then decl { expression = PhpNativeFunction name args ret result.stmts } else decl
    _ -> decl
