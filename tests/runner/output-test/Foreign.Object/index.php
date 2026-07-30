<?php

namespace Foreign\Object;

// ALL IMPORTS: Control.Applicative, Control.Apply, Control.Bind, Control.Category, Control.Monad.ST, Control.Monad.ST.Internal, Control.Semigroupoid, Data.Array, Data.Eq, Data.Foldable, Data.FoldableWithIndex, Data.Function, Data.Function.Uncurried, Data.Functor, Data.FunctorWithIndex, Data.HeytingAlgebra, Data.Maybe, Data.Monoid, Data.Ord, Data.Semigroup, Data.Show, Data.Traversable, Data.TraversableWithIndex, Data.Tuple, Data.Unfoldable, Foreign.Object, Foreign.Object.ST, Prelude, Prim, Type.Row.Homogeneous, Unsafe.Coerce
// TO REQUIRE: Control.Applicative, Control.Apply, Control.Bind, Control.Category, Control.Monad.ST, Control.Monad.ST.Internal, Control.Semigroupoid, Data.Array, Data.Eq, Data.Foldable, Data.FoldableWithIndex, Data.Function, Data.Function.Uncurried, Data.Functor, Data.FunctorWithIndex, Data.HeytingAlgebra, Data.Maybe, Data.Monoid, Data.Ord, Data.Semigroup, Data.Show, Data.Traversable, Data.TraversableWithIndex, Data.Tuple, Data.Unfoldable, Foreign.Object, Foreign.Object.ST, Prelude, Type.Row.Homogeneous, Unsafe.Coerce
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Apply/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Category/index.php';
require_once __DIR__ . '/../Control.Monad.ST/index.php';
require_once __DIR__ . '/../Control.Monad.ST.Internal/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Array/index.php';
require_once __DIR__ . '/../Data.Eq/index.php';
require_once __DIR__ . '/../Data.Foldable/index.php';
require_once __DIR__ . '/../Data.FoldableWithIndex/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Function.Uncurried/index.php';
require_once __DIR__ . '/../Data.Functor/index.php';
require_once __DIR__ . '/../Data.FunctorWithIndex/index.php';
require_once __DIR__ . '/../Data.HeytingAlgebra/index.php';
require_once __DIR__ . '/../Data.Maybe/index.php';
require_once __DIR__ . '/../Data.Monoid/index.php';
require_once __DIR__ . '/../Data.Ord/index.php';
require_once __DIR__ . '/../Data.Semigroup/index.php';
require_once __DIR__ . '/../Data.Show/index.php';
require_once __DIR__ . '/../Data.Traversable/index.php';
require_once __DIR__ . '/../Data.TraversableWithIndex/index.php';
require_once __DIR__ . '/../Data.Tuple/index.php';
require_once __DIR__ . '/../Data.Unfoldable/index.php';
require_once __DIR__ . '/../Foreign.Object/index.php';
require_once __DIR__ . '/../Foreign.Object.ST/index.php';
require_once __DIR__ . '/../Prelude/index.php';
require_once __DIR__ . '/../Type.Row.Homogeneous/index.php';
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
$ffi_Foreign_Object = \call_user_func(function() {
  $exports = [];
$_copyST = function($m) {
    return function() use ($m) {
        $r = new \stdClass();
        foreach ($m as $k => $v) {
            $r->$k = $v;
        }
        return $r;
    };
};

$empty = new \stdClass();

$runST = function($f) {
    return $f();
};

$_fmapObject = function($m0, $f = null) use (&$_fmapObject) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_fmapObject) {

            return $_fmapObject(...\array_merge($__args, $more));
        };
    }
    $m = new \stdClass();
    foreach ($m0 as $k => $v) {
        $m->$k = $f($v);
    }
    return $m;
};

$_mapWithKey = function($m0, $f = null) use (&$_mapWithKey) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_mapWithKey) {

            return $_mapWithKey(...\array_merge($__args, $more));
        };
    }
    $m = new \stdClass();
    foreach ($m0 as $k => $v) {
        $m->$k = $f($k)($v);
    }
    return $m;
};

$_foldM = function($bind, $f = null, $mz = null, $m = null) use (&$_foldM) {
    if (\func_num_args() < 4) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_foldM) {
            return $_foldM(...\array_merge($__args, $more));
        };
    }
    $acc = $mz;
    foreach ($m as $k => $v) {
        $g = function($z) use ($f, $k, $v) {
            return $f($z)($k)($v);
        };
        $acc = $bind($acc)($g);
    }
    return $acc;
};

$_foldSCObject = function($m, $z = null, $f = null, $fromMaybe = null) use (&$_foldSCObject) {
    if (\func_num_args() < 4) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_foldSCObject) {

            return $_foldSCObject(...\array_merge($__args, $more));
        };
    }
    $acc = $z;
    foreach ($m as $k => $v) {
        $maybeR = $f($acc)($k)($v);
        $r = $fromMaybe(null)($maybeR);
        if ($r === null) return $acc;
        else $acc = $r;
    }
    return $acc;
};

$all = function($f, $m = null) use (&$all) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$all) {

            return $all(...\array_merge($__args, $more));
        };
    }
    foreach ($m as $k => $v) {
        if (!$f($k)($v)) return false;
    }
    return true;
};

$size = function($m) {
    $s = 0;
    foreach ($m as $k => $v) {
        $s++;
    }
    return $s;
};

$_lookup = function($no, $yes = null, $k = null, $m = null) use (&$_lookup) {
    if (\func_num_args() < 4) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_lookup) {

            return $_lookup(...\array_merge($__args, $more));
        };
    }
    return property_exists($m, $k) ? $yes($m->$k) : $no;
};

$_lookupST = function($no, $yes = null, $k = null, $m = null) use (&$_lookupST) {
    if (\func_num_args() < 4) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_lookupST) {

            return $_lookupST(...\array_merge($__args, $more));
        };
    }
    return function() use ($no, $yes, $k, $m) {
        return property_exists($m, $k) ? $yes($m->$k) : $no;
    };
};

$toArrayWithKey = function($f, $m = null) use (&$toArrayWithKey) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$toArrayWithKey) {

            return $toArrayWithKey(...\array_merge($__args, $more));
        };
    }
    $r = [];
    foreach ($m as $k => $v) {
        $r[] = $f($k)($v);
    }
    return $r;
};

$keys = function($m) {
    $r = [];
    foreach ($m as $k => $v) {
        $r[] = (string)$k;
    }
    return $r;
};

$exports['_copyST'] = $_copyST;
$exports['empty'] = $empty;
$exports['runST'] = $runST;
$exports['_fmapObject'] = $_fmapObject;
$exports['_mapWithKey'] = $_mapWithKey;
$exports['_foldM'] = $_foldM;
$exports['_foldSCObject'] = $_foldSCObject;
$exports['all'] = $all;
$exports['size'] = $size;
$exports['_lookup'] = $_lookup;
$exports['_lookupST'] = $_lookupST;
$exports['toArrayWithKey'] = $toArrayWithKey;
$exports['keys'] = $keys;
return $exports;
  return $exports;
});
function majForeign_majObject__copymajSmajT($v0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_majObject__copymajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  global $ffi_Foreign_Object;
  $f = ($ffi_Foreign_Object['_copyST'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0);
}
$GLOBALS['Foreign_Object__copyST'] = __NAMESPACE__ . '\\majForeign_majObject__copymajSmajT';

$GLOBALS['Foreign_Object__fmapObject'] = ($ffi_Foreign_Object['_fmapObject'] ?? new class { public function __invoke(...$args) { return $this; } });
function majForeign_majObject__foldmajM($v0, $v1 = null, $v2 = null, $v3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_majObject__foldmajM';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  global $ffi_Foreign_Object;
  $f = ($ffi_Foreign_Object['_foldM'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0, $v1, $v2, $v3);
}
$GLOBALS['Foreign_Object__foldM'] = __NAMESPACE__ . '\\majForeign_majObject__foldmajM';

$GLOBALS['Foreign_Object__foldSCObject'] = ($ffi_Foreign_Object['_foldSCObject'] ?? new class { public function __invoke(...$args) { return $this; } });
$GLOBALS['Foreign_Object__lookup'] = ($ffi_Foreign_Object['_lookup'] ?? new class { public function __invoke(...$args) { return $this; } });
$GLOBALS['Foreign_Object__lookupST'] = ($ffi_Foreign_Object['_lookupST'] ?? new class { public function __invoke(...$args) { return $this; } });
$GLOBALS['Foreign_Object__mapWithKey'] = ($ffi_Foreign_Object['_mapWithKey'] ?? new class { public function __invoke(...$args) { return $this; } });
function majForeign_majObject_all($v0, $v1 = null): bool|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_majObject_all';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  global $ffi_Foreign_Object;
  $f = ($ffi_Foreign_Object['all'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0, $v1);
}
$GLOBALS['Foreign_Object_all'] = __NAMESPACE__ . '\\majForeign_majObject_all';

$GLOBALS['Foreign_Object_empty'] = ($ffi_Foreign_Object['empty'] ?? new class { public function __invoke(...$args) { return $this; } });
function majForeign_majObject_keys($v0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_majObject_keys';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  global $ffi_Foreign_Object;
  $f = ($ffi_Foreign_Object['keys'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0);
}
$GLOBALS['Foreign_Object_keys'] = __NAMESPACE__ . '\\majForeign_majObject_keys';

function majForeign_majObject_runmajSmajT($v0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_majObject_runmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  global $ffi_Foreign_Object;
  $f = ($ffi_Foreign_Object['runST'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0);
}
$GLOBALS['Foreign_Object_runST'] = __NAMESPACE__ . '\\majForeign_majObject_runmajSmajT';

function majForeign_majObject_size($v0): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_majObject_size';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  global $ffi_Foreign_Object;
  $f = ($ffi_Foreign_Object['size'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0);
}
$GLOBALS['Foreign_Object_size'] = __NAMESPACE__ . '\\majForeign_majObject_size';

function majForeign_majObject_tomajArraymajWithmajKey($v0, $v1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_majObject_tomajArraymajWithmajKey';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  global $ffi_Foreign_Object;
  $f = ($ffi_Foreign_Object['toArrayWithKey'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0, $v1);
}
$GLOBALS['Foreign_Object_toArrayWithKey'] = __NAMESPACE__ . '\\majForeign_majObject_tomajArraymajWithmajKey';





// Foreign_Object_discard
$GLOBALS['Foreign_Object_discard'] = (($GLOBALS['Control_Bind_discardUnit'])->{'discard'})($GLOBALS['Control_Monad_ST_Internal_bindST']);

// Foreign_Object_forWithIndex_
$GLOBALS['Foreign_Object_forWithIndex_'] = ($GLOBALS['Data_FoldableWithIndex_forWithIndex_'])($GLOBALS['Control_Monad_ST_Internal_applicativeST']);

// Foreign_Object_for_
$GLOBALS['Foreign_Object_for_'] = ($GLOBALS['Data_Foldable_for_'])($GLOBALS['Control_Monad_ST_Internal_applicativeST']);

// Foreign_Object_void_closure
$GLOBALS['Foreign_Object_void_closure'] = (($GLOBALS['Control_Monad_ST_Internal_functorST'])->{'map'})(function($v_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Data_Unit_unit'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});

// Foreign_Object_void
function majForeign_majObject_void($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_void';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_void_closure'])($v_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_void'] = __NAMESPACE__ . '\\majForeign_majObject_void';

// Foreign_Object_identity
function majForeign_majObject_identity($x_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_identity';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $x_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_identity'] = __NAMESPACE__ . '\\majForeign_majObject_identity';

// Foreign_Object_ordTuple
$GLOBALS['Foreign_Object_ordTuple'] = ($GLOBALS['Data_Tuple_ordTuple'])($GLOBALS['Data_Ord_ordString']);

// Foreign_Object_values_closure
$GLOBALS['Foreign_Object_values_closure'] = ($GLOBALS['Foreign_Object_toArrayWithKey'])((function() {
  $__fn = function($v_0, $v1_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = $v1_1;
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})());

// Foreign_Object_values
function majForeign_majObject_values($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_values';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_values_closure'])($v_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_values'] = __NAMESPACE__ . '\\majForeign_majObject_values';

// Foreign_Object_toUnfoldable
function majForeign_majObject_tomajUnfoldable($dictUnfoldable_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_tomajUnfoldable';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (($GLOBALS['Control_Semigroupoid_composeImpl'])(($GLOBALS['Data_Array_toUnfoldable'])($dictUnfoldable_0)))(($GLOBALS['Foreign_Object_toArrayWithKey'])($GLOBALS['Data_Tuple_Tuple']));
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_toUnfoldable'] = __NAMESPACE__ . '\\majForeign_majObject_tomajUnfoldable';

// Foreign_Object_toAscUnfoldable
function majForeign_majObject_tomajAscmajUnfoldable($dictUnfoldable_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_tomajAscmajUnfoldable';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (($GLOBALS['Control_Semigroupoid_composeImpl'])(($GLOBALS['Data_Array_toUnfoldable'])($dictUnfoldable_0)))((($GLOBALS['Control_Semigroupoid_composeImpl'])((($GLOBALS['Data_Array_sortWith'])($GLOBALS['Data_Ord_ordString']))($GLOBALS['Data_Tuple_fst'])))(($GLOBALS['Foreign_Object_toArrayWithKey'])($GLOBALS['Data_Tuple_Tuple'])));
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_toAscUnfoldable'] = __NAMESPACE__ . '\\majForeign_majObject_tomajAscmajUnfoldable';

// Foreign_Object_toAscArray_closure
$GLOBALS['Foreign_Object_toAscArray_closure'] = ($GLOBALS['Foreign_Object_toAscUnfoldable'])($GLOBALS['Data_Unfoldable_unfoldableArray']);

// Foreign_Object_toAscArray
function majForeign_majObject_tomajAscmajArray($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_tomajAscmajArray';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_toAscArray_closure'])($v_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_toAscArray'] = __NAMESPACE__ . '\\majForeign_majObject_tomajAscmajArray';

// Foreign_Object_toArray_closure
$GLOBALS['Foreign_Object_toArray_closure'] = ($GLOBALS['Foreign_Object_toArrayWithKey'])($GLOBALS['Data_Tuple_Tuple']);

// Foreign_Object_toArray
function majForeign_majObject_tomajArray($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_tomajArray';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_toArray_closure'])($v_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_toArray'] = __NAMESPACE__ . '\\majForeign_majObject_tomajArray';

// Foreign_Object_thawST_closure
$GLOBALS['Foreign_Object_thawST_closure'] = $GLOBALS['Foreign_Object__copyST'];

// Foreign_Object_thawST
function majForeign_majObject_thawmajSmajT($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_thawmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_thawST_closure'])($v_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_thawST'] = __NAMESPACE__ . '\\majForeign_majObject_thawmajSmajT';

// Foreign_Object_singleton
function majForeign_majObject_singleton(string $k_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_singleton';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = \Foreign\Object\majForeign_majObject_runmajSmajT(((($GLOBALS['Control_Monad_ST_Internal_bindST'])->{'bind'})($GLOBALS['Foreign_Object_ST_new']))((($GLOBALS['Foreign_Object_ST_poke'])($k_0))($v_1)));
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Object_singleton'] = __NAMESPACE__ . '\\majForeign_majObject_singleton';

// Foreign_Object_showObject
function majForeign_majObject_showmajObject($dictShow_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_showmajObject';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $show_1_0 = ($GLOBALS['Data_Show_showArrayImpl'])((\Data\Tuple\majData_majTuple_showmajTuple($GLOBALS['Data_Show_showString'], $dictShow_0))->{'show'});
  $__res = (object)["show" => function($m_2) use ($show_1_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Semigroup_semigroupString'])->{'append'})("(fromFoldable "))(((($GLOBALS['Data_Semigroup_semigroupString'])->{'append'})(($show_1_0)(\Foreign\Object\majForeign_majObject_tomajArray($m_2))))(")"));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_showObject'] = __NAMESPACE__ . '\\majForeign_majObject_showmajObject';

// Foreign_Object_mutate
function majForeign_majObject_mutate($f_0, $m_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_mutate';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = \Foreign\Object\majForeign_majObject_runmajSmajT(((($GLOBALS['Control_Monad_ST_Internal_bindST'])->{'bind'})(\Foreign\Object\majForeign_majObject__copymajSmajT($m_1)))(function($s_2) use ($f_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Monad_ST_Internal_bindST'])->{'bind'})(($f_0)($s_2)))(function($_dollar__unused_3) use ($s_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_ST_Internal_applicativeST'])->{'pure'})($s_2);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Object_mutate'] = __NAMESPACE__ . '\\majForeign_majObject_mutate';

// Foreign_Object_member
function majForeign_majObject_member(string $__local_var_0, $__local_var_1 = null): bool|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_member';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($GLOBALS['Foreign_Object__lookup'])(false, function($v_2) {
  $__num = \func_num_args();
  $__res = true;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, $__local_var_0, $__local_var_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Object_member'] = __NAMESPACE__ . '\\majForeign_majObject_member';

// Foreign_Object_mapWithKey
function majForeign_majObject_mapmajWithmajKey($f_0, $m_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_mapmajWithmajKey';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($GLOBALS['Foreign_Object__mapWithKey'])($m_1, $f_0);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Object_mapWithKey'] = __NAMESPACE__ . '\\majForeign_majObject_mapmajWithmajKey';

// Foreign_Object_lookup
function majForeign_majObject_lookup(string $__local_var_0, $__local_var_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_lookup';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($GLOBALS['Foreign_Object__lookup'])(new \Data\Maybe\Data_Maybe_Nothing(), $GLOBALS['Data_Maybe_Just'], $__local_var_0, $__local_var_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Object_lookup'] = __NAMESPACE__ . '\\majForeign_majObject_lookup';

// Foreign_Object_isSubmap
function majForeign_majObject_ismajSubmap($dictEq_0, $m1_1 = null, $m2_2 = null): bool|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_ismajSubmap';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = \Foreign\Object\majForeign_majObject_all((function() use ($dictEq_0, $m2_2) {
  $__fn = function($k_3, $v_4 = null) use ($dictEq_0, $m2_2, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($GLOBALS['Foreign_Object__lookup'])(false, (($dictEq_0)->{'eq'})($v_4), $k_3, $m2_2);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(), $m1_1);
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Foreign_Object_isSubmap'] = __NAMESPACE__ . '\\majForeign_majObject_ismajSubmap';

// Foreign_Object_isEmpty_closure
$GLOBALS['Foreign_Object_isEmpty_closure'] = ($GLOBALS['Foreign_Object_all'])((function() {
  $__fn = function($v_0, $v1_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = false;
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})());

// Foreign_Object_isEmpty
function majForeign_majObject_ismajEmpty($v_0): bool|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_ismajEmpty';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_isEmpty_closure'])($v_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_isEmpty'] = __NAMESPACE__ . '\\majForeign_majObject_ismajEmpty';

// Foreign_Object_insert
function majForeign_majObject_insert(string $k_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_insert';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($GLOBALS['Foreign_Object_mutate'])((($GLOBALS['Foreign_Object_ST_poke'])($k_0))($v_1));
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Object_insert'] = __NAMESPACE__ . '\\majForeign_majObject_insert';

// Foreign_Object_functorObject
$GLOBALS['Foreign_Object_functorObject'] = (object)["map" => (function() {
  $__fn = function($f_0, $m_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($GLOBALS['Foreign_Object__fmapObject'])($m_1, $f_0);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()];

// Foreign_Object_functorWithIndexObject
$GLOBALS['Foreign_Object_functorWithIndexObject'] = (object)["mapWithIndex" => $GLOBALS['Foreign_Object_mapWithKey'], "Functor0" => function($_dollar__unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Foreign_Object_functorObject'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Foreign_Object_fromHomogeneous
function majForeign_majObject_frommajHomogeneous($_dollar__unused_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_frommajHomogeneous';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $GLOBALS['Unsafe_Coerce_unsafeCoerce'];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_fromHomogeneous'] = __NAMESPACE__ . '\\majForeign_majObject_frommajHomogeneous';

// Foreign_Object_fromFoldableWithIndex
function majForeign_majObject_frommajFoldablemajWithmajIndex($dictFoldableWithIndex_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_frommajFoldablemajWithmajIndex';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $forWithIndex_1_1_0 = ($GLOBALS['Foreign_Object_forWithIndex_'])($dictFoldableWithIndex_0);
  $__res = function($l_2) use ($forWithIndex_1_1_0) {
  $__num = \func_num_args();
  $__res = \Foreign\Object\majForeign_majObject_runmajSmajT(((($GLOBALS['Control_Monad_ST_Internal_bindST'])->{'bind'})($GLOBALS['Foreign_Object_ST_new']))(function($s_3) use ($forWithIndex_1_1_0, $l_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Foreign_Object_discard'])((($forWithIndex_1_1_0)($l_2))((function() use ($s_3) {
  $__fn = function($k_4, $v_5 = null) use ($s_3, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = \Foreign\Object\ST\majForeign_majObject_majSmajT_poke($k_4, $v_5, $s_3);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})())))(function($_dollar__unused_4) use ($s_3) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_ST_Internal_applicativeST'])->{'pure'})($s_3);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_fromFoldableWithIndex'] = __NAMESPACE__ . '\\majForeign_majObject_frommajFoldablemajWithmajIndex';

// Foreign_Object_fromFoldableWith
function majForeign_majObject_frommajFoldablemajWith($dictFoldable_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_frommajFoldablemajWith';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $for_1_1_0 = ($GLOBALS['Foreign_Object_for_'])($dictFoldable_0);
  $__res = (function() use ($for_1_1_0) {
  $__fn = function($f_2, $l_3 = null) use ($for_1_1_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = \Foreign\Object\majForeign_majObject_runmajSmajT(((($GLOBALS['Control_Monad_ST_Internal_bindST'])->{'bind'})($GLOBALS['Foreign_Object_ST_new']))(function($s_4) use ($f_2, $for_1_1_0, $l_3) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Foreign_Object_discard'])((($for_1_1_0)($l_3))(function($v_5) use ($f_2, $s_4) {
  $__num = \func_num_args();
  $__local_var_6_1 = ($v_5)->{'value0'};
  $__res = ((($GLOBALS['Control_Monad_ST_Internal_bindST'])->{'bind'})(($GLOBALS['Foreign_Object__lookupST'])(($v_5)->{'value1'}, ($f_2)(($v_5)->{'value1'}), $__local_var_6_1, $s_4)))(function($v_prime_7) use ($__local_var_6_1, $s_4) {
  $__num = \func_num_args();
  $__res = \Foreign\Object\ST\majForeign_majObject_majSmajT_poke($__local_var_6_1, $v_prime_7, $s_4);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
})))(function($_dollar__unused_5) use ($s_4) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_ST_Internal_applicativeST'])->{'pure'})($s_4);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_fromFoldableWith'] = __NAMESPACE__ . '\\majForeign_majObject_frommajFoldablemajWith';

// Foreign_Object_fromFoldable
function majForeign_majObject_frommajFoldable($dictFoldable_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_frommajFoldable';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__local_var_1_0 = ($dictFoldable_0)->{'foldr'};
  $__res = function($l_2) use ($__local_var_1_0) {
  $__num = \func_num_args();
  $__res = \Foreign\Object\majForeign_majObject_runmajSmajT(((($GLOBALS['Control_Monad_ST_Internal_bindST'])->{'bind'})($GLOBALS['Foreign_Object_ST_new']))(function($s_3) use ($__local_var_1_0, $l_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Foreign_Object_discard'])(\Control\Monad\ST\Internal\majControl_majMonad_majSmajT_majInternal_foreach(($GLOBALS['Data_Array_fromFoldableImpl'])($__local_var_1_0, $l_2), function($v_4) use ($s_3) {
  $__num = \func_num_args();
  $__res = \Foreign\Object\majForeign_majObject_void(\Foreign\Object\ST\majForeign_majObject_majSmajT_poke(($v_4)->{'value0'}, ($v_4)->{'value1'}, $s_3));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
})))(function($_dollar__unused_4) use ($s_3) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_ST_Internal_applicativeST'])->{'pure'})($s_3);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_fromFoldable'] = __NAMESPACE__ . '\\majForeign_majObject_frommajFoldable';

// Foreign_Object_freezeST_closure
$GLOBALS['Foreign_Object_freezeST_closure'] = $GLOBALS['Foreign_Object__copyST'];

// Foreign_Object_freezeST
function majForeign_majObject_freezemajSmajT($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_freezemajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_freezeST_closure'])($v_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_freezeST'] = __NAMESPACE__ . '\\majForeign_majObject_freezemajSmajT';

// Foreign_Object_foldMaybe
function majForeign_majObject_foldmajMaybe($f_0, $z_1 = null, $m_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_foldmajMaybe';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = ($GLOBALS['Foreign_Object__foldSCObject'])($m_2, $z_1, $f_0, $GLOBALS['Data_Maybe_fromMaybe']);
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Foreign_Object_foldMaybe'] = __NAMESPACE__ . '\\majForeign_majObject_foldmajMaybe';

// Foreign_Object_foldM
function majForeign_majObject_foldmajM($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_foldmajM';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $bind1_1_0 = ((($dictMonad_0)->{'Bind1'})(null))->{'bind'};
  $__res = (function() use ($bind1_1_0, $dictMonad_0) {
  $__fn = function($f_2, $z_3 = null) use ($bind1_1_0, $dictMonad_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ((($GLOBALS['Foreign_Object__foldM'])($bind1_1_0))($f_2))((((($dictMonad_0)->{'Applicative0'})(null))->{'pure'})($z_3));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_foldM'] = __NAMESPACE__ . '\\majForeign_majObject_foldmajM';

// Foreign_Object_foldM1
$GLOBALS['Foreign_Object_foldM1'] = ($GLOBALS['Foreign_Object_foldM'])($GLOBALS['Control_Monad_ST_Internal_monadST']);

// Foreign_Object_union
function majForeign_majObject_union($m_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_union';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_mutate'])(function($s_1) use ($m_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Foreign_Object_foldM1'])((function() {
  $__fn = function($s_prime_2, $k_3 = null, $v_4 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = \Foreign\Object\ST\majForeign_majObject_majSmajT_poke($k_3, $v_4, $s_prime_2);
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()))($s_1))($m_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_union'] = __NAMESPACE__ . '\\majForeign_majObject_union';

// Foreign_Object_unions
function majForeign_majObject_unions($dictFoldable_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_unions';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ((($dictFoldable_0)->{'foldl'})($GLOBALS['Foreign_Object_union']))($GLOBALS['Foreign_Object_empty']);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_unions'] = __NAMESPACE__ . '\\majForeign_majObject_unions';

// Foreign_Object_unionWith
function majForeign_majObject_unionmajWith($f_0, $m1_1 = null, $m2_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_unionmajWith';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = \Foreign\Object\majForeign_majObject_mutate(function($s1_3) use ($f_0, $m1_1, $m2_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Foreign_Object_foldM1'])((function() use ($f_0, $m2_2) {
  $__fn = function($s2_4, $k_5 = null, $v1_6 = null) use ($f_0, $m2_2, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = \Foreign\Object\ST\majForeign_majObject_majSmajT_poke($k_5, ($GLOBALS['Foreign_Object__lookup'])($v1_6, function($v2_7) use ($f_0, $v1_6) {
  $__num = \func_num_args();
  $__res = (($f_0)($v1_6))($v2_7);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, $k_5, $m2_2), $s2_4);
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()))($s1_3))($m1_1);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, $m2_2);
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Foreign_Object_unionWith'] = __NAMESPACE__ . '\\majForeign_majObject_unionmajWith';

// Foreign_Object_semigroupObject
function majForeign_majObject_semigroupmajObject($dictSemigroup_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_semigroupmajObject';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["append" => ($GLOBALS['Foreign_Object_unionWith'])(($dictSemigroup_0)->{'append'})];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_semigroupObject'] = __NAMESPACE__ . '\\majForeign_majObject_semigroupmajObject';

// Foreign_Object_monoidObject
function majForeign_majObject_monoidmajObject($dictSemigroup_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_monoidmajObject';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["mempty" => $GLOBALS['Foreign_Object_empty'], "Semigroup0" => function($_dollar__unused_1) use ($dictSemigroup_0) {
  $__num = \func_num_args();
  $__res = (object)["append" => ($GLOBALS['Foreign_Object_unionWith'])(($dictSemigroup_0)->{'append'})];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_monoidObject'] = __NAMESPACE__ . '\\majForeign_majObject_monoidmajObject';

// Foreign_Object_fold_closure
$GLOBALS['Foreign_Object_fold_closure'] = ($GLOBALS['Foreign_Object__foldM'])($GLOBALS['Data_Function_applyFlipped']);

// Foreign_Object_fold
function majForeign_majObject_fold($v_0, $v_1 = null, $v_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_fold';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = ($GLOBALS['Foreign_Object_fold_closure'])($v_0, $v_1, $v_2);
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Foreign_Object_fold'] = __NAMESPACE__ . '\\majForeign_majObject_fold';

// Foreign_Object_foldMap
function majForeign_majObject_foldmajMap($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_foldmajMap';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $mempty_1_0 = ($dictMonoid_0)->{'mempty'};
  $__res = function($f_2) use ($dictMonoid_0, $mempty_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Foreign_Object_fold'])((function() use ($dictMonoid_0, $f_2) {
  $__fn = function($acc_3, $k_4 = null, $v_5 = null) use ($dictMonoid_0, $f_2, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = ((((($dictMonoid_0)->{'Semigroup0'})(null))->{'append'})($acc_3))((($f_2)($k_4))($v_5));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()))($mempty_1_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_foldMap'] = __NAMESPACE__ . '\\majForeign_majObject_foldmajMap';

// Foreign_Object_foldableObject
$GLOBALS['Foreign_Object_foldableObject'] = (object)["foldl" => function($f_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Foreign_Object_fold'])((function() use ($f_0) {
  $__fn = function($z_1, $v_2 = null) use ($f_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($f_0)($z_1);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})());
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "foldr" => (function() {
  $__fn = function($f_0, $z_1 = null, $m_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = (((($GLOBALS['Data_Foldable_foldableArray'])->{'foldr'})($f_0))($z_1))(\Foreign\Object\majForeign_majObject_values($m_2));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(), "foldMap" => function($dictMonoid_0) {
  $__num = \func_num_args();
  $foldMap1_1_0 = ($GLOBALS['Foreign_Object_foldMap'])($dictMonoid_0);
  $__res = function($f_2) use ($foldMap1_1_0) {
  $__num = \func_num_args();
  $__res = ($foldMap1_1_0)(function($v_3) use ($f_2) {
  $__num = \func_num_args();
  $__res = $f_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Foreign_Object_foldableWithIndexObject
$GLOBALS['Foreign_Object_foldableWithIndexObject'] = (object)["foldlWithIndex" => function($f_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Foreign_Object_fold'])((function() use ($f_0) {
  $__fn = function($b_1, $a_2 = null) use ($f_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = (($f_0)($a_2))($b_1);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})());
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "foldrWithIndex" => (function() {
  $__fn = function($f_0, $z_1 = null, $m_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = (((($GLOBALS['Data_Foldable_foldableArray'])->{'foldr'})(function($v_3) use ($f_0) {
  $__num = \func_num_args();
  $__res = (($f_0)(($v_3)->{'value0'}))(($v_3)->{'value1'});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($z_1))(\Foreign\Object\majForeign_majObject_tomajArraymajWithmajKey($GLOBALS['Data_Tuple_Tuple'], $m_2));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(), "foldMapWithIndex" => function($dictMonoid_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Foreign_Object_foldMap'])($dictMonoid_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Foldable0" => function($_dollar__unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Foreign_Object_foldableObject'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Foreign_Object_traversableWithIndexObject
$GLOBALS['Foreign_Object_traversableWithIndexObject'] = (object)["traverseWithIndex" => function($dictApplicative_0) {
  $__num = \func_num_args();
  $Apply0_1_0 = (($dictApplicative_0)->{'Apply0'})(null);
  $__res = (function() use ($Apply0_1_0, $dictApplicative_0) {
  $__fn = function($f_2, $ms_3 = null) use ($Apply0_1_0, $dictApplicative_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = \Foreign\Object\majForeign_majObject_fold((function() use ($Apply0_1_0, $f_2) {
  $__fn = function($acc_4, $k_5 = null, $v_6 = null) use ($Apply0_1_0, $f_2, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = ((($Apply0_1_0)->{'apply'})(((((($Apply0_1_0)->{'Functor0'})(null))->{'map'})((function() use ($k_5) {
  $__fn = function($b_7, $a_8 = null) use ($k_5, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = \Foreign\Object\majForeign_majObject_mutate((($GLOBALS['Foreign_Object_ST_poke'])($k_5))($a_8), $b_7);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()))($acc_4)))((($f_2)($k_5))($v_6));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(), (($dictApplicative_0)->{'pure'})($GLOBALS['Foreign_Object_empty']), $ms_3);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "FunctorWithIndex0" => function($_dollar__unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Foreign_Object_functorWithIndexObject'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "FoldableWithIndex1" => function($_dollar__unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Foreign_Object_foldableWithIndexObject'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Traversable2" => function($_dollar__unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Foreign_Object_traversableObject'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Foreign_Object_traversableObject
$GLOBALS['Foreign_Object_traversableObject'] = (object)["traverse" => function($dictApplicative_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Semigroupoid_composeImpl'])((($GLOBALS['Foreign_Object_traversableWithIndexObject'])->{'traverseWithIndex'})($dictApplicative_0)))($GLOBALS['Data_Function_const']);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "sequence" => function($dictApplicative_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Foreign_Object_traversableObject'])->{'traverse'})($dictApplicative_0))($GLOBALS['Foreign_Object_identity']);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Functor0" => function($_dollar__unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Foreign_Object_functorObject'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Foldable1" => function($_dollar__unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Foreign_Object_foldableObject'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Foreign_Object_filterWithKey
function majForeign_majObject_filtermajWithmajKey($predicate_0, $m_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_filtermajWithmajKey';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = \Foreign\Object\majForeign_majObject_runmajSmajT(((($GLOBALS['Control_Monad_ST_Internal_bindST'])->{'bind'})($GLOBALS['Foreign_Object_ST_new']))(function($m_prime_2) use ($m_1, $predicate_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Foreign_Object_foldM1'])((function() use ($predicate_0) {
  $__fn = function($acc_3, $k_4 = null, $v_5 = null) use ($predicate_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__t0 = null;;
  if ((($predicate_0)($k_4))($v_5)) {
$__t0 = \Foreign\Object\ST\majForeign_majObject_majSmajT_poke($k_4, $v_5, $acc_3);
goto end_branch_0;;
};
  $__t0 = (($GLOBALS['Control_Monad_ST_Internal_applicativeST'])->{'pure'})($acc_3);
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()))($m_prime_2))($m_1);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Object_filterWithKey'] = __NAMESPACE__ . '\\majForeign_majObject_filtermajWithmajKey';

// Foreign_Object_filterKeys
function majForeign_majObject_filtermajKeys($predicate_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_filtermajKeys';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_filterWithKey'])((($GLOBALS['Control_Semigroupoid_composeImpl'])($GLOBALS['Data_Function_const']))($predicate_0));
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_filterKeys'] = __NAMESPACE__ . '\\majForeign_majObject_filtermajKeys';

// Foreign_Object_filter
function majForeign_majObject_filter($predicate_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_filter';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_filterWithKey'])(function($v_1) use ($predicate_0) {
  $__num = \func_num_args();
  $__res = $predicate_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_filter'] = __NAMESPACE__ . '\\majForeign_majObject_filter';

// Foreign_Object_eqObject
function majForeign_majObject_eqmajObject($dictEq_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_eqmajObject';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["eq" => (function() use ($dictEq_0) {
  $__fn = function($m1_1, $m2_2 = null) use ($dictEq_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ((($GLOBALS['Data_HeytingAlgebra_heytingAlgebraBoolean'])->{'conj'})(\Foreign\Object\majForeign_majObject_ismajSubmap($dictEq_0, $m1_1, $m2_2)))(\Foreign\Object\majForeign_majObject_ismajSubmap($dictEq_0, $m2_2, $m1_1));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_eqObject'] = __NAMESPACE__ . '\\majForeign_majObject_eqmajObject';

// Foreign_Object_ordObject
function majForeign_majObject_ordmajObject($dictOrd_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_ordmajObject';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $eqObject1_1_0 = \Foreign\Object\majForeign_majObject_eqmajObject((($dictOrd_0)->{'Eq0'})(null));
  $__res = (object)["compare" => (function() use ($dictOrd_0) {
  $__fn = function($m1_2, $m2_3 = null) use ($dictOrd_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = (((\Data\Ord\majData_majOrd_ordmajArray(($GLOBALS['Foreign_Object_ordTuple'])($dictOrd_0)))->{'compare'})(\Foreign\Object\majForeign_majObject_tomajAscmajArray($m1_2)))(\Foreign\Object\majForeign_majObject_tomajAscmajArray($m2_3));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(), "Eq0" => function($_dollar__unused_2) use ($eqObject1_1_0) {
  $__num = \func_num_args();
  $__res = $eqObject1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_ordObject'] = __NAMESPACE__ . '\\majForeign_majObject_ordmajObject';

// Foreign_Object_eq1Object
$GLOBALS['Foreign_Object_eq1Object'] = (object)["eq1" => function($dictEq_0) {
  $__num = \func_num_args();
  $__res = (\Foreign\Object\majForeign_majObject_eqmajObject($dictEq_0))->{'eq'};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Foreign_Object_delete
function majForeign_majObject_delete(string $k_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_delete';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Object_mutate'])(($GLOBALS['Foreign_Object_ST_delete'])($k_0));
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Object_delete'] = __NAMESPACE__ . '\\majForeign_majObject_delete';

// Foreign_Object_pop
function majForeign_majObject_pop(string $k_0, $m_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_pop';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ((($GLOBALS['Data_Maybe_functorMaybe'])->{'map'})(function($a_2) use ($k_0, $m_1) {
  $__num = \func_num_args();
  $__res = new \Data\Tuple\Data_Tuple_Tuple($a_2, \Foreign\Object\majForeign_majObject_mutate(($GLOBALS['Foreign_Object_ST_delete'])($k_0), $m_1));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(($GLOBALS['Foreign_Object__lookup'])(new \Data\Maybe\Data_Maybe_Nothing(), $GLOBALS['Data_Maybe_Just'], $k_0, $m_1));
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Object_pop'] = __NAMESPACE__ . '\\majForeign_majObject_pop';

// Foreign_Object_alter
function majForeign_majObject_alter($f_0, $k_1 = null, $m_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_alter';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $v_3_0 = ($f_0)(($GLOBALS['Foreign_Object__lookup'])(new \Data\Maybe\Data_Maybe_Nothing(), $GLOBALS['Data_Maybe_Just'], $k_1, $m_2));
  $__t1 = null;;
  if ($v_3_0 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t1 = \Foreign\Object\majForeign_majObject_mutate(($GLOBALS['Foreign_Object_ST_delete'])($k_1), $m_2);
goto end_branch_1;;
};
  if ($v_3_0 instanceof \Data\Maybe\Data_Maybe_Just) {
$__t1 = \Foreign\Object\majForeign_majObject_mutate((($GLOBALS['Foreign_Object_ST_poke'])($k_1))(($v_3_0)->{'value0'}), $m_2);
goto end_branch_1;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t1 = null;
  end_branch_1:;
  $__res = $__t1;
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Foreign_Object_alter'] = __NAMESPACE__ . '\\majForeign_majObject_alter';

// Foreign_Object_update
function majForeign_majObject_update($f_0, $k_1 = null, $m_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_update';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = \Foreign\Object\majForeign_majObject_alter(function($v2_3) use ($f_0) {
  $__num = \func_num_args();
  $__t0 = null;;
  if ($v2_3 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t0 = new \Data\Maybe\Data_Maybe_Nothing();
goto end_branch_0;;
};
  if ($v2_3 instanceof \Data\Maybe\Data_Maybe_Just) {
$__t0 = ($f_0)(($v2_3)->{'value0'});
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, $k_1, $m_2);
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Foreign_Object_update'] = __NAMESPACE__ . '\\majForeign_majObject_update';

