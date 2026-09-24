<?php

namespace Control\Monad\List\Trans;

// ALL IMPORTS: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Category, Control.Monad, Control.Monad.List.Trans, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.Trans.Class, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Function, Data.Functor, Data.Lazy, Data.Maybe, Data.Monoid, Data.Newtype, Data.Ring, Data.Semigroup, Data.Tuple, Data.Unfoldable, Data.Unfoldable1, Data.Unit, Effect.Class, Prelude, Prim
// TO REQUIRE: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Category, Control.Monad, Control.Monad.List.Trans, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.Trans.Class, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Function, Data.Functor, Data.Lazy, Data.Maybe, Data.Monoid, Data.Newtype, Data.Ring, Data.Semigroup, Data.Tuple, Data.Unfoldable, Data.Unfoldable1, Data.Unit, Effect.Class, Prelude
require_once __DIR__ . '/../Control.Alt/index.php';
require_once __DIR__ . '/../Control.Alternative/index.php';
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Apply/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Category/index.php';
require_once __DIR__ . '/../Control.Monad/index.php';
require_once __DIR__ . '/../Control.Monad.List.Trans/index.php';
require_once __DIR__ . '/../Control.Monad.Rec.Class/index.php';
require_once __DIR__ . '/../Control.Monad.ST.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Trans.Class/index.php';
require_once __DIR__ . '/../Control.MonadPlus/index.php';
require_once __DIR__ . '/../Control.Plus/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Functor/index.php';
require_once __DIR__ . '/../Data.Lazy/index.php';
require_once __DIR__ . '/../Data.Maybe/index.php';
require_once __DIR__ . '/../Data.Monoid/index.php';
require_once __DIR__ . '/../Data.Newtype/index.php';
require_once __DIR__ . '/../Data.Ring/index.php';
require_once __DIR__ . '/../Data.Semigroup/index.php';
require_once __DIR__ . '/../Data.Tuple/index.php';
require_once __DIR__ . '/../Data.Unfoldable/index.php';
require_once __DIR__ . '/../Data.Unfoldable1/index.php';
require_once __DIR__ . '/../Data.Unit/index.php';
require_once __DIR__ . '/../Effect.Class/index.php';
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


final class Control_Monad_List_Trans_Yield { public $tag = 'Yield'; public function __construct(public  $value0, public  $value1) {} }
final class Control_Monad_List_Trans_Skip { public $tag = 'Skip'; public function __construct(public  $value0) {} }
final class Control_Monad_List_Trans_Done { public $tag = 'Done'; public function __construct() {} }

// Control_Monad_List_Trans_Yield
$GLOBALS['Control_Monad_List_Trans_Yield'] = (function() {
  $__fn = function($value0, $value1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield($value0, $value1);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();

// Control_Monad_List_Trans_Skip
$GLOBALS['Control_Monad_List_Trans_Skip'] = function($value0) {
  $__num = \func_num_args();
  $__res = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip($value0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};

// Control_Monad_List_Trans_Done
$GLOBALS['Control_Monad_List_Trans_Done'] = ($GLOBALS['__phpurs_data0_Done'] ??= new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done());

// Control_Monad_List_Trans_ListT
function majControl_majMonad_majList_majTrans_majListmajT($x_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_majListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $x_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_ListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_majListmajT';

// Control_Monad_List_Trans_wrapLazy
function majControl_majMonad_majList_majTrans_wrapmajLazy($dictApplicative_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_wrapmajLazy';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))((($GLOBALS['Control_Applicative_pure'])($dictApplicative_0))(new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip($v_1)));
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_wrapLazy'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_wrapmajLazy';

// Control_Monad_List_Trans_wrapEffect
function majControl_majMonad_majList_majTrans_wrapmajEffect($dictFunctor_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_wrapmajEffect';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))(((($GLOBALS['Data_Functor_map'])($dictFunctor_0))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_List_Trans_Skip']))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Data_Lazy_defer']))($GLOBALS['Data_Function_const']))))($v_1));
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_wrapEffect'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_wrapmajEffect';

// Control_Monad_List_Trans_unfold
function majControl_majMonad_majList_majTrans_unfold($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_unfold';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Control_Monad_List_Trans_unfold_dictMonad_0 = $dictMonad_0;
  tco_loop_Control_Monad_List_Trans_unfold:;
  $dictMonad_0 = $__tco_var_Control_Monad_List_Trans_unfold_dictMonad_0;
  $Functor0_1_0 = (((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($f_2) use ($Functor0_1_0, $dictMonad_0) {
  $__num = \func_num_args();
  $__res = function($z_3) use ($Functor0_1_0, $dictMonad_0, $f_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))(((($GLOBALS['Data_Functor_map'])($Functor0_1_0))(function($v_4) use ($dictMonad_0, $f_2) {
  $__num = \func_num_args();
  $__t1 = null;
  if ($v_4 instanceof \Data\Maybe\Data_Maybe_Just) {
$__local_var_5_slot = (($v_4)->{'value0'})->{'value0'};
$__t1 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield((($v_4)->{'value0'})->{'value1'}, ($GLOBALS['Data_Lazy_defer'])(function($v1_6) use ($__local_var_5_slot, $dictMonad_0, $f_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Monad_List_Trans_unfold'])($dictMonad_0))($f_2))($__local_var_5_slot);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
goto end_branch_1;;
};
  if ($v_4 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t1 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done();
goto end_branch_1;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t1 = null;
  end_branch_1:;
  $__res = $__t1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(($f_2)($z_3)));
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
$GLOBALS['Control_Monad_List_Trans_unfold'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_unfold';

// Control_Monad_List_Trans_uncons
function majControl_majMonad_majList_majTrans_uncons($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_uncons';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Control_Monad_List_Trans_uncons_dictMonad_0 = $dictMonad_0;
  tco_loop_Control_Monad_List_Trans_uncons:;
  $dictMonad_0 = $__tco_var_Control_Monad_List_Trans_uncons_dictMonad_0;
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $Applicative0_2_1 = (($dictMonad_0)->{'Applicative0'})(null);
  $Bind1_3_2 = (($dictMonad_0)->{'Bind1'})(null);
  $__res = function($v_4) use ($Applicative0_2_1, $Bind1_3_2, $dictMonad_0, $pure_1_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))($v_4))(function($v1_5) use ($Applicative0_2_1, $dictMonad_0, $pure_1_0) {
  $__num = \func_num_args();
  $__t3 = null;
  if ($v1_5 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$__t3 = (($GLOBALS['Data_Function_apply'])($pure_1_0))((($GLOBALS['Data_Function_apply'])($GLOBALS['Data_Maybe_Just']))((($GLOBALS['Data_Tuple_Tuple'])(($v1_5)->{'value0'}))(($GLOBALS['Data_Lazy_force'])(($v1_5)->{'value1'}))));
goto end_branch_3;;
};
  if ($v1_5 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t3 = (($GLOBALS['Control_Monad_List_Trans_uncons'])($dictMonad_0))(($GLOBALS['Data_Lazy_force'])(($v1_5)->{'value0'}));
goto end_branch_3;;
};
  if ($v1_5 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t3 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1))($GLOBALS['Data_Maybe_Nothing']);
goto end_branch_3;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t3 = null;
  end_branch_3:;
  $__res = $__t3;
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
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_uncons'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_uncons';

// Control_Monad_List_Trans_tail
function majControl_majMonad_majList_majTrans_tail($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_tail';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Functor0_1_0 = (((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($l_2) use ($Functor0_1_0, $dictMonad_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Functor_map'])($Functor0_1_0))((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Maybe_functorMaybe']))($GLOBALS['Data_Tuple_snd'])))((($GLOBALS['Control_Monad_List_Trans_uncons'])($dictMonad_0))($l_2));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_tail'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_tail';

// Control_Monad_List_Trans_stepMap
function majControl_majMonad_majList_majTrans_stepmajMap($dictFunctor_0, $f_1 = null, $v_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_stepmajMap';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))(((($GLOBALS['Data_Functor_map'])($dictFunctor_0))($f_1))($v_2));
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_stepMap'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_stepmajMap';

// Control_Monad_List_Trans_takeWhile
function majControl_majMonad_majList_majTrans_takemajWhile($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_takemajWhile';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Control_Monad_List_Trans_takeWhile_dictApplicative_0 = $dictApplicative_0;
  tco_loop_Control_Monad_List_Trans_takeWhile:;
  $dictApplicative_0 = $__tco_var_Control_Monad_List_Trans_takeWhile_dictApplicative_0;
  $Functor0_1_0 = (((($dictApplicative_0)->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($f_2) use ($Functor0_1_0, $dictApplicative_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_List_Trans_stepMap'])($Functor0_1_0))(function($v_3) use ($dictApplicative_0, $f_2) {
  $__num = \func_num_args();
  $__t1 = null;
  if ($v_3 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$__t2 = null;
if (($f_2)(($v_3)->{'value0'})) {
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield(($v_3)->{'value0'}, ((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_takeWhile'])($dictApplicative_0))($f_2)))(($v_3)->{'value1'}));
goto end_branch_2;;
};
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done();
end_branch_2:;
$__t1 = $__t2;
goto end_branch_1;;
};
  if ($v_3 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t1 = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_Skip']))(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_takeWhile'])($dictApplicative_0))($f_2)))(($v_3)->{'value0'}));
goto end_branch_1;;
};
  if ($v_3 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t1 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done();
goto end_branch_1;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t1 = null;
  end_branch_1:;
  $__res = $__t1;
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
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_takeWhile'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_takemajWhile';

// Control_Monad_List_Trans_scanl
function majControl_majMonad_majList_majTrans_scanl($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_scanl';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Functor0_1_0 = (((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($f_2) use ($Functor0_1_0, $dictMonad_0) {
  $__num = \func_num_args();
  $__res = function($b_3) use ($Functor0_1_0, $dictMonad_0, $f_2) {
  $__num = \func_num_args();
  $__res = function($l_4) use ($Functor0_1_0, $b_3, $dictMonad_0, $f_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Monad_List_Trans_unfold'])($dictMonad_0))(function($v_5) use ($Functor0_1_0, $f_2) {
  $__num = \func_num_args();
  $__local_var_6_slot = ($v_5)->{'value0'};
  $__res = ((($GLOBALS['Data_Functor_map'])($Functor0_1_0))(function($v1_7) use ($__local_var_6_slot, $f_2) {
  $__num = \func_num_args();
  $__t2 = null;
  if ($v1_7 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$__t2 = (($GLOBALS['Data_Function_apply'])($GLOBALS['Data_Maybe_Just']))((($GLOBALS['Data_Tuple_Tuple'])((($GLOBALS['Data_Tuple_Tuple'])((($f_2)($__local_var_6_slot))(($v1_7)->{'value0'})))(($GLOBALS['Data_Lazy_force'])(($v1_7)->{'value1'}))))($__local_var_6_slot));
goto end_branch_2;;
};
  if ($v1_7 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t2 = (($GLOBALS['Data_Function_apply'])($GLOBALS['Data_Maybe_Just']))((($GLOBALS['Data_Tuple_Tuple'])((($GLOBALS['Data_Tuple_Tuple'])($__local_var_6_slot))(($GLOBALS['Data_Lazy_force'])(($v1_7)->{'value0'}))))($__local_var_6_slot));
goto end_branch_2;;
};
  if ($v1_7 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t2 = $GLOBALS['Data_Maybe_Nothing'];
goto end_branch_2;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t2 = null;
  end_branch_2:;
  $__res = $__t2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(($v_5)->{'value1'});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))((($GLOBALS['Data_Tuple_Tuple'])($b_3))($l_4));
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
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_scanl'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_scanl';

// Control_Monad_List_Trans_prepend'
function majControl_majMonad_majList_majTrans_prepend__prime__($dictApplicative_0, $h_1 = null, $t_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_prepend__prime__';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))((($GLOBALS['Control_Applicative_pure'])($dictApplicative_0))(new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield($h_1, $t_2)));
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_prepend__prime__'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_prepend__prime__';

// Control_Monad_List_Trans_prepend
function majControl_majMonad_majList_majTrans_prepend($dictApplicative_0, $h_1 = null, $t_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_prepend';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))((($GLOBALS['Control_Applicative_pure'])($dictApplicative_0))(new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield($h_1, (($GLOBALS['Data_Function_apply'])($GLOBALS['Data_Lazy_defer']))(($GLOBALS['Data_Function_const'])($t_2)))));
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_prepend'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_prepend';

// Control_Monad_List_Trans_nil
function majControl_majMonad_majList_majTrans_nil($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_nil';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))((($GLOBALS['Control_Applicative_pure'])($dictApplicative_0))(new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done()));
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_nil'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_nil';

// Control_Monad_List_Trans_singleton
function majControl_majMonad_majList_majTrans_singleton($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_singleton';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $nil1_1_0 = ($GLOBALS['Control_Monad_List_Trans_nil'])($dictApplicative_0);
  $__res = function($a_2) use ($dictApplicative_0, $nil1_1_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Monad_List_Trans_prepend'])($dictApplicative_0))($a_2))($nil1_1_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_singleton'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_singleton';

// Control_Monad_List_Trans_take
function majControl_majMonad_majList_majTrans_take($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_take';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Control_Monad_List_Trans_take_dictApplicative_0 = $dictApplicative_0;
  tco_loop_Control_Monad_List_Trans_take:;
  $dictApplicative_0 = $__tco_var_Control_Monad_List_Trans_take_dictApplicative_0;
  $nil1_1_0 = ($GLOBALS['Control_Monad_List_Trans_nil'])($dictApplicative_0);
  $Functor0_2_1 = (((($dictApplicative_0)->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($v_3) use ($Functor0_2_1, $dictApplicative_0, $nil1_1_0) {
  $__num = \func_num_args();
  $__res = function($v1_4) use ($Functor0_2_1, $dictApplicative_0, $nil1_1_0, $v_3) {
  $__num = \func_num_args();
  $__res = match ($v_3) { 0 => $nil1_1_0, default => (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))(((($GLOBALS['Data_Functor_map'])($Functor0_2_1))(function($v2_5) use ($dictApplicative_0, $v_3) {
  $__num = \func_num_args();
  $__t2 = null;
  if ($v2_5 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield(($v2_5)->{'value0'}, ((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_take'])($dictApplicative_0))(((($GLOBALS['Data_Ring_sub'])($GLOBALS['Data_Ring_ringInt']))($v_3))(1))))(($v2_5)->{'value1'}));
goto end_branch_2;;
};
  if ($v2_5 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_take'])($dictApplicative_0))($v_3)))(($v2_5)->{'value0'}));
goto end_branch_2;;
};
  if ($v2_5 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done();
goto end_branch_2;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t2 = null;
  end_branch_2:;
  $__res = $__t2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($v1_4)) };
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
$GLOBALS['Control_Monad_List_Trans_take'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_take';

// Control_Monad_List_Trans_zipWith'
function majControl_majMonad_majList_majTrans_zipmajWith__prime__($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_zipmajWith__prime__';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Control_Monad_List_Trans_zipWith__prime___dictMonad_0 = $dictMonad_0;
  tco_loop_Control_Monad_List_Trans_zipWith__prime__:;
  $dictMonad_0 = $__tco_var_Control_Monad_List_Trans_zipWith__prime___dictMonad_0;
  $Applicative0_1_0 = (($dictMonad_0)->{'Applicative0'})(null);
  $Functor0_2_1 = (((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null);
  $prepend_prime_1_3_2 = ($GLOBALS['Control_Monad_List_Trans_prepend__prime__'])((($dictMonad_0)->{'Applicative0'})(null));
  $Bind1_4_3 = (($dictMonad_0)->{'Bind1'})(null);
  $__res = function($f_5) use ($Applicative0_1_0, $Bind1_4_3, $Functor0_2_1, $dictMonad_0, $prepend_prime_1_3_2) {
  $__num = \func_num_args();
  $__res = function($fa_6) use ($Applicative0_1_0, $Bind1_4_3, $Functor0_2_1, $dictMonad_0, $f_5, $prepend_prime_1_3_2) {
  $__num = \func_num_args();
  $__res = function($fb_7) use ($Applicative0_1_0, $Bind1_4_3, $Functor0_2_1, $dictMonad_0, $f_5, $fa_6, $prepend_prime_1_3_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_List_Trans_wrapEffect'])($Functor0_2_1))(((($GLOBALS['Control_Bind_bind'])($Bind1_4_3))((($GLOBALS['Control_Monad_List_Trans_uncons'])($dictMonad_0))($fa_6)))(function($ua_8) use ($Applicative0_1_0, $Bind1_4_3, $Functor0_2_1, $dictMonad_0, $f_5, $fb_7, $prepend_prime_1_3_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_4_3))((($GLOBALS['Control_Monad_List_Trans_uncons'])($dictMonad_0))($fb_7)))(function($ub_9) use ($Applicative0_1_0, $Functor0_2_1, $dictMonad_0, $f_5, $prepend_prime_1_3_2, $ua_8) {
  $__num = \func_num_args();
  $__t4 = null;
  if ($ub_9 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t4 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_1_0))((($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))((($GLOBALS['Control_Applicative_pure'])($Applicative0_1_0))(new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done())));
goto end_branch_4;;
};
  if ($ua_8 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t4 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_1_0))((($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))((($GLOBALS['Control_Applicative_pure'])($Applicative0_1_0))(new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done())));
goto end_branch_4;;
};
  if (($ua_8 instanceof \Data\Maybe\Data_Maybe_Just && $ub_9 instanceof \Data\Maybe\Data_Maybe_Just)) {
$__local_var_10_slot = (($ua_8)->{'value0'})->{'value1'};
$__local_var_11_slot = (($ub_9)->{'value0'})->{'value1'};
$__t4 = ((($GLOBALS['Data_Functor_map'])($Functor0_2_1))((($GLOBALS['Data_Function_flip'])($prepend_prime_1_3_2))(($GLOBALS['Data_Lazy_defer'])(function($v2_12) use ($__local_var_10_slot, $__local_var_11_slot, $dictMonad_0, $f_5) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Control_Monad_List_Trans_zipWith__prime__'])($dictMonad_0))($f_5))($__local_var_10_slot))($__local_var_11_slot);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))))((($f_5)((($ua_8)->{'value0'})->{'value0'}))((($ub_9)->{'value0'})->{'value0'}));
goto end_branch_4;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t4 = null;
  end_branch_4:;
  $__res = $__t4;
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
$GLOBALS['Control_Monad_List_Trans_zipWith__prime__'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_zipmajWith__prime__';

// Control_Monad_List_Trans_zipWith
function majControl_majMonad_majList_majTrans_zipmajWith($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_zipmajWith';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $__res = function($f_2) use ($dictMonad_0, $pure_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_List_Trans_zipWith__prime__'])($dictMonad_0))(function($a_3) use ($f_2, $pure_1_0) {
  $__num = \func_num_args();
  $__res = function($b_4) use ($a_3, $f_2, $pure_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_1_0))((($f_2)($a_3))($b_4));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
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
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_zipWith'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_zipmajWith';

// Control_Monad_List_Trans_newtypeListT
$GLOBALS['Control_Monad_List_Trans_newtypeListT'] = (object)["Coercible0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = null;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_List_Trans_mapMaybe
function majControl_majMonad_majList_majTrans_mapmajMaybe($dictFunctor_0, $f_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_mapmajMaybe';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__tco_var_Control_Monad_List_Trans_mapMaybe_dictFunctor_0 = $dictFunctor_0;
  $__tco_var_Control_Monad_List_Trans_mapMaybe_f_1 = $f_1;
  tco_loop_Control_Monad_List_Trans_mapMaybe:;
  $dictFunctor_0 = $__tco_var_Control_Monad_List_Trans_mapMaybe_dictFunctor_0;
  $f_1 = $__tco_var_Control_Monad_List_Trans_mapMaybe_f_1;
  $__res = (($GLOBALS['Control_Monad_List_Trans_stepMap'])($dictFunctor_0))(function($v_2) use ($dictFunctor_0, $f_1) {
  $__num = \func_num_args();
  $__t0 = null;
  if ($v_2 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$__t0 = ((($GLOBALS['Data_Maybe_fromMaybe'])($GLOBALS['Control_Monad_List_Trans_Skip']))(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Maybe_functorMaybe']))($GLOBALS['Control_Monad_List_Trans_Yield']))(($f_1)(($v_2)->{'value0'}))))(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_mapMaybe'])($dictFunctor_0))($f_1)))(($v_2)->{'value1'}));
goto end_branch_0;;
};
  if ($v_2 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t0 = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_Skip']))(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_mapMaybe'])($dictFunctor_0))($f_1)))(($v_2)->{'value0'}));
goto end_branch_0;;
};
  if ($v_2 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t0 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done();
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_mapMaybe'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_mapmajMaybe';

// Control_Monad_List_Trans_iterate
function majControl_majMonad_majList_majTrans_iterate($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_iterate';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $__res = function($f_2) use ($dictMonad_0, $pure_1_0) {
  $__num = \func_num_args();
  $__res = function($a_3) use ($dictMonad_0, $f_2, $pure_1_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Monad_List_Trans_unfold'])($dictMonad_0))(function($x_4) use ($f_2, $pure_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_1_0))(($GLOBALS['Data_Maybe_Just'])((($GLOBALS['Data_Tuple_Tuple'])(($f_2)($x_4)))($x_4)));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($a_3);
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
$GLOBALS['Control_Monad_List_Trans_iterate'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_iterate';

// Control_Monad_List_Trans_repeat
function majControl_majMonad_majList_majTrans_repeat($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_repeat';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (($GLOBALS['Control_Monad_List_Trans_iterate'])($dictMonad_0))(($GLOBALS['Control_Category_identity'])($GLOBALS['Control_Category_categoryFn']));
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_repeat'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_repeat';

// Control_Monad_List_Trans_head
function majControl_majMonad_majList_majTrans_head($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_head';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Functor0_1_0 = (((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($l_2) use ($Functor0_1_0, $dictMonad_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Functor_map'])($Functor0_1_0))((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Maybe_functorMaybe']))($GLOBALS['Data_Tuple_fst'])))((($GLOBALS['Control_Monad_List_Trans_uncons'])($dictMonad_0))($l_2));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_head'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_head';

// Control_Monad_List_Trans_functorListT
function majControl_majMonad_majList_majTrans_functormajListmajT($dictFunctor_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_functormajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Control_Monad_List_Trans_functorListT_dictFunctor_0 = $dictFunctor_0;
  tco_loop_Control_Monad_List_Trans_functorListT:;
  $dictFunctor_0 = $__tco_var_Control_Monad_List_Trans_functorListT_dictFunctor_0;
  $__res = (object)["map" => function($f_1) use ($dictFunctor_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_List_Trans_stepMap'])($dictFunctor_0))(function($v_2) use ($dictFunctor_0, $f_1) {
  $__num = \func_num_args();
  $__t0 = null;
  if ($v_2 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$__t0 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield(($f_1)(($v_2)->{'value0'}), ((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Data_Functor_map'])(($GLOBALS['Control_Monad_List_Trans_functorListT'])($dictFunctor_0)))($f_1)))(($v_2)->{'value1'}));
goto end_branch_0;;
};
  if ($v_2 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t0 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Data_Functor_map'])(($GLOBALS['Control_Monad_List_Trans_functorListT'])($dictFunctor_0)))($f_1)))(($v_2)->{'value0'}));
goto end_branch_0;;
};
  if ($v_2 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t0 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done();
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_functorListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_functormajListmajT';

// Control_Monad_List_Trans_fromEffect
function majControl_majMonad_majList_majTrans_frommajEffect($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_frommajEffect';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Functor0_1_0 = (((($dictApplicative_0)->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($fa_2) use ($Functor0_1_0, $dictApplicative_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))(((($GLOBALS['Data_Functor_map'])($Functor0_1_0))((($GLOBALS['Data_Function_flip'])($GLOBALS['Control_Monad_List_Trans_Yield']))((($GLOBALS['Data_Function_apply'])($GLOBALS['Data_Lazy_defer']))(function($v_3) use ($dictApplicative_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))((($GLOBALS['Control_Applicative_pure'])($dictApplicative_0))(new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done()));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))))($fa_2));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_fromEffect'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_frommajEffect';

// Control_Monad_List_Trans_monadTransListT
$GLOBALS['Control_Monad_List_Trans_monadTransListT'] = (object)["lift" => function($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_List_Trans_fromEffect'])((($dictMonad_0)->{'Applicative0'})(null));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_List_Trans_foldlRec'
function majControl_majMonad_majList_majTrans_foldlmajRec__prime__($dictMonadRec_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_foldlmajRec__prime__';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadRec_0)->{'Monad0'})(null);
  $Applicative0_2_1 = (($Monad0_1_0)->{'Applicative0'})(null);
  $Bind1_3_2 = (($Monad0_1_0)->{'Bind1'})(null);
  $Monad01_4_3 = (($dictMonadRec_0)->{'Monad0'})(null);
  $__res = function($f_5) use ($Applicative0_2_1, $Bind1_3_2, $Monad01_4_3, $dictMonadRec_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_Rec_Class_tailRecM2'])($dictMonadRec_0))(function($b_6) use ($Applicative0_2_1, $Bind1_3_2, $Monad01_4_3, $f_5) {
  $__num = \func_num_args();
  $__res = function($l_7) use ($Applicative0_2_1, $Bind1_3_2, $Monad01_4_3, $b_6, $f_5) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))((($GLOBALS['Control_Monad_List_Trans_uncons'])($Monad01_4_3))($l_7)))(function($v_8) use ($Applicative0_2_1, $Bind1_3_2, $b_6, $f_5) {
  $__num = \func_num_args();
  $__t4 = null;
  if ($v_8 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t4 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1))(($GLOBALS['Control_Monad_Rec_Class_Done'])($b_6));
goto end_branch_4;;
};
  if ($v_8 instanceof \Data\Maybe\Data_Maybe_Just) {
$__local_var_9_slot = (($v_8)->{'value0'})->{'value1'};
$__t4 = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))((($f_5)($b_6))((($v_8)->{'value0'})->{'value0'})))(function($b_prime__10) use ($Applicative0_2_1, $__local_var_9_slot) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1))(($GLOBALS['Control_Monad_Rec_Class_Loop'])((object)["a" => $b_prime__10, "b" => $__local_var_9_slot]));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
goto end_branch_4;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t4 = null;
  end_branch_4:;
  $__res = $__t4;
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
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_foldlRec__prime__'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_foldlmajRec__prime__';

// Control_Monad_List_Trans_runListTRec
function majControl_majMonad_majList_majTrans_runmajListmajTmajRec($dictMonadRec_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_runmajListmajTmajRec';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Applicative0_1_0 = (((($dictMonadRec_0)->{'Monad0'})(null))->{'Applicative0'})(null);
  $__res = ((($GLOBALS['Control_Monad_List_Trans_foldlRec__prime__'])($dictMonadRec_0))(function($v_2) use ($Applicative0_1_0) {
  $__num = \func_num_args();
  $__res = function($v1_3) use ($Applicative0_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Applicative_pure'])($Applicative0_1_0))($GLOBALS['Data_Unit_unit']);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($GLOBALS['Data_Unit_unit']);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_runListTRec'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_runmajListmajTmajRec';

// Control_Monad_List_Trans_foldlRec
function majControl_majMonad_majList_majTrans_foldlmajRec($dictMonadRec_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_foldlmajRec';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadRec_0)->{'Monad0'})(null);
  $Applicative0_2_1 = (($Monad0_1_0)->{'Applicative0'})(null);
  $Bind1_3_2 = (($Monad0_1_0)->{'Bind1'})(null);
  $Monad01_4_3 = (($dictMonadRec_0)->{'Monad0'})(null);
  $__res = function($f_5) use ($Applicative0_2_1, $Bind1_3_2, $Monad01_4_3, $dictMonadRec_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_Rec_Class_tailRecM2'])($dictMonadRec_0))(function($b_6) use ($Applicative0_2_1, $Bind1_3_2, $Monad01_4_3, $f_5) {
  $__num = \func_num_args();
  $__res = function($l_7) use ($Applicative0_2_1, $Bind1_3_2, $Monad01_4_3, $b_6, $f_5) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))((($GLOBALS['Control_Monad_List_Trans_uncons'])($Monad01_4_3))($l_7)))(function($v_8) use ($Applicative0_2_1, $b_6, $f_5) {
  $__num = \func_num_args();
  $__t4 = null;
  if ($v_8 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t4 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1))(($GLOBALS['Control_Monad_Rec_Class_Done'])($b_6));
goto end_branch_4;;
};
  if ($v_8 instanceof \Data\Maybe\Data_Maybe_Just) {
$__t4 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1))(($GLOBALS['Control_Monad_Rec_Class_Loop'])((object)["a" => (($f_5)($b_6))((($v_8)->{'value0'})->{'value0'}), "b" => (($v_8)->{'value0'})->{'value1'}]));
goto end_branch_4;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t4 = null;
  end_branch_4:;
  $__res = $__t4;
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
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_foldlRec'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_foldlmajRec';

// Control_Monad_List_Trans_foldl'
function majControl_majMonad_majList_majTrans_foldl__prime__($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_foldl__prime__';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Applicative0_1_0 = (($dictMonad_0)->{'Applicative0'})(null);
  $Bind1_2_1 = (($dictMonad_0)->{'Bind1'})(null);
  $__res = function($f_3) use ($Applicative0_1_0, $Bind1_2_1, $dictMonad_0) {
  $__num = \func_num_args();
  $loop_4_2 = null;
  $loop_4_2 = function($b_5) use ($Applicative0_1_0, $Bind1_2_1, $dictMonad_0, $f_3, &$loop_4_2) {
  $__num = \func_num_args();
  $__res = function($l_6) use ($Applicative0_1_0, $Bind1_2_1, $b_5, $dictMonad_0, $f_3, &$loop_4_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_1))((($GLOBALS['Control_Monad_List_Trans_uncons'])($dictMonad_0))($l_6)))(function($v_7) use ($Applicative0_1_0, $Bind1_2_1, $b_5, $f_3, &$loop_4_2) {
  $__num = \func_num_args();
  $__t3 = null;
  if ($v_7 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t3 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_1_0))($b_5);
goto end_branch_3;;
};
  if ($v_7 instanceof \Data\Maybe\Data_Maybe_Just) {
$__t3 = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_1))((($f_3)($b_5))((($v_7)->{'value0'})->{'value0'})))((($GLOBALS['Data_Function_flip'])($loop_4_2))((($v_7)->{'value0'})->{'value1'}));
goto end_branch_3;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t3 = null;
  end_branch_3:;
  $__res = $__t3;
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
};
  $__res = $loop_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_foldl__prime__'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_foldl__prime__';

// Control_Monad_List_Trans_runListT
function majControl_majMonad_majList_majTrans_runmajListmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_runmajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Applicative0_1_0 = (($dictMonad_0)->{'Applicative0'})(null);
  $__res = ((($GLOBALS['Control_Monad_List_Trans_foldl__prime__'])($dictMonad_0))(function($v_2) use ($Applicative0_1_0) {
  $__num = \func_num_args();
  $__res = function($v1_3) use ($Applicative0_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Applicative_pure'])($Applicative0_1_0))($GLOBALS['Data_Unit_unit']);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($GLOBALS['Data_Unit_unit']);
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_runListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_runmajListmajT';

// Control_Monad_List_Trans_foldl
function majControl_majMonad_majList_majTrans_foldl($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_foldl';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Applicative0_1_0 = (($dictMonad_0)->{'Applicative0'})(null);
  $Bind1_2_1 = (($dictMonad_0)->{'Bind1'})(null);
  $__res = function($f_3) use ($Applicative0_1_0, $Bind1_2_1, $dictMonad_0) {
  $__num = \func_num_args();
  $loop_4_2 = null;
  $loop_4_2 = function($b_5) use ($Applicative0_1_0, $Bind1_2_1, $dictMonad_0, $f_3, &$loop_4_2) {
  $__num = \func_num_args();
  $__res = function($l_6) use ($Applicative0_1_0, $Bind1_2_1, $b_5, $dictMonad_0, $f_3, &$loop_4_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_1))((($GLOBALS['Control_Monad_List_Trans_uncons'])($dictMonad_0))($l_6)))(function($v_7) use ($Applicative0_1_0, $b_5, $f_3, &$loop_4_2) {
  $__num = \func_num_args();
  $__t3 = null;
  if ($v_7 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t3 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_1_0))($b_5);
goto end_branch_3;;
};
  if ($v_7 instanceof \Data\Maybe\Data_Maybe_Just) {
$__t3 = (($loop_4_2)((($f_3)($b_5))((($v_7)->{'value0'})->{'value0'})))((($v_7)->{'value0'})->{'value1'});
goto end_branch_3;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t3 = null;
  end_branch_3:;
  $__res = $__t3;
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
};
  $__res = $loop_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_foldl'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_foldl';

// Control_Monad_List_Trans_filter
function majControl_majMonad_majList_majTrans_filter($dictFunctor_0, $f_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_filter';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__tco_var_Control_Monad_List_Trans_filter_dictFunctor_0 = $dictFunctor_0;
  $__tco_var_Control_Monad_List_Trans_filter_f_1 = $f_1;
  tco_loop_Control_Monad_List_Trans_filter:;
  $dictFunctor_0 = $__tco_var_Control_Monad_List_Trans_filter_dictFunctor_0;
  $f_1 = $__tco_var_Control_Monad_List_Trans_filter_f_1;
  $__res = (($GLOBALS['Control_Monad_List_Trans_stepMap'])($dictFunctor_0))(function($v_2) use ($dictFunctor_0, $f_1) {
  $__num = \func_num_args();
  $__t0 = null;
  if ($v_2 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$s_prime__3_1 = ((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_filter'])($dictFunctor_0))($f_1)))(($v_2)->{'value1'});
$__t2 = null;
if (($f_1)(($v_2)->{'value0'})) {
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield(($v_2)->{'value0'}, $s_prime__3_1);
goto end_branch_2;;
};
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip($s_prime__3_1);
end_branch_2:;
$__t0 = $__t2;
goto end_branch_0;;
};
  if ($v_2 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t0 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_filter'])($dictFunctor_0))($f_1)))(($v_2)->{'value0'}));
goto end_branch_0;;
};
  if ($v_2 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t0 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done();
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_filter'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_filter';

// Control_Monad_List_Trans_dropWhile
function majControl_majMonad_majList_majTrans_dropmajWhile($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_dropmajWhile';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Control_Monad_List_Trans_dropWhile_dictApplicative_0 = $dictApplicative_0;
  tco_loop_Control_Monad_List_Trans_dropWhile:;
  $dictApplicative_0 = $__tco_var_Control_Monad_List_Trans_dropWhile_dictApplicative_0;
  $Functor0_1_0 = (((($dictApplicative_0)->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($f_2) use ($Functor0_1_0, $dictApplicative_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Monad_List_Trans_stepMap'])($Functor0_1_0))(function($v_3) use ($dictApplicative_0, $f_2) {
  $__num = \func_num_args();
  $__t1 = null;
  if ($v_3 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$__t2 = null;
if (($f_2)(($v_3)->{'value0'})) {
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_dropWhile'])($dictApplicative_0))($f_2)))(($v_3)->{'value1'}));
goto end_branch_2;;
};
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield(($v_3)->{'value0'}, ($v_3)->{'value1'});
end_branch_2:;
$__t1 = $__t2;
goto end_branch_1;;
};
  if ($v_3 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t1 = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_Skip']))(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_dropWhile'])($dictApplicative_0))($f_2)))(($v_3)->{'value0'}));
goto end_branch_1;;
};
  if ($v_3 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t1 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done();
goto end_branch_1;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t1 = null;
  end_branch_1:;
  $__res = $__t1;
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
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_dropWhile'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_dropmajWhile';

// Control_Monad_List_Trans_drop
function majControl_majMonad_majList_majTrans_drop($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_drop';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Control_Monad_List_Trans_drop_dictApplicative_0 = $dictApplicative_0;
  tco_loop_Control_Monad_List_Trans_drop:;
  $dictApplicative_0 = $__tco_var_Control_Monad_List_Trans_drop_dictApplicative_0;
  $Functor0_1_0 = (((($dictApplicative_0)->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($v_2) use ($Functor0_1_0, $dictApplicative_0) {
  $__num = \func_num_args();
  $__res = function($v1_3) use ($Functor0_1_0, $dictApplicative_0, $v_2) {
  $__num = \func_num_args();
  $__res = match ($v_2) { 0 => $v1_3, default => (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))(((($GLOBALS['Data_Functor_map'])($Functor0_1_0))(function($v2_4) use ($dictApplicative_0, $v_2) {
  $__num = \func_num_args();
  $__t1 = null;
  if ($v2_4 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$__t1 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_drop'])($dictApplicative_0))(((($GLOBALS['Data_Ring_sub'])($GLOBALS['Data_Ring_ringInt']))($v_2))(1))))(($v2_4)->{'value1'}));
goto end_branch_1;;
};
  if ($v2_4 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t1 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))((($GLOBALS['Control_Monad_List_Trans_drop'])($dictApplicative_0))($v_2)))(($v2_4)->{'value0'}));
goto end_branch_1;;
};
  if ($v2_4 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t1 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done();
goto end_branch_1;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t1 = null;
  end_branch_1:;
  $__res = $__t1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($v1_3)) };
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
$GLOBALS['Control_Monad_List_Trans_drop'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_drop';

// Control_Monad_List_Trans_cons
function majControl_majMonad_majList_majTrans_cons($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_cons';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])($dictApplicative_0);
  $__res = function($lh_2) use ($pure_1_0) {
  $__num = \func_num_args();
  $__res = function($t_3) use ($lh_2, $pure_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))((($GLOBALS['Data_Function_apply'])($pure_1_0))(new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield(($GLOBALS['Data_Lazy_force'])($lh_2), $t_3)));
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
$GLOBALS['Control_Monad_List_Trans_cons'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_cons';

// Control_Monad_List_Trans_unfoldable1ListT
function majControl_majMonad_majList_majTrans_unfoldable1majListmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_unfoldable1majListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Applicative0_1_0 = (($dictMonad_0)->{'Applicative0'})(null);
  $__res = (object)["unfoldr1" => function($f_2) use ($Applicative0_1_0) {
  $__num = \func_num_args();
  $__res = function($b_3) use ($Applicative0_1_0, $f_2) {
  $__num = \func_num_args();
  $go__go_4_1 = null;
  $go__go_4_1 = function($v_5) use ($Applicative0_1_0, $f_2, &$go__go_4_1) {
  $__num = \func_num_args();
  $__t2 = null;
  if (($v_5)->{'value1'} instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t2 = ((($GLOBALS['Control_Monad_List_Trans_prepend'])($Applicative0_1_0))(($v_5)->{'value0'}))(($GLOBALS['Control_Monad_List_Trans_nil'])($Applicative0_1_0));
goto end_branch_2;;
};
  if (($v_5)->{'value1'} instanceof \Data\Maybe\Data_Maybe_Just) {
$__local_var_6_slot = (($v_5)->{'value1'})->{'value0'};
$__t2 = ((($GLOBALS['Control_Monad_List_Trans_cons'])($Applicative0_1_0))((($GLOBALS['Control_Applicative_pure'])($GLOBALS['Data_Lazy_applicativeLazy']))(($v_5)->{'value0'})))(($GLOBALS['Data_Lazy_defer'])(function($v1_7) use ($__local_var_6_slot, $f_2, &$go__go_4_1) {
  $__num = \func_num_args();
  $__res = ($go__go_4_1)(($f_2)($__local_var_6_slot));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
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
  $__res = ($go__go_4_1)(($f_2)($b_3));
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
$GLOBALS['Control_Monad_List_Trans_unfoldable1ListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_unfoldable1majListmajT';

// Control_Monad_List_Trans_unfoldableListT
function majControl_majMonad_majList_majTrans_unfoldablemajListmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_unfoldablemajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Applicative0_1_0 = (($dictMonad_0)->{'Applicative0'})(null);
  $unfoldable1ListT1_2_1 = ($GLOBALS['Control_Monad_List_Trans_unfoldable1ListT'])($dictMonad_0);
  $__res = (object)["unfoldr" => function($f_3) use ($Applicative0_1_0) {
  $__num = \func_num_args();
  $__res = function($b_4) use ($Applicative0_1_0, $f_3) {
  $__num = \func_num_args();
  $go__go_5_2 = null;
  $go__go_5_2 = function($v_6) use ($Applicative0_1_0, $f_3, &$go__go_5_2) {
  $__num = \func_num_args();
  $__t3 = null;
  if ($v_6 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t3 = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))((($GLOBALS['Control_Applicative_pure'])($Applicative0_1_0))(new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done()));
goto end_branch_3;;
};
  if ($v_6 instanceof \Data\Maybe\Data_Maybe_Just) {
$__local_var_7_slot = (($v_6)->{'value0'})->{'value1'};
$__t3 = ((($GLOBALS['Control_Monad_List_Trans_cons'])($Applicative0_1_0))((($GLOBALS['Control_Applicative_pure'])($GLOBALS['Data_Lazy_applicativeLazy']))((($v_6)->{'value0'})->{'value0'})))(($GLOBALS['Data_Lazy_defer'])(function($v1_8) use ($__local_var_7_slot, $f_3, &$go__go_5_2) {
  $__num = \func_num_args();
  $__res = ($go__go_5_2)(($f_3)($__local_var_7_slot));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
goto end_branch_3;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t3 = null;
  end_branch_3:;
  $__res = $__t3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  $__res = ($go__go_5_2)(($f_3)($b_4));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Unfoldable10" => function($_dollar___unused_3) use ($unfoldable1ListT1_2_1) {
  $__num = \func_num_args();
  $__res = $unfoldable1ListT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_unfoldableListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_unfoldablemajListmajT';

// Control_Monad_List_Trans_semigroupListT
function majControl_majMonad_majList_majTrans_semigroupmajListmajT($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_semigroupmajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["append" => ($GLOBALS['Control_Monad_List_Trans_concat'])($dictApplicative_0)];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_semigroupListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_semigroupmajListmajT';

// Control_Monad_List_Trans_concat
function majControl_majMonad_majList_majTrans_concat($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_concat';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Functor0_1_0 = (((($dictApplicative_0)->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = function($x_2) use ($Functor0_1_0, $dictApplicative_0) {
  $__num = \func_num_args();
  $__res = function($y_3) use ($Functor0_1_0, $dictApplicative_0, $x_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))(((($GLOBALS['Data_Functor_map'])($Functor0_1_0))(function($v_4) use ($dictApplicative_0, $y_3) {
  $__num = \func_num_args();
  $__t1 = null;
  if ($v_4 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$__t1 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield(($v_4)->{'value0'}, ((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))(function($v1_5) use ($dictApplicative_0, $y_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Semigroup_append'])(($GLOBALS['Control_Monad_List_Trans_semigroupListT'])($dictApplicative_0)))($v1_5))($y_3);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(($v_4)->{'value1'}));
goto end_branch_1;;
};
  if ($v_4 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t1 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))(function($v1_5) use ($dictApplicative_0, $y_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Semigroup_append'])(($GLOBALS['Control_Monad_List_Trans_semigroupListT'])($dictApplicative_0)))($v1_5))($y_3);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(($v_4)->{'value0'}));
goto end_branch_1;;
};
  if ($v_4 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t1 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip((($GLOBALS['Data_Function_apply'])($GLOBALS['Data_Lazy_defer']))(($GLOBALS['Data_Function_const'])($y_3)));
goto end_branch_1;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t1 = null;
  end_branch_1:;
  $__res = $__t1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($x_2));
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
$GLOBALS['Control_Monad_List_Trans_concat'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_concat';

// Control_Monad_List_Trans_monoidListT
function majControl_majMonad_majList_majTrans_monoidmajListmajT($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_monoidmajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $semigroupListT1_1_0 = ($GLOBALS['Control_Monad_List_Trans_semigroupListT'])($dictApplicative_0);
  $__res = (object)["mempty" => (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))((($GLOBALS['Control_Applicative_pure'])($dictApplicative_0))(new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done())), "Semigroup0" => function($_dollar___unused_2) use ($semigroupListT1_1_0) {
  $__num = \func_num_args();
  $__res = $semigroupListT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_monoidListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_monoidmajListmajT';

// Control_Monad_List_Trans_catMaybes
function majControl_majMonad_majList_majTrans_catmajMaybes($dictFunctor_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_catmajMaybes';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (($GLOBALS['Control_Monad_List_Trans_mapMaybe'])($dictFunctor_0))(($GLOBALS['Control_Category_identity'])($GLOBALS['Control_Category_categoryFn']));
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_catMaybes'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_catmajMaybes';

// Control_Monad_List_Trans_monadListT
function majControl_majMonad_majList_majTrans_monadmajListmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_monadmajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["Applicative0" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_List_Trans_applicativeListT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Bind1" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_List_Trans_bindListT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_monadListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_monadmajListmajT';

// Control_Monad_List_Trans_bindListT
function majControl_majMonad_majList_majTrans_bindmajListmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_bindmajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $semigroupListT1_1_0 = ($GLOBALS['Control_Monad_List_Trans_semigroupListT'])((($dictMonad_0)->{'Applicative0'})(null));
  $Functor0_2_1 = (((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null);
  $__res = (object)["bind" => function($fa_3) use ($Functor0_2_1, $dictMonad_0, $semigroupListT1_1_0) {
  $__num = \func_num_args();
  $__res = function($f_4) use ($Functor0_2_1, $dictMonad_0, $fa_3, $semigroupListT1_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_List_Trans_ListT']))(((($GLOBALS['Data_Functor_map'])($Functor0_2_1))(function($v_5) use ($dictMonad_0, $f_4, $semigroupListT1_1_0) {
  $__num = \func_num_args();
  $__t2 = null;
  if ($v_5 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Yield) {
$__local_var_6_slot = ($v_5)->{'value0'};
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))(function($s_prime__7) use ($__local_var_6_slot, $dictMonad_0, $f_4, $semigroupListT1_1_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Semigroup_append'])($semigroupListT1_1_0))(($f_4)($__local_var_6_slot)))(((($GLOBALS['Control_Bind_bind'])(($GLOBALS['Control_Monad_List_Trans_bindListT'])($dictMonad_0)))($s_prime__7))($f_4));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(($v_5)->{'value1'}));
goto end_branch_2;;
};
  if ($v_5 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip) {
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Skip(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Lazy_functorLazy']))(function($v1_6) use ($dictMonad_0, $f_4) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])(($GLOBALS['Control_Monad_List_Trans_bindListT'])($dictMonad_0)))($v1_6))($f_4);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(($v_5)->{'value0'}));
goto end_branch_2;;
};
  if ($v_5 instanceof \Control\Monad\List\Trans\Control_Monad_List_Trans_Done) {
$__t2 = new \Control\Monad\List\Trans\Control_Monad_List_Trans_Done();
goto end_branch_2;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t2 = null;
  end_branch_2:;
  $__res = $__t2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($fa_3));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Apply0" => function($_dollar___unused_3) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_List_Trans_applyListT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_bindListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_bindmajListmajT';

// Control_Monad_List_Trans_applyListT
function majControl_majMonad_majList_majTrans_applymajListmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_applymajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorListT1_1_0 = ($GLOBALS['Control_Monad_List_Trans_functorListT'])((((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null));
  $__res = (object)["apply" => ($GLOBALS['Control_Monad_ap'])(($GLOBALS['Control_Monad_List_Trans_monadListT'])($dictMonad_0)), "Functor0" => function($_dollar___unused_2) use ($functorListT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorListT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_applyListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_applymajListmajT';

// Control_Monad_List_Trans_applicativeListT
function majControl_majMonad_majList_majTrans_applicativemajListmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_applicativemajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["pure" => ($GLOBALS['Control_Monad_List_Trans_singleton'])((($dictMonad_0)->{'Applicative0'})(null)), "Apply0" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_List_Trans_applyListT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_applicativeListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_applicativemajListmajT';

// Control_Monad_List_Trans_monadEffectListT
function majControl_majMonad_majList_majTrans_monadmajEffectmajListmajT($dictMonadEffect_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_monadmajEffectmajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadEffect_0)->{'Monad0'})(null);
  $monadListT1_2_1 = ($GLOBALS['Control_Monad_List_Trans_monadListT'])($Monad0_1_0);
  $__res = (object)["liftEffect" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_List_Trans_monadTransListT']))($Monad0_1_0)))(($GLOBALS['Effect_Class_liftEffect'])($dictMonadEffect_0)), "Monad0" => function($_dollar___unused_3) use ($monadListT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadListT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_monadEffectListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_monadmajEffectmajListmajT';

// Control_Monad_List_Trans_monadSTListT
function majControl_majMonad_majList_majTrans_monadmajSmajTmajListmajT($dictMonadST_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_monadmajSmajTmajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadST_0)->{'Monad0'})(null);
  $monadListT1_2_1 = ($GLOBALS['Control_Monad_List_Trans_monadListT'])($Monad0_1_0);
  $__res = (object)["liftST" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_List_Trans_monadTransListT']))($Monad0_1_0)))(($GLOBALS['Control_Monad_ST_Class_liftST'])($dictMonadST_0)), "Monad0" => function($_dollar___unused_3) use ($monadListT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadListT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_monadSTListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_monadmajSmajTmajListmajT';

// Control_Monad_List_Trans_altListT
function majControl_majMonad_majList_majTrans_altmajListmajT($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_altmajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorListT1_1_0 = ($GLOBALS['Control_Monad_List_Trans_functorListT'])((((($dictApplicative_0)->{'Apply0'})(null))->{'Functor0'})(null));
  $__res = (object)["alt" => ($GLOBALS['Control_Monad_List_Trans_concat'])($dictApplicative_0), "Functor0" => function($_dollar___unused_2) use ($functorListT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorListT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_altListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_altmajListmajT';

// Control_Monad_List_Trans_plusListT
function majControl_majMonad_majList_majTrans_plusmajListmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_plusmajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Applicative0_1_0 = (($dictMonad_0)->{'Applicative0'})(null);
  $altListT1_2_1 = ($GLOBALS['Control_Monad_List_Trans_altListT'])($Applicative0_1_0);
  $__res = (object)["empty" => ($GLOBALS['Control_Monad_List_Trans_nil'])($Applicative0_1_0), "Alt0" => function($_dollar___unused_3) use ($altListT1_2_1) {
  $__num = \func_num_args();
  $__res = $altListT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_plusListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_plusmajListmajT';

// Control_Monad_List_Trans_alternativeListT
function majControl_majMonad_majList_majTrans_alternativemajListmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_alternativemajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeListT1_1_0 = ($GLOBALS['Control_Monad_List_Trans_applicativeListT'])($dictMonad_0);
  $plusListT1_2_1 = ($GLOBALS['Control_Monad_List_Trans_plusListT'])($dictMonad_0);
  $__res = (object)["Applicative0" => function($_dollar___unused_3) use ($applicativeListT1_1_0) {
  $__num = \func_num_args();
  $__res = $applicativeListT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Plus1" => function($_dollar___unused_3) use ($plusListT1_2_1) {
  $__num = \func_num_args();
  $__res = $plusListT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_alternativeListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_alternativemajListmajT';

// Control_Monad_List_Trans_monadPlusListT
function majControl_majMonad_majList_majTrans_monadmajPlusmajListmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majList_majTrans_monadmajPlusmajListmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadListT1_1_0 = ($GLOBALS['Control_Monad_List_Trans_monadListT'])($dictMonad_0);
  $alternativeListT1_2_1 = ($GLOBALS['Control_Monad_List_Trans_alternativeListT'])($dictMonad_0);
  $__res = (object)["Monad0" => function($_dollar___unused_3) use ($monadListT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadListT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Alternative1" => function($_dollar___unused_3) use ($alternativeListT1_2_1) {
  $__num = \func_num_args();
  $__res = $alternativeListT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_List_Trans_monadPlusListT'] = __NAMESPACE__ . '\\majControl_majMonad_majList_majTrans_monadmajPlusmajListmajT';

