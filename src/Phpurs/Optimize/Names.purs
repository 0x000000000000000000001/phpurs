-- | Collision-free prefixes for private workers and constructors. PHP class
-- | and function names are case-insensitive, so compare folded spellings.
module Phpurs.Optimize.Names (freshPrefixFor) where

import Prelude

import Data.Array as A
import Data.Map as Map
import Data.Newtype (unwrap)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Data.Tuple (Tuple(..))
import PureScript.Backend.Optimizer.Convert (BackendModule)
import PureScript.Backend.Optimizer.CoreFn (Ident)

freshPrefixFor :: String -> BackendModule -> String
freshPrefixFor family mod = go 0
  where
  names =
    map (String.toLower <<< unwrap)
      (A.concatMap (map (\(Tuple k _) -> k) <<< _.bindings) mod.bindings)
      <> map String.toLower (A.concatMap (map _.name <<< _.constructors) mod.dataDecls)
      <> map (String.toLower <<< unwrap) (Set.toUnfoldable (Map.keys mod.foreign) :: Array Ident)
  go n =
    let
      prefix = family <> show n <> "_"
    in
      if A.any (String.contains (Pattern prefix)) names then go (n + 1) else prefix
