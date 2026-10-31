<?php

namespace Demo;

// ALL IMPORTS: 
// TO REQUIRE: 
if (!\function_exists('phpurs_curry_fallback') || !\function_exists('phpurs_execute_effect') || !\function_exists('phpurs_ref_new') || !\function_exists('phpurs_value') || !\function_exists('phpurs_record') || !\function_exists('phpurs_promise')) require_once __DIR__ . '/../runtime.php';



$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };




// Demo_loop
function majDemo_loop__phpurs_exact(int $x0_0): int {
  tco_loop_Demo_loop:;
  $__t0 = null;
  if (($x0_0 > 0)) {
$__tco_1 = ($x0_0 - 1);
$x0_0 = $__tco_1;
goto tco_loop_Demo_loop;;
$__t0 = null;
goto end_branch_0;;
};
  $__t0 = 0;
  end_branch_0:;
  return $__t0;
}
function majDemo_loop(int $x0_0): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_loop';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = majDemo_loop__phpurs_exact($x0_0);
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_loop'] = __NAMESPACE__ . '\\majDemo_loop';

