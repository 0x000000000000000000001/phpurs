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
function majRecord_majBuilder_copymajRecord($v0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majRecord_majBuilder_copymajRecord';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  global $ffi_Record_Builder;
  $f = ($ffi_Record_Builder['copyRecord'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0);
}
$GLOBALS['Record_Builder_copyRecord'] = __NAMESPACE__ . '\\majRecord_majBuilder_copymajRecord';

function majRecord_majBuilder_unsafemajDelete(string $v0, $v1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majRecord_majBuilder_unsafemajDelete';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  global $ffi_Record_Builder;
  $f = ($ffi_Record_Builder['unsafeDelete'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0, $v1);
}
$GLOBALS['Record_Builder_unsafeDelete'] = __NAMESPACE__ . '\\majRecord_majBuilder_unsafemajDelete';

function majRecord_majBuilder_unsafemajInsert(string $v0, $v1 = null, $v2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majRecord_majBuilder_unsafemajInsert';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  global $ffi_Record_Builder;
  $f = ($ffi_Record_Builder['unsafeInsert'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0, $v1, $v2);
}
$GLOBALS['Record_Builder_unsafeInsert'] = __NAMESPACE__ . '\\majRecord_majBuilder_unsafemajInsert';

function majRecord_majBuilder_unsafemajModify(string $v0, $v1 = null, $v2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majRecord_majBuilder_unsafemajModify';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  global $ffi_Record_Builder;
  $f = ($ffi_Record_Builder['unsafeModify'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0, $v1, $v2);
}
$GLOBALS['Record_Builder_unsafeModify'] = __NAMESPACE__ . '\\majRecord_majBuilder_unsafemajModify';

function majRecord_majBuilder_unsafemajRename(string $v0, $v1 = null, $v2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majRecord_majBuilder_unsafemajRename';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  global $ffi_Record_Builder;
  $f = ($ffi_Record_Builder['unsafeRename'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0, $v1, $v2);
}
$GLOBALS['Record_Builder_unsafeRename'] = __NAMESPACE__ . '\\majRecord_majBuilder_unsafemajRename';





// Record_Builder_union
function majRecord_majBuilder_union($_dollar__unused_0, $r1_1 = null, $r2_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_union';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = ($GLOBALS['Record_Unsafe_Union_unsafeUnionFn'])($r1_1, $r2_2);
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Record_Builder_union'] = __NAMESPACE__ . '\\majRecord_majBuilder_union';

// Record_Builder_semigroupoidBuilder
$GLOBALS['Record_Builder_semigroupoidBuilder'] = $GLOBALS['Control_Semigroupoid_semigroupoidFn'];

// Record_Builder_rename
function majRecord_majBuilder_rename($dictIsSymbol_0, $dictIsSymbol1_1 = null, $_dollar__unused_2 = null, $_dollar__unused_3 = null, $_dollar__unused_4 = null, $_dollar__unused_5 = null, $l1_6 = null, $l2_7 = null, $r1_8 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_rename';
  if ($__num < 9) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 9);
  }
  $__res = \Record\Builder\majRecord_majBuilder_unsafemajRename((($dictIsSymbol_0)->{'reflectSymbol'})($l1_6), (($dictIsSymbol1_1)->{'reflectSymbol'})($l2_7), $r1_8);
  goto __end;;
  __end:
  return 9 < $__num ? $__res(...\array_slice(\func_get_args(), 9)) : $__res;
}
$GLOBALS['Record_Builder_rename'] = __NAMESPACE__ . '\\majRecord_majBuilder_rename';

// Record_Builder_nub
function majRecord_majBuilder_nub($_dollar__unused_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_nub';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $GLOBALS['Unsafe_Coerce_unsafeCoerce'];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Record_Builder_nub'] = __NAMESPACE__ . '\\majRecord_majBuilder_nub';

// Record_Builder_modify
function majRecord_majBuilder_modify($_dollar__unused_0, $_dollar__unused_1 = null, $dictIsSymbol_2 = null, $l_3 = null, $f_4 = null, $r1_5 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_modify';
  if ($__num < 6) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 6);
  }
  $__res = \Record\Builder\majRecord_majBuilder_unsafemajModify((($dictIsSymbol_2)->{'reflectSymbol'})($l_3), $f_4, $r1_5);
  goto __end;;
  __end:
  return 6 < $__num ? $__res(...\array_slice(\func_get_args(), 6)) : $__res;
}
$GLOBALS['Record_Builder_modify'] = __NAMESPACE__ . '\\majRecord_majBuilder_modify';

// Record_Builder_merge
function majRecord_majBuilder_merge($_dollar__unused_0, $_dollar__unused_1 = null, $r1_2 = null, $r2_3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_merge';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = ($GLOBALS['Record_Unsafe_Union_unsafeUnionFn'])($r1_2, $r2_3);
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Record_Builder_merge'] = __NAMESPACE__ . '\\majRecord_majBuilder_merge';

// Record_Builder_insert
function majRecord_majBuilder_insert($_dollar__unused_0, $_dollar__unused_1 = null, $dictIsSymbol_2 = null, $l_3 = null, $a_4 = null, $r1_5 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_insert';
  if ($__num < 6) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 6);
  }
  $__res = \Record\Builder\majRecord_majBuilder_unsafemajInsert((($dictIsSymbol_2)->{'reflectSymbol'})($l_3), $a_4, $r1_5);
  goto __end;;
  __end:
  return 6 < $__num ? $__res(...\array_slice(\func_get_args(), 6)) : $__res;
}
$GLOBALS['Record_Builder_insert'] = __NAMESPACE__ . '\\majRecord_majBuilder_insert';

// Record_Builder_disjointUnion
function majRecord_majBuilder_disjointmajUnion($_dollar__unused_0, $_dollar__unused_1 = null, $r1_2 = null, $r2_3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_disjointmajUnion';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = ($GLOBALS['Record_Unsafe_Union_unsafeUnionFn'])($r1_2, $r2_3);
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Record_Builder_disjointUnion'] = __NAMESPACE__ . '\\majRecord_majBuilder_disjointmajUnion';

// Record_Builder_delete
function majRecord_majBuilder_delete($dictIsSymbol_0, $_dollar__unused_1 = null, $_dollar__unused_2 = null, $l_3 = null, $r2_4 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_delete';
  if ($__num < 5) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 5);
  }
  $__res = \Record\Builder\majRecord_majBuilder_unsafemajDelete((($dictIsSymbol_0)->{'reflectSymbol'})($l_3), $r2_4);
  goto __end;;
  __end:
  return 5 < $__num ? $__res(...\array_slice(\func_get_args(), 5)) : $__res;
}
$GLOBALS['Record_Builder_delete'] = __NAMESPACE__ . '\\majRecord_majBuilder_delete';

// Record_Builder_categoryBuilder
$GLOBALS['Record_Builder_categoryBuilder'] = $GLOBALS['Control_Category_categoryFn'];

// Record_Builder_build
function majRecord_majBuilder_build($v_0, $r1_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_build';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($v_0)(\Record\Builder\majRecord_majBuilder_copymajRecord($r1_1));
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Record_Builder_build'] = __NAMESPACE__ . '\\majRecord_majBuilder_build';

// Record_Builder_buildFromScratch
function majRecord_majBuilder_buildmajFrommajScratch($a_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_buildmajFrommajScratch';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($a_0)((object)[]);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Record_Builder_buildFromScratch'] = __NAMESPACE__ . '\\majRecord_majBuilder_buildmajFrommajScratch';

// Record_Builder_flip
function majRecord_majBuilder_flip($f_0, $b_1 = null, $a_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majRecord_majBuilder_flip';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = (($f_0)($a_2))(\Record\Builder\majRecord_majBuilder_copymajRecord($b_1));
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Record_Builder_flip'] = __NAMESPACE__ . '\\majRecord_majBuilder_flip';

