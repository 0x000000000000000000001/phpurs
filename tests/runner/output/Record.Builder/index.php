<?php

namespace Record\Builder;

// ALL IMPORTS: Control.Category, Control.Semigroupoid, Data.Function, Data.Function.Uncurried, Data.Symbol, Prelude, Prim, Prim.Row, Record.Builder, Record.Unsafe.Union, Type.Proxy, Unsafe.Coerce
// TO REQUIRE: Control.Category, Control.Semigroupoid, Data.Function, Data.Function.Uncurried, Data.Symbol, Prelude, Record.Builder, Record.Unsafe.Union, Type.Proxy, Unsafe.Coerce
require_once __DIR__ . '/../Control.Category/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Function.Uncurried/index.php';
require_once __DIR__ . '/../Data.Symbol/index.php';
require_once __DIR__ . '/../Prelude/index.php';
require_once __DIR__ . '/../Record.Builder/index.php';
require_once __DIR__ . '/../Record.Unsafe.Union/index.php';
require_once __DIR__ . '/../Type.Proxy/index.php';
require_once __DIR__ . '/../Unsafe.Coerce/index.php';

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
\PhpursThunks::$thunks['Record_Builder_union'] = function() { $v = (function() {
  $__fn = function($dollar__unused_0 = null, $r1_1 = null, $r2_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = (((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))(($GLOBALS['Record_Unsafe_Union_unsafeUnionFn'] ?? \PhpursThunks::eval('Record_Unsafe_Union_unsafeUnionFn'))))($r1_1))($r2_2);
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Record_Builder_semigroupoidBuilder'] = function() { $v = ($GLOBALS['Control_Semigroupoid_semigroupoidFn'] ?? \PhpursThunks::eval('Control_Semigroupoid_semigroupoidFn')); return $v; };
\PhpursThunks::$thunks['Record_Builder_rename'] = function() { $v = (function() {
  $__fn = function($dictIsSymbol_0 = null, $dictIsSymbol1_1 = null, $dollar__unused_2 = null, $dollar__unused_3 = null, $dollar__unused_4 = null, $dollar__unused_5 = null, $l1_6 = null, $l2_7 = null, $r1_8 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 9) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 9);
  }
  $__res = (((($GLOBALS['Record_Builder_unsafeRename'] ?? \PhpursThunks::eval('Record_Builder_unsafeRename')))((($dictIsSymbol_0)->{'reflectSymbol'})($l1_6)))((($dictIsSymbol1_1)->{'reflectSymbol'})($l2_7)))($r1_8);
  goto __end;;
  __end:
  return $__num > 9 ? $__res(...\array_slice(\func_get_args(), 9)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Record_Builder_nub'] = function() { $v = function($dollar__unused_0 = null) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Unsafe_Coerce_unsafeCoerce'] ?? \PhpursThunks::eval('Unsafe_Coerce_unsafeCoerce'));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Record_Builder_modify'] = function() { $v = (function() {
  $__fn = function($dollar__unused_0 = null, $dollar__unused_1 = null, $dictIsSymbol_2 = null, $l_3 = null, $f_4 = null, $r1_5 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 6) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 6);
  }
  $__res = (((($GLOBALS['Record_Builder_unsafeModify'] ?? \PhpursThunks::eval('Record_Builder_unsafeModify')))((($dictIsSymbol_2)->{'reflectSymbol'})($l_3)))($f_4))($r1_5);
  goto __end;;
  __end:
  return $__num > 6 ? $__res(...\array_slice(\func_get_args(), 6)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Record_Builder_merge'] = function() { $v = (function() {
  $__fn = function($dollar__unused_0 = null, $dollar__unused_1 = null, $r1_2 = null, $r2_3 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = (((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))(($GLOBALS['Record_Unsafe_Union_unsafeUnionFn'] ?? \PhpursThunks::eval('Record_Unsafe_Union_unsafeUnionFn'))))($r1_2))($r2_3);
  goto __end;;
  __end:
  return $__num > 4 ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Record_Builder_insert'] = function() { $v = (function() {
  $__fn = function($dollar__unused_0 = null, $dollar__unused_1 = null, $dictIsSymbol_2 = null, $l_3 = null, $a_4 = null, $r1_5 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 6) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 6);
  }
  $__res = (((($GLOBALS['Record_Builder_unsafeInsert'] ?? \PhpursThunks::eval('Record_Builder_unsafeInsert')))((($dictIsSymbol_2)->{'reflectSymbol'})($l_3)))($a_4))($r1_5);
  goto __end;;
  __end:
  return $__num > 6 ? $__res(...\array_slice(\func_get_args(), 6)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Record_Builder_disjointUnion'] = function() { $v = (function() {
  $__fn = function($dollar__unused_0 = null, $dollar__unused_1 = null, $r1_2 = null, $r2_3 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = (((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))(($GLOBALS['Record_Unsafe_Union_unsafeUnionFn'] ?? \PhpursThunks::eval('Record_Unsafe_Union_unsafeUnionFn'))))($r1_2))($r2_3);
  goto __end;;
  __end:
  return $__num > 4 ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Record_Builder_delete'] = function() { $v = (function() {
  $__fn = function($dictIsSymbol_0 = null, $dollar__unused_1 = null, $dollar__unused_2 = null, $l_3 = null, $r2_4 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 5) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 5);
  }
  $__res = ((($GLOBALS['Record_Builder_unsafeDelete'] ?? \PhpursThunks::eval('Record_Builder_unsafeDelete')))((($dictIsSymbol_0)->{'reflectSymbol'})($l_3)))($r2_4);
  goto __end;;
  __end:
  return $__num > 5 ? $__res(...\array_slice(\func_get_args(), 5)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Record_Builder_categoryBuilder'] = function() { $v = ($GLOBALS['Control_Category_categoryFn'] ?? \PhpursThunks::eval('Control_Category_categoryFn')); return $v; };
\PhpursThunks::$thunks['Record_Builder_build'] = function() { $v = (function() {
  $__fn = function($v_0 = null, $r1_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($v_0)((($GLOBALS['Record_Builder_copyRecord'] ?? \PhpursThunks::eval('Record_Builder_copyRecord')))($r1_1));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Record_Builder_buildFromScratch'] = function() { $v = function($a_0 = null) {
  $__num = \func_num_args();
  $__res = ($a_0)((($GLOBALS['Record_Builder_copyRecord'] ?? \PhpursThunks::eval('Record_Builder_copyRecord')))((object)[]));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Record_Builder_flip'] = function() { $v = (function() {
  $__fn = function($f_0 = null, $b_1 = null, $a_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = (($f_0)($a_2))((($GLOBALS['Record_Builder_copyRecord'] ?? \PhpursThunks::eval('Record_Builder_copyRecord')))($b_1));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(); return $v; };
$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };
$ffi_Record_Builder = \call_user_func(function() {
  $exports = [];
$_copyRecord = function($rec) {
    if (\is_object($rec)) {
        return clone $rec;
    } elseif (\is_array($rec)) {
        return $rec;
    }
    return $rec;
};

$_unsafeInsert = function($l) {
    return function($a) use ($l) {
        return function($rec) use ($l, $a) {
            if (\is_array($rec)) {
                $rec[$l] = $a;
            } else {
                $rec->{$l} = $a;
            }
            return $rec;
        };
    };
};

$_unsafeModify = function($l) {
    return function($f) use ($l) {
        return function($rec) use ($l, $f) {
            if (\is_array($rec)) {
                $rec[$l] = $f($rec[$l]);
            } else {
                $rec->{$l} = $f($rec->{$l});
            }
            return $rec;
        };
    };
};

$_unsafeDelete = function($l) {
    return function($rec) use ($l) {
        if (\is_array($rec)) {
            unset($rec[$l]);
        } else {
            unset($rec->{$l});
        }
        return $rec;
    };
};

$_unsafeRename = function($l1) {
    return function($l2) use ($l1) {
        return function($rec) use ($l1, $l2) {
            if (\is_array($rec)) {
                $rec[$l2] = $rec[$l1];
                unset($rec[$l1]);
            } else {
                $rec->{$l2} = $rec->{$l1};
                unset($rec->{$l1});
            }
            return $rec;
        };
    };
};

$exports['copyRecord'] = $_copyRecord;
$exports['unsafeInsert'] = $_unsafeInsert;
$exports['unsafeModify'] = $_unsafeModify;
$exports['unsafeDelete'] = $_unsafeDelete;
$exports['unsafeRename'] = $_unsafeRename;

return $exports;
  return $exports;
});
\PhpursThunks::$thunks['Record_Builder_copyRecord'] = function() use (&$ffi_Record_Builder) { return $ffi_Record_Builder['copyRecord']; };
\PhpursThunks::$thunks['Record_Builder_unsafeDelete'] = function() use (&$ffi_Record_Builder) { return $ffi_Record_Builder['unsafeDelete']; };
\PhpursThunks::$thunks['Record_Builder_unsafeInsert'] = function() use (&$ffi_Record_Builder) { return $ffi_Record_Builder['unsafeInsert']; };
\PhpursThunks::$thunks['Record_Builder_unsafeModify'] = function() use (&$ffi_Record_Builder) { return $ffi_Record_Builder['unsafeModify']; };
\PhpursThunks::$thunks['Record_Builder_unsafeRename'] = function() use (&$ffi_Record_Builder) { return $ffi_Record_Builder['unsafeRename']; };















