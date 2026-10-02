-- | Connect a complete captured key plan to the optional module-state store.
module Phpurs.BuildCache (CacheSession, disabled, prepareCache) where

import Prelude

import Data.Either (Either(..))
import Data.Map as Map
import Data.Maybe (Maybe(..))
import Data.Newtype (unwrap)
import Data.Tuple (Tuple(..))
import Effect (Effect)
import Effect.Class (liftEffect)
import Effect.Console as Console
import Effect.Ref as Ref
import Phpurs.CacheKey (BuildContext, ModuleInput, planKeys)
import Phpurs.ModuleCache (CacheHooks, loadModuleState, saveModuleState)

type CacheSession = { hooks :: CacheHooks, report :: Effect Unit }

disabled :: CacheSession
disabled = { hooks: { load: \_ -> pure Nothing, store: \_ -> pure unit }, report: pure unit }

prepareCache :: BuildContext -> Either String (Array ModuleInput) -> Effect CacheSession
prepareCache context inputs = case inputs >>= planKeys context of
  Left reason -> do
    Console.error $ "[phpurs] cache disabled: " <> reason
    pure disabled
  Right plan -> do
    counts <- Ref.new { hits: 0, misses: 0, stores: 0 }
    let
      keys = Map.fromFoldable (map (\entry -> Tuple entry.name entry.key) plan.modules)
      directory = context.options.outputDir <> "/.phpurs-cache"
      emission = { emitModules: context.options.emitModules, emitBundle: context.options.emitBundle }
      load name = liftEffect do
        result <- case Map.lookup (unwrap name) keys of
          Nothing -> pure Nothing
          Just key -> loadModuleState { directory, key, moduleName: name, emission }
        Ref.modify_ (\c -> case result of
          Just _ -> c { hits = c.hits + 1 }
          Nothing -> c { misses = c.misses + 1 }) counts
        pure result
      store state = liftEffect case Map.lookup (unwrap state.backend.name) keys of
        Nothing -> pure unit
        Just key -> do
          saved <- saveModuleState directory key state
          when saved $ Ref.modify_ (\c -> c { stores = c.stores + 1 }) counts
      report = do
        c <- Ref.read counts
        Console.error $ "[phpurs] cache: " <> show c.hits <> " hits, " <> show c.misses <> " misses, " <> show c.stores <> " stores"
    pure { hooks: { load, store }, report }
