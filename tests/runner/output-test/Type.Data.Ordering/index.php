<?php

namespace Type\Data\Ordering;

// ALL IMPORTS: Data.Ordering, Prim, Prim.Ordering, Type.Data.Boolean, Type.Data.Ordering, Type.Proxy
// TO REQUIRE: Data.Ordering, Type.Data.Boolean, Type.Data.Ordering, Type.Proxy
require_once __DIR__ . '/../Data.Ordering/index.php';
require_once __DIR__ . '/../Type.Data.Boolean/index.php';
require_once __DIR__ . '/../Type.Data.Ordering/index.php';
require_once __DIR__ . '/../Type.Proxy/index.php';

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




// Type_Data_Ordering_reflectOrdering
function majType_majData_majOrdering_reflectmajOrdering($dict_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majType_majData_majOrdering_reflectmajOrdering';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($dict_0)->{'reflectOrdering'};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Type_Data_Ordering_reflectOrdering'] = __NAMESPACE__ . '\\majType_majData_majOrdering_reflectmajOrdering';

// Type_Data_Ordering_isOrderingLT
$GLOBALS['Type_Data_Ordering_isOrderingLT'] = (object)["reflectOrdering" => function($v_0) {
  $__num = \func_num_args();
  $__res = new \Data\Ordering\Data_Ordering_LT();
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Type_Data_Ordering_isOrderingGT
$GLOBALS['Type_Data_Ordering_isOrderingGT'] = (object)["reflectOrdering" => function($v_0) {
  $__num = \func_num_args();
  $__res = new \Data\Ordering\Data_Ordering_GT();
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Type_Data_Ordering_isOrderingEQ
$GLOBALS['Type_Data_Ordering_isOrderingEQ'] = (object)["reflectOrdering" => function($v_0) {
  $__num = \func_num_args();
  $__res = new \Data\Ordering\Data_Ordering_EQ();
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Type_Data_Ordering_reifyOrdering
function majType_majData_majOrdering_reifymajOrdering($v_0, $v1_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majType_majData_majOrdering_reifymajOrdering';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__t0 = null;;
  if ($v_0 instanceof \Data\Ordering\Data_Ordering_LT) {
$__t0 = (($v1_1)($GLOBALS['Type_Data_Ordering_isOrderingLT']))(new \Type\Proxy\Type_Proxy_Proxy());
goto end_branch_0;;
};
  if ($v_0 instanceof \Data\Ordering\Data_Ordering_EQ) {
$__t0 = (($v1_1)($GLOBALS['Type_Data_Ordering_isOrderingEQ']))(new \Type\Proxy\Type_Proxy_Proxy());
goto end_branch_0;;
};
  if ($v_0 instanceof \Data\Ordering\Data_Ordering_GT) {
$__t0 = (($v1_1)($GLOBALS['Type_Data_Ordering_isOrderingGT']))(new \Type\Proxy\Type_Proxy_Proxy());
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Type_Data_Ordering_reifyOrdering'] = __NAMESPACE__ . '\\majType_majData_majOrdering_reifymajOrdering';

// Type_Data_Ordering_invertOrderingLT
$GLOBALS['Type_Data_Ordering_invertOrderingLT'] = (object)[];

// Type_Data_Ordering_invertOrderingGT
$GLOBALS['Type_Data_Ordering_invertOrderingGT'] = (object)[];

// Type_Data_Ordering_invertOrderingEQ
$GLOBALS['Type_Data_Ordering_invertOrderingEQ'] = (object)[];

// Type_Data_Ordering_invert
function majType_majData_majOrdering_invert($_dollar__unused_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majType_majData_majOrdering_invert';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = new \Type\Proxy\Type_Proxy_Proxy();
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Type_Data_Ordering_invert'] = __NAMESPACE__ . '\\majType_majData_majOrdering_invert';

// Type_Data_Ordering_equalsLTLT
$GLOBALS['Type_Data_Ordering_equalsLTLT'] = (object)[];

// Type_Data_Ordering_equalsLTGT
$GLOBALS['Type_Data_Ordering_equalsLTGT'] = (object)[];

// Type_Data_Ordering_equalsLTEQ
$GLOBALS['Type_Data_Ordering_equalsLTEQ'] = (object)[];

// Type_Data_Ordering_equalsGTLT
$GLOBALS['Type_Data_Ordering_equalsGTLT'] = (object)[];

// Type_Data_Ordering_equalsGTGT
$GLOBALS['Type_Data_Ordering_equalsGTGT'] = (object)[];

// Type_Data_Ordering_equalsGTEQ
$GLOBALS['Type_Data_Ordering_equalsGTEQ'] = (object)[];

// Type_Data_Ordering_equalsEQLT
$GLOBALS['Type_Data_Ordering_equalsEQLT'] = (object)[];

// Type_Data_Ordering_equalsEQGT
$GLOBALS['Type_Data_Ordering_equalsEQGT'] = (object)[];

// Type_Data_Ordering_equalsEQEQ
$GLOBALS['Type_Data_Ordering_equalsEQEQ'] = (object)[];

// Type_Data_Ordering_equals
function majType_majData_majOrdering_equals($_dollar__unused_0, $v_1 = null, $v1_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majType_majData_majOrdering_equals';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = new \Type\Proxy\Type_Proxy_Proxy();
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Type_Data_Ordering_equals'] = __NAMESPACE__ . '\\majType_majData_majOrdering_equals';

// Type_Data_Ordering_appendOrderingLT
$GLOBALS['Type_Data_Ordering_appendOrderingLT'] = (object)[];

// Type_Data_Ordering_appendOrderingGT
$GLOBALS['Type_Data_Ordering_appendOrderingGT'] = (object)[];

// Type_Data_Ordering_appendOrderingEQ
$GLOBALS['Type_Data_Ordering_appendOrderingEQ'] = (object)[];

// Type_Data_Ordering_append
function majType_majData_majOrdering_append($_dollar__unused_0, $v_1 = null, $v1_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majType_majData_majOrdering_append';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = new \Type\Proxy\Type_Proxy_Proxy();
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Type_Data_Ordering_append'] = __NAMESPACE__ . '\\majType_majData_majOrdering_append';

