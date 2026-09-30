-- | Namespace-local PHP helpers embedded in every emitted module. Keep their
-- | order and formatting stable for modular output and concatenated bundles.
module Phpurs.Printer.Runtime (preamble) where

import Prelude

import Data.Array as Array
import Data.String as String

preamble :: String
preamble = dataClasses <> curryFallback <> effectHelpers <> referenceHelpers

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
  """if (!\function_exists(__NAMESPACE__ . '\\phpurs_curry_fallback')) {
  function phpurs_curry_fallback($fn, $args, $expected) {
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
      return phpurs_curry_fallback($fn, $merged, $expected);
    };
  }
}
"""

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
        <> " return phpurs_curry_fallback($fn, $args, $expected); }\n")
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
  """if (!\function_exists(__NAMESPACE__ . '\\phpurs_execute_effect')) {
  function phpurs_execute_effect($val) {
    if (\is_callable($val)) {
      return $val($GLOBALS['Data_Unit_unit']);
    }
    return $val;
  }
}
"""

referenceHelpers :: String
referenceHelpers =
  """if (!\function_exists(__NAMESPACE__ . '\\phpurs_ref_new')) {
  function phpurs_ref_new($value) {
    return (object)['value' => $value];
  }
  function phpurs_ref_read($ref) {
    return $ref->value;
  }
  function phpurs_ref_write($ref, $value) {
    $ref->value = $value;
    return null;
  }
}
"""
