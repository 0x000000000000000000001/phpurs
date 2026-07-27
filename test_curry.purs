module TestCurry where

import Prelude
import Data.Array as Array
import Data.String as String

genStaticCurry :: String -> String -> Int -> String
genStaticCurry fnName ffiVar arity =
  if arity <= 0 then
    "$GLOBALS['" <> fnName <> "'] = " <> ffiVar <> " ?? function() {};"
  else
    let
      args = map (\i -> "$v" <> show i) (Array.range 0 (arity - 1))
      
      genLevel :: Int -> String
      genLevel i =
        if i == arity then
          let
            callArgs = String.joinWith ", " args
          in
            "        $f = " <> ffiVar <> ";\n" <>
            "        return $f(" <> callArgs <> ");\n"
        else
          let
            arg = "$v" <> show i
            uses = if i == 0 then "use (" <> ffiVar <> ")" else "use (" <> ffiVar <> ", " <> String.joinWith ", " (Array.take i args) <> ")"
            indent = String.joinWith "" (Array.replicate (i + 1) "    ")
          in
            indent <> "return function(" <> arg <> ") " <> uses <> " {\n" <>
            genLevel (i + 1) <>
            indent <> "};\n"
            
    in
      "$GLOBALS['" <> fnName <> "'] = " <> String.trim (genLevel 0) <> ";"
