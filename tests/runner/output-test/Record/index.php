<?php

namespace Record;

// ALL IMPORTS: Data.Eq, Data.Function.Uncurried, Data.HeytingAlgebra, Data.Symbol, Prelude, Prim, Prim.Row, Prim.RowList, Record, Record.Unsafe, Record.Unsafe.Union, Type.Proxy, Unsafe.Coerce
// TO REQUIRE: Data.Eq, Data.Function.Uncurried, Data.HeytingAlgebra, Data.Symbol, Prelude, Record, Record.Unsafe, Record.Unsafe.Union, Type.Proxy, Unsafe.Coerce
require_once __DIR__ . '/../Data.Eq/index.php';
require_once __DIR__ . '/../Data.Function.Uncurried/index.php';
require_once __DIR__ . '/../Data.HeytingAlgebra/index.php';
require_once __DIR__ . '/../Data.Symbol/index.php';
require_once __DIR__ . '/../Prelude/index.php';
require_once __DIR__ . '/../Record/index.php';
require_once __DIR__ . '/../Record.Unsafe/index.php';
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

$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };




// Record_union
function majRecord_union($_dollar__unused_0, $l_1 = null, $r_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_union';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = ($GLOBALS['Record_Unsafe_Union_unsafeUnionFn'])($l_1, $r_2);
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Record_union'] = __NAMESPACE__ . '\\majRecord_union';

// Record_set
function majRecord_set($dictIsSymbol_0, $_dollar__unused_1 = null, $_dollar__unused_2 = null, $l_3 = null, $b_4 = null, $r_5 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_set';
  if ($__num < 6) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 6);
  }
  $__res = \Record\Unsafe\majRecord_majUnsafe_unsafemajSet((($dictIsSymbol_0)->{'reflectSymbol'})($l_3), $b_4, $r_5);
  goto __end;;
  __end:
  return 6 < $__num ? $__res(...\array_slice(\func_get_args(), 6)) : $__res;
}
$GLOBALS['Record_set'] = __NAMESPACE__ . '\\majRecord_set';

// Record_nub
function majRecord_nub($_dollar__unused_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_nub';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $GLOBALS['Unsafe_Coerce_unsafeCoerce'];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Record_nub'] = __NAMESPACE__ . '\\majRecord_nub';

// Record_merge
function majRecord_merge($_dollar__unused_0, $_dollar__unused_1 = null, $l_2 = null, $r_3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_merge';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = ($GLOBALS['Record_Unsafe_Union_unsafeUnionFn'])($l_2, $r_3);
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Record_merge'] = __NAMESPACE__ . '\\majRecord_merge';

// Record_insert
function majRecord_insert($dictIsSymbol_0, $_dollar__unused_1 = null, $_dollar__unused_2 = null, $l_3 = null, $a_4 = null, $r_5 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_insert';
  if ($__num < 6) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 6);
  }
  $__res = \Record\Unsafe\majRecord_majUnsafe_unsafemajSet((($dictIsSymbol_0)->{'reflectSymbol'})($l_3), $a_4, $r_5);
  goto __end;;
  __end:
  return 6 < $__num ? $__res(...\array_slice(\func_get_args(), 6)) : $__res;
}
$GLOBALS['Record_insert'] = __NAMESPACE__ . '\\majRecord_insert';

// Record_get
function majRecord_get($dictIsSymbol_0, $_dollar__unused_1 = null, $l_2 = null, $r_3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_get';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = \Record\Unsafe\majRecord_majUnsafe_unsafemajGet((($dictIsSymbol_0)->{'reflectSymbol'})($l_2), $r_3);
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Record_get'] = __NAMESPACE__ . '\\majRecord_get';

// Record_modify
function majRecord_modify($dictIsSymbol_0, $_dollar__unused_1 = null, $_dollar__unused_2 = null, $l_3 = null, $f_4 = null, $r_5 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_modify';
  if ($__num < 6) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 6);
  }
  $__res = \Record\Unsafe\majRecord_majUnsafe_unsafemajSet((($dictIsSymbol_0)->{'reflectSymbol'})($l_3), ($f_4)(\Record\Unsafe\majRecord_majUnsafe_unsafemajGet((($dictIsSymbol_0)->{'reflectSymbol'})($l_3), $r_5)), $r_5);
  goto __end;;
  __end:
  return 6 < $__num ? $__res(...\array_slice(\func_get_args(), 6)) : $__res;
}
$GLOBALS['Record_modify'] = __NAMESPACE__ . '\\majRecord_modify';

// Record_equalFieldsNil
$GLOBALS['Record_equalFieldsNil'] = (object)["equalFields" => (function() {
  $__fn = function($v_0, $v1_1 = null, $v2_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = true;
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()];

// Record_equalFields
function majRecord_equalmajFields($dict_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_equalmajFields';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($dict_0)->{'equalFields'};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Record_equalFields'] = __NAMESPACE__ . '\\majRecord_equalmajFields';

// Record_equalFieldsCons
function majRecord_equalmajFieldsmajCons($dictIsSymbol_0, $dictEq_1 = null, $_dollar__unused_2 = null, $dictEqualFields_3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_equalmajFieldsmajCons';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = (object)["equalFields" => (function() use ($dictEq_1, $dictEqualFields_3, $dictIsSymbol_0) {
  $__fn = function($v_4, $a_5 = null, $b_6 = null) use ($dictEq_1, $dictEqualFields_3, $dictIsSymbol_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = ((($GLOBALS['Data_HeytingAlgebra_heytingAlgebraBoolean'])->{'conj'})(((($dictEq_1)->{'eq'})(\Record\Unsafe\majRecord_majUnsafe_unsafemajGet((($dictIsSymbol_0)->{'reflectSymbol'})(new \Type\Proxy\Type_Proxy_Proxy()), $a_5)))(\Record\Unsafe\majRecord_majUnsafe_unsafemajGet((($dictIsSymbol_0)->{'reflectSymbol'})(new \Type\Proxy\Type_Proxy_Proxy()), $b_6))))((((($dictEqualFields_3)->{'equalFields'})(new \Type\Proxy\Type_Proxy_Proxy()))($a_5))($b_6));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()];
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Record_equalFieldsCons'] = __NAMESPACE__ . '\\majRecord_equalmajFieldsmajCons';

// Record_equal
function majRecord_equal($_dollar__unused_0, $dictEqualFields_1 = null, $a_2 = null, $b_3 = null): bool|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_equal';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = (((($dictEqualFields_1)->{'equalFields'})(new \Type\Proxy\Type_Proxy_Proxy()))($a_2))($b_3);
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Record_equal'] = __NAMESPACE__ . '\\majRecord_equal';

// Record_disjointUnion
function majRecord_disjointmajUnion($_dollar__unused_0, $_dollar__unused_1 = null, $l_2 = null, $r_3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_disjointmajUnion';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = ($GLOBALS['Record_Unsafe_Union_unsafeUnionFn'])($l_2, $r_3);
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Record_disjointUnion'] = __NAMESPACE__ . '\\majRecord_disjointmajUnion';

// Record_delete
function majRecord_delete($dictIsSymbol_0, $_dollar__unused_1 = null, $_dollar__unused_2 = null, $l_3 = null, $r_4 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_delete';
  if ($__num < 5) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 5);
  }
  $__res = \Record\Unsafe\majRecord_majUnsafe_unsafemajDelete((($dictIsSymbol_0)->{'reflectSymbol'})($l_3), $r_4);
  goto __end;;
  __end:
  return 5 < $__num ? $__res(...\array_slice(\func_get_args(), 5)) : $__res;
}
$GLOBALS['Record_delete'] = __NAMESPACE__ . '\\majRecord_delete';

// Record_rename
function majRecord_rename($dictIsSymbol_0, $dictIsSymbol1_1 = null, $_dollar__unused_2 = null, $_dollar__unused_3 = null, $_dollar__unused_4 = null, $_dollar__unused_5 = null, $prev_6 = null, $next_7 = null, $record_8 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_rename';
  if ($__num < 9) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 9);
  }
  $__res = \Record\Unsafe\majRecord_majUnsafe_unsafemajSet((($dictIsSymbol1_1)->{'reflectSymbol'})($next_7), \Record\Unsafe\majRecord_majUnsafe_unsafemajGet((($dictIsSymbol_0)->{'reflectSymbol'})($prev_6), $record_8), \Record\Unsafe\majRecord_majUnsafe_unsafemajDelete((($dictIsSymbol_0)->{'reflectSymbol'})($prev_6), $record_8));
  goto __end;;
  __end:
  return 9 < $__num ? $__res(...\array_slice(\func_get_args(), 9)) : $__res;
}
$GLOBALS['Record_rename'] = __NAMESPACE__ . '\\majRecord_rename';

