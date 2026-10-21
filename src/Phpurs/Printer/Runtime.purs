-- | PHP runtime assembled once per output. Data classes remain namespace-local;
-- | curry/effect/reference helpers live in a shared global runtime file and are
-- | referenced through qualified `\phpurs_*` calls.
module Phpurs.Printer.Runtime (dataClasses, sharedRuntime, sharedRuntimeFile, bundleHeader) where

import Prelude

import Data.Array as Array
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Phpurs.RuntimeNames as Names

replaceNames :: String -> String
replaceNames text =
  text
    # String.replaceAll (Pattern "__CURRY__") (Replacement Names.curryFallback)
    # String.replaceAll (Pattern "__EFFECT__") (Replacement Names.executeEffect)
    # String.replaceAll (Pattern "__REF_NEW__") (Replacement Names.refNew)
    # String.replaceAll (Pattern "__REF_READ__") (Replacement Names.refRead)
    # String.replaceAll (Pattern "__REF_WRITE__") (Replacement Names.refWrite)

-- | Global helpers shared by every module. Generated calls use the fully
-- | qualified names (`\phpurs_*`); the runtime itself is emitted once.
sharedRuntime :: String
sharedRuntime = curryFallback <> effectHelpers <> referenceHelpers

sharedRuntimeFile :: String
sharedRuntimeFile = "<?php\n\n" <> sharedRuntime

-- | Runtime block prepended to a bundle before its module namespaces.
bundleHeader :: String
bundleHeader = "<?php\n\nnamespace {\n" <> sharedRuntime <> "}\n\n"

-- | The generic data representation supports constructor arities 0 through 12.
dataClasses :: String
dataClasses =
  "if (!class_exists(__NAMESPACE__ . '\\\\Phpurs_Data0')) {\n"
    <> String.joinWith "" (map dataClass (Array.range 0 12)) <> "}\n"
  where
  dataClass arity =
    let
      fields = map (\index -> "value" <> show index) (Array.take arity (Array.range 0 11))
      args = map ("$" <> _) fields
      properties = if Array.null fields then "" else " public " <> String.joinWith ", " args <> ";"
      assignments = String.joinWith "" (map (\field -> " $this->" <> field <> " = $" <> field <> ";") fields)
    in
      "  class Phpurs_Data" <> show arity <> " { public $tag;" <> properties
        <> " public function __construct(" <> String.joinWith ", " ([ "$t" ] <> args)
        <> ") { $this->tag = $t;" <> assignments <> " } }\n"

curryFallback :: String
curryFallback =
  replaceNames
    ("""if (!\function_exists('__CURRY__')) {
  function __CURRY__($fn, $args, $expected) {
    $missing = $expected - \count($args);
""" <> String.joinWith "" (map (\arity -> specializedCurry (Array.take arity [ "a", "b", "c", "d" ])) (Array.range 1 4)) <>
  """    return function(...$more) use ($fn, $args, $expected) {
      $merged = \array_merge($args, $more);
      if (\count($merged) >= $expected) {
        $res = $fn(...\array_slice($merged, 0, $expected));
        if (\count($merged) > $expected) {
          return $res(...\array_slice($merged, $expected));
        }
        return $res;
      }
      return __CURRY__($fn, $merged, $expected);
    };
  }
}
"""
    )

-- | One to four missing arguments use fixed PHP parameters. Larger partial
-- | applications use the variadic fallback above. Both paths allow extra args.
specializedCurry :: Array String -> String
specializedCurry args =
  let
    arity = Array.length args
    n = show arity
    params = Array.mapWithIndex (\index arg -> "$" <> arg <> (if index == 0 then "" else " = null")) args
    appendArgs supplied = String.joinWith " " (map (\arg -> "$args[] = $" <> arg <> ";") supplied)
    partialCounts = if arity > 1 then Array.range 1 (arity - 1) else []
    partialCalls = map
      (\count -> "        if ($num === " <> show count <> ") { " <> appendArgs (Array.take count args)
        <> " return __CURRY__($fn, $args, $expected); }\n")
      partialCounts
  in
    "    if ($missing === " <> n <> ") {\n"
      <> "      return function(" <> String.joinWith ", " params <> ") use ($fn, $args, $expected) {\n"
      <> "        $num = \\func_num_args();\n"
      <> String.joinWith "" partialCalls
      <> "        if ($num > " <> n <> ") {\n"
      <> "          $merged = \\array_merge($args, \\func_get_args());\n"
      <> "          $res = $fn(...\\array_slice($merged, 0, $expected));\n"
      <> "          return $res(...\\array_slice($merged, $expected));\n"
      <> "        }\n"
      <> "        " <> appendArgs args <> "\n"
      <> "        return $fn(...$args);\n"
      <> "      };\n"
      <> "    }\n"

effectHelpers :: String
effectHelpers =
  replaceNames ("""if (!\function_exists('__EFFECT__')) {
  function __EFFECT__($val) {
    if (\is_callable($val)) {
      return $val($GLOBALS['Data_Unit_unit']);
    }
    return $val;
  }
}
""")

referenceHelpers :: String
referenceHelpers =
  replaceNames ("""if (!\function_exists('__REF_NEW__')) {
  function __REF_NEW__($value) {
    return (object)['value' => $value];
  }
  function __REF_READ__($ref) {
    return $ref->value;
  }
  function __REF_WRITE__($ref, $value) {
    $ref->value = $value;
    return null;
  }
}
""")
