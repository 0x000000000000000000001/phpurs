module Phpurs.ComposerMerge where

import Prelude
import Effect (Effect)
import Data.Maybe (Maybe(..))
import Data.Nullable (Nullable, toNullable, toMaybe)

foreign import mergeComposersImpl :: Nullable String -> Effect Unit

mergeComposers :: String -> Effect Unit
mergeComposers dir = mergeComposersImpl (toNullable (if dir == "" then Nothing else Just dir))

foreign import findFfiFileImpl :: Nullable String -> String -> Nullable String -> Effect (Nullable String)

findFfiFile :: Maybe String -> String -> Maybe String -> Effect (Maybe String)
findFfiFile mbFfiDir modName mbModulePath = do
  path <- findFfiFileImpl (toNullable mbFfiDir) modName (toNullable mbModulePath)
  case toMaybe path of
    Nothing -> pure mempty
    Just p -> if p == "" then pure mempty else pure (pure p)
