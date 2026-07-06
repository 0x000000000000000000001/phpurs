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
import Data.Traversable (for)
import Control.Monad.Error.Class (try)
import Data.String (joinWith, trim)
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))

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
    Right content -> case jsonParser content of
      Left err -> do
        liftEffect $ log $ "Parse error in " <> dir <> ": " <> err
        pure Nothing
      Right json -> case decodeJson json of
        Left err -> do
          liftEffect $ log $ "Decode error in " <> dir <> ": " <> show err
          pure Nothing
        Right (mod :: CF.Module) -> pure (Just mod)

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
  let phpAst = CodeGen.translate env mod
  let phpStr = Printer.printPhpFile isBundle ffiCode phpAst
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

  let mbFfiIndex = elemIndex "--ffi" args
  let mbFfiDir = case mbFfiIndex of
        Just i -> args !! (i + 1)
        Nothing -> Nothing

  let mbBundle = isJust (elemIndex "--bundle" args)

  let mbComposerInIndex = elemIndex "--composer-in" args
  let mbComposerIn = case mbComposerInIndex of
        Just i -> args !! (i + 1)
        Nothing -> Nothing

  let mbComposerOutIndex = elemIndex "--composer-out" args
  let mbComposerOut = case mbComposerOutIndex of
        Just i -> args !! (i + 1)
        Nothing -> Nothing

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
      
      -- read all modules
      mbModules <- for validDirs readModule
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
      
      -- translate each module
      if mbBundle then do
        strs <- for dceModules (generateModule globalEnv mbFfiDir true)
        let bundleContent = "<?php\n\n" <> joinWith "\n" strs
        
        case mbMainModule of
          Just mainMod -> do
            let
              ns = joinWith "\\" (String.split (Pattern ".") mainMod)
              entryPoint = "($GLOBALS['" <> mainMod <> "_main'] ?? \\" <> ns <> "\\phpurs_eval_thunk('" <> mainMod <> "_main'))();\nif (class_exists('\\\\Revolt\\\\EventLoop')) { \\Revolt\\EventLoop::run(); }\n"
            writeTextFile UTF8 "output/bundle.php" (bundleContent <> "\n" <> entryPoint)
            liftEffect $ log $ "phpurs: Successfully bundled all modules into output/bundle.php for " <> mainMod
          Nothing -> do
            writeTextFile UTF8 "output/bundle.php" bundleContent
            liftEffect $ log "phpurs: Successfully bundled all modules into output/bundle.php"
      else do
        _ <- for dceModules (generateModule globalEnv mbFfiDir false)
        
        case mbMainModule of
          Just mainMod -> do
            let
              ns = joinWith "\\" (String.split (Pattern ".") mainMod)
              entryPoint = "<?php\nrequire_once __DIR__ . '/" <> mainMod <> "/index.php';\n($GLOBALS['" <> mainMod <> "_main'] ?? \\" <> ns <> "\\phpurs_eval_thunk('" <> mainMod <> "_main'))();\nif (class_exists('\\\\Revolt\\\\EventLoop')) { \\Revolt\\EventLoop::run(); }\n"
            writeTextFile UTF8 "output/main.php" entryPoint
            liftEffect $ log $ "phpurs: Successfully compiled all modules. Generated main.php for " <> mainMod
          Nothing -> do
            liftEffect $ log "phpurs: Successfully compiled all modules."

      case mbFfiDir of
        Just dir -> liftEffect $ ComposerMerge.mergeComposers dir mbComposerIn mbComposerOut
        Nothing -> liftEffect $ ComposerMerge.mergeComposers "" mbComposerIn mbComposerOut
