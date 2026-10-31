<?php

namespace Demo;

// ALL IMPORTS: 
// TO REQUIRE: 
if (!\function_exists('phpurs_curry_fallback') || !\function_exists('phpurs_execute_effect') || !\function_exists('phpurs_ref_new') || !\function_exists('phpurs_value') || !\function_exists('phpurs_record') || !\function_exists('phpurs_promise')) require_once __DIR__ . '/../runtime.php';



$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };




// Demo_make
function majDemo_make(int $x0_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_make';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["value" => $x0_0];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_make'] = __NAMESPACE__ . '\\majDemo_make';

// Demo_read
function majDemo_read($x0_0): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_read';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($x0_0)->{'value'};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_read'] = __NAMESPACE__ . '\\majDemo_read';

// Demo_bump
function majDemo_bump($x0_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_bump';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["value" => (($x0_0)->{'value'} + 1)];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_bump'] = __NAMESPACE__ . '\\majDemo_bump';

