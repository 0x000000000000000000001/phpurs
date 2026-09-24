<?php

namespace Foreign;

// ALL IMPORTS: Control.Applicative, Control.Monad.Error.Class, Control.Monad.Except, Control.Monad.Except.Trans, Control.Semigroupoid, Data.Boolean, Data.Either, Data.Eq, Data.Function, Data.Functor, Data.HeytingAlgebra, Data.Int, Data.List.NonEmpty, Data.Maybe, Data.Ord, Data.Ordering, Data.Semigroup, Data.Show, Data.String.CodeUnits, Foreign, Prelude, Prim, Unsafe.Coerce
// TO REQUIRE: Control.Applicative, Control.Monad.Error.Class, Control.Monad.Except, Control.Monad.Except.Trans, Control.Semigroupoid, Data.Boolean, Data.Either, Data.Eq, Data.Function, Data.Functor, Data.HeytingAlgebra, Data.Int, Data.List.NonEmpty, Data.Maybe, Data.Ord, Data.Ordering, Data.Semigroup, Data.Show, Data.String.CodeUnits, Foreign, Prelude, Unsafe.Coerce
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Monad.Error.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Except/index.php';
require_once __DIR__ . '/../Control.Monad.Except.Trans/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Boolean/index.php';
require_once __DIR__ . '/../Data.Either/index.php';
require_once __DIR__ . '/../Data.Eq/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Functor/index.php';
require_once __DIR__ . '/../Data.HeytingAlgebra/index.php';
require_once __DIR__ . '/../Data.Int/index.php';
require_once __DIR__ . '/../Data.List.NonEmpty/index.php';
require_once __DIR__ . '/../Data.Maybe/index.php';
require_once __DIR__ . '/../Data.Ord/index.php';
require_once __DIR__ . '/../Data.Ordering/index.php';
require_once __DIR__ . '/../Data.Semigroup/index.php';
require_once __DIR__ . '/../Data.Show/index.php';
require_once __DIR__ . '/../Data.String.CodeUnits/index.php';
require_once __DIR__ . '/../Foreign/index.php';
require_once __DIR__ . '/../Prelude/index.php';
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
if (!\function_exists(__NAMESPACE__ . '\\phpurs_execute_effect')) {
  function phpurs_execute_effect($val) {
    if (\is_callable($val)) {
      return $val($GLOBALS['Data_Unit_unit']);
    }
    return $val;
  }
}
if (!\function_exists(__NAMESPACE__ . '\\phpurs_ref_new')) {
  function phpurs_ref_new($value) {
    return (object)['value' => $value];
  }
  function phpurs_ref_read($ref) {
    return $ref->value;
  }
  function phpurs_ref_write($ref, $value) {
    $ref->value = $value;
    return null;
  }
}

$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };
$ffi_Foreign = \call_user_func(function() {
  $exports = [];
if (!isset($GLOBALS['__Purs_Undefined'])) {
    $GLOBALS['__Purs_Undefined'] = new \stdClass();
}

$exports['typeOf'] = function($value) {
    if ($value === null) {
        return 'object';
    }
    if (is_array($value)) {
        return 'object';
    }
    if (is_bool($value)) {
        return 'boolean';
    }
    if (is_int($value) || is_float($value)) {
        return 'number';
    }
    if (is_string($value)) {
        return 'string';
    }
    if ($value === $GLOBALS['__Purs_Undefined']) {
        return 'undefined';
    }
    if (is_object($value)) {
        if ($value instanceof \Closure) {
            return 'function';
        }
        return 'object';
    }
    if ($value === $GLOBALS['__Purs_Undefined']) {
        return 'undefined';
    }
    return 'undefined';
};

$exports['tagOf'] = function($value) {
    if ($value === $GLOBALS['__Purs_Undefined']) return 'Undefined';
    if ($value === null) return 'Null';
    if (is_array($value)) return 'Array';
    if (is_bool($value)) return 'Boolean';
    if (is_int($value) || is_float($value)) return 'Number';
    if (is_string($value)) return 'String';
    if (is_object($value)) {
        if ($value instanceof \Closure) return 'Function';
        if (get_class($value) === 'Data\JSDate\PursJSDate') return 'Date'; return 'Object';
    }
    return 'Undefined';
};

$exports['isNull'] = function($value) {
    return $value === null;
};

$exports['isUndefined'] = function($value) {
    return $value === $GLOBALS['__Purs_Undefined'];
};

$exports['isArray'] = function($value) {
    return is_array($value);
};

return $exports;
  return $exports;
});
function majForeign_ismajArray($v0): bool|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_ismajArray';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  global $ffi_Foreign;
  $f = (\array_key_exists('isArray', $ffi_Foreign) ? $ffi_Foreign['isArray'] : new class { public function __invoke(...$args) { return $this; } });
  return $f($v0);
}
$GLOBALS['Foreign_isArray'] = __NAMESPACE__ . '\\majForeign_ismajArray';

function majForeign_ismajNull($v0): bool|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_ismajNull';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  global $ffi_Foreign;
  $f = (\array_key_exists('isNull', $ffi_Foreign) ? $ffi_Foreign['isNull'] : new class { public function __invoke(...$args) { return $this; } });
  return $f($v0);
}
$GLOBALS['Foreign_isNull'] = __NAMESPACE__ . '\\majForeign_ismajNull';

function majForeign_ismajUndefined($v0): bool|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_ismajUndefined';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  global $ffi_Foreign;
  $f = (\array_key_exists('isUndefined', $ffi_Foreign) ? $ffi_Foreign['isUndefined'] : new class { public function __invoke(...$args) { return $this; } });
  return $f($v0);
}
$GLOBALS['Foreign_isUndefined'] = __NAMESPACE__ . '\\majForeign_ismajUndefined';

function majForeign_tagmajOf($v0): string|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_tagmajOf';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  global $ffi_Foreign;
  $f = (\array_key_exists('tagOf', $ffi_Foreign) ? $ffi_Foreign['tagOf'] : new class { public function __invoke(...$args) { return $this; } });
  return $f($v0);
}
$GLOBALS['Foreign_tagOf'] = __NAMESPACE__ . '\\majForeign_tagmajOf';

function majForeign_typemajOf($v0): string|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\majForeign_typemajOf';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  global $ffi_Foreign;
  $f = (\array_key_exists('typeOf', $ffi_Foreign) ? $ffi_Foreign['typeOf'] : new class { public function __invoke(...$args) { return $this; } });
  return $f($v0);
}
$GLOBALS['Foreign_typeOf'] = __NAMESPACE__ . '\\majForeign_typemajOf';



final class Foreign_ForeignError { public $tag = 'ForeignError'; public function __construct(public string $value0) {} }
final class Foreign_TypeMismatch { public $tag = 'TypeMismatch'; public function __construct(public string $value0, public string $value1) {} }
final class Foreign_ErrorAtIndex { public $tag = 'ErrorAtIndex'; public function __construct(public int $value0, public  $value1) {} }
final class Foreign_ErrorAtProperty { public $tag = 'ErrorAtProperty'; public function __construct(public string $value0, public  $value1) {} }

// Foreign_ForeignError_closure
$GLOBALS['Foreign_ForeignError_closure'] = function($value0) {
  $__num = \func_num_args();
  $__res = new \Foreign\Foreign_ForeignError($value0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};

// Foreign_ForeignError
function majForeign_majForeignmajError(string $v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majForeignmajError';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_ForeignError_closure'])($v_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_ForeignError'] = __NAMESPACE__ . '\\majForeign_majForeignmajError';

// Foreign_TypeMismatch_closure
$GLOBALS['Foreign_TypeMismatch_closure'] = (function() {
  $__fn = function($value0, $value1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = new \Foreign\Foreign_TypeMismatch($value0, $value1);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();

// Foreign_TypeMismatch
function majForeign_majTypemajMismatch(string $v_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majTypemajMismatch';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($GLOBALS['Foreign_TypeMismatch_closure'])($v_0, $v_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_TypeMismatch'] = __NAMESPACE__ . '\\majForeign_majTypemajMismatch';

// Foreign_ErrorAtIndex_closure
$GLOBALS['Foreign_ErrorAtIndex_closure'] = (function() {
  $__fn = function($value0, $value1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = new \Foreign\Foreign_ErrorAtIndex($value0, $value1);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();

// Foreign_ErrorAtIndex
function majForeign_majErrormajAtmajIndex(int $v_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majErrormajAtmajIndex';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($GLOBALS['Foreign_ErrorAtIndex_closure'])($v_0, $v_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_ErrorAtIndex'] = __NAMESPACE__ . '\\majForeign_majErrormajAtmajIndex';

// Foreign_ErrorAtProperty_closure
$GLOBALS['Foreign_ErrorAtProperty_closure'] = (function() {
  $__fn = function($value0, $value1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = new \Foreign\Foreign_ErrorAtProperty($value0, $value1);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();

// Foreign_ErrorAtProperty
function majForeign_majErrormajAtmajProperty(string $v_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majErrormajAtmajProperty';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($GLOBALS['Foreign_ErrorAtProperty_closure'])($v_0, $v_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_ErrorAtProperty'] = __NAMESPACE__ . '\\majForeign_majErrormajAtmajProperty';

// Foreign_unsafeToForeign_closure
$GLOBALS['Foreign_unsafeToForeign_closure'] = $GLOBALS['Unsafe_Coerce_unsafeCoerce'];

// Foreign_unsafeToForeign
function majForeign_unsafemajTomajForeign($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_unsafemajTomajForeign';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_unsafeToForeign_closure'])($v_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_unsafeToForeign'] = __NAMESPACE__ . '\\majForeign_unsafemajTomajForeign';

// Foreign_unsafeFromForeign_closure
$GLOBALS['Foreign_unsafeFromForeign_closure'] = $GLOBALS['Unsafe_Coerce_unsafeCoerce'];

// Foreign_unsafeFromForeign
function majForeign_unsafemajFrommajForeign($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_unsafemajFrommajForeign';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_unsafeFromForeign_closure'])($v_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_unsafeFromForeign'] = __NAMESPACE__ . '\\majForeign_unsafemajFrommajForeign';

// Foreign_showForeignError
$GLOBALS['Foreign_showForeignError'] = (object)["show" => function($v_0) {
  $__num = \func_num_args();
  $__t0 = null;
  if ($v_0 instanceof \Foreign\Foreign_ForeignError) {
$__t0 = ((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))("(ForeignError "))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))((($GLOBALS['Data_Show_show'])($GLOBALS['Data_Show_showString']))(($v_0)->{'value0'})))(")"));
goto end_branch_0;;
};
  if ($v_0 instanceof \Foreign\Foreign_ErrorAtIndex) {
$__t0 = ((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))("(ErrorAtIndex "))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))((($GLOBALS['Data_Show_show'])($GLOBALS['Data_Show_showInt']))(($v_0)->{'value0'})))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))(" "))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))((($GLOBALS['Data_Show_show'])($GLOBALS['Foreign_showForeignError']))(($v_0)->{'value1'})))(")"))));
goto end_branch_0;;
};
  if ($v_0 instanceof \Foreign\Foreign_ErrorAtProperty) {
$__t0 = ((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))("(ErrorAtProperty "))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))((($GLOBALS['Data_Show_show'])($GLOBALS['Data_Show_showString']))(($v_0)->{'value0'})))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))(" "))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))((($GLOBALS['Data_Show_show'])($GLOBALS['Foreign_showForeignError']))(($v_0)->{'value1'})))(")"))));
goto end_branch_0;;
};
  if ($v_0 instanceof \Foreign\Foreign_TypeMismatch) {
$__t0 = ((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))("(TypeMismatch "))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))((($GLOBALS['Data_Show_show'])($GLOBALS['Data_Show_showString']))(($v_0)->{'value0'})))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))(" "))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))((($GLOBALS['Data_Show_show'])($GLOBALS['Data_Show_showString']))(($v_0)->{'value1'})))(")"))));
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Foreign_renderForeignError
function majForeign_rendermajForeignmajError($v_0): string|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_rendermajForeignmajError';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Foreign_renderForeignError_v_0 = $v_0;
  tco_loop_Foreign_renderForeignError:;
  $v_0 = $__tco_var_Foreign_renderForeignError_v_0;
  $__t0 = null;
  if ($v_0 instanceof \Foreign\Foreign_ForeignError) {
$__t0 = ($v_0)->{'value0'};
goto end_branch_0;;
};
  if ($v_0 instanceof \Foreign\Foreign_ErrorAtIndex) {
$__t0 = ((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))("Error at array index "))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))((($GLOBALS['Data_Show_show'])($GLOBALS['Data_Show_showInt']))(($v_0)->{'value0'})))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))(": "))(\Foreign\majForeign_rendermajForeignmajError(($v_0)->{'value1'}))));
goto end_branch_0;;
};
  if ($v_0 instanceof \Foreign\Foreign_ErrorAtProperty) {
$__t0 = ((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))("Error at property "))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))((($GLOBALS['Data_Show_show'])($GLOBALS['Data_Show_showString']))(($v_0)->{'value0'})))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))(": "))(\Foreign\majForeign_rendermajForeignmajError(($v_0)->{'value1'}))));
goto end_branch_0;;
};
  if ($v_0 instanceof \Foreign\Foreign_TypeMismatch) {
$__t0 = ((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))("Type mismatch: expected "))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))(($v_0)->{'value0'}))(((($GLOBALS['Data_Semigroup_append'])($GLOBALS['Data_Semigroup_semigroupString']))(", found "))(($v_0)->{'value1'})));
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
$GLOBALS['Foreign_renderForeignError'] = __NAMESPACE__ . '\\majForeign_rendermajForeignmajError';

// Foreign_readUndefined
function majForeign_readmajUndefined($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_readmajUndefined';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeExceptT_1_0 = ($GLOBALS['Control_Monad_Except_Trans_applicativeExceptT'])($dictMonad_0);
  $__res = function($value_2) use ($applicativeExceptT_1_0) {
  $__num = \func_num_args();
  $__t1 = null;
  if (\Foreign\majForeign_ismajUndefined($value_2)) {
$__t1 = (($GLOBALS['Control_Applicative_pure'])($applicativeExceptT_1_0))($GLOBALS['Data_Maybe_Nothing']);
goto end_branch_1;;
};
  if ($GLOBALS['Data_Boolean_otherwise']) {
$__t1 = (($GLOBALS['Control_Applicative_pure'])($applicativeExceptT_1_0))(($GLOBALS['Data_Maybe_Just'])($value_2));
goto end_branch_1;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t1 = null;
  end_branch_1:;
  $__res = $__t1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_readUndefined'] = __NAMESPACE__ . '\\majForeign_readmajUndefined';

// Foreign_readNullOrUndefined
function majForeign_readmajNullmajOrmajUndefined($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_readmajNullmajOrmajUndefined';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeExceptT_1_0 = ($GLOBALS['Control_Monad_Except_Trans_applicativeExceptT'])($dictMonad_0);
  $__res = function($value_2) use ($applicativeExceptT_1_0) {
  $__num = \func_num_args();
  $__t1 = null;
  if (((($GLOBALS['Data_HeytingAlgebra_disj'])($GLOBALS['Data_HeytingAlgebra_heytingAlgebraBoolean']))(\Foreign\majForeign_ismajNull($value_2)))(\Foreign\majForeign_ismajUndefined($value_2))) {
$__t1 = (($GLOBALS['Control_Applicative_pure'])($applicativeExceptT_1_0))($GLOBALS['Data_Maybe_Nothing']);
goto end_branch_1;;
};
  if ($GLOBALS['Data_Boolean_otherwise']) {
$__t1 = (($GLOBALS['Control_Applicative_pure'])($applicativeExceptT_1_0))(($GLOBALS['Data_Maybe_Just'])($value_2));
goto end_branch_1;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t1 = null;
  end_branch_1:;
  $__res = $__t1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_readNullOrUndefined'] = __NAMESPACE__ . '\\majForeign_readmajNullmajOrmajUndefined';

// Foreign_readNull
function majForeign_readmajNull($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_readmajNull';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeExceptT_1_0 = ($GLOBALS['Control_Monad_Except_Trans_applicativeExceptT'])($dictMonad_0);
  $__res = function($value_2) use ($applicativeExceptT_1_0) {
  $__num = \func_num_args();
  $__t1 = null;
  if (\Foreign\majForeign_ismajNull($value_2)) {
$__t1 = (($GLOBALS['Control_Applicative_pure'])($applicativeExceptT_1_0))($GLOBALS['Data_Maybe_Nothing']);
goto end_branch_1;;
};
  if ($GLOBALS['Data_Boolean_otherwise']) {
$__t1 = (($GLOBALS['Control_Applicative_pure'])($applicativeExceptT_1_0))(($GLOBALS['Data_Maybe_Just'])($value_2));
goto end_branch_1;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t1 = null;
  end_branch_1:;
  $__res = $__t1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_readNull'] = __NAMESPACE__ . '\\majForeign_readmajNull';

// Foreign_fail
function majForeign_fail($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_fail';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($GLOBALS['Control_Monad_Error_Class_throwError'])(($GLOBALS['Control_Monad_Except_Trans_monadThrowExceptT'])($dictMonad_0))))($GLOBALS['Data_List_NonEmpty_singleton']);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_fail'] = __NAMESPACE__ . '\\majForeign_fail';

// Foreign_readArray
function majForeign_readmajArray($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_readmajArray';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure2_1_0 = ($GLOBALS['Control_Applicative_pure'])(($GLOBALS['Control_Monad_Except_Trans_applicativeExceptT'])($dictMonad_0));
  $fail1_2_1 = ($GLOBALS['Foreign_fail'])($dictMonad_0);
  $__res = function($value_3) use ($fail1_2_1, $pure2_1_0) {
  $__num = \func_num_args();
  $__t2 = null;
  if (\Foreign\majForeign_ismajArray($value_3)) {
$__t2 = (($GLOBALS['Data_Function_apply'])($pure2_1_0))($value_3);
goto end_branch_2;;
};
  if ($GLOBALS['Data_Boolean_otherwise']) {
$__t2 = (($GLOBALS['Data_Function_apply'])($fail1_2_1))(new \Foreign\Foreign_TypeMismatch("array", \Foreign\majForeign_tagmajOf($value_3)));
goto end_branch_2;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t2 = null;
  end_branch_2:;
  $__res = $__t2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_readArray'] = __NAMESPACE__ . '\\majForeign_readmajArray';

// Foreign_unsafeReadTagged
function majForeign_unsafemajReadmajTagged($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_unsafemajReadmajTagged';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeExceptT_1_0 = ($GLOBALS['Control_Monad_Except_Trans_applicativeExceptT'])($dictMonad_0);
  $fail1_2_1 = ($GLOBALS['Foreign_fail'])($dictMonad_0);
  $__res = function($tag_3) use ($applicativeExceptT_1_0, $fail1_2_1) {
  $__num = \func_num_args();
  $__res = function($value_4) use ($applicativeExceptT_1_0, $fail1_2_1, $tag_3) {
  $__num = \func_num_args();
  $__t2 = null;
  if (((($GLOBALS['Data_Eq_eq'])($GLOBALS['Data_Eq_eqString']))(\Foreign\majForeign_tagmajOf($value_4)))($tag_3)) {
$__t2 = (($GLOBALS['Control_Applicative_pure'])($applicativeExceptT_1_0))($value_4);
goto end_branch_2;;
};
  if ($GLOBALS['Data_Boolean_otherwise']) {
$__t2 = (($GLOBALS['Data_Function_apply'])($fail1_2_1))(new \Foreign\Foreign_TypeMismatch($tag_3, \Foreign\majForeign_tagmajOf($value_4)));
goto end_branch_2;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t2 = null;
  end_branch_2:;
  $__res = $__t2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_unsafeReadTagged'] = __NAMESPACE__ . '\\majForeign_unsafemajReadmajTagged';

// Foreign_readBoolean
function majForeign_readmajBoolean($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_readmajBoolean';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (($GLOBALS['Foreign_unsafeReadTagged'])($dictMonad_0))("Boolean");
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_readBoolean'] = __NAMESPACE__ . '\\majForeign_readmajBoolean';

// Foreign_readNumber
function majForeign_readmajNumber($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_readmajNumber';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (($GLOBALS['Foreign_unsafeReadTagged'])($dictMonad_0))("Number");
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_readNumber'] = __NAMESPACE__ . '\\majForeign_readmajNumber';

// Foreign_readInt
function majForeign_readmajInt($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_readmajInt';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $go__map_1_0 = ($GLOBALS['Data_Functor_map'])((((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null));
  $__res = function($value_2) use ($dictMonad_0, $go__map_1_0) {
  $__num = \func_num_args();
  $error_3_1 = (($GLOBALS['Data_Function_apply'])($GLOBALS['Data_Either_Left']))((($GLOBALS['Data_Function_apply'])($GLOBALS['Data_List_NonEmpty_singleton']))(new \Foreign\Foreign_TypeMismatch("Int", \Foreign\majForeign_tagmajOf($value_2))));
  $__res = ((($GLOBALS['Data_Function_apply'])($go__map_1_0))((($GLOBALS['Data_Either_either'])(($GLOBALS['Data_Function_const'])($error_3_1)))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Data_Maybe_maybe'])($error_3_1))(($GLOBALS['Control_Applicative_pure'])($GLOBALS['Data_Either_applicativeEither']))))($GLOBALS['Data_Int_fromNumber']))))(((($GLOBALS['Foreign_unsafeReadTagged'])($dictMonad_0))("Number"))($value_2));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_readInt'] = __NAMESPACE__ . '\\majForeign_readmajInt';

// Foreign_readString
function majForeign_readmajString($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_readmajString';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (($GLOBALS['Foreign_unsafeReadTagged'])($dictMonad_0))("String");
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_readString'] = __NAMESPACE__ . '\\majForeign_readmajString';

// Foreign_readChar
function majForeign_readmajChar($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_readmajChar';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $go__map_1_0 = ($GLOBALS['Data_Functor_map'])((((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null));
  $__res = function($value_2) use ($dictMonad_0, $go__map_1_0) {
  $__num = \func_num_args();
  $error_3_1 = (($GLOBALS['Data_Function_apply'])($GLOBALS['Data_Either_Left']))((($GLOBALS['Data_Function_apply'])($GLOBALS['Data_List_NonEmpty_singleton']))(new \Foreign\Foreign_TypeMismatch("Char", \Foreign\majForeign_tagmajOf($value_2))));
  $__res = ((($GLOBALS['Data_Function_apply'])($go__map_1_0))((($GLOBALS['Data_Either_either'])(($GLOBALS['Data_Function_const'])($error_3_1)))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Data_Maybe_maybe'])($error_3_1))(($GLOBALS['Control_Applicative_pure'])($GLOBALS['Data_Either_applicativeEither']))))($GLOBALS['Data_String_CodeUnits_toChar']))))(((($GLOBALS['Foreign_unsafeReadTagged'])($dictMonad_0))("String"))($value_2));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_readChar'] = __NAMESPACE__ . '\\majForeign_readmajChar';

// Foreign_eqForeignError
$GLOBALS['Foreign_eqForeignError'] = (object)["eq" => function($x_0) {
  $__num = \func_num_args();
  $__res = function($y_1) use ($x_0) {
  $__num = \func_num_args();
  $__t0 = null;
  if ($x_0 instanceof \Foreign\Foreign_ForeignError) {
$__t0 = ($y_1 instanceof \Foreign\Foreign_ForeignError && ((($GLOBALS['Data_Eq_eq'])($GLOBALS['Data_Eq_eqString']))(($x_0)->{'value0'}))(($y_1)->{'value0'}));
goto end_branch_0;;
};
  if ($x_0 instanceof \Foreign\Foreign_TypeMismatch) {
$__t0 = ($y_1 instanceof \Foreign\Foreign_TypeMismatch && ((($GLOBALS['Data_HeytingAlgebra_conj'])($GLOBALS['Data_HeytingAlgebra_heytingAlgebraBoolean']))(((($GLOBALS['Data_Eq_eq'])($GLOBALS['Data_Eq_eqString']))(($x_0)->{'value0'}))(($y_1)->{'value0'})))(((($GLOBALS['Data_Eq_eq'])($GLOBALS['Data_Eq_eqString']))(($x_0)->{'value1'}))(($y_1)->{'value1'})));
goto end_branch_0;;
};
  if ($x_0 instanceof \Foreign\Foreign_ErrorAtIndex) {
$__t0 = ($y_1 instanceof \Foreign\Foreign_ErrorAtIndex && ((($GLOBALS['Data_HeytingAlgebra_conj'])($GLOBALS['Data_HeytingAlgebra_heytingAlgebraBoolean']))(((($GLOBALS['Data_Eq_eq'])($GLOBALS['Data_Eq_eqInt']))(($x_0)->{'value0'}))(($y_1)->{'value0'})))(((($GLOBALS['Data_Eq_eq'])($GLOBALS['Foreign_eqForeignError']))(($x_0)->{'value1'}))(($y_1)->{'value1'})));
goto end_branch_0;;
};
  $__t0 = ($x_0 instanceof \Foreign\Foreign_ErrorAtProperty && ($y_1 instanceof \Foreign\Foreign_ErrorAtProperty && ((($GLOBALS['Data_HeytingAlgebra_conj'])($GLOBALS['Data_HeytingAlgebra_heytingAlgebraBoolean']))(((($GLOBALS['Data_Eq_eq'])($GLOBALS['Data_Eq_eqString']))(($x_0)->{'value0'}))(($y_1)->{'value0'})))(((($GLOBALS['Data_Eq_eq'])($GLOBALS['Foreign_eqForeignError']))(($x_0)->{'value1'}))(($y_1)->{'value1'}))));
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Foreign_ordForeignError
$GLOBALS['Foreign_ordForeignError'] = (object)["compare" => function($x_0) {
  $__num = \func_num_args();
  $__res = function($y_1) use ($x_0) {
  $__num = \func_num_args();
  $__t0 = null;
  if ($x_0 instanceof \Foreign\Foreign_ForeignError) {
$__t1 = null;
if ($y_1 instanceof \Foreign\Foreign_ForeignError) {
$__t1 = ((($GLOBALS['Data_Ord_compare'])($GLOBALS['Data_Ord_ordString']))(($x_0)->{'value0'}))(($y_1)->{'value0'});
goto end_branch_1;;
};
$__t1 = $GLOBALS['Data_Ordering_LT'];
end_branch_1:;
$__t0 = $__t1;
goto end_branch_0;;
};
  if ($y_1 instanceof \Foreign\Foreign_ForeignError) {
$__t0 = $GLOBALS['Data_Ordering_GT'];
goto end_branch_0;;
};
  if ($x_0 instanceof \Foreign\Foreign_TypeMismatch) {
$__t2 = null;
if ($y_1 instanceof \Foreign\Foreign_TypeMismatch) {
$v_2_3 = ((($GLOBALS['Data_Ord_compare'])($GLOBALS['Data_Ord_ordString']))(($x_0)->{'value0'}))(($y_1)->{'value0'});
$__t4 = null;
if ($v_2_3 instanceof \Data\Ordering\Data_Ordering_LT) {
$__t4 = $GLOBALS['Data_Ordering_LT'];
goto end_branch_4;;
};
if ($v_2_3 instanceof \Data\Ordering\Data_Ordering_GT) {
$__t4 = $GLOBALS['Data_Ordering_GT'];
goto end_branch_4;;
};
$__t4 = ((($GLOBALS['Data_Ord_compare'])($GLOBALS['Data_Ord_ordString']))(($x_0)->{'value1'}))(($y_1)->{'value1'});
end_branch_4:;
$__t2 = $__t4;
goto end_branch_2;;
};
$__t2 = $GLOBALS['Data_Ordering_LT'];
end_branch_2:;
$__t0 = $__t2;
goto end_branch_0;;
};
  if ($y_1 instanceof \Foreign\Foreign_TypeMismatch) {
$__t0 = $GLOBALS['Data_Ordering_GT'];
goto end_branch_0;;
};
  if ($x_0 instanceof \Foreign\Foreign_ErrorAtIndex) {
$__t5 = null;
if ($y_1 instanceof \Foreign\Foreign_ErrorAtIndex) {
$v_2_6 = ((($GLOBALS['Data_Ord_compare'])($GLOBALS['Data_Ord_ordInt']))(($x_0)->{'value0'}))(($y_1)->{'value0'});
$__t7 = null;
if ($v_2_6 instanceof \Data\Ordering\Data_Ordering_LT) {
$__t7 = $GLOBALS['Data_Ordering_LT'];
goto end_branch_7;;
};
if ($v_2_6 instanceof \Data\Ordering\Data_Ordering_GT) {
$__t7 = $GLOBALS['Data_Ordering_GT'];
goto end_branch_7;;
};
$__t7 = ((($GLOBALS['Data_Ord_compare'])($GLOBALS['Foreign_ordForeignError']))(($x_0)->{'value1'}))(($y_1)->{'value1'});
end_branch_7:;
$__t5 = $__t7;
goto end_branch_5;;
};
$__t5 = $GLOBALS['Data_Ordering_LT'];
end_branch_5:;
$__t0 = $__t5;
goto end_branch_0;;
};
  if ($y_1 instanceof \Foreign\Foreign_ErrorAtIndex) {
$__t0 = $GLOBALS['Data_Ordering_GT'];
goto end_branch_0;;
};
  if (($x_0 instanceof \Foreign\Foreign_ErrorAtProperty && $y_1 instanceof \Foreign\Foreign_ErrorAtProperty)) {
$v_2_8 = ((($GLOBALS['Data_Ord_compare'])($GLOBALS['Data_Ord_ordString']))(($x_0)->{'value0'}))(($y_1)->{'value0'});
$__t9 = null;
if ($v_2_8 instanceof \Data\Ordering\Data_Ordering_LT) {
$__t9 = $GLOBALS['Data_Ordering_LT'];
goto end_branch_9;;
};
if ($v_2_8 instanceof \Data\Ordering\Data_Ordering_GT) {
$__t9 = $GLOBALS['Data_Ordering_GT'];
goto end_branch_9;;
};
$__t9 = ((($GLOBALS['Data_Ord_compare'])($GLOBALS['Foreign_ordForeignError']))(($x_0)->{'value1'}))(($y_1)->{'value1'});
end_branch_9:;
$__t0 = $__t9;
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Eq0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Foreign_eqForeignError'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

