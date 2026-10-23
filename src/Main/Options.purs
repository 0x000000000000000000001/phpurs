-- | Parsed build options shared by the Main orchestration.
module Main.Options
  ( Options
  , parseOptions
  ) where

import Prelude

import Data.Array as Array
import Data.Either (Either)
import Data.Maybe (Maybe, fromMaybe)
import Phpurs.ModuleState (Emission)
import Phpurs.PurmetaBudget as PurmetaBudget
import Phpurs.RewriteLimit as RewriteLimit
import PureScript.Backend.Optimizer.App (CLIArgs, parseCLIArgs)

type Options =
  { args :: CLIArgs
  , verbose :: Boolean
  , emission :: Emission
  , outputDir :: String
  , purmetaBudget :: Maybe Number
  , rewriteLimit :: Int
  , profilePurmeta :: Boolean
  , noCache :: Boolean
  }

parseOptions :: Array String -> Either String Options
parseOptions cliArgs = do
  purmetaBudget <- PurmetaBudget.parseBudget cliArgs
  rewriteLimit <- RewriteLimit.parseLimit cliArgs
  let
    args = parseCLIArgs cliArgs
    bundleOnly = Array.elem "--bundle-only" cliArgs
    emitBundle = args.bundle || bundleOnly
    emitModules = not bundleOnly
  pure
    { args
    , verbose: Array.elem "--verbose" cliArgs
    , emission: { emitModules, emitBundle }
    , outputDir: fromMaybe "output" args.mbOutputDir
    , purmetaBudget
    , rewriteLimit
    , profilePurmeta: Array.elem "--profile-purmeta" cliArgs
    , noCache: Array.elem "--no-cache" cliArgs
    }
