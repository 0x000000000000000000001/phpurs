<?php

namespace Data\Number;

// ALL IMPORTS: Data.Function.Uncurried, Data.Maybe, Data.Number, Prim
// TO REQUIRE: Data.Function.Uncurried, Data.Maybe, Data.Number
require_once __DIR__ . '/../Data.Function.Uncurried/index.php';
require_once __DIR__ . '/../Data.Maybe/index.php';
require_once __DIR__ . '/../Data.Number/index.php';

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
\PhpursThunks::$thunks['Data_Number_tau'] = function() { $v = 6.283185307179586; return $v; };
\PhpursThunks::$thunks['Data_Number_sqrt2'] = function() { $v = 1.4142135623730951; return $v; };
\PhpursThunks::$thunks['Data_Number_sqrt1_2'] = function() { $v = 0.7071067811865476; return $v; };
\PhpursThunks::$thunks['Data_Number_pi'] = function() { $v = 3.141592653589793; return $v; };
\PhpursThunks::$thunks['Data_Number_log2e'] = function() { $v = 1.4426950408889634; return $v; };
\PhpursThunks::$thunks['Data_Number_log10e'] = function() { $v = 0.4342944819032518; return $v; };
\PhpursThunks::$thunks['Data_Number_ln2'] = function() { $v = 0.6931471805599453; return $v; };
\PhpursThunks::$thunks['Data_Number_ln10'] = function() { $v = 2.302585092994046; return $v; };
\PhpursThunks::$thunks['Data_Number_fromString'] = function() { $v = function($str_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Data_Number_fromString"), recVars=[];
  $__res = (((((($GLOBALS['Data_Function_Uncurried_runFn4'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn4')))(($GLOBALS['Data_Number_fromStringImpl'] ?? \PhpursThunks::eval('Data_Number_fromStringImpl'))))($str_0))(($GLOBALS['Data_Number_isFinite'] ?? \PhpursThunks::eval('Data_Number_isFinite'))))(($GLOBALS['Data_Maybe_Just'] ?? \PhpursThunks::eval('Data_Maybe_Just'))))(new Phpurs_Data0("Nothing"));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Data_Number_e'] = function() { $v = 2.718281828459045; return $v; };
$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };
$ffi_Data_Number = \call_user_func(function() {
  $exports = [];
$nan = NAN;
$isNaN = function($n) use (&$isNaN) {
    return is_nan($n);
};

$infinity = INF;
$isFinite = function($n) use (&$isFinite) {
    return is_finite($n);
};

$fromStringImpl = function($str, $isFinite = null, $just = null, $nothing = null) use (&$fromStringImpl) {
    if (\func_num_args() < 4) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$fromStringImpl) {

            return $fromStringImpl(...\array_merge($__args, $more));
        };
    }
    // JS parseFloat behavior: parse leading float
    if (preg_match('/^[+-]?(?:(?:\d+\.?\d*)|(?:\.\d+))(?:[eE][+-]?\d+)?/', trim($str), $matches)) {
        $num = floatval($matches[0]);
        if ($isFinite($num)) {
            return $just($num);
        }
    }
    return $nothing;
};

$abs = function($n) use (&$abs) { return abs($n); };
$acos = function($n) use (&$acos) { return acos($n); };
$asin = function($n) use (&$asin) { return asin($n); };
$atan = function($n) use (&$atan) { return atan($n); };

$atan2 = function($y, $x = null) use (&$atan2) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$atan2) {

            return $atan2(...\array_merge($__args, $more));
        };
    }
    return atan2($y, $x);
};

$ceil = function($n) use (&$ceil) { return ceil($n); };
$cos = function($n) use (&$cos) { return cos($n); };
$exp = function($n) use (&$exp) { return exp($n); };
$floor = function($n) use (&$floor) { return floor($n); };
$log = function($n) use (&$log) { return log($n); };

$max = function($n1, $n2 = null) use (&$max) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$max) {

            return $max(...\array_merge($__args, $more));
        };
    }
    if (is_nan($n1) || is_nan($n2)) return NAN;
    return \max($n1, $n2);
};

$min = function($n1, $n2 = null) use (&$min) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$min) {

            return $min(...\array_merge($__args, $more));
        };
    }
    if (is_nan($n1) || is_nan($n2)) return NAN;
    return \min($n1, $n2);
};

$pow = function($n, $p = null) use (&$pow) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$pow) {

            return $pow(...\array_merge($__args, $more));
        };
    }
    return pow($n, $p);
};

$remainder = function($n, $m = null) use (&$remainder) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$remainder) {

            return $remainder(...\array_merge($__args, $more));
        };
    }
    return fmod($n, $m);
};

$round = function($n) use (&$round) { return round($n); };

$sign = function($x) use (&$sign) {
    if (is_nan($x)) return NAN;
    if ($x === 0.0 || $x === -0.0) return $x;
    return $x < 0 ? -1.0 : 1.0;
};

$sin = function($n) use (&$sin) { return sin($n); };
$sqrt = function($n) use (&$sqrt) { return sqrt($n); };
$tan = function($n) use (&$tan) { return tan($n); };

$trunc = function($x) use (&$trunc) {
    return $x < 0 ? ceil($x) : floor($x);
};

$exports['nan'] = $nan;
$exports['isNaN'] = $isNaN;
$exports['infinity'] = $infinity;
$exports['isFinite'] = $isFinite;
$exports['fromStringImpl'] = $fromStringImpl;
$exports['abs'] = $abs;
$exports['acos'] = $acos;
$exports['asin'] = $asin;
$exports['atan'] = $atan;
$exports['atan2'] = $atan2;
$exports['ceil'] = $ceil;
$exports['cos'] = $cos;
$exports['exp'] = $exp;
$exports['floor'] = $floor;
$exports['log'] = $log;
$exports['max'] = $max;
$exports['min'] = $min;
$exports['pow'] = $pow;
$exports['remainder'] = $remainder;
$exports['round'] = $round;
$exports['sign'] = $sign;
$exports['sin'] = $sin;
$exports['sqrt'] = $sqrt;
$exports['tan'] = $tan;
$exports['trunc'] = $trunc;
return $exports;
  return $exports;
});
\PhpursThunks::$thunks['Data_Number_abs'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['abs']; };
\PhpursThunks::$thunks['Data_Number_acos'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['acos']; };
\PhpursThunks::$thunks['Data_Number_asin'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['asin']; };
\PhpursThunks::$thunks['Data_Number_atan'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['atan']; };
\PhpursThunks::$thunks['Data_Number_atan2'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['atan2']; };
\PhpursThunks::$thunks['Data_Number_ceil'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['ceil']; };
\PhpursThunks::$thunks['Data_Number_cos'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['cos']; };
\PhpursThunks::$thunks['Data_Number_exp'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['exp']; };
\PhpursThunks::$thunks['Data_Number_floor'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['floor']; };
\PhpursThunks::$thunks['Data_Number_fromStringImpl'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['fromStringImpl']; };
\PhpursThunks::$thunks['Data_Number_infinity'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['infinity']; };
\PhpursThunks::$thunks['Data_Number_isFinite'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['isFinite']; };
\PhpursThunks::$thunks['Data_Number_isNaN'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['isNaN']; };
\PhpursThunks::$thunks['Data_Number_log'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['log']; };
\PhpursThunks::$thunks['Data_Number_max'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['max']; };
\PhpursThunks::$thunks['Data_Number_min'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['min']; };
\PhpursThunks::$thunks['Data_Number_nan'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['nan']; };
\PhpursThunks::$thunks['Data_Number_pow'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['pow']; };
\PhpursThunks::$thunks['Data_Number_remainder'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['remainder']; };
\PhpursThunks::$thunks['Data_Number_round'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['round']; };
\PhpursThunks::$thunks['Data_Number_sign'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['sign']; };
\PhpursThunks::$thunks['Data_Number_sin'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['sin']; };
\PhpursThunks::$thunks['Data_Number_sqrt'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['sqrt']; };
\PhpursThunks::$thunks['Data_Number_tan'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['tan']; };
\PhpursThunks::$thunks['Data_Number_trunc'] = function() use (&$ffi_Data_Number) { return $ffi_Data_Number['trunc']; };












