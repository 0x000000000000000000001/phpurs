-- | Preserve generated PHP files whose UTF-8 bytes are already up to date.
module Phpurs.FileEmission (writeTextFileIfChanged) where

import Prelude

import Data.Either (Either(..))
import Effect.Aff (Aff, attempt, throwError)
import Effect.Class (liftEffect)
import Effect.Exception (Error)
import Node.Buffer as Buffer
import Node.Buffer.Immutable as ImmutableBuffer
import Node.Encoding (Encoding(..))
import Node.FS.Aff as FS

foreign import isMissingFile :: Error -> Boolean

-- | Compare bytes rather than decoded text: invalid UTF-8 in an existing file
-- | must not be mistaken for a generated replacement character. Only ENOENT
-- | means there is no previous output; other I/O errors fail the build.
writeTextFileIfChanged :: String -> String -> Aff Unit
writeTextFileIfChanged path contents = do
  let bytes = ImmutableBuffer.fromString contents UTF8
  previous <- attempt $ FS.readFile path
  unchanged <- case previous of
    Right buffer -> (_ == bytes) <$> liftEffect (Buffer.unsafeFreeze buffer)
    Left err | isMissingFile err -> pure false
    Left err -> throwError err
  unless unchanged do
    -- Neither buffer is mutated; thawing shares the bytes with Node's writer.
    buffer <- liftEffect $ Buffer.unsafeThaw bytes
    FS.writeFile path buffer
