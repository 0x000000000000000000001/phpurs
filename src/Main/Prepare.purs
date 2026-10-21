-- | Capture FFI inputs, resolve package roots, build callback evidence and
-- | prepare the persistent cache for one build.
module Main.Prepare
  ( Config
  , Prepared
  , prepare
  ) where

import Prelude

import Data.Array as Array
import Data.Either (Either(..), note)
import Data.List (List)
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.Set as Set
import Data.Traversable (traverse)
import Data.Tuple (Tuple(..))
import Effect.Aff (Aff)
import Effect.Class (liftEffect)
import Node.Buffer.Immutable as Bytes
import Node.Encoding (Encoding(..))
import Node.Process as Process
import Phpurs.BuildCache as BuildCache
import Phpurs.BuildInputs (LoadedInputs, captureForeign)
import Phpurs.BuildProfile as Profile
import Phpurs.CacheKey (Toolchain, fingerprintBytes)
import Phpurs.ForeignTraversals as ForeignTraversals
import Phpurs.ModuleCache (CacheHooks)
import Phpurs.ModuleState (BuildRefs, Emission, newBuildRefs)
import Phpurs.NativeCallbacks as NativeCallbacks
import Phpurs.PackagePaths (PackagePaths, resolvePackagePaths)
import PureScript.Backend.Optimizer.App (CLIArgs, loadDirectives)
import PureScript.Backend.Optimizer.CoreFn (Ann, Module(..), ModuleName, importName)
import PureScript.Backend.Optimizer.Directives.Defaults (defaultDirectives)
import PureScript.Backend.Optimizer.Semantics (InlineDirectiveMap)

type Config =
  { profile :: Profile.Profile
  , args :: CLIArgs
  , allModules :: Array (Module Ann)
  , finalModules :: List (Module Ann)
  , loaded :: LoadedInputs
  , override :: Maybe CacheHooks
  , toolchain :: Maybe Toolchain
  , emission :: Emission
  , outputDir :: String
  , rewriteLimit :: Int
  , noCache :: Boolean
  }

type Prepared =
  { refs :: BuildRefs
  , directives :: InlineDirectiveMap
  , packagePaths :: PackagePaths
  , foreignSources :: Map ModuleName String
  , nativeCallbacks :: NativeCallbacks.Callbacks
  , traversals :: ForeignTraversals.Traversals
  , cache :: BuildCache.CacheSession
  }

prepare :: Config -> Aff Prepared
prepare config = do
  refs <- liftEffect newBuildRefs

  -- Composer precedence follows module-name order, not dependency order.
  -- Package roots describe the loaded workspace, not the optimized subset:
  -- narrowing them would invalidate an existing reviewed Composer lock.
  let modulePaths = map (\(Module m) -> m.path) $ Array.sortWith (\(Module m) -> m.name) config.allModules
  packagePaths <- liftEffect $ resolvePackagePaths { ffiDir: config.args.mbFfiDir, modulePaths }
  directives <- loadDirectives
  captures <- traverse (\m@(Module core) -> do
    ffi <- Profile.measureAff config.profile "ffi" (unwrap core.name) \_ -> captureForeign packagePaths.ffiRoots m
    pure { core, ffi }) (Array.fromFoldable config.finalModules)
  cwd <- liftEffect Process.cwd
  let
    foreignSources = Map.fromFoldable $ map (\c -> Tuple c.core.name c.ffi.source) captures
    -- Use the same captured source/signature as codegen and cache identity.
    -- Reconstruct this evidence even when its module will be a cache hit.
    nativeCallbacks = if config.loaded.cacheable then Set.unions (map
      (\c -> NativeCallbacks.fromForeign c.core.name c.core.foreign c.ffi.source) captures)
      else Set.empty
    traversals = if config.loaded.cacheable then Set.unions (map
      (\c -> ForeignTraversals.fromForeign c.core.name c.core.foreign c.ffi.source) captures)
      else Set.empty
    inputs = if not config.loaded.cacheable then Left "Incomplete or duplicate module input" else
      note "Missing captured CoreFn fingerprint" $ traverse (\c -> do
        let name = unwrap c.core.name
        coreFn <- Map.lookup name config.loaded.fingerprints
        pure { name, coreFn, foreignInput: c.ffi.input, dependencies: map (unwrap <<< importName) c.core.imports }) captures
  cache <- Profile.measureAff config.profile "cache.plan" "" \_ -> case config.override, config.toolchain of
    Just hooks, _ -> pure { hooks, report: pure unit }
    _, Just identity | not config.noCache -> liftEffect $ BuildCache.prepareCache
      { toolchain: identity
      , options: { cwd, outputDir: config.outputDir, ffiRoots: packagePaths.ffiRoots, emitModules: config.emission.emitModules, emitBundle: config.emission.emitBundle, mainModule: config.args.mbMainModule, autoloadPath: config.args.mbAutoloadPath, rewriteLimit: config.rewriteLimit }
      , directives: fingerprintBytes (Bytes.fromString defaultDirectives UTF8)
      } inputs
    _, _ -> pure BuildCache.disabled
  pure { refs, directives, packagePaths, foreignSources, nativeCallbacks, traversals, cache }
