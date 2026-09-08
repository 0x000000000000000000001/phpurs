<?php

namespace Test\RBTree;

// ALL IMPORTS: Bench, Control.Applicative, Control.Bind, Data.Ord, Data.Ring, Data.Semiring, Data.Show, Effect, Effect.Console, Prelude, Prim, Test.RBTree
// TO REQUIRE: Bench, Control.Applicative, Control.Bind, Data.Ord, Data.Ring, Data.Semiring, Data.Show, Effect, Effect.Console, Prelude, Test.RBTree
require_once __DIR__ . '/../Bench/index.php';
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Data.Ord/index.php';
require_once __DIR__ . '/../Data.Ring/index.php';
require_once __DIR__ . '/../Data.Semiring/index.php';
require_once __DIR__ . '/../Data.Show/index.php';
require_once __DIR__ . '/../Effect/index.php';
require_once __DIR__ . '/../Effect.Console/index.php';
require_once __DIR__ . '/../Prelude/index.php';
require_once __DIR__ . '/../Test.RBTree/index.php';

if (!class_exists(__NAMESPACE__ . '\\Phpurs_Data0')) {
  class Phpurs_Data0 { public $tag; public function __construct($t) { $this->tag = $t; } }
  class Phpurs_Data1 { public $tag; public $value0; public function __construct($t, $value0) { $this->tag = $t; $this->value0 = $value0; } }
  class Phpurs_Data2 { public $tag; public $value0, $value1; public function __construct($t, $value0, $value1) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; } }
  class Phpurs_Data3 { public $tag; public $value0, $value1, $value2; public function __construct($t, $value0, $value1, $value2) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; } }
  class Phpurs_Data4 { public $tag; public $value0, $value1, $value2, $value3; public function __construct($t, $value0, $value1, $value2, $value3) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; } }
  class Phpurs_Data5 { public $tag; public $value0, $value1, $value2, $value3, $value4; public function __construct($t, $value0, $value1, $value2, $value3, $value4) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; } }
  class Phpurs_Data6 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; } }
  class Phpurs_Data7 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; } }
  class Phpurs_Data8 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; $this->value7 = $value7; } }
  class Phpurs_Data9 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; $this->value7 = $value7; $this->value8 = $value8; } }
  class Phpurs_Data10 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; $this->value7 = $value7; $this->value8 = $value8; $this->value9 = $value9; } }
  class Phpurs_Data11 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9, $value10; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9, $value10) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; $this->value7 = $value7; $this->value8 = $value8; $this->value9 = $value9; $this->value10 = $value10; } }
  class Phpurs_Data12 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; $this->value7 = $value7; $this->value8 = $value8; $this->value9 = $value9; $this->value10 = $value10; $this->value11 = $value11; } }
}
if (!\function_exists(__NAMESPACE__ . '\\phpurs_curry_fallback')) {
  function phpurs_curry_fallback($fn, $args, $expected) {
    $missing = $expected - \count($args);
    if ($missing === 1) {
      return function($a) use ($fn, $args, $expected) {
        $num = \func_num_args();
        if ($num > 1) {
          $merged = \array_merge($args, \func_get_args());
          $res = $fn(...\array_slice($merged, 0, $expected));
          return $res(...\array_slice($merged, $expected));
        }
        $args[] = $a;
        return $fn(...$args);
      };
    }
    if ($missing === 2) {
      return function($a, $b = null) use ($fn, $args, $expected) {
        $num = \func_num_args();
        if ($num === 1) { $args[] = $a; return phpurs_curry_fallback($fn, $args, $expected); }
        if ($num > 2) {
          $merged = \array_merge($args, \func_get_args());
          $res = $fn(...\array_slice($merged, 0, $expected));
          return $res(...\array_slice($merged, $expected));
        }
        $args[] = $a; $args[] = $b;
        return $fn(...$args);
      };
    }
    if ($missing === 3) {
      return function($a, $b = null, $c = null) use ($fn, $args, $expected) {
        $num = \func_num_args();
        if ($num === 1) { $args[] = $a; return phpurs_curry_fallback($fn, $args, $expected); }
        if ($num === 2) { $args[] = $a; $args[] = $b; return phpurs_curry_fallback($fn, $args, $expected); }
        if ($num > 3) {
          $merged = \array_merge($args, \func_get_args());
          $res = $fn(...\array_slice($merged, 0, $expected));
          return $res(...\array_slice($merged, $expected));
        }
        $args[] = $a; $args[] = $b; $args[] = $c;
        return $fn(...$args);
      };
    }
    if ($missing === 4) {
      return function($a, $b = null, $c = null, $d = null) use ($fn, $args, $expected) {
        $num = \func_num_args();
        if ($num === 1) { $args[] = $a; return phpurs_curry_fallback($fn, $args, $expected); }
        if ($num === 2) { $args[] = $a; $args[] = $b; return phpurs_curry_fallback($fn, $args, $expected); }
        if ($num === 3) { $args[] = $a; $args[] = $b; $args[] = $c; return phpurs_curry_fallback($fn, $args, $expected); }
        if ($num > 4) {
          $merged = \array_merge($args, \func_get_args());
          $res = $fn(...\array_slice($merged, 0, $expected));
          return $res(...\array_slice($merged, $expected));
        }
        $args[] = $a; $args[] = $b; $args[] = $c; $args[] = $d;
        return $fn(...$args);
      };
    }
    return function(...$more) use ($fn, $args, $expected) {
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
if (!\function_exists(__NAMESPACE__ . '\\phpurs_execute_effect')) {
  function phpurs_execute_effect($val) {
    if (\is_callable($val)) {
      return $val($GLOBALS['Data_Unit_unit']);
    }
    return $val;
  }
}

$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };


final class Test_RBTree_R { public $tag = 'R'; public function __construct() {} }
final class Test_RBTree_B { public $tag = 'B'; public function __construct() {} }
final class Test_RBTree_E { public $tag = 'E'; public function __construct() {} }
final class Test_RBTree_T { public $tag = 'T'; public function __construct(public  $value0, public  $value1, public int $value2, public  $value3) {} }

// Test_RBTree_R
$GLOBALS['Test_RBTree_R'] = ($GLOBALS['__phpurs_data0_R'] ??= new \Test\RBTree\Test_RBTree_R());

// Test_RBTree_B
$GLOBALS['Test_RBTree_B'] = ($GLOBALS['__phpurs_data0_B'] ??= new \Test\RBTree\Test_RBTree_B());

// Test_RBTree_E
$GLOBALS['Test_RBTree_E'] = ($GLOBALS['__phpurs_data0_E'] ??= new \Test\RBTree\Test_RBTree_E());

// Test_RBTree_T_closure
$GLOBALS['Test_RBTree_T_closure'] = (function() {
  $__fn = function($value0, $value1 = null, $value2 = null, $value3 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = new \Test\RBTree\Test_RBTree_T($value0, $value1, $value2, $value3);
  goto __end;;
  __end:
  return $__num > 4 ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
  };
  return $__fn;
})();

// Test_RBTree_T
function majTest_majRmajBmajTree_majT($v_0, $v_1 = null, $v_2 = null, $v_3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majTest_majRmajBmajTree_majT';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = ($GLOBALS['Test_RBTree_T_closure'])($v_0, $v_1, $v_2, $v_3);
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Test_RBTree_T'] = __NAMESPACE__ . '\\majTest_majRmajBmajTree_majT';

// Test_RBTree_max
function majTest_majRmajBmajTree_max(int $x_0, $y_1 = null): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majTest_majRmajBmajTree_max';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__t0 = null;
  if (($x_0 > $y_1)) {
$__t0 = $x_0;
goto end_branch_0;;
};
  $__t0 = $y_1;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Test_RBTree_max'] = __NAMESPACE__ . '\\majTest_majRmajBmajTree_max';

// Test_RBTree_makeBlack
function majTest_majRmajBmajTree_makemajBlack($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majTest_majRmajBmajTree_makemajBlack';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__t0 = null;
  if ($v_0 instanceof \Test\RBTree\Test_RBTree_T) {
$__t0 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), ($v_0)->{'value1'}, ($v_0)->{'value2'}, ($v_0)->{'value3'});
goto end_branch_0;;
};
  if ($v_0 instanceof \Test\RBTree\Test_RBTree_E) {
$__t0 = new \Test\RBTree\Test_RBTree_E();
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Test_RBTree_makeBlack'] = __NAMESPACE__ . '\\majTest_majRmajBmajTree_makemajBlack';

// Test_RBTree_describe
$GLOBALS['Test_RBTree_describe'] = \Effect\Console\majEffect_majConsole_log("Red-Black Tree (100k Worst-Case Insertions):");

// Test_RBTree_depth
function majTest_majRmajBmajTree_depth($v_0): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majTest_majRmajBmajTree_depth';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Test_RBTree_depth_v_0 = $v_0;
  tco_loop_Test_RBTree_depth:;
  $v_0 = $__tco_var_Test_RBTree_depth_v_0;
  $__t0 = null;
  if ($v_0 instanceof \Test\RBTree\Test_RBTree_E) {
$__t0 = 0;
goto end_branch_0;;
};
  if ($v_0 instanceof \Test\RBTree\Test_RBTree_T) {
$__local_var_1_1 = \Test\RBTree\majTest_majRmajBmajTree_depth(($v_0)->{'value1'});
$__local_var_2_2 = \Test\RBTree\majTest_majRmajBmajTree_depth(($v_0)->{'value3'});
$__t3 = null;
if (($__local_var_1_1 > $__local_var_2_2)) {
$__t3 = $__local_var_1_1;
goto end_branch_3;;
};
$__t3 = $__local_var_2_2;
end_branch_3:;
$__t0 = (1 + $__t3);
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Test_RBTree_depth'] = __NAMESPACE__ . '\\majTest_majRmajBmajTree_depth';

// Test_RBTree_balance
function majTest_majRmajBmajTree_balance($v_0, $v1_1 = null, $v2_2 = null, $v3_3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majTest_majRmajBmajTree_balance';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__t4 = null;
  if ($v_0 instanceof \Test\RBTree\Test_RBTree_B) {
$__t9 = null;
if ($v1_1 instanceof \Test\RBTree\Test_RBTree_T) {
$__t14 = null;
if (($v1_1)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__t19 = null;
if (($v1_1)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t24 = null;
if ((($v1_1)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_slot = (($v1_1)->{'value1'})->{'value1'};
$__local_var_5_slot = (($v1_1)->{'value1'})->{'value3'};
$__local_var_6_slot = ($v1_1)->{'value3'};
$__local_var_7_slot = $v3_3;
$__local_var_8_slot = (($v1_1)->{'value1'})->{'value2'};
$__local_var_9_slot = ($v1_1)->{'value2'};
$__local_var_10_slot = $v2_2;
$__t24 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_24;;
};
if (($v1_1)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t36 = null;
if ((($v1_1)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_slot = ($v1_1)->{'value1'};
$__local_var_5_slot = (($v1_1)->{'value3'})->{'value1'};
$__local_var_6_slot = (($v1_1)->{'value3'})->{'value3'};
$__local_var_7_slot = $v3_3;
$__local_var_8_slot = ($v1_1)->{'value2'};
$__local_var_9_slot = (($v1_1)->{'value3'})->{'value2'};
$__local_var_10_slot = $v2_2;
$__t36 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_36;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t48 = null;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t53 = null;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_slot = ($v3_3)->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_slot = ($v3_3)->{'value2'};
$__t53 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_53;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t53 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_53;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t53 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_53:;
$__t48 = $__t53;
goto end_branch_48;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t48 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_48;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t48 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_48:;
$__t36 = $__t48;
goto end_branch_36;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t36 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_36:;
$__t24 = $__t36;
goto end_branch_24;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t79 = null;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t84 = null;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_slot = ($v3_3)->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_slot = ($v3_3)->{'value2'};
$__t84 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_84;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t84 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_84;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t84 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_84:;
$__t79 = $__t84;
goto end_branch_79;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t79 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_79;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t79 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_79:;
$__t24 = $__t79;
goto end_branch_24;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t24 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_24:;
$__t19 = $__t24;
goto end_branch_19;;
};
if (($v1_1)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t110 = null;
if ((($v1_1)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_slot = ($v1_1)->{'value1'};
$__local_var_5_slot = (($v1_1)->{'value3'})->{'value1'};
$__local_var_6_slot = (($v1_1)->{'value3'})->{'value3'};
$__local_var_7_slot = $v3_3;
$__local_var_8_slot = ($v1_1)->{'value2'};
$__local_var_9_slot = (($v1_1)->{'value3'})->{'value2'};
$__local_var_10_slot = $v2_2;
$__t110 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_110;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t122 = null;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t127 = null;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_slot = ($v3_3)->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_slot = ($v3_3)->{'value2'};
$__t127 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_127;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t127 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_127;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t127 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_127:;
$__t122 = $__t127;
goto end_branch_122;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t122 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_122;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t122 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_122:;
$__t110 = $__t122;
goto end_branch_110;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t110 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_110:;
$__t19 = $__t110;
goto end_branch_19;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t153 = null;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t158 = null;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_slot = ($v3_3)->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_slot = ($v3_3)->{'value2'};
$__t158 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_158;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t158 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_158;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t158 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_158:;
$__t153 = $__t158;
goto end_branch_153;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t153 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_153;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t153 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_153:;
$__t19 = $__t153;
goto end_branch_19;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t19 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_19:;
$__t14 = $__t19;
goto end_branch_14;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t184 = null;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t189 = null;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_slot = ($v3_3)->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_slot = ($v3_3)->{'value2'};
$__t189 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_189;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t189 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_189;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t189 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_189:;
$__t184 = $__t189;
goto end_branch_184;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t184 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_184;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t184 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_184:;
$__t14 = $__t184;
goto end_branch_14;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t14 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_14:;
$__t9 = $__t14;
goto end_branch_9;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t215 = null;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t220 = null;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_slot = ($v3_3)->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_slot = ($v3_3)->{'value2'};
$__t220 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_220;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t220 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_220;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t220 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_220:;
$__t215 = $__t220;
goto end_branch_215;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = ($v3_3)->{'value1'};
$__local_var_6_slot = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_slot = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_slot = $v2_2;
$__local_var_9_slot = ($v3_3)->{'value2'};
$__local_var_10_slot = (($v3_3)->{'value3'})->{'value2'};
$__t215 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_slot, $__local_var_8_slot, $__local_var_5_slot), $__local_var_9_slot, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_slot, $__local_var_10_slot, $__local_var_7_slot));
goto end_branch_215;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t215 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_215:;
$__t9 = $__t215;
goto end_branch_9;;
};
$__local_var_4_slot = $v1_1;
$__local_var_5_slot = $v3_3;
$__local_var_6_slot = $v_0;
$__local_var_7_slot = $v2_2;
$__t9 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
end_branch_9:;
$__t4 = $__t9;
goto end_branch_4;;
};
  $__local_var_4_slot = $v1_1;
  $__local_var_5_slot = $v3_3;
  $__local_var_6_slot = $v_0;
  $__local_var_7_slot = $v2_2;
  $__t4 = new \Test\RBTree\Test_RBTree_T($__local_var_6_slot, $__local_var_4_slot, $__local_var_7_slot, $__local_var_5_slot);
  end_branch_4:;
  $__res = $__t4;
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Test_RBTree_balance'] = __NAMESPACE__ . '\\majTest_majRmajBmajTree_balance';

// Test_RBTree_ins
function majTest_majRmajBmajTree_ins(int $v_0, $v1_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majTest_majRmajBmajTree_ins';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__tco_var_Test_RBTree_ins_v_0 = $v_0;
  $__tco_var_Test_RBTree_ins_v1_1 = $v1_1;
  $v_0 = $__tco_var_Test_RBTree_ins_v_0;
  $v1_1 = $__tco_var_Test_RBTree_ins_v1_1;
  if ($v1_1 instanceof \Test\RBTree\Test_RBTree_E) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_E(), $v_0, new \Test\RBTree\Test_RBTree_E());
goto __end;;
};
  if ($v1_1 instanceof \Test\RBTree\Test_RBTree_T) {
$__t2 = null;
if (($v_0 < ($v1_1)->{'value2'})) {
$__phpurs_inline_0_0_v_0 = ($v1_1)->{'value0'};
$__phpurs_inline_0_0_v1_1 = \Test\RBTree\majTest_majRmajBmajTree_ins($v_0, ($v1_1)->{'value1'});
$__phpurs_inline_0_0_v2_2 = ($v1_1)->{'value2'};
$__phpurs_inline_0_0_v3_3 = ($v1_1)->{'value3'};
$__phpurs_inline_0_0___t4 = null;
if ($__phpurs_inline_0_0_v_0 instanceof \Test\RBTree\Test_RBTree_B) {
$__phpurs_inline_0_0___t9 = null;
if ($__phpurs_inline_0_0_v1_1 instanceof \Test\RBTree\Test_RBTree_T) {
$__phpurs_inline_0_0___t14 = null;
if (($__phpurs_inline_0_0_v1_1)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__phpurs_inline_0_0___t19 = null;
if (($__phpurs_inline_0_0_v1_1)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__phpurs_inline_0_0___t24 = null;
if ((($__phpurs_inline_0_0_v1_1)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v1_1)->{'value1'})->{'value1'}, (($__phpurs_inline_0_0_v1_1)->{'value1'})->{'value2'}, (($__phpurs_inline_0_0_v1_1)->{'value1'})->{'value3'}), ($__phpurs_inline_0_0_v1_1)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), ($__phpurs_inline_0_0_v1_1)->{'value3'}, $__phpurs_inline_0_0_v2_2, $__phpurs_inline_0_0_v3_3));
goto __end;;
};
if (($__phpurs_inline_0_0_v1_1)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T) {
$__phpurs_inline_0_0___t36 = null;
if ((($__phpurs_inline_0_0_v1_1)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), ($__phpurs_inline_0_0_v1_1)->{'value1'}, ($__phpurs_inline_0_0_v1_1)->{'value2'}, (($__phpurs_inline_0_0_v1_1)->{'value3'})->{'value1'}), (($__phpurs_inline_0_0_v1_1)->{'value3'})->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v1_1)->{'value3'})->{'value3'}, $__phpurs_inline_0_0_v2_2, $__phpurs_inline_0_0_v3_3));
goto __end;;
};
if (($__phpurs_inline_0_0_v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($__phpurs_inline_0_0_v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__phpurs_inline_0_0___t48 = null;
if (($__phpurs_inline_0_0_v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__phpurs_inline_0_0___t53 = null;
if ((($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value1'}), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value3'}, ($__phpurs_inline_0_0_v3_3)->{'value2'}, ($__phpurs_inline_0_0_v3_3)->{'value3'}));
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t53 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t53;
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t48 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t48;
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t36 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t36;
goto __end;;
};
if (($__phpurs_inline_0_0_v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($__phpurs_inline_0_0_v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__phpurs_inline_0_0___t79 = null;
if (($__phpurs_inline_0_0_v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__phpurs_inline_0_0___t84 = null;
if ((($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value1'}), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value3'}, ($__phpurs_inline_0_0_v3_3)->{'value2'}, ($__phpurs_inline_0_0_v3_3)->{'value3'}));
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t84 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t84;
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t79 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t79;
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t24 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t24;
goto __end;;
};
if (($__phpurs_inline_0_0_v1_1)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T) {
$__phpurs_inline_0_0___t110 = null;
if ((($__phpurs_inline_0_0_v1_1)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), ($__phpurs_inline_0_0_v1_1)->{'value1'}, ($__phpurs_inline_0_0_v1_1)->{'value2'}, (($__phpurs_inline_0_0_v1_1)->{'value3'})->{'value1'}), (($__phpurs_inline_0_0_v1_1)->{'value3'})->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v1_1)->{'value3'})->{'value3'}, $__phpurs_inline_0_0_v2_2, $__phpurs_inline_0_0_v3_3));
goto __end;;
};
if (($__phpurs_inline_0_0_v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($__phpurs_inline_0_0_v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__phpurs_inline_0_0___t122 = null;
if (($__phpurs_inline_0_0_v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__phpurs_inline_0_0___t127 = null;
if ((($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value1'}), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value3'}, ($__phpurs_inline_0_0_v3_3)->{'value2'}, ($__phpurs_inline_0_0_v3_3)->{'value3'}));
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t127 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t127;
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t122 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t122;
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t110 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t110;
goto __end;;
};
if (($__phpurs_inline_0_0_v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($__phpurs_inline_0_0_v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__phpurs_inline_0_0___t153 = null;
if (($__phpurs_inline_0_0_v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__phpurs_inline_0_0___t158 = null;
if ((($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value1'}), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value3'}, ($__phpurs_inline_0_0_v3_3)->{'value2'}, ($__phpurs_inline_0_0_v3_3)->{'value3'}));
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t158 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t158;
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t153 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t153;
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t19 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t19;
goto __end;;
};
if (($__phpurs_inline_0_0_v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($__phpurs_inline_0_0_v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__phpurs_inline_0_0___t184 = null;
if (($__phpurs_inline_0_0_v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__phpurs_inline_0_0___t189 = null;
if ((($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value1'}), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value3'}, ($__phpurs_inline_0_0_v3_3)->{'value2'}, ($__phpurs_inline_0_0_v3_3)->{'value3'}));
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t189 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t189;
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t184 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t184;
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t14 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t14;
goto __end;;
};
if (($__phpurs_inline_0_0_v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($__phpurs_inline_0_0_v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__phpurs_inline_0_0___t215 = null;
if (($__phpurs_inline_0_0_v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__phpurs_inline_0_0___t220 = null;
if ((($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value1'}), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value1'})->{'value3'}, ($__phpurs_inline_0_0_v3_3)->{'value2'}, ($__phpurs_inline_0_0_v3_3)->{'value3'}));
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t220 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t220;
goto __end;;
};
if ((($__phpurs_inline_0_0_v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__res = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__phpurs_inline_0_0_v1_1, $__phpurs_inline_0_0_v2_2, ($__phpurs_inline_0_0_v3_3)->{'value1'}), ($__phpurs_inline_0_0_v3_3)->{'value2'}, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value1'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value2'}, (($__phpurs_inline_0_0_v3_3)->{'value3'})->{'value3'}));
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t215 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t215;
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t9 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t9;
goto __end;;
};
$__phpurs_inline_0_0___local_var_4_slot = $__phpurs_inline_0_0_v1_1;
$__phpurs_inline_0_0___local_var_5_slot = $__phpurs_inline_0_0_v3_3;
$__phpurs_inline_0_0___local_var_6_slot = $__phpurs_inline_0_0_v_0;
$__phpurs_inline_0_0___local_var_7_slot = $__phpurs_inline_0_0_v2_2;
$__phpurs_inline_0_0___t4 = new \Test\RBTree\Test_RBTree_T($__phpurs_inline_0_0___local_var_6_slot, $__phpurs_inline_0_0___local_var_4_slot, $__phpurs_inline_0_0___local_var_7_slot, $__phpurs_inline_0_0___local_var_5_slot);
$__res = $__phpurs_inline_0_0___t4;
goto __end;;
};
$__t1 = null;
if (($v_0 > ($v1_1)->{'value2'})) {
$__t1 = \Test\RBTree\majTest_majRmajBmajTree_balance(($v1_1)->{'value0'}, ($v1_1)->{'value1'}, ($v1_1)->{'value2'}, \Test\RBTree\majTest_majRmajBmajTree_ins($v_0, ($v1_1)->{'value3'}));
goto end_branch_1;;
};
$__t1 = new \Test\RBTree\Test_RBTree_T(($v1_1)->{'value0'}, ($v1_1)->{'value1'}, ($v1_1)->{'value2'}, ($v1_1)->{'value3'});
end_branch_1:;
$__t2 = $__t1;
$__res = $__t2;
goto __end;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Test_RBTree_ins'] = __NAMESPACE__ . '\\majTest_majRmajBmajTree_ins';

// Test_RBTree_insert
function majTest_majRmajBmajTree_insert(int $x_0, $s_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majTest_majRmajBmajTree_insert';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__local_var_2_0 = \Test\RBTree\majTest_majRmajBmajTree_ins($x_0, $s_1);
  $__t1 = null;
  if ($__local_var_2_0 instanceof \Test\RBTree\Test_RBTree_T) {
$__t1 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), ($__local_var_2_0)->{'value1'}, ($__local_var_2_0)->{'value2'}, ($__local_var_2_0)->{'value3'});
goto end_branch_1;;
};
  if ($__local_var_2_0 instanceof \Test\RBTree\Test_RBTree_E) {
$__t1 = new \Test\RBTree\Test_RBTree_E();
goto end_branch_1;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t1 = null;
  end_branch_1:;
  $__res = $__t1;
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Test_RBTree_insert'] = __NAMESPACE__ . '\\majTest_majRmajBmajTree_insert';

// Test_RBTree_buildTree
function majTest_majRmajBmajTree_buildmajTree(int $v_0, $v1_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majTest_majRmajBmajTree_buildmajTree';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__tco_var_Test_RBTree_buildTree_v_0 = $v_0;
  $__tco_var_Test_RBTree_buildTree_v1_1 = $v1_1;
  tco_loop_Test_RBTree_buildTree:;
  $v_0 = $__tco_var_Test_RBTree_buildTree_v_0;
  $v1_1 = $__tco_var_Test_RBTree_buildTree_v1_1;
  $__t2 = null;
  switch ($v_0) {
case 0:
$__t2 = $v1_1;
goto end_branch_2;;
break;
default:
;
break;
};
  $__tco_0 = ($v_0 - 1);
  $__tco_1 = \Test\RBTree\majTest_majRmajBmajTree_insert($v_0, $v1_1);
  $__tco_var_Test_RBTree_buildTree_v_0 = $__tco_0;
  $__tco_var_Test_RBTree_buildTree_v1_1 = $__tco_1;
  goto tco_loop_Test_RBTree_buildTree;;
  $__t2 = null;
  end_branch_2:;
  $__res = $__t2;
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Test_RBTree_buildTree'] = __NAMESPACE__ . '\\majTest_majRmajBmajTree_buildmajTree';

// Test_RBTree_act
$GLOBALS['Test_RBTree_act'] = (function() use (&$__fn) {
$__local_var_0_0 = \Bench\majBench_opaque(100000);
return function() use ($__local_var_0_0, &$__fn) {
$dummy_1_1 = phpurs_execute_effect($__local_var_0_0);
return phpurs_execute_effect(\Data\Show\majData_majShow_showmajIntmajImpl(\Test\RBTree\majTest_majRmajBmajTree_depth(\Test\RBTree\majTest_majRmajBmajTree_buildmajTree($dummy_1_1, new \Test\RBTree\Test_RBTree_E()))));
};
})();

