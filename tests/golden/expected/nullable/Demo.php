<?php

namespace Demo;

// ALL IMPORTS: 
// TO REQUIRE: 
if (!\function_exists('phpurs_curry_fallback') || !\function_exists('phpurs_execute_effect') || !\function_exists('phpurs_ref_new') || !\function_exists('phpurs_value') || !\function_exists('phpurs_record') || !\function_exists('phpurs_promise')) require_once __DIR__ . '/../runtime.php';



$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };


final class Demo_Empty { public $tag = 'Empty'; public function __construct() {} }
final class Demo_Box { public $tag = 'Box'; public function __construct(public int $value0) {} }

// Demo_Empty
$GLOBALS['Demo_Empty'] = new \Demo\Demo_Empty();

// Demo_make
function majDemo_make(int $x0_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_make';
  if ($__num < 1) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__t0 = null;
  if (($x0_0 > -1)) {
$__t0 = new \Demo\Demo_Box($x0_0);
goto end_branch_0;;
};
  $__t0 = new \Demo\Demo_Empty();
  end_branch_0:;
  $__res = $__t0;
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
  $__t1 = null;
  if ($x0_0 instanceof \Demo\Demo_Empty) {
$__t1 = -1;
goto end_branch_1;;
};
  $__t0 = null;
  if ($x0_0 instanceof \Demo\Demo_Box) {
$__t0 = ($x0_0)->{'value0'};
goto end_branch_0;;
};
  throw new \Exception("unreachable at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__t1 = $__t0;
  end_branch_1:;
  $__res = $__t1;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Demo_read'] = __NAMESPACE__ . '\\majDemo_read';

// Demo_score
function majDemo_score__phpurs_exact(int $x0_0): int {
  return \Demo\majDemo_read(\Demo\majDemo_make($x0_0));
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

