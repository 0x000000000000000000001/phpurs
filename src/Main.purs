module Main where

import Prelude
import Data.Either (Either(..))
import Data.Maybe (Maybe(..))
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

processModule :: Maybe String -> String -> Aff Unit
processModule mbFfiDir dir = do
  let path = "output/" <> dir <> "/corefn.json"
  -- use `try` to catch ENOENT if file doesn't exist
  result <- try (readTextFile UTF8 path)
  case result of
    Left _ -> pure unit
    Right content -> case jsonParser content of
      Left err -> do
        liftEffect $ log $ "Parse error in " <> dir <> ": " <> err
        pure unit
      Right json -> case decodeJson json of
        Left err -> do
          liftEffect $ log $ "Decode error in " <> dir <> ": " <> show err
          pure unit
        Right (mod :: CF.Module) -> do
          let modNameStr = joinWith "." (unwrap mod).moduleName
          ffiPathMb <- liftEffect $ ComposerMerge.findFfiFile mbFfiDir modNameStr (unwrap mod).modulePath
          ffiCode <- case ffiPathMb of
            Nothing -> pure ""
            Just ffiPath -> do
              res <- try (readTextFile UTF8 ffiPath)
              case res of
                Left _ -> pure ""
                Right content -> pure (String.trim (String.replace (Pattern "<?php\n") (Replacement "") (String.replace (Pattern "<?php") (Replacement "") content)))
          let phpAst = CodeGen.translate mod
          let phpStr = Printer.printPhpFile ffiCode phpAst
          writeTextFile UTF8 ("output/" <> modNameStr <> "/index.php") phpStr

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
      
      -- translate each module
      _ <- for validDirs (processModule mbFfiDir)
      
      case mbMainModule of
        Just mainMod -> do
          let entryPoint = "<?php\nrequire_once __DIR__ . '/" <> mainMod <> "/index.php';\n$" <> mainMod <> "_main();\nif (class_exists('\\\\Revolt\\\\EventLoop')) { \\Revolt\\EventLoop::run(); }\n"
          writeTextFile UTF8 "output/main.php" entryPoint
          liftEffect $ log $ "phpurs: Successfully compiled all modules. Generated main.php for " <> mainMod
        Nothing -> do
          liftEffect $ log "phpurs: Successfully compiled all modules."

      case mbFfiDir of
        Just dir -> liftEffect $ ComposerMerge.mergeComposers dir
        Nothing -> liftEffect $ ComposerMerge.mergeComposers ""
