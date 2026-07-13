module Main where

import Prelude

import Effect (Effect)
import Effect.Class (liftEffect)
import Effect.Aff (Aff, launchAff_, attempt)
import Effect.Console as Console
import Node.FS.Aff as FS
import Node.FS.Stats as Stats
import Node.Encoding (Encoding(..))
import Node.Process as Process
import Data.Argonaut.Parser (jsonParser)
import Data.Either (Either(..), isRight)
import Data.Bifunctor (lmap)
import Data.Argonaut.Decode.Error (printJsonDecodeError)
import Data.Array as Array
import Data.List as List
import Data.Maybe (Maybe(..), isJust)
import Data.Map as Map
import Data.Set as Set
import Data.Traversable (traverse)
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.String as String
import PureScript.Backend.Optimizer.CoreFn.Json (decodeModule)
import PureScript.Backend.Optimizer.CoreFn.Sort (sortModules)
import PureScript.Backend.Optimizer.Builder (buildModules)
import PureScript.Backend.Optimizer.CoreFn (Module(..), Ann, Ident(..), importName)
import Phpurs.CodeGen (translate)
import Phpurs.Printer (printPhpFile, safeName)
import Phpurs.ComposerMerge (findFfiFile, mergeComposers)
import Data.Newtype (unwrap)
import Data.String (joinWith, replace, replaceAll, trim, Pattern(..), Replacement(..), length)
import PureScript.Backend.Optimizer.CoreFn (Ident(..))
import Effect.Ref as Ref

readCoreFnModule :: String -> Aff (Maybe (Module Ann))
readCoreFnModule filePath = do
  statRes <- attempt (FS.stat filePath)
  if isRight statRes then do
    contents <- FS.readTextFile UTF8 filePath
    case jsonParser contents >>= (lmap printJsonDecodeError <<< decodeModule) of
      Left err -> do
        liftEffect $ Console.error $ "Failed to decode " <> filePath <> ": " <> err
        pure Nothing
      Right mod -> pure (Just mod)
  else
    pure Nothing

main :: Effect Unit
main = launchAff_ do
  argsRaw <- liftEffect Process.argv

  let
    args = Array.concatMap (\s -> String.split (Pattern " ") s) argsRaw
    mbMainIndex = Array.elemIndex "--main" args
    mbMainModule = case mbMainIndex of
      Just i -> Array.index args (i + 1)
      Nothing -> Nothing

    mbAutoloadIndex = Array.elemIndex "--autoload-path" args
    mbAutoloadPath = case mbAutoloadIndex of
      Just i -> Array.index args (i + 1)
      Nothing -> Nothing

    mbBundle = isJust (Array.elemIndex "--bundle" args)

  files <- FS.readdir "output"

  validDirs <- Array.filterA
    ( \f -> do
        stat <- FS.stat ("output/" <> f)
        pure (Stats.isDirectory stat)
    )
    files

  mbModules <- traverse (\dir -> readCoreFnModule ("output/" <> dir <> "/corefn.json")) validDirs
  let modulesArray = Array.catMaybes mbModules
  let modulesList = List.fromFoldable modulesArray

  let sortedModules = sortModules modulesList

  bundleContentRef <- liftEffect $ Ref.new "<?php\n\n"

  buildModules
    { directives: Map.empty
    , analyzeCustom: \_ _ -> Nothing
    , foreignSemantics: Map.empty
    , traceIdents: Set.empty
    , onPrepareModule: \_ m -> pure m
    , onCodegenModule: \_ (Module coreFnMod) backendMod _ -> do
        let
          importsArray = map (\i -> String.split (Pattern ".") (unwrap (importName i))) coreFnMod.imports
          phpFile = translate importsArray backendMod
          modNameStr = unwrap backendMod.name

        ffiPathMb <- liftEffect $ findFfiFile Nothing modNameStr (Just coreFnMod.path)
        ffiCode <- case ffiPathMb of
          Nothing -> pure ""
          Just ffiPath -> do
            content <- FS.readTextFile UTF8 ffiPath
            pure (trim (replace (Pattern "<?php\n") (Replacement "") (replace (Pattern "<?php") (Replacement "") content)))

        let
          phpModName = replaceAll (Pattern ".") (Replacement "_") modNameStr
          wrappedFfiCode =
            if length ffiCode > 0 then
              let
                closureStart = "$ffi_" <> phpModName <> " = \\call_user_func(function() {\n  $exports = [];\n"
                closureEnd = "\n  return $exports;\n});\n"
                mappings = joinWith "\n" (map (\(Ident f) -> "$GLOBALS['__phpurs_thunks']['" <> safeName (phpModName <> "_" <> f) <> "'] = function() use (&$ffi_" <> phpModName <> ") { return $ffi_" <> phpModName <> "['" <> f <> "']; };") (Array.fromFoldable backendMod.foreign))
              in
                closureStart <> ffiCode <> closureEnd <> mappings <> "\n"
            else
              ""

        if mbBundle then do
          let phpCodeBundle = printPhpFile true wrappedFfiCode phpFile
          liftEffect $ Ref.modify_ (\s -> s <> phpCodeBundle <> "\n") bundleContentRef
        else pure unit

        let phpCode = printPhpFile false wrappedFfiCode phpFile
        FS.writeTextFile UTF8 ("output/" <> modNameStr <> "/index.php") phpCode
    }
    sortedModules

  let
    targetMainModules = case mbMainModule of
      Just mainMod -> [ mainMod ]
      Nothing -> Array.mapMaybe (\(Module m) -> if isJust (Array.elemIndex (Ident "main") m.exports) then Just (unwrap m.name) else Nothing) modulesArray

  _ <- traverse
    ( \mainMod -> do
        let
          autoloadStr = case mbAutoloadPath of
            Just p -> "if (file_exists(__DIR__ . '/../../" <> p <> "')) require_once __DIR__ . '/../../" <> p <> "';\nelseif (file_exists('" <> p <> "')) require_once '" <> p <> "';\n"
            Nothing -> "if (file_exists(__DIR__ . '/../../vendor/autoload.php')) require_once __DIR__ . '/../../vendor/autoload.php';\n"
          ns = joinWith "\\" (String.split (Pattern ".") mainMod)
          sanitizedMain = String.replaceAll (Pattern ".") (Replacement "_") mainMod <> "_main"
          baseEntryPoint = autoloadStr <> "set_exception_handler(function($e) { echo 'FATAL: ' . $e->getMessage() . \"\\n\" . $e->getTraceAsString() . \"\\n\"; exit(1); });\n"
          callStr = "($GLOBALS['" <> sanitizedMain <> "'] ?? \\" <> ns <> "\\phpurs_eval_thunk('" <> sanitizedMain <> "'))();\nif (class_exists('\\\\Revolt\\\\EventLoop')) { \\Revolt\\EventLoop::run(); }\n"

        if mbBundle then do
          bundleContent <- liftEffect $ Ref.read bundleContentRef
          let entryPoint = baseEntryPoint <> callStr
          FS.writeTextFile UTF8 ("output/" <> mainMod <> "/main.bundle.php") (bundleContent <> "\n" <> entryPoint)
        else pure unit

        let modEntryPoint = "<?php\n" <> baseEntryPoint <> "require_once __DIR__ . '/index.php';\n" <> callStr
        FS.writeTextFile UTF8 ("output/" <> mainMod <> "/main.mod.php") modEntryPoint
    )
    targetMainModules

  if mbBundle then do
    case mbMainModule of
      Just _ -> pure unit
      Nothing -> do
        bundleContent <- liftEffect $ Ref.read bundleContentRef
        FS.writeTextFile UTF8 "output/bundle.php" bundleContent
  else pure unit

  liftEffect $ mergeComposers ""
