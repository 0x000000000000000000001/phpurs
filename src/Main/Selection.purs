-- | Pure main selection and CoreFn import-closure helpers.
module Main.Selection
  ( moduleClosure
  , reachableFrom
  , selectModules
  , selectTargetMains
  ) where

import Prelude

import Data.Array as Array
import Data.Either (Either(..))
import Data.List (List)
import Data.List as List
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Newtype (unwrap)
import Data.Set (Set)
import Data.Set as Set
import Data.Tuple (Tuple(..))
import PureScript.Backend.Optimizer.CoreFn (Ident(..), Module(..), ModuleName(..), importName)

moduleClosure :: forall a. List (Module a) -> Map ModuleName (Set ModuleName)
moduleClosure modules = Map.fromFoldable $ map
  (\(Module m) -> Tuple m.name (Set.fromFoldable (map importName m.imports)))
  (Array.fromFoldable modules)

reachableFrom :: Map ModuleName (Set ModuleName) -> Array ModuleName -> Set ModuleName
reachableFrom graph roots = go Set.empty roots
  where
  go seen remaining = case Array.uncons remaining of
    Nothing -> seen
    Just { head: root, tail }
      | Set.member root seen -> go seen tail
      | otherwise -> go (Set.insert root seen)
          (Array.fromFoldable (fromMaybe Set.empty (Map.lookup root graph)) <> tail)

exportsMain :: forall a. Module a -> Boolean
exportsMain (Module m) = Array.elem (Ident "main") m.exports

selectTargetMains :: forall a. Maybe String -> Array (Module a) -> Either String (Array String)
selectTargetMains explicit modules =
  let
    discovered = map (\(Module m) -> unwrap m.name) $ Array.filter exportsMain modules
  in
    case explicit of
      Nothing -> Right discovered
      Just mainMod -> case Array.find (\(Module m) -> unwrap m.name == mainMod) modules of
        Nothing -> Left $ "Main module " <> mainMod <> " was not loaded"
        Just m | not (exportsMain m) -> Left $ "Module " <> mainMod <> " does not export main"
        Just _ -> Right [ mainMod ]

selectModules :: forall a. Array String -> List (Module a) -> List (Module a)
selectModules targetMainModules modules =
  let
    roots = map ModuleName targetMainModules
    reachable = reachableFrom (moduleClosure modules) roots
  in
    if Array.null roots then modules
    else List.filter (\(Module m) -> Set.member m.name reachable) modules
