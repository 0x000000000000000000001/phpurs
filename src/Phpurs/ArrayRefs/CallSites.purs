-- | Call-site discovery for array-reference promotion.
module Phpurs.ArrayRefs.CallSites
  ( Call
  , callsIn
  , paramIndex
  , statementsIn
  , Site
  , branchTail
  , isTerminator
  , isGoto
  , sitesIn
  ) where

import Prelude

import Data.Array as A
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Tuple (Tuple(..))
import Phpurs.ArrayRefs.Support (callHead, exprChildren)
import Phpurs.PhpAst (PhpExpr(..))

type Call = { target :: String, args :: Array PhpExpr }

callsIn :: PhpExpr -> Array Call
callsIn e = case e of
  PhpDirectCall target args -> A.cons { target, args } (A.concatMap callsIn args)
  PhpCall f args -> case callHead f of
    Just head -> A.cons { target: head.target, args: head.args <> args }
      (A.concatMap callsIn (A.cons f args))
    Nothing -> A.concatMap callsIn (A.cons f args)
  _ -> A.concatMap callsIn (exprChildren e)

paramIndex :: Array String -> String -> Maybe Int
paramIndex names name = A.findIndex (_ == name) names

-- Other-field reads of the parent stay allowed; re-reading the mutated field
-- or aliasing the parent would observe the update and is refused.

statementsIn :: Array PhpExpr -> Array PhpExpr
statementsIn ss = A.concatMap stmtsOf ss
  where
  stmtsOf st = A.cons st case st of
    PhpIf _ t e -> statementsIn t <> statementsIn e
    PhpSwitch _ cases d -> A.concatMap (\c -> statementsIn c.stmts) cases <> statementsIn
      (fromMaybe [] d)
    PhpWhile _ b -> statementsIn b
    _ -> []

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
sitesIn body = A.concatMap (\(Tuple i st) -> sitesOf i (branchTail (A.drop (i + 1) body) []) st)
  (A.mapWithIndex Tuple body)
  where
  sitesOf topIndex rest st = case st of
    PhpIf _ t e -> sitesOfList topIndex rest t <> sitesOfList topIndex rest e
    PhpSwitch _ cases d -> A.concatMap (sitesOfList topIndex rest) (map _.stmts cases) <>
      sitesOfList topIndex rest (fromMaybe [] d)
    PhpWhile _ b -> sitesOfList topIndex rest b
    PhpReturn _ -> map (\c -> { statement: topIndex, call: c, tail: [] }) (callsIn st)
    PhpThrow _ -> map (\c -> { statement: topIndex, call: c, tail: [] }) (callsIn st)
    _ -> map (\c -> { statement: topIndex, call: c, tail: rest }) (callsIn st)

  sitesOfList topIndex outerTail stmts =
    A.concatMap (\(Tuple j b) -> sitesOf topIndex (branchTail (A.drop (j + 1) stmts) outerTail) b)
      (A.mapWithIndex Tuple stmts)
