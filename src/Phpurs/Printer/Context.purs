-- | Printer context shared by expression rendering and declaration emission.
module Phpurs.Printer.Context (PrinterContext, emptyContext, exactSuffix) where

import Data.Map (Map)
import Data.Set (Set)
import Data.Set as Set

-- | Exact bodies are private implementation functions. The public wrapper keeps
-- | currying and overapplication; only calls with the proven runtime arity use
-- | the body directly.
type PrinterContext = { arities :: Map String Int, exacts :: Set String }

exactSuffix :: String
exactSuffix = "__phpurs_exact"

emptyContext :: Map String Int -> PrinterContext
emptyContext arities = { arities, exacts: Set.empty }

