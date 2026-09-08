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
  $__t0 = null;;
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
  $__t0 = null;;
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
  $__t0 = null;;
  if ($v_0 instanceof \Test\RBTree\Test_RBTree_E) {
$__t0 = 0;
goto end_branch_0;;
};
  if ($v_0 instanceof \Test\RBTree\Test_RBTree_T) {
$__local_var_1_1 = \Test\RBTree\majTest_majRmajBmajTree_depth(($v_0)->{'value1'});
$__local_var_2_2 = \Test\RBTree\majTest_majRmajBmajTree_depth(($v_0)->{'value3'});
$__t3 = null;;
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
  $__t4 = null;;
  if ($v_0 instanceof \Test\RBTree\Test_RBTree_B) {
$__t9 = null;;
if ($v1_1 instanceof \Test\RBTree\Test_RBTree_T) {
$__t14 = null;;
if (($v1_1)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__t19 = null;;
if (($v1_1)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t24 = null;;
if ((($v1_1)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_25 = (($v1_1)->{'value1'})->{'value1'};
$__local_var_5_26 = (($v1_1)->{'value1'})->{'value3'};
$__local_var_6_27 = ($v1_1)->{'value3'};
$__local_var_7_28 = $v3_3;
$__local_var_8_29 = (($v1_1)->{'value1'})->{'value2'};
$__local_var_9_30 = ($v1_1)->{'value2'};
$__local_var_10_31 = $v2_2;
$__t24 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_25, $__local_var_8_29, $__local_var_5_26), $__local_var_9_30, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_27, $__local_var_10_31, $__local_var_7_28));
goto end_branch_24;;
};
if (($v1_1)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t36 = null;;
if ((($v1_1)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_37 = ($v1_1)->{'value1'};
$__local_var_5_38 = (($v1_1)->{'value3'})->{'value1'};
$__local_var_6_39 = (($v1_1)->{'value3'})->{'value3'};
$__local_var_7_40 = $v3_3;
$__local_var_8_41 = ($v1_1)->{'value2'};
$__local_var_9_42 = (($v1_1)->{'value3'})->{'value2'};
$__local_var_10_43 = $v2_2;
$__t36 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_37, $__local_var_8_41, $__local_var_5_38), $__local_var_9_42, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_39, $__local_var_10_43, $__local_var_7_40));
goto end_branch_36;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t48 = null;;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t53 = null;;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_54 = $v1_1;
$__local_var_5_55 = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_56 = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_57 = ($v3_3)->{'value3'};
$__local_var_8_58 = $v2_2;
$__local_var_9_59 = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_60 = ($v3_3)->{'value2'};
$__t53 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_54, $__local_var_8_58, $__local_var_5_55), $__local_var_9_59, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_56, $__local_var_10_60, $__local_var_7_57));
goto end_branch_53;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_61 = $v1_1;
$__local_var_5_62 = ($v3_3)->{'value1'};
$__local_var_6_63 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_64 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_65 = $v2_2;
$__local_var_9_66 = ($v3_3)->{'value2'};
$__local_var_10_67 = (($v3_3)->{'value3'})->{'value2'};
$__t53 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_61, $__local_var_8_65, $__local_var_5_62), $__local_var_9_66, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_63, $__local_var_10_67, $__local_var_7_64));
goto end_branch_53;;
};
$__local_var_4_49 = $v1_1;
$__local_var_5_50 = $v3_3;
$__local_var_6_51 = $v_0;
$__local_var_7_52 = $v2_2;
$__t53 = new \Test\RBTree\Test_RBTree_T($__local_var_6_51, $__local_var_4_49, $__local_var_7_52, $__local_var_5_50);
end_branch_53:;
$__t48 = $__t53;
goto end_branch_48;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_68 = $v1_1;
$__local_var_5_69 = ($v3_3)->{'value1'};
$__local_var_6_70 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_71 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_72 = $v2_2;
$__local_var_9_73 = ($v3_3)->{'value2'};
$__local_var_10_74 = (($v3_3)->{'value3'})->{'value2'};
$__t48 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_68, $__local_var_8_72, $__local_var_5_69), $__local_var_9_73, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_70, $__local_var_10_74, $__local_var_7_71));
goto end_branch_48;;
};
$__local_var_4_44 = $v1_1;
$__local_var_5_45 = $v3_3;
$__local_var_6_46 = $v_0;
$__local_var_7_47 = $v2_2;
$__t48 = new \Test\RBTree\Test_RBTree_T($__local_var_6_46, $__local_var_4_44, $__local_var_7_47, $__local_var_5_45);
end_branch_48:;
$__t36 = $__t48;
goto end_branch_36;;
};
$__local_var_4_32 = $v1_1;
$__local_var_5_33 = $v3_3;
$__local_var_6_34 = $v_0;
$__local_var_7_35 = $v2_2;
$__t36 = new \Test\RBTree\Test_RBTree_T($__local_var_6_34, $__local_var_4_32, $__local_var_7_35, $__local_var_5_33);
end_branch_36:;
$__t24 = $__t36;
goto end_branch_24;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t79 = null;;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t84 = null;;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_85 = $v1_1;
$__local_var_5_86 = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_87 = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_88 = ($v3_3)->{'value3'};
$__local_var_8_89 = $v2_2;
$__local_var_9_90 = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_91 = ($v3_3)->{'value2'};
$__t84 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_85, $__local_var_8_89, $__local_var_5_86), $__local_var_9_90, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_87, $__local_var_10_91, $__local_var_7_88));
goto end_branch_84;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_92 = $v1_1;
$__local_var_5_93 = ($v3_3)->{'value1'};
$__local_var_6_94 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_95 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_96 = $v2_2;
$__local_var_9_97 = ($v3_3)->{'value2'};
$__local_var_10_98 = (($v3_3)->{'value3'})->{'value2'};
$__t84 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_92, $__local_var_8_96, $__local_var_5_93), $__local_var_9_97, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_94, $__local_var_10_98, $__local_var_7_95));
goto end_branch_84;;
};
$__local_var_4_80 = $v1_1;
$__local_var_5_81 = $v3_3;
$__local_var_6_82 = $v_0;
$__local_var_7_83 = $v2_2;
$__t84 = new \Test\RBTree\Test_RBTree_T($__local_var_6_82, $__local_var_4_80, $__local_var_7_83, $__local_var_5_81);
end_branch_84:;
$__t79 = $__t84;
goto end_branch_79;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_99 = $v1_1;
$__local_var_5_100 = ($v3_3)->{'value1'};
$__local_var_6_101 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_102 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_103 = $v2_2;
$__local_var_9_104 = ($v3_3)->{'value2'};
$__local_var_10_105 = (($v3_3)->{'value3'})->{'value2'};
$__t79 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_99, $__local_var_8_103, $__local_var_5_100), $__local_var_9_104, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_101, $__local_var_10_105, $__local_var_7_102));
goto end_branch_79;;
};
$__local_var_4_75 = $v1_1;
$__local_var_5_76 = $v3_3;
$__local_var_6_77 = $v_0;
$__local_var_7_78 = $v2_2;
$__t79 = new \Test\RBTree\Test_RBTree_T($__local_var_6_77, $__local_var_4_75, $__local_var_7_78, $__local_var_5_76);
end_branch_79:;
$__t24 = $__t79;
goto end_branch_24;;
};
$__local_var_4_20 = $v1_1;
$__local_var_5_21 = $v3_3;
$__local_var_6_22 = $v_0;
$__local_var_7_23 = $v2_2;
$__t24 = new \Test\RBTree\Test_RBTree_T($__local_var_6_22, $__local_var_4_20, $__local_var_7_23, $__local_var_5_21);
end_branch_24:;
$__t19 = $__t24;
goto end_branch_19;;
};
if (($v1_1)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t110 = null;;
if ((($v1_1)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_111 = ($v1_1)->{'value1'};
$__local_var_5_112 = (($v1_1)->{'value3'})->{'value1'};
$__local_var_6_113 = (($v1_1)->{'value3'})->{'value3'};
$__local_var_7_114 = $v3_3;
$__local_var_8_115 = ($v1_1)->{'value2'};
$__local_var_9_116 = (($v1_1)->{'value3'})->{'value2'};
$__local_var_10_117 = $v2_2;
$__t110 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_111, $__local_var_8_115, $__local_var_5_112), $__local_var_9_116, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_113, $__local_var_10_117, $__local_var_7_114));
goto end_branch_110;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t122 = null;;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t127 = null;;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_128 = $v1_1;
$__local_var_5_129 = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_130 = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_131 = ($v3_3)->{'value3'};
$__local_var_8_132 = $v2_2;
$__local_var_9_133 = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_134 = ($v3_3)->{'value2'};
$__t127 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_128, $__local_var_8_132, $__local_var_5_129), $__local_var_9_133, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_130, $__local_var_10_134, $__local_var_7_131));
goto end_branch_127;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_135 = $v1_1;
$__local_var_5_136 = ($v3_3)->{'value1'};
$__local_var_6_137 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_138 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_139 = $v2_2;
$__local_var_9_140 = ($v3_3)->{'value2'};
$__local_var_10_141 = (($v3_3)->{'value3'})->{'value2'};
$__t127 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_135, $__local_var_8_139, $__local_var_5_136), $__local_var_9_140, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_137, $__local_var_10_141, $__local_var_7_138));
goto end_branch_127;;
};
$__local_var_4_123 = $v1_1;
$__local_var_5_124 = $v3_3;
$__local_var_6_125 = $v_0;
$__local_var_7_126 = $v2_2;
$__t127 = new \Test\RBTree\Test_RBTree_T($__local_var_6_125, $__local_var_4_123, $__local_var_7_126, $__local_var_5_124);
end_branch_127:;
$__t122 = $__t127;
goto end_branch_122;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_142 = $v1_1;
$__local_var_5_143 = ($v3_3)->{'value1'};
$__local_var_6_144 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_145 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_146 = $v2_2;
$__local_var_9_147 = ($v3_3)->{'value2'};
$__local_var_10_148 = (($v3_3)->{'value3'})->{'value2'};
$__t122 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_142, $__local_var_8_146, $__local_var_5_143), $__local_var_9_147, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_144, $__local_var_10_148, $__local_var_7_145));
goto end_branch_122;;
};
$__local_var_4_118 = $v1_1;
$__local_var_5_119 = $v3_3;
$__local_var_6_120 = $v_0;
$__local_var_7_121 = $v2_2;
$__t122 = new \Test\RBTree\Test_RBTree_T($__local_var_6_120, $__local_var_4_118, $__local_var_7_121, $__local_var_5_119);
end_branch_122:;
$__t110 = $__t122;
goto end_branch_110;;
};
$__local_var_4_106 = $v1_1;
$__local_var_5_107 = $v3_3;
$__local_var_6_108 = $v_0;
$__local_var_7_109 = $v2_2;
$__t110 = new \Test\RBTree\Test_RBTree_T($__local_var_6_108, $__local_var_4_106, $__local_var_7_109, $__local_var_5_107);
end_branch_110:;
$__t19 = $__t110;
goto end_branch_19;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t153 = null;;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t158 = null;;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_159 = $v1_1;
$__local_var_5_160 = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_161 = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_162 = ($v3_3)->{'value3'};
$__local_var_8_163 = $v2_2;
$__local_var_9_164 = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_165 = ($v3_3)->{'value2'};
$__t158 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_159, $__local_var_8_163, $__local_var_5_160), $__local_var_9_164, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_161, $__local_var_10_165, $__local_var_7_162));
goto end_branch_158;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_166 = $v1_1;
$__local_var_5_167 = ($v3_3)->{'value1'};
$__local_var_6_168 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_169 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_170 = $v2_2;
$__local_var_9_171 = ($v3_3)->{'value2'};
$__local_var_10_172 = (($v3_3)->{'value3'})->{'value2'};
$__t158 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_166, $__local_var_8_170, $__local_var_5_167), $__local_var_9_171, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_168, $__local_var_10_172, $__local_var_7_169));
goto end_branch_158;;
};
$__local_var_4_154 = $v1_1;
$__local_var_5_155 = $v3_3;
$__local_var_6_156 = $v_0;
$__local_var_7_157 = $v2_2;
$__t158 = new \Test\RBTree\Test_RBTree_T($__local_var_6_156, $__local_var_4_154, $__local_var_7_157, $__local_var_5_155);
end_branch_158:;
$__t153 = $__t158;
goto end_branch_153;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_173 = $v1_1;
$__local_var_5_174 = ($v3_3)->{'value1'};
$__local_var_6_175 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_176 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_177 = $v2_2;
$__local_var_9_178 = ($v3_3)->{'value2'};
$__local_var_10_179 = (($v3_3)->{'value3'})->{'value2'};
$__t153 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_173, $__local_var_8_177, $__local_var_5_174), $__local_var_9_178, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_175, $__local_var_10_179, $__local_var_7_176));
goto end_branch_153;;
};
$__local_var_4_149 = $v1_1;
$__local_var_5_150 = $v3_3;
$__local_var_6_151 = $v_0;
$__local_var_7_152 = $v2_2;
$__t153 = new \Test\RBTree\Test_RBTree_T($__local_var_6_151, $__local_var_4_149, $__local_var_7_152, $__local_var_5_150);
end_branch_153:;
$__t19 = $__t153;
goto end_branch_19;;
};
$__local_var_4_15 = $v1_1;
$__local_var_5_16 = $v3_3;
$__local_var_6_17 = $v_0;
$__local_var_7_18 = $v2_2;
$__t19 = new \Test\RBTree\Test_RBTree_T($__local_var_6_17, $__local_var_4_15, $__local_var_7_18, $__local_var_5_16);
end_branch_19:;
$__t14 = $__t19;
goto end_branch_14;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t184 = null;;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t189 = null;;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_190 = $v1_1;
$__local_var_5_191 = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_192 = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_193 = ($v3_3)->{'value3'};
$__local_var_8_194 = $v2_2;
$__local_var_9_195 = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_196 = ($v3_3)->{'value2'};
$__t189 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_190, $__local_var_8_194, $__local_var_5_191), $__local_var_9_195, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_192, $__local_var_10_196, $__local_var_7_193));
goto end_branch_189;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_197 = $v1_1;
$__local_var_5_198 = ($v3_3)->{'value1'};
$__local_var_6_199 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_200 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_201 = $v2_2;
$__local_var_9_202 = ($v3_3)->{'value2'};
$__local_var_10_203 = (($v3_3)->{'value3'})->{'value2'};
$__t189 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_197, $__local_var_8_201, $__local_var_5_198), $__local_var_9_202, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_199, $__local_var_10_203, $__local_var_7_200));
goto end_branch_189;;
};
$__local_var_4_185 = $v1_1;
$__local_var_5_186 = $v3_3;
$__local_var_6_187 = $v_0;
$__local_var_7_188 = $v2_2;
$__t189 = new \Test\RBTree\Test_RBTree_T($__local_var_6_187, $__local_var_4_185, $__local_var_7_188, $__local_var_5_186);
end_branch_189:;
$__t184 = $__t189;
goto end_branch_184;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_204 = $v1_1;
$__local_var_5_205 = ($v3_3)->{'value1'};
$__local_var_6_206 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_207 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_208 = $v2_2;
$__local_var_9_209 = ($v3_3)->{'value2'};
$__local_var_10_210 = (($v3_3)->{'value3'})->{'value2'};
$__t184 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_204, $__local_var_8_208, $__local_var_5_205), $__local_var_9_209, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_206, $__local_var_10_210, $__local_var_7_207));
goto end_branch_184;;
};
$__local_var_4_180 = $v1_1;
$__local_var_5_181 = $v3_3;
$__local_var_6_182 = $v_0;
$__local_var_7_183 = $v2_2;
$__t184 = new \Test\RBTree\Test_RBTree_T($__local_var_6_182, $__local_var_4_180, $__local_var_7_183, $__local_var_5_181);
end_branch_184:;
$__t14 = $__t184;
goto end_branch_14;;
};
$__local_var_4_10 = $v1_1;
$__local_var_5_11 = $v3_3;
$__local_var_6_12 = $v_0;
$__local_var_7_13 = $v2_2;
$__t14 = new \Test\RBTree\Test_RBTree_T($__local_var_6_12, $__local_var_4_10, $__local_var_7_13, $__local_var_5_11);
end_branch_14:;
$__t9 = $__t14;
goto end_branch_9;;
};
if (($v3_3 instanceof \Test\RBTree\Test_RBTree_T && ($v3_3)->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__t215 = null;;
if (($v3_3)->{'value1'} instanceof \Test\RBTree\Test_RBTree_T) {
$__t220 = null;;
if ((($v3_3)->{'value1'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R) {
$__local_var_4_221 = $v1_1;
$__local_var_5_222 = (($v3_3)->{'value1'})->{'value1'};
$__local_var_6_223 = (($v3_3)->{'value1'})->{'value3'};
$__local_var_7_224 = ($v3_3)->{'value3'};
$__local_var_8_225 = $v2_2;
$__local_var_9_226 = (($v3_3)->{'value1'})->{'value2'};
$__local_var_10_227 = ($v3_3)->{'value2'};
$__t220 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_221, $__local_var_8_225, $__local_var_5_222), $__local_var_9_226, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_223, $__local_var_10_227, $__local_var_7_224));
goto end_branch_220;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_228 = $v1_1;
$__local_var_5_229 = ($v3_3)->{'value1'};
$__local_var_6_230 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_231 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_232 = $v2_2;
$__local_var_9_233 = ($v3_3)->{'value2'};
$__local_var_10_234 = (($v3_3)->{'value3'})->{'value2'};
$__t220 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_228, $__local_var_8_232, $__local_var_5_229), $__local_var_9_233, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_230, $__local_var_10_234, $__local_var_7_231));
goto end_branch_220;;
};
$__local_var_4_216 = $v1_1;
$__local_var_5_217 = $v3_3;
$__local_var_6_218 = $v_0;
$__local_var_7_219 = $v2_2;
$__t220 = new \Test\RBTree\Test_RBTree_T($__local_var_6_218, $__local_var_4_216, $__local_var_7_219, $__local_var_5_217);
end_branch_220:;
$__t215 = $__t220;
goto end_branch_215;;
};
if ((($v3_3)->{'value3'} instanceof \Test\RBTree\Test_RBTree_T && (($v3_3)->{'value3'})->{'value0'} instanceof \Test\RBTree\Test_RBTree_R)) {
$__local_var_4_235 = $v1_1;
$__local_var_5_236 = ($v3_3)->{'value1'};
$__local_var_6_237 = (($v3_3)->{'value3'})->{'value1'};
$__local_var_7_238 = (($v3_3)->{'value3'})->{'value3'};
$__local_var_8_239 = $v2_2;
$__local_var_9_240 = ($v3_3)->{'value2'};
$__local_var_10_241 = (($v3_3)->{'value3'})->{'value2'};
$__t215 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_4_235, $__local_var_8_239, $__local_var_5_236), $__local_var_9_240, new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_B(), $__local_var_6_237, $__local_var_10_241, $__local_var_7_238));
goto end_branch_215;;
};
$__local_var_4_211 = $v1_1;
$__local_var_5_212 = $v3_3;
$__local_var_6_213 = $v_0;
$__local_var_7_214 = $v2_2;
$__t215 = new \Test\RBTree\Test_RBTree_T($__local_var_6_213, $__local_var_4_211, $__local_var_7_214, $__local_var_5_212);
end_branch_215:;
$__t9 = $__t215;
goto end_branch_9;;
};
$__local_var_4_5 = $v1_1;
$__local_var_5_6 = $v3_3;
$__local_var_6_7 = $v_0;
$__local_var_7_8 = $v2_2;
$__t9 = new \Test\RBTree\Test_RBTree_T($__local_var_6_7, $__local_var_4_5, $__local_var_7_8, $__local_var_5_6);
end_branch_9:;
$__t4 = $__t9;
goto end_branch_4;;
};
  $__local_var_4_0 = $v1_1;
  $__local_var_5_1 = $v3_3;
  $__local_var_6_2 = $v_0;
  $__local_var_7_3 = $v2_2;
  $__t4 = new \Test\RBTree\Test_RBTree_T($__local_var_6_2, $__local_var_4_0, $__local_var_7_3, $__local_var_5_1);
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
  tco_loop_Test_RBTree_ins:;
  $v_0 = $__tco_var_Test_RBTree_ins_v_0;
  $v1_1 = $__tco_var_Test_RBTree_ins_v1_1;
  $__t0 = null;;
  if ($v1_1 instanceof \Test\RBTree\Test_RBTree_E) {
$__t0 = new \Test\RBTree\Test_RBTree_T(new \Test\RBTree\Test_RBTree_R(), new \Test\RBTree\Test_RBTree_E(), $v_0, new \Test\RBTree\Test_RBTree_E());
goto end_branch_0;;
};
  if ($v1_1 instanceof \Test\RBTree\Test_RBTree_T) {
$__t2 = null;;
if (($v_0 < ($v1_1)->{'value2'})) {
$__t2 = \Test\RBTree\majTest_majRmajBmajTree_balance(($v1_1)->{'value0'}, \Test\RBTree\majTest_majRmajBmajTree_ins($v_0, ($v1_1)->{'value1'}), ($v1_1)->{'value2'}, ($v1_1)->{'value3'});
goto end_branch_2;;
};
$__t1 = null;;
if (($v_0 > ($v1_1)->{'value2'})) {
$__t1 = \Test\RBTree\majTest_majRmajBmajTree_balance(($v1_1)->{'value0'}, ($v1_1)->{'value1'}, ($v1_1)->{'value2'}, \Test\RBTree\majTest_majRmajBmajTree_ins($v_0, ($v1_1)->{'value3'}));
goto end_branch_1;;
};
$__t1 = new \Test\RBTree\Test_RBTree_T(($v1_1)->{'value0'}, ($v1_1)->{'value1'}, ($v1_1)->{'value2'}, ($v1_1)->{'value3'});
end_branch_1:;
$__t2 = $__t1;
end_branch_2:;
$__t0 = $__t2;
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
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
  $__t1 = null;;
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
  $__t2 = null;;
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

