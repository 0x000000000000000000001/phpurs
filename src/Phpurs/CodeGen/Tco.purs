-- | TCO loop helpers: detect nested loops, select a tail target, save jump
-- | arguments and emit the loop entry protocol.
module Phpurs.CodeGen.Tco
  ( containsNestedLoop
  , findTailTarget
  , translateTailJump
  , wrapLoopBody
  ) where

import Prelude

import Data.Array as Array
import Data.Foldable (any)
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Newtype (unwrap)
import Data.Tuple (Tuple(..))
import Phpurs.CodeGen.Context (LoopContext, TranslationContext, TranslationResult, TranslatedValues)
import Phpurs.PhpAst (PhpExpr(..))
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..), tcoAnalysisOf)
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..)) as Syn

-- | A loop can use its parameters as registers only when no nested TCO loop
-- | can capture those registers by reference. Buffered loops keep the old
-- | temporary/slot protocol.
containsNestedLoop :: TcoExpr -> Boolean
containsNestedLoop (TcoExpr _ syntax) = case syntax of
  Syn.LetRec _ bindings body ->
    any (\(Tuple _ value) -> (unwrap (tcoAnalysisOf value)).role.isLoop) bindings
      || any (\(Tuple _ value) -> containsNestedLoop value) bindings
      || containsNestedLoop body
  other -> any containsNestedLoop (Array.fromFoldable other)

findTailTarget :: TranslationContext -> String -> Maybe LoopContext
findTailTarget ctx ident =
  if ctx.isTail then Array.find (\loop -> loop.ident == ident) ctx.loops
  else Nothing

-- | Save every argument before updating any loop parameter: recursive calls
-- | may swap parameters or reuse their old values in later arguments.
translateTailJump :: LoopContext -> TranslatedValues -> TranslationResult
translateTailJump loop args =
  let
    tempName index = "__tco_" <> show (args.nextId + index)
    saveArgs = Array.mapWithIndex (\index expr -> PhpAssign (tempName index) expr) args.exprs
    assignParams = Array.mapWithIndex
      ( \index _ ->
          let
            param = fromMaybe "" (Array.index loop.params index)
          in
            PhpAssign (if loop.direct then param else loop.varPrefix <> param) (PhpVar (tempName index))
      )
      args.exprs
  in
    { stmts: args.stmts <> saveArgs <> assignParams <> [ PhpGoto loop.labelName ]
    , expr: PhpRaw "null"
    , nextId: args.nextId + Array.length args.exprs
    }

-- | Local closures and native functions use the same loop-entry protocol.
-- | The label follows initialization so a tail jump keeps the updated slots.
wrapLoopBody :: LoopContext -> TranslationResult -> Array PhpExpr
wrapLoopBody loop result =
  let
    initVars = map (\param -> PhpAssign (loop.varPrefix <> param) (PhpVar param)) loop.params
    bindParams = map (\param -> PhpAssign param (PhpVar (loop.varPrefix <> param))) loop.params
    prologue = if loop.direct then [ PhpLabel loop.labelName ] else initVars <> [ PhpLabel loop.labelName ] <> bindParams
  in
    prologue <> result.stmts <> [ PhpReturn result.expr ]
