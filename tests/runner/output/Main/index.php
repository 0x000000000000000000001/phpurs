<?php

namespace Main;

// ALL IMPORTS: Control.Bind, Data.Eq, Data.Semigroup, Effect, Effect.Console, Main, Prelude, Prim, Test.Assert
// TO REQUIRE: Control.Bind, Data.Eq, Data.Semigroup, Effect, Effect.Console, Main, Prelude, Test.Assert
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Data.Eq/index.php';
require_once __DIR__ . '/../Data.Semigroup/index.php';
require_once __DIR__ . '/../Effect/index.php';
require_once __DIR__ . '/../Effect.Console/index.php';
require_once __DIR__ . '/../Main/index.php';
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
\PhpursThunks::$thunks['Main_surrogatePair'] = function() { $v = ((($GLOBALS['Data_Eq_eqStringImpl'] ?? \PhpursThunks::eval('Data_Eq_eqStringImpl')))("𝌆"))("𝌆"); return $v; };
\PhpursThunks::$thunks['Main_singleCharacter'] = function() { $v = ((($GLOBALS['Data_Eq_eqStringImpl'] ?? \PhpursThunks::eval('Data_Eq_eqStringImpl')))("	
\"\\"))("	
\"\\"); return $v; };
\PhpursThunks::$thunks['Main_replacement'] = function() { $v = "�"; return $v; };
\PhpursThunks::$thunks['Main_lowSurrogate'] = function() { $v = "\xED\xBC\x86"; return $v; };
\PhpursThunks::$thunks['Main_highSurrogate'] = function() { $v = "\xED\xA0\xB4"; return $v; };
\PhpursThunks::$thunks['Main_loneSurrogates'] = function() { $v = ((($GLOBALS['Data_Eq_eqStringImpl'] ?? \PhpursThunks::eval('Data_Eq_eqStringImpl')))(((($GLOBALS['Data_Semigroup_concatString'] ?? \PhpursThunks::eval('Data_Semigroup_concatString')))("\xED\xA0\xB4"))("\xED\xBC\x86")))("𝌆"); return $v; };
\PhpursThunks::$thunks['Main_notReplacing'] = function() { $v = ((($GLOBALS['Data_Eq_eqBooleanImpl'] ?? \PhpursThunks::eval('Data_Eq_eqBooleanImpl')))(((($GLOBALS['Data_Eq_eqStringImpl'] ?? \PhpursThunks::eval('Data_Eq_eqStringImpl')))("�"))("\xED\xA0\xB4")))(false); return $v; };
\PhpursThunks::$thunks['Main_outOfOrderSurrogates'] = function() { $v = ((($GLOBALS['Data_Eq_eqStringImpl'] ?? \PhpursThunks::eval('Data_Eq_eqStringImpl')))(((($GLOBALS['Data_Semigroup_concatString'] ?? \PhpursThunks::eval('Data_Semigroup_concatString')))("\xED\xBC\x86"))("\xED\xA0\xB4")))("\xED\xBC\x86\xED\xA0\xB4"); return $v; };
\PhpursThunks::$thunks['Main_hex'] = function() { $v = ((($GLOBALS['Data_Eq_eqStringImpl'] ?? \PhpursThunks::eval('Data_Eq_eqStringImpl')))("𝌆☃φà"))("𝌆☃φà"); return $v; };
\PhpursThunks::$thunks['Main_main'] = function() { $v = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("single-character escape sequences"))(($GLOBALS['Main_singleCharacter'] ?? \PhpursThunks::eval('Main_singleCharacter')))))(function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("hex escape sequences"))(($GLOBALS['Main_hex'] ?? \PhpursThunks::eval('Main_hex')))))(function($dollar__unused_1) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("astral code points are represented as a UTF-16 surrogate pair"))(($GLOBALS['Main_surrogatePair'] ?? \PhpursThunks::eval('Main_surrogatePair')))))(function($dollar__unused_2) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("lone surrogates may be combined into a surrogate pair"))(($GLOBALS['Main_loneSurrogates'] ?? \PhpursThunks::eval('Main_loneSurrogates')))))(function($dollar__unused_3) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("lone surrogates may be combined out of order to remain lone surrogates"))(($GLOBALS['Main_outOfOrderSurrogates'] ?? \PhpursThunks::eval('Main_outOfOrderSurrogates')))))(function($dollar__unused_4) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("lone surrogates are not replaced with the Unicode replacement character U+FFFD"))(($GLOBALS['Main_notReplacing'] ?? \PhpursThunks::eval('Main_notReplacing')))))(function($dollar__unused_5) {
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
}); return $v; };
$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };












