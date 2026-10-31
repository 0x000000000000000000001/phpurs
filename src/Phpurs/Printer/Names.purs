-- | PHP names and global reads shared by the printer and declaration emitter.
module Phpurs.Printer.Names
  ( globalIdentifier
  , nativeName
  , printGlobal
  , safeFuncName
  , safeName
  ) where

import Prelude

import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.String (joinWith, replaceAll, Pattern(..), Replacement(..))
import Phpurs.PhpAst (foreignValueArity)

foreign import safeNameImpl :: String -> String
foreign import safeFuncNameImpl :: String -> String

safeName :: String -> String
safeName = safeNameImpl
  <<< replaceAll (Pattern "'") (Replacement "__prime__")

safeFuncName :: String -> String
safeFuncName = safeFuncNameImpl
  <<< replaceAll (Pattern "'") (Replacement "__prime__")

globalIdentifier :: Maybe (Array String) -> String -> String
globalIdentifier moduleName ident = case moduleName of
  Just parts -> joinWith "_" parts <> "_" <> ident
  Nothing -> ident

nativeName :: Maybe (Array String) -> String -> String
nativeName moduleName ident =
  ( case moduleName of
      Just parts -> "\\" <> joinWith "\\" parts <> "\\"
      Nothing -> ""
  ) <> safeFuncName (globalIdentifier moduleName ident)

printGlobal :: Map String Int -> Maybe (Array String) -> String -> String
printGlobal allArities moduleName ident =
  let
    key = safeName (globalIdentifier moduleName ident)
    value = "$GLOBALS['" <> key <> "']"
  in
    if Map.lookup key allArities == Just foreignValueArity then
      "(" <> value <> " ?? " <> nativeName moduleName ident <> "())"
    else value

