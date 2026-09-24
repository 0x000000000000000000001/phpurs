<?php

namespace Control\Comonad\Env\Trans;

// ALL IMPORTS: Control.Comonad, Control.Comonad.Env.Trans, Control.Comonad.Trans.Class, Control.Extend, Control.Semigroupoid, Data.Foldable, Data.FoldableWithIndex, Data.Function, Data.Functor, Data.FunctorWithIndex, Data.Newtype, Data.Traversable, Data.TraversableWithIndex, Data.Tuple, Prelude, Prim
// TO REQUIRE: Control.Comonad, Control.Comonad.Env.Trans, Control.Comonad.Trans.Class, Control.Extend, Control.Semigroupoid, Data.Foldable, Data.FoldableWithIndex, Data.Function, Data.Functor, Data.FunctorWithIndex, Data.Newtype, Data.Traversable, Data.TraversableWithIndex, Data.Tuple, Prelude
require_once __DIR__ . '/../Control.Comonad/index.php';
require_once __DIR__ . '/../Control.Comonad.Env.Trans/index.php';
require_once __DIR__ . '/../Control.Comonad.Trans.Class/index.php';
require_once __DIR__ . '/../Control.Extend/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Foldable/index.php';
require_once __DIR__ . '/../Data.FoldableWithIndex/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Functor/index.php';
require_once __DIR__ . '/../Data.FunctorWithIndex/index.php';
require_once __DIR__ . '/../Data.Newtype/index.php';
require_once __DIR__ . '/../Data.Traversable/index.php';
require_once __DIR__ . '/../Data.TraversableWithIndex/index.php';
require_once __DIR__ . '/../Data.Tuple/index.php';
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




// Control_Comonad_Env_Trans_EnvT
function majControl_majComonad_majEnv_majTrans_majEnvmajT($x_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_majEnvmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $x_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Comonad_Env_Trans_EnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_majEnvmajT';

// Control_Comonad_Env_Trans_withEnvT
function majControl_majComonad_majEnv_majTrans_withmajEnvmajT($f_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_withmajEnvmajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Comonad_Env_Trans_EnvT']))((($GLOBALS['Data_Tuple_Tuple'])(($f_0)(($v_1)->{'value0'})))(($v_1)->{'value1'}));
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Comonad_Env_Trans_withEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_withmajEnvmajT';

// Control_Comonad_Env_Trans_runEnvT
function majControl_majComonad_majEnv_majTrans_runmajEnvmajT($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_runmajEnvmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $v_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Comonad_Env_Trans_runEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_runmajEnvmajT';

// Control_Comonad_Env_Trans_newtypeEnvT
$GLOBALS['Control_Comonad_Env_Trans_newtypeEnvT'] = (object)["Coercible0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = null;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Comonad_Env_Trans_mapEnvT
function majControl_majComonad_majEnv_majTrans_mapmajEnvmajT($f_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_mapmajEnvmajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Comonad_Env_Trans_EnvT']))((($GLOBALS['Data_Tuple_Tuple'])(($v_1)->{'value0'}))(($f_0)(($v_1)->{'value1'})));
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Comonad_Env_Trans_mapEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_mapmajEnvmajT';

// Control_Comonad_Env_Trans_functorEnvT
function majControl_majComonad_majEnv_majTrans_functormajEnvmajT($dictFunctor_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_functormajEnvmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["map" => function($f_1) use ($dictFunctor_0) {
  $__num = \func_num_args();
  $__res = function($v_2) use ($dictFunctor_0, $f_1) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Comonad_Env_Trans_EnvT']))((($GLOBALS['Data_Tuple_Tuple'])(($v_2)->{'value0'}))(((($GLOBALS['Data_Functor_map'])($dictFunctor_0))($f_1))(($v_2)->{'value1'})));
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
$GLOBALS['Control_Comonad_Env_Trans_functorEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_functormajEnvmajT';

// Control_Comonad_Env_Trans_functorWithIndexEnvT
function majControl_majComonad_majEnv_majTrans_functormajWithmajIndexmajEnvmajT($dictFunctorWithIndex_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_functormajWithmajIndexmajEnvmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorEnvT1_1_0 = ($GLOBALS['Control_Comonad_Env_Trans_functorEnvT'])((($dictFunctorWithIndex_0)->{'Functor0'})(null));
  $__res = (object)["mapWithIndex" => function($f_2) use ($dictFunctorWithIndex_0) {
  $__num = \func_num_args();
  $__res = function($v_3) use ($dictFunctorWithIndex_0, $f_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Comonad_Env_Trans_EnvT']))((($GLOBALS['Data_Tuple_Tuple'])(($v_3)->{'value0'}))(((($GLOBALS['Data_FunctorWithIndex_mapWithIndex'])($dictFunctorWithIndex_0))($f_2))(($v_3)->{'value1'})));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Functor0" => function($_dollar___unused_2) use ($functorEnvT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorEnvT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Comonad_Env_Trans_functorWithIndexEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_functormajWithmajIndexmajEnvmajT';

// Control_Comonad_Env_Trans_foldableEnvT
function majControl_majComonad_majEnv_majTrans_foldablemajEnvmajT($dictFoldable_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_foldablemajEnvmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["foldl" => function($fn_1) use ($dictFoldable_0) {
  $__num = \func_num_args();
  $__res = function($a_2) use ($dictFoldable_0, $fn_1) {
  $__num = \func_num_args();
  $__res = function($v_3) use ($a_2, $dictFoldable_0, $fn_1) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Data_Foldable_foldl'])($dictFoldable_0))($fn_1))($a_2))(($v_3)->{'value1'});
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
}, "foldr" => function($fn_1) use ($dictFoldable_0) {
  $__num = \func_num_args();
  $__res = function($a_2) use ($dictFoldable_0, $fn_1) {
  $__num = \func_num_args();
  $__res = function($v_3) use ($a_2, $dictFoldable_0, $fn_1) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Data_Foldable_foldr'])($dictFoldable_0))($fn_1))($a_2))(($v_3)->{'value1'});
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
}, "foldMap" => function($dictMonoid_1) use ($dictFoldable_0) {
  $__num = \func_num_args();
  $__res = function($fn_2) use ($dictFoldable_0, $dictMonoid_1) {
  $__num = \func_num_args();
  $__res = function($v_3) use ($dictFoldable_0, $dictMonoid_1, $fn_2) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Data_Foldable_foldMap'])($dictFoldable_0))($dictMonoid_1))($fn_2))(($v_3)->{'value1'});
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
$GLOBALS['Control_Comonad_Env_Trans_foldableEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_foldablemajEnvmajT';

// Control_Comonad_Env_Trans_foldableWithIndexEnvT
function majControl_majComonad_majEnv_majTrans_foldablemajWithmajIndexmajEnvmajT($dictFoldableWithIndex_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_foldablemajWithmajIndexmajEnvmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $foldableEnvT1_1_0 = ($GLOBALS['Control_Comonad_Env_Trans_foldableEnvT'])((($dictFoldableWithIndex_0)->{'Foldable0'})(null));
  $__res = (object)["foldlWithIndex" => function($f_2) use ($dictFoldableWithIndex_0) {
  $__num = \func_num_args();
  $__res = function($a_3) use ($dictFoldableWithIndex_0, $f_2) {
  $__num = \func_num_args();
  $__res = function($v_4) use ($a_3, $dictFoldableWithIndex_0, $f_2) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Data_FoldableWithIndex_foldlWithIndex'])($dictFoldableWithIndex_0))($f_2))($a_3))(($v_4)->{'value1'});
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
}, "foldrWithIndex" => function($f_2) use ($dictFoldableWithIndex_0) {
  $__num = \func_num_args();
  $__res = function($a_3) use ($dictFoldableWithIndex_0, $f_2) {
  $__num = \func_num_args();
  $__res = function($v_4) use ($a_3, $dictFoldableWithIndex_0, $f_2) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Data_FoldableWithIndex_foldrWithIndex'])($dictFoldableWithIndex_0))($f_2))($a_3))(($v_4)->{'value1'});
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
}, "foldMapWithIndex" => function($dictMonoid_2) use ($dictFoldableWithIndex_0) {
  $__num = \func_num_args();
  $__res = function($f_3) use ($dictFoldableWithIndex_0, $dictMonoid_2) {
  $__num = \func_num_args();
  $__res = function($v_4) use ($dictFoldableWithIndex_0, $dictMonoid_2, $f_3) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Data_FoldableWithIndex_foldMapWithIndex'])($dictFoldableWithIndex_0))($dictMonoid_2))($f_3))(($v_4)->{'value1'});
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
}, "Foldable0" => function($_dollar___unused_2) use ($foldableEnvT1_1_0) {
  $__num = \func_num_args();
  $__res = $foldableEnvT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Comonad_Env_Trans_foldableWithIndexEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_foldablemajWithmajIndexmajEnvmajT';

// Control_Comonad_Env_Trans_traversableEnvT
function majControl_majComonad_majEnv_majTrans_traversablemajEnvmajT($dictTraversable_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_traversablemajEnvmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorEnvT1_1_0 = ($GLOBALS['Control_Comonad_Env_Trans_functorEnvT'])((($dictTraversable_0)->{'Functor0'})(null));
  $foldableEnvT1_2_1 = ($GLOBALS['Control_Comonad_Env_Trans_foldableEnvT'])((($dictTraversable_0)->{'Foldable1'})(null));
  $__res = (object)["sequence" => function($dictApplicative_3) use ($dictTraversable_0) {
  $__num = \func_num_args();
  $Functor0_4_2 = (((($dictApplicative_3)->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($v_5) use ($Functor0_4_2, $dictApplicative_3, $dictTraversable_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Functor_map'])($Functor0_4_2))(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Functor_functorFn']))($GLOBALS['Control_Comonad_Env_Trans_EnvT']))(($GLOBALS['Data_Tuple_Tuple'])(($v_5)->{'value0'}))))(((($GLOBALS['Data_Traversable_sequence'])($dictTraversable_0))($dictApplicative_3))(($v_5)->{'value1'}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "traverse" => function($dictApplicative_3) use ($dictTraversable_0) {
  $__num = \func_num_args();
  $Functor0_4_3 = (((($dictApplicative_3)->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($f_5) use ($Functor0_4_3, $dictApplicative_3, $dictTraversable_0) {
  $__num = \func_num_args();
  $__res = function($v_6) use ($Functor0_4_3, $dictApplicative_3, $dictTraversable_0, $f_5) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Functor_map'])($Functor0_4_3))(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Functor_functorFn']))($GLOBALS['Control_Comonad_Env_Trans_EnvT']))(($GLOBALS['Data_Tuple_Tuple'])(($v_6)->{'value0'}))))((((($GLOBALS['Data_Traversable_traverse'])($dictTraversable_0))($dictApplicative_3))($f_5))(($v_6)->{'value1'}));
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
}, "Functor0" => function($_dollar___unused_3) use ($functorEnvT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorEnvT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Foldable1" => function($_dollar___unused_3) use ($foldableEnvT1_2_1) {
  $__num = \func_num_args();
  $__res = $foldableEnvT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Comonad_Env_Trans_traversableEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_traversablemajEnvmajT';

// Control_Comonad_Env_Trans_traversableWithIndexEnvT
function majControl_majComonad_majEnv_majTrans_traversablemajWithmajIndexmajEnvmajT($dictTraversableWithIndex_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_traversablemajWithmajIndexmajEnvmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorWithIndexEnvT1_1_0 = ($GLOBALS['Control_Comonad_Env_Trans_functorWithIndexEnvT'])((($dictTraversableWithIndex_0)->{'FunctorWithIndex0'})(null));
  $foldableWithIndexEnvT1_2_1 = ($GLOBALS['Control_Comonad_Env_Trans_foldableWithIndexEnvT'])((($dictTraversableWithIndex_0)->{'FoldableWithIndex1'})(null));
  $traversableEnvT1_3_2 = ($GLOBALS['Control_Comonad_Env_Trans_traversableEnvT'])((($dictTraversableWithIndex_0)->{'Traversable2'})(null));
  $__res = (object)["traverseWithIndex" => function($dictApplicative_4) use ($dictTraversableWithIndex_0) {
  $__num = \func_num_args();
  $Functor0_5_3 = (((($dictApplicative_4)->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($f_6) use ($Functor0_5_3, $dictApplicative_4, $dictTraversableWithIndex_0) {
  $__num = \func_num_args();
  $__res = function($v_7) use ($Functor0_5_3, $dictApplicative_4, $dictTraversableWithIndex_0, $f_6) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Functor_map'])($Functor0_5_3))(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Functor_functorFn']))($GLOBALS['Control_Comonad_Env_Trans_EnvT']))(($GLOBALS['Data_Tuple_Tuple'])(($v_7)->{'value0'}))))((((($GLOBALS['Data_TraversableWithIndex_traverseWithIndex'])($dictTraversableWithIndex_0))($dictApplicative_4))($f_6))(($v_7)->{'value1'}));
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
}, "FunctorWithIndex0" => function($_dollar___unused_4) use ($functorWithIndexEnvT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorWithIndexEnvT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "FoldableWithIndex1" => function($_dollar___unused_4) use ($foldableWithIndexEnvT1_2_1) {
  $__num = \func_num_args();
  $__res = $foldableWithIndexEnvT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Traversable2" => function($_dollar___unused_4) use ($traversableEnvT1_3_2) {
  $__num = \func_num_args();
  $__res = $traversableEnvT1_3_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Comonad_Env_Trans_traversableWithIndexEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_traversablemajWithmajIndexmajEnvmajT';

// Control_Comonad_Env_Trans_extendEnvT
function majControl_majComonad_majEnv_majTrans_extendmajEnvmajT($dictExtend_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_extendmajEnvmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Functor0_1_0 = (($dictExtend_0)->{'Functor0'})(null);
  $functorEnvT1_2_1 = ($GLOBALS['Control_Comonad_Env_Trans_functorEnvT'])((($dictExtend_0)->{'Functor0'})(null));
  $__res = (object)["extend" => function($f_3) use ($Functor0_1_0, $dictExtend_0) {
  $__num = \func_num_args();
  $__res = function($v_4) use ($Functor0_1_0, $dictExtend_0, $f_3) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Comonad_Env_Trans_EnvT']))((($GLOBALS['Data_Tuple_Tuple'])(($v_4)->{'value0'}))(((($GLOBALS['Data_Functor_map'])($Functor0_1_0))($f_3))(((($GLOBALS['Control_Extend_extend'])($dictExtend_0))(((($GLOBALS['Control_Semigroupoid_composeFlipped'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($GLOBALS['Data_Tuple_Tuple'])(($v_4)->{'value0'})))($GLOBALS['Control_Comonad_Env_Trans_EnvT'])))(($v_4)->{'value1'}))));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Functor0" => function($_dollar___unused_3) use ($functorEnvT1_2_1) {
  $__num = \func_num_args();
  $__res = $functorEnvT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Comonad_Env_Trans_extendEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_extendmajEnvmajT';

// Control_Comonad_Env_Trans_comonadTransEnvT
$GLOBALS['Control_Comonad_Env_Trans_comonadTransEnvT'] = (object)["lower" => function($dictComonad_0) {
  $__num = \func_num_args();
  $__res = function($v_1) {
  $__num = \func_num_args();
  $__res = ($v_1)->{'value1'};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Comonad_Env_Trans_comonadEnvT
function majControl_majComonad_majEnv_majTrans_comonadmajEnvmajT($dictComonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majComonad_majEnv_majTrans_comonadmajEnvmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $extendEnvT1_1_0 = ($GLOBALS['Control_Comonad_Env_Trans_extendEnvT'])((($dictComonad_0)->{'Extend0'})(null));
  $__res = (object)["extract" => function($v_2) use ($dictComonad_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Comonad_extract'])($dictComonad_0))(($v_2)->{'value1'});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Extend0" => function($_dollar___unused_2) use ($extendEnvT1_1_0) {
  $__num = \func_num_args();
  $__res = $extendEnvT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Comonad_Env_Trans_comonadEnvT'] = __NAMESPACE__ . '\\majControl_majComonad_majEnv_majTrans_comonadmajEnvmajT';

