<?php

namespace Foreign\Object\ST;

// ALL IMPORTS: Control.Monad.ST, Data.Maybe, Foreign.Object.ST, Prim
// TO REQUIRE: Control.Monad.ST, Data.Maybe, Foreign.Object.ST
require_once __DIR__ . '/../Control.Monad.ST/index.php';
require_once __DIR__ . '/../Data.Maybe/index.php';
require_once __DIR__ . '/../Foreign.Object.ST/index.php';

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
$ffi_Foreign_Object_ST = \call_user_func(function() {
  $exports = [];
$new = function() {
    return new \stdClass();
};

$peekImpl = function($just, $nothing = null, $k = null, $m = null) use (&$peekImpl) {
    if (\func_num_args() < 4) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$peekImpl) {

            return $peekImpl(...\array_merge($__args, $more));
        };
    }
    return function() use ($just, $nothing, $k, $m) {
        return property_exists($m, $k) ? $just($m->$k) : $nothing;
    };
};

$poke = function($k, $v = null, $m = null) use (&$poke) {
    if (\func_num_args() < 3) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$poke) {

            return $poke(...\array_merge($__args, $more));
        };
    }
    return function() use ($k, $v, $m) {
        $m->$k = $v;
        return $m;
    };
};

$delete = function($k, $m = null) use (&$delete) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$delete) {

            return $delete(...\array_merge($__args, $more));
        };
    }
    return function() use ($k, $m) {
        unset($m->$k);
        return $m;
    };
};

$exports['new'] = $new;
$exports['peekImpl'] = $peekImpl;
$exports['poke'] = $poke;
$exports['delete'] = $delete;
return $exports;
  return $exports;
});
function majForeign_majObject_majSmajT_delete(string $v0, $v1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_majObject_majSmajT_delete';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  global $ffi_Foreign_Object_ST;
  $f = ($ffi_Foreign_Object_ST['delete'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0, $v1);
}
$GLOBALS['Foreign_Object_ST_delete'] = __NAMESPACE__ . '\\majForeign_majObject_majSmajT_delete';

$GLOBALS['Foreign_Object_ST_new'] = ($ffi_Foreign_Object_ST['new'] ?? new class { public function __invoke(...$args) { return $this; } });
function majForeign_majObject_majSmajT_peekmajImpl($v0, $v1 = null, $v2 = null, $v3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_majObject_majSmajT_peekmajImpl';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  global $ffi_Foreign_Object_ST;
  $f = ($ffi_Foreign_Object_ST['peekImpl'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0, $v1, $v2, $v3);
}
$GLOBALS['Foreign_Object_ST_peekImpl'] = __NAMESPACE__ . '\\majForeign_majObject_majSmajT_peekmajImpl';

function majForeign_majObject_majSmajT_poke(string $v0, $v1 = null, $v2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_majObject_majSmajT_poke';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  global $ffi_Foreign_Object_ST;
  $f = ($ffi_Foreign_Object_ST['poke'] ?? new class { public function __invoke(...$args) { return $this; } });
  return $f($v0, $v1, $v2);
}
$GLOBALS['Foreign_Object_ST_poke'] = __NAMESPACE__ . '\\majForeign_majObject_majSmajT_poke';





// Foreign_Object_ST_peek_closure
$GLOBALS['Foreign_Object_ST_peek_closure'] = (($GLOBALS['Foreign_Object_ST_peekImpl'])($GLOBALS['Data_Maybe_Just']))(new \Data\Maybe\Data_Maybe_Nothing());

// Foreign_Object_ST_peek
function majForeign_majObject_majSmajT_peek(string $v_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majObject_majSmajT_peek';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($GLOBALS['Foreign_Object_ST_peek_closure'])($v_0, $v_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Object_ST_peek'] = __NAMESPACE__ . '\\majForeign_majObject_majSmajT_peek';

