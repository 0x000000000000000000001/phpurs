-- | Pure rendering of executable entrypoints. Dependency selection and file
-- | writes belong to the build driver; startup behavior is shared here.
module Phpurs.EntryPoint
  ( EntryPointOptions
  , printModularEntryPoint
  , printBundleEntryPoint
  ) where

import Prelude

import Data.Map (Map)
import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.String as String
import Data.String.Pattern (Pattern(..))
import Phpurs.PhpAst (PhpExpr(..))
import Phpurs.Printer (printExpr)
import PureScript.Backend.Optimizer.CoreFn (ModuleName)

type EntryPointOptions =
  { mainModule :: String
  , autoloadPath :: Maybe String
  , arities :: Map String Int
  }

-- | Dependencies arrive in the module loader's topological order.
printModularEntryPoint :: EntryPointOptions -> Array ModuleName -> String
printModularEntryPoint options dependencies =
  let
    requires = String.joinWith "" (map (\name -> "require_once __DIR__ . '/../" <> unwrap name <> "/index.php';\n") dependencies)
  in
    "<?php\n" <> printStartup options.autoloadPath <> requires <> printMainCall options

printBundleEntryPoint :: EntryPointOptions -> String
printBundleEntryPoint options =
  "namespace {\n" <> printStartup options.autoloadPath <> printMainCall options <> "}\n"

printStartup :: Maybe String -> String
printStartup autoloadPath =
  let
    autoload = case autoloadPath of
      Just path -> "if (file_exists(__DIR__ . '/../../" <> path <> "')) require_once __DIR__ . '/../../" <> path <> "';\nelseif (file_exists('" <> path <> "')) require_once '" <> path <> "';\n"
      Nothing -> "if (file_exists(__DIR__ . '/../../vendor/autoload.php')) require_once __DIR__ . '/../../vendor/autoload.php';\n"
    exceptionHandler = "set_exception_handler(function($e) { echo 'FATAL: ' . $e->getMessage() . \"\\n\" . $e->getTraceAsString() . \"\\n\"; exit(1); });\n"
  in
    autoload <> exceptionHandler

-- | Keep draining Revolt after main returns so asynchronous effects complete.
printMainCall :: EntryPointOptions -> String
printMainCall { mainModule, arities } =
  let main = printExpr arities (PhpGlobalVar (Just (String.split (Pattern ".") mainModule)) "main")
  in main <> "();\nif (class_exists('\\\\Revolt\\\\EventLoop')) { \\Revolt\\EventLoop::run(); }\n"
