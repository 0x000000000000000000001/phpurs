module Phpurs.Mtime where

import Prelude
import Effect (Effect)

foreign import getMtimeMsImpl :: String -> Effect Number

getMtimeMs :: String -> Effect Number
getMtimeMs = getMtimeMsImpl
