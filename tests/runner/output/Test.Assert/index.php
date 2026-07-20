<?php

namespace Test\Assert;

// ALL IMPORTS: Control.Applicative, Control.Bind, Data.Eq, Data.Function, Data.Semigroup, Data.Show, Effect, Effect.Console, Prelude, Prim, Test.Assert
// TO REQUIRE: Control.Applicative, Control.Bind, Data.Eq, Data.Function, Data.Semigroup, Data.Show, Effect, Effect.Console, Prelude, Test.Assert
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Data.Eq/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Semigroup/index.php';
require_once __DIR__ . '/../Data.Show/index.php';
require_once __DIR__ . '/../Effect/index.php';
require_once __DIR__ . '/../Effect.Console/index.php';
require_once __DIR__ . '/../Prelude/index.php';
require_once __DIR__ . '/../Test.Assert/index.php';

if (!class_exists(__NAMESPACE__ . '\\Phpurs_Data0')) {
  class Phpurs_Data0 { public $tag; public function __construct($t) { $this->tag = $t; } }
  class Phpurs_Data1 { public $tag; public $value0; public function __construct($t, $value0) { $this->tag = $t; $this->value0 = $value0; } }
  class Phpurs_Data2 { public $tag; public $value0, $value1; public function __construct($t, $value0, $value1) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; } }
  class Phpurs_Data3 { public $tag; public $value0, $value1, $value2; public function __construct($t, $value0, $value1, $value2) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; } }
  class Phpurs_Data4 { public $tag; public $value0, $value1, $value2, $value3; public function __construct($t, $value0, $value1, $value2, $value3) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; } }
  class Phpurs_Data5 { public $tag; public $value0, $value1, $value2, $value3, $value4; public function __construct($t, $value0, $value1, $value2, $value3, $value4) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; } }
  class Phpurs_Data6 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; } }
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
\PhpursThunks::$thunks['Test_Assert_assert__prime__'] = function() { $v = ($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')); return $v; };
\PhpursThunks::$thunks['Test_Assert_assertEqual__prime__'] = function() { $v = (function() {
  $__fn = function($dictEq_0, $dictShow_1 = null, $userMessage_2 = null, $v_3 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Test_Assert_assertEqual'"), recVars=[];
  $result_4_0 = ((($dictEq_0)->{'eq'})(($v_3)->{'actual'}))(($v_3)->{'expected'});
  if (((($GLOBALS['Data_Eq_eqStringImpl'] ?? \PhpursThunks::eval('Data_Eq_eqStringImpl')))($userMessage_2))("")) {
$__t1 = "";
} else {
$__t1 = ((($GLOBALS['Data_Semigroup_concatString'] ?? \PhpursThunks::eval('Data_Semigroup_concatString')))($userMessage_2))("
");
};
  $message_5_1 = ((($GLOBALS['Data_Semigroup_concatString'] ?? \PhpursThunks::eval('Data_Semigroup_concatString')))($__t1))(((($GLOBALS['Data_Semigroup_concatString'] ?? \PhpursThunks::eval('Data_Semigroup_concatString')))("Expected: "))(((($GLOBALS['Data_Semigroup_concatString'] ?? \PhpursThunks::eval('Data_Semigroup_concatString')))((($dictShow_1)->{'show'})(($v_3)->{'expected'})))(((($GLOBALS['Data_Semigroup_concatString'] ?? \PhpursThunks::eval('Data_Semigroup_concatString')))("
Actual:   "))((($dictShow_1)->{'show'})(($v_3)->{'actual'})))));
  $__local_var_6_3 = (($GLOBALS['Effect_Console_error'] ?? \PhpursThunks::eval('Effect_Console_error')))($message_5_1);
  if (( ! $result_4_0)) {
$__t4 = $__local_var_6_3;
} else {
if ($result_4_0) {
$__t4 = (($GLOBALS['Effect_pureE'] ?? \PhpursThunks::eval('Effect_pureE')))(($GLOBALS['Data_Unit_unit'] ?? \PhpursThunks::eval('Data_Unit_unit')));
} else {
throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
$__t4 = null;
};
};
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))($__t4))(function($dollar__unused_6) use ($message_5_1, $result_4_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))($message_5_1))($result_4_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 4 ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Test_Assert_assertEqual'] = function() { $v = (function() {
  $__fn = function($dictEq_0, $dictShow_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Test_Assert_assertEqual"), recVars=[];
  $__res = (((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))($dictEq_0))($dictShow_1))("");
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Test_Assert_assertFalse'] = function() { $v = function($actual_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Test_Assert_assertFalse"), recVars=[];
  $__res = ((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqBoolean'] ?? \PhpursThunks::eval('Data_Eq_eqBoolean'))))(($GLOBALS['Data_Show_showBoolean'] ?? \PhpursThunks::eval('Data_Show_showBoolean'))))(""))((object)["actual" => $actual_0, "expected" => false]);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Test_Assert_assertTrue'] = function() { $v = function($actual_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Test_Assert_assertTrue"), recVars=[];
  $__res = ((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqBoolean'] ?? \PhpursThunks::eval('Data_Eq_eqBoolean'))))(($GLOBALS['Data_Show_showBoolean'] ?? \PhpursThunks::eval('Data_Show_showBoolean'))))(""))((object)["actual" => $actual_0, "expected" => true]);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Test_Assert_assertFalse__prime__'] = function() { $v = (function() {
  $__fn = function($message_0, $actual_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Test_Assert_assertFalse'"), recVars=[];
  $__res = ((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqBoolean'] ?? \PhpursThunks::eval('Data_Eq_eqBoolean'))))(($GLOBALS['Data_Show_showBoolean'] ?? \PhpursThunks::eval('Data_Show_showBoolean'))))($message_0))((object)["actual" => $actual_1, "expected" => false]);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Test_Assert_assertTrue__prime__'] = function() { $v = (function() {
  $__fn = function($message_0, $actual_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Test_Assert_assertTrue'"), recVars=[];
  $__res = ((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqBoolean'] ?? \PhpursThunks::eval('Data_Eq_eqBoolean'))))(($GLOBALS['Data_Show_showBoolean'] ?? \PhpursThunks::eval('Data_Show_showBoolean'))))($message_0))((object)["actual" => $actual_1, "expected" => true]);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Test_Assert_assertThrows__prime__'] = function() { $v = (function() {
  $__fn = function($msg_0, $fn_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Test_Assert_assertThrows'"), recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((($GLOBALS['Test_Assert_checkThrows'] ?? \PhpursThunks::eval('Test_Assert_checkThrows')))($fn_1)))((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))($msg_0));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Test_Assert_assertThrows'] = function() { $v = (($GLOBALS['Test_Assert_assertThrows__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertThrows__prime__')))("Assertion failed: An error should have been thrown"); return $v; };
\PhpursThunks::$thunks['Test_Assert_assert'] = function() { $v = (($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("Assertion failed"); return $v; };
$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };
$ffi_Test_Assert = \call_user_func(function() {
  $exports = [];
$assertImpl = function($message) {
  return function($condition) use ($message) {
    return function() use ($message, $condition) {
      if (!$condition) {
        throw new \Exception($message);
      }
    };
  };
};

$checkThrows = function($fn) {
  return function() use ($fn) {
    try {
      $fn();
      return false;
    } catch (\Exception $e) {
      return true;
    }
  };
};

$exports['assertImpl'] = $assertImpl;
$exports['checkThrows'] = $checkThrows;
  return $exports;
});
\PhpursThunks::$thunks['Test_Assert_assertImpl'] = function() use (&$ffi_Test_Assert) { return $ffi_Test_Assert['assertImpl']; };
\PhpursThunks::$thunks['Test_Assert_checkThrows'] = function() use (&$ffi_Test_Assert) { return $ffi_Test_Assert['checkThrows']; };












