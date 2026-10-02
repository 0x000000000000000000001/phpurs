-- | Parse and scope PHPurs's optional PBO RAM-cache budget.
module Phpurs.PurmetaBudget (parseBudget, withBudget) where

import Prelude

import Data.Either (Either(..))
import Data.Maybe (Maybe(..))
import Effect.Aff (Aff, bracket)
import Effect.Class (liftEffect)
import PureScript.Backend.Optimizer.Cache (setPurmetaCacheBudgetBytes, trimPurmetaCache)

foreign import parseBudgetImpl
  :: (String -> Either String (Maybe Number))
  -> Either String (Maybe Number)
  -> (Number -> Either String (Maybe Number))
  -> Array String
  -> Either String (Maybe Number)

-- | Arguments have already been split using the shared CLI's grouping rules.
parseBudget :: Array String -> Either String (Maybe Number)
parseBudget = parseBudgetImpl Left (Right Nothing) (Right <<< Just)

withBudget :: forall a. Maybe Number -> Aff a -> Aff a
withBudget Nothing action = action
withBudget (Just bytes) action = bracket
  (liftEffect $ setPurmetaCacheBudgetBytes bytes)
  (\previous -> liftEffect do
    void $ setPurmetaCacheBudgetBytes previous
    -- The invocation has ended: release any excess retained under a larger
    -- override before returning to another caller in the same process.
    trimPurmetaCache)
  (\_ -> action)
