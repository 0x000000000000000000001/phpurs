-- | Content keys for module-state reuse, in the builder's module order.
module Phpurs.CacheKey
  ( Fingerprint
  , fingerprintBytes
  , fingerprintString
  , ForeignInput(..)
  , Toolchain
  , BuildOptions
  , BuildContext
  , ModuleInput
  , ModuleKey
  , KeyPlan
  , planKeys
  ) where

import Prelude

import Data.Either (Either(..))
import Data.Maybe (Maybe(..))
import Data.Nullable (Nullable, toNullable)
import Node.Buffer.Immutable (ImmutableBuffer)

newtype Fingerprint = Fingerprint String

derive newtype instance eqFingerprint :: Eq Fingerprint

foreign import fingerprintBytesImpl :: ImmutableBuffer -> String

-- | Hash the exact bytes supplied to the loader, without UTF-8 decoding or mtime.
fingerprintBytes :: ImmutableBuffer -> Fingerprint
fingerprintBytes = Fingerprint <<< fingerprintBytesImpl

fingerprintString :: Fingerprint -> String
fingerprintString (Fingerprint value) = value

-- | Resolve foreign input anew before planning, including previous misses.
data ForeignInput
  = NoForeign
  | MissingForeign
  | ForeignSource String Fingerprint

type Toolchain =
  { phpursVersion :: String
  , pboVersion :: String
  , backend :: Fingerprint
  , nodeVersion :: String
  , v8Version :: String
  , platform :: String
  , arch :: String
  }

-- | Effective driver options, not raw CLI arguments. Keep path spellings and
-- | Nothing vs Just: entrypoint rendering can distinguish an explicit default.
type BuildOptions =
  { cwd :: String
  , outputDir :: String
  , ffiRoots :: Array String
  , emitModules :: Boolean
  , emitBundle :: Boolean
  , mainModule :: Maybe String
  , autoloadPath :: Maybe String
  , rewriteLimit :: Int
  }

type BuildContext =
  { toolchain :: Toolchain
  , options :: BuildOptions
  , directives :: Fingerprint
  }

type ModuleInput =
  { name :: String
  , coreFn :: Fingerprint
  , foreignInput :: ForeignInput
  , dependencies :: Array String
  }

type ModuleKey = { name :: String, key :: Fingerprint }

type KeyPlan =
  { context :: Fingerprint
  , modules :: Array ModuleKey
  , key :: Fingerprint
  }

type OptionsImpl =
  { cwd :: String
  , outputDir :: String
  , ffiRoots :: Array String
  , emitModules :: Boolean
  , emitBundle :: Boolean
  , mainModule :: Nullable String
  , autoloadPath :: Nullable String
  , rewriteLimit :: Int
  }

type ForeignImpl =
  { kind :: String
  , path :: Nullable String
  , content :: Nullable Fingerprint
  }

foreign import planKeysImpl
  :: (String -> Either String KeyPlan)
  -> (KeyPlan -> Either String KeyPlan)
  -> { toolchain :: Toolchain, options :: OptionsImpl, directives :: Fingerprint }
  -> Array { name :: String, coreFn :: Fingerprint, foreignInput :: ForeignImpl, dependencies :: Array String }
  -> Either String KeyPlan

-- | Require unique modules in dependency order. An unsupported plan is a Left,
-- | so the caller can use the ordinary build rather than a partial key set.
planKeys :: BuildContext -> Array ModuleInput -> Either String KeyPlan
planKeys context modules = planKeysImpl Left Right
  (context { options = context.options
    { mainModule = toNullable context.options.mainModule
    , autoloadPath = toNullable context.options.autoloadPath
    }
  })
  (map (\input -> input { foreignInput = foreignImpl input.foreignInput }) modules)
  where
  foreignImpl = case _ of
    NoForeign -> { kind: "none", path: toNullable Nothing, content: toNullable Nothing }
    MissingForeign -> { kind: "missing", path: toNullable Nothing, content: toNullable Nothing }
    ForeignSource path content -> { kind: "source", path: toNullable (Just path), content: toNullable (Just content) }
