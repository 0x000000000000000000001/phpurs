-- | Invocation-local, observational timings. No process-global profiler state.
module Phpurs.BuildProfile
  ( Profile, disabled, withProfile, measureAff, measurePure
  , beginOptimization, endOptimization, restoredModule
  ) where

import Prelude

import Effect (Effect)
import Effect.Aff (Aff, generalBracket)
import Effect.Class (liftEffect)
import Effect.Console as Console

foreign import data Profile :: Type
foreign import data Span :: Type
foreign import disabled :: Profile
foreign import enabled :: Profile -> Boolean
foreign import create :: Effect Profile
foreign import begin :: Profile -> String -> String -> Effect Span
foreign import finish :: Span -> String -> Effect Unit
foreign import reportJson :: Profile -> String -> Effect String
foreign import beginOptimization :: Profile -> String -> Effect Unit
foreign import endOptimization :: Profile -> Effect Unit
foreign import restoredModule :: Profile -> String -> Effect Unit

-- | The thunk keeps strict pure work inside the clock interval.
foreign import measurePure :: forall a. Profile -> String -> String -> (Unit -> a) -> Effect a

measureAff :: forall a. Profile -> String -> String -> (Unit -> Aff a) -> Aff a
measureAff profile label mod action
  | not (enabled profile) = pure unit >>= action
  | otherwise = generalBracket
      (liftEffect $ begin profile label mod)
      { completed: \_ span -> liftEffect $ finish span "completed"
      , failed: \_ span -> liftEffect $ finish span "failed"
      , killed: \_ span -> liftEffect $ finish span "cancelled"
      }
      (\_ -> action unit)

withProfile :: forall a. Boolean -> (Profile -> Aff a) -> Aff a
withProfile false action = action disabled
withProfile true action = generalBracket (liftEffect create)
  { completed: \_ profile -> report "completed" profile
  , failed: \_ profile -> report "failed" profile
  , killed: \_ profile -> report "cancelled" profile
  }
  action
  where
  report status profile = liftEffect do
    json <- reportJson profile status
    Console.error $ "[phpurs] build-profile: " <> json
