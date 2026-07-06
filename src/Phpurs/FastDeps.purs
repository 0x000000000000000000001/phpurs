module Phpurs.FastDeps where

import Prelude
import Effect (Effect)

type ModuleDeps = 
  { moduleName :: Array String
  , imports :: Array (Array String)
  }

foreign import parseAllImportsImpl :: Array String -> Effect (Array ModuleDeps)

parseAllImports :: Array String -> Effect (Array ModuleDeps)
parseAllImports = parseAllImportsImpl
