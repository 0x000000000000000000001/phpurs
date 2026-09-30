-- | Lower primitive operators to PHP expressions. Operand evaluation order is
-- | handled by CodeGen; in particular, statements for a short-circuiting right
-- | operand must stay inside that operand.
module Phpurs.CodeGen.Operators
  ( isLogicalShortCircuit
  , translateOperator1
  , translateOperator2
  ) where

import Prelude

import Data.Maybe (Maybe(..))
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Phpurs.PhpAst (PhpExpr(..))
import PureScript.Backend.Optimizer.CoreFn (Ident(..), ModuleName(..), Qualified(..))
import PureScript.Backend.Optimizer.Syntax (BackendOperator1(..), BackendOperator2(..), BackendOperatorNum(..), BackendOperatorOrd(..))

isLogicalShortCircuit :: BackendOperator2 -> Boolean
isLogicalShortCircuit = case _ of
  OpBooleanAnd -> true
  OpBooleanOr -> true
  _ -> false

translateOperator1 :: BackendOperator1 -> PhpExpr -> PhpExpr
translateOperator1 operator expr = case operator of
  OpBooleanNot -> PhpBinOp "!" (PhpRaw "") expr
  OpIntBitNot -> PhpBinOp "~" (PhpRaw "") expr
  OpIntNegate -> PhpBinOp "-" (PhpRaw "") expr
  OpNumberNegate -> PhpBinOp "-" (PhpRaw "") expr
  OpArrayLength -> PhpCall (PhpRaw "count") [ expr ]
  OpIsTag (Qualified mbMod (Ident tag)) ->
    let
      safeTag = String.replaceAll (Pattern "'") (Replacement "_prime_") tag
      className = case mbMod of
        Just (ModuleName name) ->
          "\\" <> String.replaceAll (Pattern ".") (Replacement "\\") name
            <> "\\" <> String.replaceAll (Pattern ".") (Replacement "_") name <> "_" <> safeTag
        Nothing -> safeTag
    in
      PhpInstanceOf expr className

translateOperator2 :: BackendOperator2 -> PhpExpr -> PhpExpr -> PhpExpr
translateOperator2 operator left right = case operator of
  OpArrayIndex -> PhpArrayIndex left right
  OpBooleanAnd -> binary "&&"
  OpBooleanOr -> binary "||"
  OpBooleanOrd comparison -> translateComparison comparison left right
  OpCharOrd comparison -> translateComparison comparison left right
  OpIntBitAnd -> binary "&"
  OpIntBitOr -> binary "|"
  OpIntBitShiftLeft -> binary "<<"
  OpIntBitShiftRight -> binary ">>"
  OpIntBitXor -> binary "^"
  OpIntBitZeroFillShiftRight -> binary ">>"
  OpIntNum OpAdd -> binary "+"
  OpIntNum OpSubtract -> binary "-"
  OpIntNum OpMultiply -> binary "*"
  -- PHP's `/` can return a float for integer operands.
  OpIntNum OpDivide -> PhpCall (PhpRaw "\\intdiv") [ left, right ]
  OpIntNum OpMod -> binary "%"
  OpIntOrd comparison -> translateComparison comparison left right
  OpNumberNum OpAdd -> binary "+"
  OpNumberNum OpSubtract -> binary "-"
  OpNumberNum OpMultiply -> binary "*"
  OpNumberNum OpDivide -> binary "/"
  OpNumberNum OpMod -> PhpCall (PhpRaw "\\fmod") [ left, right ]
  OpNumberOrd comparison -> translateComparison comparison left right
  OpStringAppend -> binary "."
  OpStringOrd comparison -> translateComparison comparison left right
  where
  binary symbol = PhpBinOp symbol left right

translateComparison :: BackendOperatorOrd -> PhpExpr -> PhpExpr -> PhpExpr
translateComparison operator = PhpBinOp case operator of
  OpEq -> "==="
  OpNotEq -> "!=="
  OpGt -> ">"
  OpGte -> ">="
  OpLt -> "<"
  OpLte -> "<="
