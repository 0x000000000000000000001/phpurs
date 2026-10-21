-- | Small PHP-AST helpers shared by ownership analysis and rebuild rewriting.
module Phpurs.ArrayRefs.Support
  ( exprChildren
  , fieldAccessor
  ) where

import Prelude

import Data.Array as Array
import Phpurs.PhpAst (PhpExpr(..))
import Phpurs.PhpAst.Traversal (children)

-- | Ownership scans keep match arms before the fallback in their dependency
-- | enumeration. All other nodes use the shared traversal across every scope.
exprChildren :: PhpExpr -> Array PhpExpr
exprChildren = case _ of
  PhpMatch s cases d -> Array.cons s (Array.concatMap (\c -> [ c.val, c.body ]) cases <> [ d ])
  e -> children e

fieldAccessor :: String -> Int -> PhpExpr
fieldAccessor p i = PhpPropertyAccess (PhpVar p) ("value" <> show i)
