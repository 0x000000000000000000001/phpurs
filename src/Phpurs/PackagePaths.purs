-- | Resolve package roots once per build for PHP FFI lookup and Composer.
module Phpurs.PackagePaths
  ( PackagePaths
  , resolvePackagePaths
  , findForeignFile
  ) where

import Prelude

import Data.Maybe (Maybe)
import Data.Nullable (Nullable, toMaybe, toNullable)
import Effect (Effect)

type PackagePaths =
  { ffiRoots :: Array String
  , composerRoots :: Array String
  }

foreign import resolvePackagePathsImpl
  :: { ffiDir :: Nullable String, modulePaths :: Array String }
  -> Effect PackagePaths

-- | Module paths must be in module-name order to retain Composer merge priority.
resolvePackagePaths
  :: { ffiDir :: Maybe String, modulePaths :: Array String }
  -> Effect PackagePaths
resolvePackagePaths { ffiDir, modulePaths } =
  resolvePackagePathsImpl { ffiDir: toNullable ffiDir, modulePaths }

foreign import findForeignFileImpl :: Array String -> String -> String -> Effect (Nullable String)

-- | Try the adjacent PHP file first, then only the supplied, ordered roots.
findForeignFile :: Array String -> String -> String -> Effect (Maybe String)
findForeignFile roots moduleName modulePath =
  toMaybe <$> findForeignFileImpl roots moduleName modulePath
