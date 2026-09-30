-- | Pure rendering of executable entrypoints. Dependency selection and file
-- | writes belong to the build driver; startup behavior is shared here.
module Phpurs.EntryPoint
  ( EntryPointOptions
  , printModularEntryPoint
  , printBundleEntryPoint
  ) where

import Prelude

import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import PureScript.Backend.Optimizer.CoreFn (ModuleName)

type EntryPointOptions =
  { mainModule :: String
  , autoloadPath :: Maybe String
  }

-- | Dependencies arrive in the module loader's topological order.
printModularEntryPoint :: EntryPointOptions -> Array ModuleName -> String
printModularEntryPoint options dependencies =
  let
    requires = String.joinWith "" (map (\name -> "require_once __DIR__ . '/../" <> unwrap name <> "/index.php';\n") dependencies)
  in
    "<?php\n" <> printStartup options.autoloadPath <> requires <> printMainCall options.mainModule

printBundleEntryPoint :: EntryPointOptions -> String
printBundleEntryPoint options =
  "namespace {\n" <> printStartup options.autoloadPath <> printMainCall options.mainModule <> "}\n"

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
printMainCall :: String -> String
printMainCall mainModule =
  let globalKey = String.replaceAll (Pattern ".") (Replacement "_") mainModule <> "_main"
  in "$GLOBALS['" <> globalKey <> "']();\nif (class_exists('\\\\Revolt\\\\EventLoop')) { \\Revolt\\EventLoop::run(); }\n"
