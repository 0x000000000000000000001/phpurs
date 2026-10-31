<?php

namespace Demo;

// ALL IMPORTS: 
// TO REQUIRE: 
if (!\function_exists('phpurs_curry_fallback') || !\function_exists('phpurs_execute_effect') || !\function_exists('phpurs_ref_new') || !\function_exists('phpurs_value') || !\function_exists('phpurs_record') || !\function_exists('phpurs_promise')) require_once __DIR__ . '/../runtime.php';



$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };
$ffi_Demo = \call_user_func(function() {
  $exports = [];
$exports['add'] = function($a, $b) { return $a + $b; };
$exports['label'] = 'demo';
  return $exports;
});
function majDemo_add(int $v0, $v1 = null): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majDemo_add';
  if ($__num < 2) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  global $ffi_Demo;
  $f = (\array_key_exists("add", $ffi_Demo) ? $ffi_Demo["add"] : (throw new \RuntimeException("Missing PHP FFI export: Demo.add")));
  $__ret = $f($v0, $v1);
  return $__ret;
}
$GLOBALS['Demo_add'] = __NAMESPACE__ . '\\majDemo_add';

function majDemo_label() {
  return \array_key_exists('Demo_label', $GLOBALS) ? $GLOBALS['Demo_label'] : (throw new \RuntimeException("Missing PHP FFI export: Demo.label"));
}
if (\array_key_exists("label", $ffi_Demo)) {
  $GLOBALS['Demo_label'] = $ffi_Demo["label"];
} else {
  unset($GLOBALS['Demo_label']);
}






