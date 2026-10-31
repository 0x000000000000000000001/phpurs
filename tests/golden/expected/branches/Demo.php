<?php

namespace Demo;

// ALL IMPORTS: 
// TO REQUIRE: 
if (!\function_exists('phpurs_curry_fallback') || !\function_exists('phpurs_execute_effect') || !\function_exists('phpurs_ref_new') || !\function_exists('phpurs_value') || !\function_exists('phpurs_record') || !\function_exists('phpurs_promise')) require_once __DIR__ . '/../runtime.php';



$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };




// Demo_classify
function majDemo_classify__phpurs_exact(int $x0_0): int {
  return match ($x0_0) { 0 => 10, 1 => 20, default => 30 };
}
function majDemo_classify(int $x0_0): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_classify';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = majDemo_classify__phpurs_exact($x0_0);
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_classify'] = __NAMESPACE__ . '\\majDemo_classify';

// Demo_sign
function majDemo_sign__phpurs_exact(int $x0_0): int {
  $__t0 = null;
  if (($x0_0 > 0)) {
$__t0 = 1;
goto end_branch_0;;
};
  $__t0 = -1;
  end_branch_0:;
  return $__t0;
}
function majDemo_sign(int $x0_0): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_sign';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = majDemo_sign__phpurs_exact($x0_0);
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_sign'] = __NAMESPACE__ . '\\majDemo_sign';

