module Phpurs.ComposerMerge where

import Prelude
import Effect (Effect)
import Data.Maybe (Maybe)
import Data.Nullable (Nullable, toNullable)

foreign import mergeComposersImpl :: Nullable String -> Nullable String -> Nullable String -> Effect Unit

mergeComposers :: String -> Maybe String -> Maybe String -> Effect Unit
mergeComposers dir mbIn mbOut = mergeComposersImpl (toNullable (pure dir)) (toNullable mbIn) (toNullable mbOut)

foreign import findFfiFileImpl :: Nullable String -> String -> Nullable String -> Effect String

findFfiFile :: Maybe String -> String -> Maybe String -> Effect (Maybe String)
findFfiFile mbFfiDir modName mbModulePath = do
  path <- findFfiFileImpl (toNullable mbFfiDir) modName (toNullable mbModulePath)
  if path == "" then pure mempty else pure (pure path)
