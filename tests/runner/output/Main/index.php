<?php

namespace Main;

// ALL IMPORTS: Control.Bind, Data.Function, Data.Function.Uncurried, Data.HeytingAlgebra, Data.Ord, Data.Ring, Data.Semiring, Data.Show, Effect, Effect.Console, Main, Prelude, Prim
// TO REQUIRE: Control.Bind, Data.Function, Data.Function.Uncurried, Data.HeytingAlgebra, Data.Ord, Data.Ring, Data.Semiring, Data.Show, Effect, Effect.Console, Main, Prelude
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Function.Uncurried/index.php';
require_once __DIR__ . '/../Data.HeytingAlgebra/index.php';
require_once __DIR__ . '/../Data.Ord/index.php';
require_once __DIR__ . '/../Data.Ring/index.php';
require_once __DIR__ . '/../Data.Semiring/index.php';
require_once __DIR__ . '/../Data.Show/index.php';
require_once __DIR__ . '/../Effect/index.php';
require_once __DIR__ . '/../Effect.Console/index.php';
require_once __DIR__ . '/../Main/index.php';
require_once __DIR__ . '/../Prelude/index.php';

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
\PhpursThunks::$thunks['Main_show1'] = function() { $v = (($GLOBALS['Data_Show_showArrayImpl'] ?? \PhpursThunks::eval('Data_Show_showArrayImpl')))(($GLOBALS['Data_Show_showNumberImpl'] ?? \PhpursThunks::eval('Data_Show_showNumberImpl'))); return $v; };
\PhpursThunks::$thunks['Main_g'] = function() { $v = (($GLOBALS['Data_Function_Uncurried_mkFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn2')))((function() {
  $__fn = function($a_0, $b_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Main_g","Main_f"];
  if (((($GLOBALS['Data_HeytingAlgebra_boolDisj'] ?? \PhpursThunks::eval('Data_HeytingAlgebra_boolDisj')))(( ! (is_object((((($GLOBALS['Data_Ord_ordNumber'] ?? \PhpursThunks::eval('Data_Ord_ordNumber')))->{'compare'})($a_0))(0.0)) && (((((($GLOBALS['Data_Ord_ordNumber'] ?? \PhpursThunks::eval('Data_Ord_ordNumber')))->{'compare'})($a_0))(0.0))->{'tag'} === "GT")))))(( ! (is_object((((($GLOBALS['Data_Ord_ordNumber'] ?? \PhpursThunks::eval('Data_Ord_ordNumber')))->{'compare'})($b_1))(0.0)) && (((((($GLOBALS['Data_Ord_ordNumber'] ?? \PhpursThunks::eval('Data_Ord_ordNumber')))->{'compare'})($b_1))(0.0))->{'tag'} === "GT"))))) {
$__t0 = $b_1;
} else {
$__t0 = (((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))(($GLOBALS['Main_f'] ?? \PhpursThunks::eval('Main_f'))))(((($GLOBALS['Data_Ring_numSub'] ?? \PhpursThunks::eval('Data_Ring_numSub')))($a_0))(0.0)))(((($GLOBALS['Data_Ring_numSub'] ?? \PhpursThunks::eval('Data_Ring_numSub')))($b_1))(0.0));
};
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()); return $v; };
\PhpursThunks::$thunks['Main_f'] = function() { $v = (($GLOBALS['Data_Function_Uncurried_mkFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn2')))((function() {
  $__fn = function($a_0, $b_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Main_g","Main_f"];
  $__res = ((($GLOBALS['Data_Semiring_numAdd'] ?? \PhpursThunks::eval('Data_Semiring_numAdd')))((((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))(($GLOBALS['Main_g'] ?? \PhpursThunks::eval('Main_g'))))($a_0))($b_1)))((((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))(($GLOBALS['Main_g'] ?? \PhpursThunks::eval('Main_g'))))($b_1))($a_0));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()); return $v; };
\PhpursThunks::$thunks['Main_main'] = function() { $v = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((($GLOBALS['Data_Function_Uncurried_runFn0'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn0')))((($GLOBALS['Data_Function_Uncurried_mkFn0'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn0')))(function($v_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Data_Show_showNumberImpl'] ?? \PhpursThunks::eval('Data_Show_showNumberImpl')))(0.0));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))))(function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Data_Show_showNumberImpl'] ?? \PhpursThunks::eval('Data_Show_showNumberImpl')))(0.0))))(function($dollar__unused_1) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))((($GLOBALS['Data_Function_Uncurried_mkFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn2')))((function() {
  $__fn = function($a_2, $b_3 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Main_show1'] ?? \PhpursThunks::eval('Main_show1')))([$a_2, $b_3]));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})())))(0.0))(0.0)))(function($dollar__unused_2) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((($GLOBALS['Data_Function_Uncurried_runFn3'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn3')))((($GLOBALS['Data_Function_Uncurried_mkFn3'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn3')))((function() {
  $__fn = function($a_3, $b_4 = null, $c_5 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Main_show1'] ?? \PhpursThunks::eval('Main_show1')))([$a_3, $b_4, $c_5]));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})())))(0.0))(0.0))(0.0)))(function($dollar__unused_3) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((((((($GLOBALS['Data_Function_Uncurried_runFn4'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn4')))((($GLOBALS['Data_Function_Uncurried_mkFn4'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn4')))((function() {
  $__fn = function($a_4, $b_5 = null, $c_6 = null, $d_7 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Main_show1'] ?? \PhpursThunks::eval('Main_show1')))([$a_4, $b_5, $c_6, $d_7]));
  goto __end;;
  __end:
  return $__num > 4 ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
  };
  return $__fn;
})())))(0.0))(0.0))(0.0))(0.0)))(function($dollar__unused_4) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((((($GLOBALS['Data_Function_Uncurried_runFn5'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn5')))((($GLOBALS['Data_Function_Uncurried_mkFn5'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn5')))((function() {
  $__fn = function($a_5, $b_6 = null, $c_7 = null, $d_8 = null, $e_9 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 5) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 5);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Main_show1'] ?? \PhpursThunks::eval('Main_show1')))([$a_5, $b_6, $c_7, $d_8, $e_9]));
  goto __end;;
  __end:
  return $__num > 5 ? $__res(...\array_slice(\func_get_args(), 5)) : $__res;
  };
  return $__fn;
})())))(0.0))(0.0))(0.0))(0.0))(0.0)))(function($dollar__unused_5) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((((((((($GLOBALS['Data_Function_Uncurried_runFn6'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn6')))((($GLOBALS['Data_Function_Uncurried_mkFn6'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn6')))((function() {
  $__fn = function($a_6, $b_7 = null, $c_8 = null, $d_9 = null, $e_10 = null, $f1_11 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 6) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 6);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Main_show1'] ?? \PhpursThunks::eval('Main_show1')))([$a_6, $b_7, $c_8, $d_9, $e_10, $f1_11]));
  goto __end;;
  __end:
  return $__num > 6 ? $__res(...\array_slice(\func_get_args(), 6)) : $__res;
  };
  return $__fn;
})())))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0)))(function($dollar__unused_6) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((((((($GLOBALS['Data_Function_Uncurried_runFn7'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn7')))((($GLOBALS['Data_Function_Uncurried_mkFn7'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn7')))((function() {
  $__fn = function($a_7, $b_8 = null, $c_9 = null, $d_10 = null, $e_11 = null, $f1_12 = null, $g1_13 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 7) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 7);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Main_show1'] ?? \PhpursThunks::eval('Main_show1')))([$a_7, $b_8, $c_9, $d_10, $e_11, $f1_12, $g1_13]));
  goto __end;;
  __end:
  return $__num > 7 ? $__res(...\array_slice(\func_get_args(), 7)) : $__res;
  };
  return $__fn;
})())))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0)))(function($dollar__unused_7) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((((((((((($GLOBALS['Data_Function_Uncurried_runFn8'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn8')))((($GLOBALS['Data_Function_Uncurried_mkFn8'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn8')))((function() {
  $__fn = function($a_8, $b_9 = null, $c_10 = null, $d_11 = null, $e_12 = null, $f1_13 = null, $g1_14 = null, $h_15 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 8) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 8);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Main_show1'] ?? \PhpursThunks::eval('Main_show1')))([$a_8, $b_9, $c_10, $d_11, $e_12, $f1_13, $g1_14, $h_15]));
  goto __end;;
  __end:
  return $__num > 8 ? $__res(...\array_slice(\func_get_args(), 8)) : $__res;
  };
  return $__fn;
})())))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0)))(function($dollar__unused_8) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((((((((($GLOBALS['Data_Function_Uncurried_runFn9'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn9')))((($GLOBALS['Data_Function_Uncurried_mkFn9'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn9')))((function() {
  $__fn = function($a_9, $b_10 = null, $c_11 = null, $d_12 = null, $e_13 = null, $f1_14 = null, $g1_15 = null, $h_16 = null, $i_17 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 9) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 9);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Main_show1'] ?? \PhpursThunks::eval('Main_show1')))([$a_9, $b_10, $c_11, $d_12, $e_13, $f1_14, $g1_15, $h_16, $i_17]));
  goto __end;;
  __end:
  return $__num > 9 ? $__res(...\array_slice(\func_get_args(), 9)) : $__res;
  };
  return $__fn;
})())))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0)))(function($dollar__unused_9) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((((((((((((($GLOBALS['Data_Function_Uncurried_runFn10'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn10')))((($GLOBALS['Data_Function_Uncurried_mkFn10'] ?? \PhpursThunks::eval('Data_Function_Uncurried_mkFn10')))((function() {
  $__fn = function($a_10, $b_11 = null, $c_12 = null, $d_13 = null, $e_14 = null, $f1_15 = null, $g1_16 = null, $h_17 = null, $i_18 = null, $j_19 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 10) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 10);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Main_show1'] ?? \PhpursThunks::eval('Main_show1')))([$a_10, $b_11, $c_12, $d_13, $e_14, $f1_15, $g1_16, $h_17, $i_18, $j_19]));
  goto __end;;
  __end:
  return $__num > 10 ? $__res(...\array_slice(\func_get_args(), 10)) : $__res;
  };
  return $__fn;
})())))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0))(0.0)))(function($dollar__unused_10) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))((($GLOBALS['Data_Show_showNumberImpl'] ?? \PhpursThunks::eval('Data_Show_showNumberImpl')))((((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))(($GLOBALS['Main_g'] ?? \PhpursThunks::eval('Main_g'))))(0.0))(0.0)))))(function($dollar__unused_11) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))("Done");
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}); return $v; };
$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };






