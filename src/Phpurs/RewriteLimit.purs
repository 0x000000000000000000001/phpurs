-- | Validate the effective per-binding optimizer iteration guard once, before
-- | loading inputs, then share it between the builder and persistent keys.
module Phpurs.RewriteLimit (defaultLimit, parseLimit) where

import Data.Either (Either(..))

defaultLimit :: Int
defaultLimit = 10000

foreign import parseLimitImpl
  :: (String -> Either String Int)
  -> (Int -> Either String Int)
  -> Int
  -> Array String
  -> Either String Int

-- | Arguments have already been split using the shared CLI's grouping rules.
parseLimit :: Array String -> Either String Int
parseLimit = parseLimitImpl Left Right defaultLimit
