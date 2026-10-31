<?php

namespace Demo;

// ALL IMPORTS: 
// TO REQUIRE: 
if (!\function_exists('phpurs_curry_fallback') || !\function_exists('phpurs_execute_effect') || !\function_exists('phpurs_ref_new') || !\function_exists('phpurs_value') || !\function_exists('phpurs_record') || !\function_exists('phpurs_promise')) require_once __DIR__ . '/../runtime.php';



$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };




// Demo_text
$GLOBALS['Demo_text'] = "quote \" backslash \\ dollar \$var newline 
 tab 	 single ' end";

// Demo_letter
$GLOBALS['Demo_letter'] = "é";

// Demo_echo
function majDemo_echo__phpurs_exact(string $value_0): string {
  return $value_0;
}
function majDemo_echo(string $value_0): string|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_echo';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = majDemo_echo__phpurs_exact($value_0);
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_echo'] = __NAMESPACE__ . '\\majDemo_echo';

