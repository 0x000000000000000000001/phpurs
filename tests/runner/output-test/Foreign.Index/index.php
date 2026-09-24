<?php

namespace Foreign\Index;

// ALL IMPORTS: Control.Applicative, Control.Bind, Control.Monad.Except.Trans, Data.Eq, Data.Function, Data.Function.Uncurried, Data.HeytingAlgebra, Data.List.NonEmpty, Foreign, Foreign.Index, Prelude, Prim
// TO REQUIRE: Control.Applicative, Control.Bind, Control.Monad.Except.Trans, Data.Eq, Data.Function, Data.Function.Uncurried, Data.HeytingAlgebra, Data.List.NonEmpty, Foreign, Foreign.Index, Prelude
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Monad.Except.Trans/index.php';
require_once __DIR__ . '/../Data.Eq/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Function.Uncurried/index.php';
require_once __DIR__ . '/../Data.HeytingAlgebra/index.php';
require_once __DIR__ . '/../Data.List.NonEmpty/index.php';
require_once __DIR__ . '/../Foreign/index.php';
require_once __DIR__ . '/../Foreign.Index/index.php';
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
$ffi_Foreign_Index = \call_user_func(function() {
  $exports = [];
$exports['unsafeReadPropImpl'] = function($f, $s, $key, $value) {
    if ($value === null || $value === $GLOBALS['__Purs_Undefined']) {
        return $f;
    }
    if (is_array($value)) {
        if (array_key_exists($key, $value)) {
            return $s($value[$key]);
        }
        return $s($GLOBALS['__Purs_Undefined']);
    }
    if (is_object($value)) {
        if (property_exists($value, $key)) {
            return $s($value->$key);
        }
        // If property is accessed on an object that implements ArrayAccess or similar, we might need special handling.
        // But for normal objects:
        return $s($GLOBALS['__Purs_Undefined']); 
    }
    return $s($GLOBALS['__Purs_Undefined']);
};

$exports['unsafeHasOwnProperty'] = function($prop, $value) {
    if (is_array($value)) {
        return array_key_exists($prop, $value);
    }
    if (is_object($value)) {
        return property_exists($value, $prop);
    }
    return false;
};

$exports['unsafeHasProperty'] = function($prop, $value) {
    if (is_array($value)) {
        return array_key_exists($prop, $value);
    }
    if (is_object($value)) {
        return property_exists($value, $prop);
    }
    return false;
};

return $exports;
  return $exports;
});
$GLOBALS['Foreign_Index_unsafeHasOwnProperty'] = (\array_key_exists('unsafeHasOwnProperty', $ffi_Foreign_Index) ? $ffi_Foreign_Index['unsafeHasOwnProperty'] : new class { public function __invoke(...$args) { return $this; } });
$GLOBALS['Foreign_Index_unsafeHasProperty'] = (\array_key_exists('unsafeHasProperty', $ffi_Foreign_Index) ? $ffi_Foreign_Index['unsafeHasProperty'] : new class { public function __invoke(...$args) { return $this; } });
$GLOBALS['Foreign_Index_unsafeReadPropImpl'] = (\array_key_exists('unsafeReadPropImpl', $ffi_Foreign_Index) ? $ffi_Foreign_Index['unsafeReadPropImpl'] : new class { public function __invoke(...$args) { return $this; } });




// Foreign_Index_unsafeReadProp
function majForeign_majIndex_unsafemajReadmajProp($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_unsafemajReadmajProp';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])(($GLOBALS['Control_Monad_Except_Trans_applicativeExceptT'])($dictMonad_0));
  $__res = function($k_2) use ($dictMonad_0, $pure_1_0) {
  $__num = \func_num_args();
  $__res = function($value_3) use ($dictMonad_0, $k_2, $pure_1_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Foreign_Index_unsafeReadPropImpl'])((((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($GLOBALS['Control_Monad_Error_Class_throwError'])(($GLOBALS['Control_Monad_Except_Trans_monadThrowExceptT'])($dictMonad_0))))($GLOBALS['Data_List_NonEmpty_singleton']))(new \Foreign\Foreign_TypeMismatch("object", \Foreign\majForeign_typemajOf($value_3))), $pure_1_0, $k_2, $value_3);
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
$GLOBALS['Foreign_Index_unsafeReadProp'] = __NAMESPACE__ . '\\majForeign_majIndex_unsafemajReadmajProp';

// Foreign_Index_readProp
function majForeign_majIndex_readmajProp($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_readmajProp';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Index_unsafeReadProp'])($dictMonad_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_readProp'] = __NAMESPACE__ . '\\majForeign_majIndex_readmajProp';

// Foreign_Index_readIndex
function majForeign_majIndex_readmajIndex($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_readmajIndex';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($GLOBALS['Foreign_Index_unsafeReadProp'])($dictMonad_0);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_readIndex'] = __NAMESPACE__ . '\\majForeign_majIndex_readmajIndex';

// Foreign_Index_ix
function majForeign_majIndex_ix($dict_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_ix';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($dict_0)->{'ix'};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_ix'] = __NAMESPACE__ . '\\majForeign_majIndex_ix';

// Foreign_Index_index
function majForeign_majIndex_index($dict_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_index';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($dict_0)->{'index'};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_index'] = __NAMESPACE__ . '\\majForeign_majIndex_index';

// Foreign_Index_indexableExceptT
function majForeign_majIndex_indexablemajExceptmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_indexablemajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $bindExceptT_1_0 = ($GLOBALS['Control_Monad_Except_Trans_bindExceptT'])($dictMonad_0);
  $__res = (object)["ix" => function($dictIndex_2) use ($bindExceptT_1_0) {
  $__num = \func_num_args();
  $index1_3_1 = ($GLOBALS['Foreign_Index_index'])($dictIndex_2);
  $__res = function($f_4) use ($bindExceptT_1_0, $index1_3_1) {
  $__num = \func_num_args();
  $__res = function($i_5) use ($bindExceptT_1_0, $f_4, $index1_3_1) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bindFlipped'])($bindExceptT_1_0))((($GLOBALS['Data_Function_flip'])($index1_3_1))($i_5)))($f_4);
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
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_indexableExceptT'] = __NAMESPACE__ . '\\majForeign_majIndex_indexablemajExceptmajT';

// Foreign_Index_indexableForeign
function majForeign_majIndex_indexablemajForeign($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_indexablemajForeign';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["ix" => function($dictIndex_1) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Foreign_Index_index'])($dictIndex_1);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_indexableForeign'] = __NAMESPACE__ . '\\majForeign_majIndex_indexablemajForeign';

// Foreign_Index_hasPropertyImpl
function majForeign_majIndex_hasmajPropertymajImpl($v_0, $v1_1 = null): bool|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_hasmajPropertymajImpl';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__t0 = null;
  if (\Foreign\majForeign_ismajNull($v1_1)) {
$__t0 = false;
goto end_branch_0;;
};
  if (\Foreign\majForeign_ismajUndefined($v1_1)) {
$__t0 = false;
goto end_branch_0;;
};
  $__t0 = (((($GLOBALS['Data_HeytingAlgebra_disj'])($GLOBALS['Data_HeytingAlgebra_heytingAlgebraBoolean']))(((($GLOBALS['Data_Eq_eq'])($GLOBALS['Data_Eq_eqString']))(\Foreign\majForeign_typemajOf($v1_1)))("object")))(((($GLOBALS['Data_Eq_eq'])($GLOBALS['Data_Eq_eqString']))(\Foreign\majForeign_typemajOf($v1_1)))("function")) && ($GLOBALS['Foreign_Index_unsafeHasProperty'])($v_0, $v1_1));
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Index_hasPropertyImpl'] = __NAMESPACE__ . '\\majForeign_majIndex_hasmajPropertymajImpl';

// Foreign_Index_hasProperty
function majForeign_majIndex_hasmajProperty($dict_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_hasmajProperty';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($dict_0)->{'hasProperty'};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_hasProperty'] = __NAMESPACE__ . '\\majForeign_majIndex_hasmajProperty';

// Foreign_Index_hasOwnPropertyImpl
function majForeign_majIndex_hasmajOwnmajPropertymajImpl($v_0, $v1_1 = null): bool|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_hasmajOwnmajPropertymajImpl';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__t0 = null;
  if (\Foreign\majForeign_ismajNull($v1_1)) {
$__t0 = false;
goto end_branch_0;;
};
  if (\Foreign\majForeign_ismajUndefined($v1_1)) {
$__t0 = false;
goto end_branch_0;;
};
  $__t0 = (((($GLOBALS['Data_HeytingAlgebra_disj'])($GLOBALS['Data_HeytingAlgebra_heytingAlgebraBoolean']))(((($GLOBALS['Data_Eq_eq'])($GLOBALS['Data_Eq_eqString']))(\Foreign\majForeign_typemajOf($v1_1)))("object")))(((($GLOBALS['Data_Eq_eq'])($GLOBALS['Data_Eq_eqString']))(\Foreign\majForeign_typemajOf($v1_1)))("function")) && ($GLOBALS['Foreign_Index_unsafeHasOwnProperty'])($v_0, $v1_1));
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Foreign_Index_hasOwnPropertyImpl'] = __NAMESPACE__ . '\\majForeign_majIndex_hasmajOwnmajPropertymajImpl';

// Foreign_Index_indexInt
function majForeign_majIndex_indexmajInt($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_indexmajInt';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["index" => ($GLOBALS['Data_Function_flip'])(($GLOBALS['Foreign_Index_readIndex'])($dictMonad_0)), "hasProperty" => $GLOBALS['Foreign_Index_hasPropertyImpl'], "hasOwnProperty" => $GLOBALS['Foreign_Index_hasOwnPropertyImpl'], "errorAt" => $GLOBALS['Foreign_ErrorAtIndex']];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_indexInt'] = __NAMESPACE__ . '\\majForeign_majIndex_indexmajInt';

// Foreign_Index_indexString
function majForeign_majIndex_indexmajString($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_indexmajString';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["index" => ($GLOBALS['Data_Function_flip'])(($GLOBALS['Foreign_Index_readProp'])($dictMonad_0)), "hasProperty" => $GLOBALS['Foreign_Index_hasPropertyImpl'], "hasOwnProperty" => $GLOBALS['Foreign_Index_hasOwnPropertyImpl'], "errorAt" => $GLOBALS['Foreign_ErrorAtProperty']];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_indexString'] = __NAMESPACE__ . '\\majForeign_majIndex_indexmajString';

// Foreign_Index_hasOwnProperty
function majForeign_majIndex_hasmajOwnmajProperty($dict_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_hasmajOwnmajProperty';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($dict_0)->{'hasOwnProperty'};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_hasOwnProperty'] = __NAMESPACE__ . '\\majForeign_majIndex_hasmajOwnmajProperty';

// Foreign_Index_errorAt
function majForeign_majIndex_errormajAt($dict_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majForeign_majIndex_errormajAt';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($dict_0)->{'errorAt'};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Foreign_Index_errorAt'] = __NAMESPACE__ . '\\majForeign_majIndex_errormajAt';

