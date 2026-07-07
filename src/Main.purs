module Main where

import Prelude
import Data.Either (Either(..))
import Data.Maybe (Maybe(..), isJust)
import Data.Newtype (unwrap)
import Effect (Effect)
import Effect.Class (liftEffect)
import Effect.Console (log)
import Effect.Aff (Aff, launchAff_)
import Node.FS.Aff (readTextFile, writeTextFile, readdir, stat)
import Node.FS.Stats (isDirectory)
import Node.Encoding (Encoding(..))
import Data.Argonaut.Parser (jsonParser)
import Data.Argonaut.Decode (decodeJson)
import Data.Array (filterA, mapMaybe, elemIndex, (!!))
import Data.Array as Array
import Node.Process as Process
import Data.Traversable (traverse, for)
import Control.Monad.Error.Class (try)
import Data.String (joinWith, trim)
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.Traversable (traverse)
import Data.Set as Set

import Phpurs.Mtime as Mtime
import Phpurs.FastDeps as FastDeps
import Phpurs.CoreFn as CF
import Phpurs.CodeGen as CodeGen
import Phpurs.Printer as Printer
import Phpurs.ComposerMerge as ComposerMerge
import Phpurs.Dce as Dce

readModule :: String -> Aff (Maybe CF.Module)
readModule dir = do
  let path = "output/" <> dir <> "/corefn.json"
  result <- try (readTextFile UTF8 path)
  case result of
    Left _ -> pure Nothing
    Right content -> liftEffect (CF.parseModuleImpl content)

generateModule :: CodeGen.GlobalEnv -> Maybe String -> Boolean -> CF.Module -> Aff String
generateModule env mbFfiDir isBundle mod = do
  let modNameStr = joinWith "." (unwrap mod).moduleName
  ffiPathMb <- liftEffect $ ComposerMerge.findFfiFile mbFfiDir modNameStr (unwrap mod).modulePath
  ffiCode <- case ffiPathMb of
    Nothing -> pure ""
    Just ffiPath -> do
      res <- try (readTextFile UTF8 ffiPath)
      case res of
        Left _ -> pure ""
        Right content -> pure (String.trim (String.replace (Pattern "<?php\n") (Replacement "") (String.replace (Pattern "<?php") (Replacement "") content)))
  let
    phpModName = String.replaceAll (Pattern ".") (Replacement "_") modNameStr
    wrappedFfiCode = if String.length ffiCode > 0 then
      let
        closureStart = "$ffi_" <> phpModName <> " = \\call_user_func(function() {\n"
        closureEnd = "\n});\n"
        mappings = joinWith "\n" (map (\f -> "$GLOBALS['" <> Printer.sanitize (phpModName <> "_" <> f) <> "'] = $ffi_" <> phpModName <> "['" <> f <> "'] ?? null;") (unwrap mod).foreign)
      in
        closureStart <> ffiCode <> closureEnd <> mappings <> "\n"
    else
      ""
  let phpAst = CodeGen.translate env mod
  let phpStr = Printer.printPhpFile isBundle wrappedFfiCode phpAst
  if isBundle then
    pure phpStr
  else do
    writeTextFile UTF8 ("output/" <> modNameStr <> "/index.php") phpStr
    pure ""

main :: Effect Unit
main = launchAff_ do
  argsRaw <- liftEffect Process.argv
  let args = Array.concatMap (\s -> String.split (Pattern " ") s) argsRaw
  
  -- Handle both ["--main", "App"] and ["--main App"]
  let mbMainIndex = elemIndex "--main" args
  let mbMainModule = case mbMainIndex of
        Just i -> args !! (i + 1)
        Nothing -> Nothing

  let mbAutoloadIndex = elemIndex "--autoload-path" args
  let mbAutoloadPath = case mbAutoloadIndex of
        Just i -> args !! (i + 1)
        Nothing -> Nothing

  let mbFfiIndex = elemIndex "--ffi" args
  let mbFfiDir = case mbFfiIndex of
        Just i -> args !! (i + 1)
        Nothing -> Nothing

  let mbBundle = isJust (elemIndex "--bundle" args)

  liftEffect $ log "phpurs: Scanning output directory..."
  
  result <- try (readdir "output")
  case result of
    Left _ -> do
      liftEffect $ log "Error: 'output' directory not found. Please compile with 'purs compile ... --codegen corefn' first."
    Right files -> do
      -- filter only directories
      validDirs <- filterA (\f -> do
        s <- stat ("output/" <> f)
        pure $ isDirectory s
      ) files
      
      if mbBundle then do
        mbModules <- traverse readModule validDirs
        let modules = Array.mapMaybe identity mbModules
        let dceModules = case mbMainModule of
              Just mainMod ->
                let
                  entryPoint = mainMod <> ".main"
                  depGraph = Dce.buildDepGraph modules
                  reachable = Dce.computeReachable entryPoint depGraph
                in Dce.filterModules reachable modules
              Nothing -> modules
        let globalEnv = CodeGen.buildGlobalEnv dceModules
        strs <- traverse (generateModule globalEnv mbFfiDir true) dceModules
        let bundleContent = "<?php\n\n" <> joinWith "\n" strs
        case mbMainModule of
          Just mainMod -> do
            let
              autoloadStr = case mbAutoloadPath of
                Just p -> "if (file_exists(__DIR__ . '/../" <> p <> "')) require_once __DIR__ . '/../" <> p <> "';\nelseif (file_exists('" <> p <> "')) require_once '" <> p <> "';\n"
                Nothing -> "if (file_exists(__DIR__ . '/../vendor/autoload.php')) require_once __DIR__ . '/../vendor/autoload.php';\n"
              ns = joinWith "\\" (String.split (Pattern ".") mainMod)
              entryPoint = "<?php\n" <> autoloadStr <> "($GLOBALS['" <> mainMod <> "_main'] ?? \\" <> ns <> "\\phpurs_eval_thunk('" <> mainMod <> "_main'))();\nif (class_exists('\\\\Revolt\\\\EventLoop')) { \\Revolt\\EventLoop::run(); }\n"
            writeTextFile UTF8 "output/bundle.php" (bundleContent <> "\n" <> entryPoint)
            liftEffect $ log $ "phpurs: Successfully bundled all modules into output/bundle.php for " <> mainMod
          Nothing -> do
            writeTextFile UTF8 "output/bundle.php" bundleContent
            liftEffect $ log "phpurs: Successfully bundled all modules into output/bundle.php"
      else do
        liftEffect $ log "phpurs: FastDeps parsing started"
        let corefnFiles = map (\dir -> "output/" <> dir <> "/corefn.json") validDirs
        moduleDepsList <- liftEffect $ FastDeps.parseAllImports corefnFiles
        liftEffect $ log "phpurs: FastDeps parsing finished"
        
        let reachableDepsList = case mbMainModule of
              Just mainMod ->
                let reachable = Dce.computeReachableModulesFast mainMod moduleDepsList
                in Array.filter (\deps -> Set.member (joinWith "." deps.moduleName) reachable) moduleDepsList
              Nothing -> moduleDepsList
        
        liftEffect $ log "phpurs: Mtime checking started"
        directlyDirty <- liftEffect $ filterA (\deps -> do
          let modName = joinWith "." deps.moduleName
          corefnMtime <- Mtime.getMtimeMs ("output/" <> modName <> "/corefn.json")
          phpMtime <- Mtime.getMtimeMs ("output/" <> modName <> "/index.php")
          pure (corefnMtime > 0.0 && (phpMtime == 0.0 || corefnMtime > phpMtime))
        ) reachableDepsList
        liftEffect $ log "phpurs: Mtime checking finished"
        
        let directlyDirtyNames = map (\deps -> joinWith "." deps.moduleName) directlyDirty
        liftEffect $ log $ "Directly dirty count: " <> show (Array.length directlyDirtyNames)
        liftEffect $ log $ "Directly dirty sample: " <> joinWith ", " (Array.take 10 directlyDirtyNames)
        let transitivelyDirtySet = Dce.computeTransitiveDirtyFast directlyDirtyNames reachableDepsList
        let dirtyNames = Array.filter (\deps -> Set.member (joinWith "." deps.moduleName) transitivelyDirtySet) reachableDepsList
        
        let dirtyCount = Array.length dirtyNames
        let totalCount = Array.length reachableDepsList
        
        if dirtyCount == 0 then do
          liftEffect $ log $ "phpurs: 0/" <> show totalCount <> " modules modified. Up to date."
        else do
          liftEffect $ log $ "phpurs: Recompiling " <> show dirtyCount <> "/" <> show totalCount <> " modules (incremental)..."
          
          -- We must read ALL reachable modules to build a complete globalEnv for cross-module inlining
          let reachableNames = map (\deps -> joinWith "." deps.moduleName) reachableDepsList
          mbReachableModules <- traverse readModule reachableNames
          let reachableModules = Array.mapMaybe identity mbReachableModules
          
          let globalEnv = CodeGen.buildGlobalEnv reachableModules
          
          -- But we only generate PHP for the dirty subset
          let dirtyModules = Array.filter (\(CF.Module m) -> Set.member (joinWith "." m.moduleName) transitivelyDirtySet) reachableModules
          _ <- traverse (\m -> do
            liftEffect $ log $ "Generating " <> joinWith "." (unwrap m).moduleName
            res <- generateModule globalEnv mbFfiDir false m
            pure res
          ) dirtyModules
          pure unit
        
        case mbMainModule of
          Just mainMod -> do
            let
              autoloadStr = case mbAutoloadPath of
                Just p -> "if (file_exists(__DIR__ . '/../" <> p <> "')) require_once __DIR__ . '/../" <> p <> "';\nelseif (file_exists('" <> p <> "')) require_once '" <> p <> "';\n"
                Nothing -> "if (file_exists(__DIR__ . '/../vendor/autoload.php')) require_once __DIR__ . '/../vendor/autoload.php';\n"
              ns = joinWith "\\" (String.split (Pattern ".") mainMod)
              sanitizedMain = String.replaceAll (Pattern ".") (Replacement "_") mainMod <> "_main"
              entryPoint = "<?php\n" <> autoloadStr <> "require_once __DIR__ . '/" <> mainMod <> "/index.php';\n($GLOBALS['" <> sanitizedMain <> "'] ?? \\" <> ns <> "\\phpurs_eval_thunk('" <> sanitizedMain <> "'))();\nif (class_exists('\\\\Revolt\\\\EventLoop')) { \\Revolt\\EventLoop::run(); }\n"
            writeTextFile UTF8 "output/main.php" entryPoint
            liftEffect $ log $ "phpurs: Successfully compiled all modules. Generated main.php for " <> mainMod
          Nothing -> pure unit
        
        if dirtyCount > 0 then do
          case mbFfiDir of
            Just dir -> liftEffect $ ComposerMerge.mergeComposers dir
            Nothing -> liftEffect $ ComposerMerge.mergeComposers ""
        else pure unit
