-- | Shared state of expression translation: loop scope, lexical names and
-- | evaluation position. Kept separate from signature helpers so the core can
-- | be split without importing the whole code generator.
module Phpurs.CodeGen.Context
  ( LoopContext
  , TranslationContext
  , TranslationResult
  , TranslatedValues
  , initialContext
  , valueContext
  ) where

import Data.Map (Map)
import Data.Map as Map
import Phpurs.PhpAst (PhpExpr)

-- | Statements must execute before the result expression is consumed. `nextId`
-- | is threaded through siblings so their live temporaries cannot collide.
type TranslationResult = { stmts :: Array PhpExpr, expr :: PhpExpr, nextId :: Int }

type TranslatedValues = { stmts :: Array PhpExpr, exprs :: Array PhpExpr, nextId :: Int }

type LoopContext =
  { ident :: String
  , params :: Array String
  , varPrefix :: String
  , labelName :: String
  , direct :: Boolean
  }

-- | Lexical scope and evaluation position for one expression. A value operand
-- | starts outside the enclosing tail/effect position; binding bodies inherit it.
type TranslationContext =
  { moduleName :: String
  , recursiveVars :: Array String
  , boundVars :: Map String String -- Optimizer local IDs -> renamed PHP locals.
  , loops :: Array LoopContext
  , isTail :: Boolean
  , inEffectBlock :: Boolean -- Already lowering an effect-executing body.
  , allowDirect :: Boolean -- Parameters may replace buffered TCO registers.
  }

-- | The module name uses the PHP global-key spelling, e.g. `Data_Maybe`.
initialContext :: String -> TranslationContext
initialContext moduleName =
  { moduleName
  , recursiveVars: []
  , boundVars: Map.empty
  , loops: []
  , isTail: false
  , inEffectBlock: false
  , allowDirect: true
  }

valueContext :: TranslationContext -> TranslationContext
valueContext ctx = ctx { loops = [], isTail = false, inEffectBlock = false }
