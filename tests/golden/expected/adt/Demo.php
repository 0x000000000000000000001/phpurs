<?php

namespace Demo;

// ALL IMPORTS: 
// TO REQUIRE: 
if (!\function_exists('phpurs_curry_fallback') || !\function_exists('phpurs_execute_effect') || !\function_exists('phpurs_ref_new') || !\function_exists('phpurs_value') || !\function_exists('phpurs_record') || !\function_exists('phpurs_promise')) require_once __DIR__ . '/../runtime.php';



$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };


final class Demo_Red { public $tag = 'Red'; public function __construct() {} }
final class Demo_Blue { public $tag = 'Blue'; public function __construct() {} }

// Demo_choose
function majDemo_choose(int $x0_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_choose';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__t0 = null;
  if (($x0_0 > 0)) {
$__t0 = new \Demo\Demo_Red();
goto end_branch_0;;
};
  $__t0 = new \Demo\Demo_Blue();
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_choose'] = __NAMESPACE__ . '\\majDemo_choose';

// Demo_read
function majDemo_read($x0_0): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_read';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__t0 = null;
  if ($x0_0 instanceof \Demo\Demo_Red) {
$__t0 = 1;
goto end_branch_0;;
};
  $__t0 = 2;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_read'] = __NAMESPACE__ . '\\majDemo_read';

// Demo_score
function majDemo_score__phpurs_exact(int $x0_0): int {
  $__phpurs_inline_0_1_x0_0 = \Demo\majDemo___phpurs_enum_0_choose($x0_0);
  return match ($__phpurs_inline_0_1_x0_0) { 0 => 1, default => 2 };
}
function majDemo_score(int $x0_0): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_score';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = majDemo_score__phpurs_exact($x0_0);
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_score'] = __NAMESPACE__ . '\\majDemo_score';

// Demo___phpurs_enum_0_choose
function majDemo___phpurs_enum_0_choose($x0_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo___phpurs_enum_0_choose';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  if (($x0_0 > 0)) {
$__res = 0;
goto __end;;
};
  $__res = 1;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}

// Demo___phpurs_enum_0_read
function majDemo___phpurs_enum_0_read($x0_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo___phpurs_enum_0_read';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = match ($x0_0) { 0 => 1, default => 2 };
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}

