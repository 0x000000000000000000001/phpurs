-- | Small PHP-AST helpers shared by ownership analysis and rebuild rewriting.
module Phpurs.ArrayRefs.Support
  ( callHead
  , exprChildren
  , fieldAccessor
  , fieldIndexOf
  , usesVar
  , varsIn
  ) where

import Prelude

import Data.Array as Array
import Data.Int as Int
import Data.Maybe (Maybe(..))
import Data.String as String
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

varsIn :: PhpExpr -> Array String
varsIn e = case e of
  PhpVar v -> [ v ]
  PhpFunction captures _ _ _ -> captures <> Array.concatMap varsIn (exprChildren e)
  PhpCompactFunction captures _ _ _ -> captures <> Array.concatMap varsIn (exprChildren e)
  _ -> Array.concatMap varsIn (exprChildren e)

usesVar :: String -> PhpExpr -> Boolean
usesVar name e = Array.elem name (varsIn e)

-- | Curried applications are nested calls: `((f a) b)` is one call with two
-- | arguments. Flatten them before checking passability.
callHead :: PhpExpr -> Maybe { target :: String, args :: Array PhpExpr }
callHead = case _ of
  PhpGlobalVar mbMod ident -> Just { target: modKey mbMod ident, args: [] }
  PhpCall f args -> case callHead f of
    Just head -> Just { target: head.target, args: head.args <> args }
    Nothing -> Nothing
  _ -> Nothing

modKey :: Maybe (Array String) -> String -> String
modKey mbMod ident = case mbMod of
  Just parts -> String.joinWith "_" parts <> "_" <> ident
  Nothing -> ident

fieldIndexOf :: String -> Maybe Int
fieldIndexOf f = case String.stripPrefix (String.Pattern "value") f of
  Just digits -> Int.fromString digits
  Nothing -> Nothing
