-- | Scope optional PBO diagnostics to one PHP optimization/emission phase.
module Phpurs.PurmetaProfile (withProfile) where

import Prelude

import Effect.Aff (Aff, bracket)
import Effect.Class (liftEffect)
import Effect.Console as Console
import PureScript.Backend.Optimizer.Cache (readPurmetaStatsJson, setPurmetaStatsEnabled)

withProfile :: forall a. Boolean -> Aff a -> Aff a
withProfile false action = action
withProfile true action = bracket
  (liftEffect $ setPurmetaStatsEnabled true)
  (\_ -> liftEffect do
    stats <- readPurmetaStatsJson
    setPurmetaStatsEnabled false
    Console.error $ "[phpurs] purmeta: " <> stats)
  (\_ -> action)
