-- Compact captured functions only in a proven local flow through one TCO loop.
module Phpurs.CompactLoops (optimize) where

import Prelude

import Data.Array as Array
import Data.Foldable (all, any)
import Data.Maybe (Maybe(..), fromMaybe)
import Data.String as String
import Phpurs.PhpAst (PhpExpr(..))
import PureScript.Backend.Optimizer.CoreFn (ExprType(..))

type Signature = { fArgs :: Array ExprType, fRet :: ExprType }

-- Closure results are restricted to scalars, so invoking a captured function
-- cannot return another internal function that needs tracking/conversion.
scalar :: ExprType -> Boolean
scalar = case _ of
  Int -> true
  Number -> true
  String -> true
  Boolean -> true
  Unit -> true
  _ -> false

children :: PhpExpr -> Array PhpExpr
children = case _ of
  PhpCall f xs -> [f] <> xs
  PhpDirectCall _ xs -> xs
  PhpArray xs -> xs
  PhpAssocArray xs -> map _.value xs
  PhpPropertyAccess e _ -> [e]
  PhpRecordAccess e _ -> [e]
  PhpArrayIndex e i -> [e, i]
  PhpAssign _ e -> [e]
  PhpAssignExpr a b -> [a, b]
  PhpIf c t e -> [c] <> t <> e
  PhpMatch s cs d -> [s, d] <> Array.concatMap (\c -> [c.val, c.body]) cs
  PhpThrow e -> [e]
  PhpTernary c t e -> [c, t, e]
  PhpReturn e -> [e]
  PhpBinOp _ a b -> [a, b]
  PhpWhile c b -> [c] <> b
  PhpNew _ xs -> xs
  PhpClone e -> [e]
  PhpSwitch s cs d -> [s] <> Array.concatMap (\c -> c.matchCases <> c.stmts) cs <> fromMaybe [] d
  PhpInstanceOf e _ -> [e]
  -- Closures have a separate lexical scope, inspected explicitly below.
  _ -> []

nodes :: PhpExpr -> Array PhpExpr
nodes e = [e] <> Array.concatMap nodes (children e)

-- Names assigned at this function's level only, not inside its closures.
mapBlocks :: (Array PhpExpr -> Array PhpExpr) -> PhpExpr -> PhpExpr
mapBlocks f = case _ of
  PhpIf c t e -> PhpIf c (f t) (f e)
  PhpSwitch s cs d -> PhpSwitch s (map (\c -> c { stmts = f c.stmts }) cs) (map f d)
  e -> e

optimize :: String -> Maybe Signature -> PhpExpr -> PhpExpr
optimize loopLabel (Just signature) original@(PhpNativeFunction name args ret body)
  | [input] <- Array.drop (Array.length args) signature.fArgs
  , scalar input && scalar signature.fRet
  , [seed] <- Array.mapMaybe identity (Array.zipWith
      (\arg ty -> if ty == Func [input] signature.fRet then Just arg.name else Nothing)
      args signature.fArgs)
  , Array.length (Array.concatMap nodes body) <= 4096 =
    let
      ns = Array.concatMap nodes body
      -- Propagate through local copies, phi slots and captures until fixed point.
      -- The cap is conservative: failure to converge keeps the original code.
      mentions tracked = case _ of
        PhpVar v -> Array.elem v tracked
        PhpFunction caps _ _ _ -> any (\v -> Array.elem (stripRef v) tracked) caps
        e -> any (mentions tracked) (children e)
      stripRef v = if String.take 1 v == "&" then String.drop 1 v else v
      close 0 _ = Nothing
      close n tracked =
        let next = Array.nub $ tracked <> Array.mapMaybe (case _ of
              PhpAssign v e | mentions tracked e -> Just v
              _ -> Nothing) ns
        in if next == tracked then Just tracked else close (n - 1) next
      rewrite tracked =
        let
          tainted = mentions tracked
          isTracked v = Array.elem v tracked
          -- A candidate captures the previous function by value; its body may
          -- invoke it but cannot inspect, return or pass that function onwards.
          candidate fn = case fn of
            PhpFunction caps params _ stmts ->
              Array.length params == 1 && any isTracked caps
                && all (\v -> String.take 1 v /= "&") caps
                && all (\a -> not (isTracked a.name)) params
                && all (safeInner tracked) stmts
            _ -> false
          safeValue = case _ of
            PhpVar v | isTracked v -> true
            fn@(PhpFunction _ _ _ _) | tainted fn -> candidate fn
            e -> not (tainted e) && safeOuter e
          safeOuter = case _ of
            PhpAssign _ e -> safeValue e
            PhpReturn e -> safeValue e
            PhpIf c t e -> clean c && all safeOuter t && all safeOuter e
            PhpSwitch s cs d -> clean s && all (\c -> all clean c.matchCases && all safeOuter c.stmts) cs && all safeOuter (fromMaybe [] d)
            PhpGoto _ -> true
            PhpLabel _ -> true
            PhpRaw "null" -> true
            PhpRaw "" -> true
            e -> clean e
          clean e = not (tainted e) && safeInner [] e
          -- Requiring a back edge ensures this pass is confined to loop code.
          hasLoop = any (case _ of
            PhpGoto l -> l == loopLabel
            _ -> false) ns
          count = Array.length $ Array.filter candidate $ Array.concatMap children ns
          convert = case _ of
            fn@(PhpFunction caps params r stmts) | candidate fn -> PhpCompactFunction caps params r stmts
            e -> e
          go = map (case _ of
            PhpAssign v e -> PhpAssign v (convert e)
            PhpReturn e -> PhpReturn (convert e)
            e -> mapBlocks go e)
        in if hasLoop && count > 0 && all safeOuter body
          then PhpCompactLoop name args ret (go body)
          else original
    in case close 64 [seed] of
      Just tracked -> rewrite tracked
      Nothing -> original
optimize _ _ original = original

-- All raw code and nested closures are excluded from a compact capture body.
-- Passing tainted values to a call is forbidden even when the target is known.
-- A direct call of the captured function is allowed; its scalar result follows
-- from the TAST signature checked above.
safeInner :: Array String -> PhpExpr -> Boolean
safeInner tracked = go
  where
  tainted = case _ of
    PhpVar v -> Array.elem v tracked
    PhpFunction caps _ _ _ -> any (flip Array.elem tracked) caps
    e -> any tainted (children e)
  go = case _ of
    PhpVar v -> not (Array.elem v tracked)
    PhpGlobalVar _ _ -> true
    PhpInt _ -> true
    PhpNumber _ -> true
    PhpString _ -> true
    PhpBoolean _ -> true
    PhpRaw "null" -> true
    PhpRaw "" -> true
    PhpCall (PhpVar v) xs | Array.elem v tracked -> all (\e -> not (tainted e) && go e) xs
    PhpCall f xs -> go f && all go xs
    PhpDirectCall _ xs -> all go xs
    PhpAssign v e -> not (Array.elem v tracked) && go e
    PhpIf c t e -> go c && all go t && all go e
    PhpMatch s cs d -> go s && all (\c -> go c.val && go c.body) cs && go d
    PhpReturn e -> go e
    PhpBinOp _ a b -> go a && go b
    PhpTernary c t e -> go c && go t && go e
    PhpArray xs -> all go xs
    PhpAssocArray xs -> all (go <<< _.value) xs
    PhpPropertyAccess e _ -> go e
    PhpRecordAccess e _ -> go e
    PhpArrayIndex e ix -> go e && go ix
    PhpNew _ xs -> all go xs
    PhpInstanceOf e _ -> go e
    PhpThrow e -> go e
    PhpSwitch s cs d -> go s && all (\c -> all go c.matchCases && all go c.stmts) cs && all go (fromMaybe [] d)
    _ -> false
