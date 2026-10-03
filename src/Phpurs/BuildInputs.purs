-- | Capture each input once: the key and decoder/codegen consume the same bytes.
module Phpurs.BuildInputs (LoadedInputs, ForeignCapture, loadInputs, loadInputsWithProfile, captureForeign) where

import Prelude

import Control.Parallel (parTraverse)
import Data.Array as Array
import Data.Either (Either(..))
import Data.Foldable (all)
import Data.List (List)
import Data.List as List
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Effect (Effect)
import Effect.Aff (Aff, attempt)
import Effect.Class (liftEffect)
import Effect.Console as Console
import Effect.Exception (Error)
import Node.Buffer as Buffer
import Node.Buffer.Immutable as Bytes
import Node.Encoding (Encoding(..))
import Node.FS.Aff as FS
import Node.FS.Stats as Stats
import Phpurs.BuildProfile as Profile
import Phpurs.CacheKey (Fingerprint, ForeignInput(..), fingerprintBytes)
import Phpurs.PackagePaths (findForeignFile)
import PureScript.Backend.Optimizer.CoreFn (Ann, Module(..))
import PureScript.Backend.Optimizer.CoreFn.Json.Text (parseModule)
import PureScript.Backend.Optimizer.CoreFn.Sort (sortModules)

type LoadedInputs =
  { modules :: List (Module Ann)
  , fingerprints :: Map String Fingerprint
  , cacheable :: Boolean
  }

type ForeignCapture = { input :: ForeignInput, source :: String }

foreign import moduleReadConcurrency :: Effect Int
foreign import isMissingFile :: Error -> Boolean

-- | Match PBO's bounded loader and sorter, but retain fingerprints and detect
-- | ambiguous names before the sorter collapses them into its module index.
loadInputs :: String -> Aff LoadedInputs
loadInputs = loadInputsWithProfile Profile.disabled

loadInputsWithProfile :: Profile.Profile -> String -> Aff LoadedInputs
loadInputsWithProfile profile outputDir = do
  jobs <- liftEffect moduleReadConcurrency
  files <- Profile.measureAff profile "corefn.read" "" \_ -> FS.readdir outputDir
  let
    absent = { captured: Nothing, valid: true }
    readModule file = do
      statResult <- Profile.measureAff profile "corefn.read" "" \_ -> attempt (FS.stat file)
      case statResult of
        Right stat | Stats.isFile stat -> do
          buffer <- Profile.measureAff profile "corefn.read" "" \_ -> FS.readFile file
          bytes <- liftEffect (Buffer.unsafeFreeze buffer)
          decoded <- liftEffect $ Profile.measurePure profile "corefn.decode" "" \_ -> do
            mod <- parseModule (Bytes.toString UTF8 bytes)
            pure { mod, fingerprint: fingerprintBytes bytes }
          case decoded of
            Left err -> do
              liftEffect $ Console.error $ "Failed to decode " <> file <> ": " <> err
              pure { captured: Nothing, valid: false }
            Right captured -> pure { captured: Just captured, valid: true }
        Right _ -> pure absent
        Left err | isMissingFile err -> pure absent
        Left err -> do
          liftEffect $ Console.error $ "Failed to stat " <> file <> ": " <> show err
          pure { captured: Nothing, valid: false }
    readDirectory dir = do
      stat <- Profile.measureAff profile "corefn.read" "" \_ -> FS.stat (outputDir <> "/" <> dir)
      if Stats.isDirectory stat then readModule (outputDir <> "/" <> dir <> "/corefn.json")
      else pure absent
    loadBatches remaining
      | Array.null remaining = pure List.Nil
      | otherwise = do
          let { before, after } = Array.splitAt jobs remaining
          batch <- parTraverse readDirectory before
          rest <- loadBatches after
          pure (List.Cons batch rest)
  results <- if jobs == 1 then traverse readDirectory files
    else Array.concat <<< Array.fromFoldable <$> loadBatches files
  let
    captured = Array.mapMaybe _.captured results
    fingerprints = Map.fromFoldable $ map (\{ mod: Module m, fingerprint } -> Tuple (unwrap m.name) fingerprint) captured
  modules <- liftEffect $ Profile.measurePure profile "corefn.sort" "" \_ ->
    sortModules (List.fromFoldable (map _.mod captured))
  pure
    { modules
    , fingerprints
    , cacheable: all _.valid results && Map.size fingerprints == Array.length captured
    }

captureForeign :: Array String -> Module Ann -> Aff ForeignCapture
captureForeign roots (Module m)
  | Map.isEmpty m.foreign = pure { input: NoForeign, source: "" }
  | otherwise = do
      selected <- liftEffect $ findForeignFile roots (unwrap m.name) m.path
      case selected of
        Nothing -> pure { input: MissingForeign, source: "" }
        Just file -> do
          buffer <- FS.readFile file
          bytes <- liftEffect (Buffer.unsafeFreeze buffer)
          pure { input: ForeignSource file (fingerprintBytes bytes), source: Bytes.toString UTF8 bytes }
