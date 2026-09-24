<?php

namespace Control\Monad\Writer\Trans;

// ALL IMPORTS: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Monad, Control.Monad.Cont.Class, Control.Monad.Error.Class, Control.Monad.Reader.Class, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.Monad.Writer.Trans, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Function, Data.Functor, Data.Monoid, Data.Newtype, Data.Semigroup, Data.Tuple, Data.Unit, Effect.Class, Prelude, Prim
// TO REQUIRE: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Monad, Control.Monad.Cont.Class, Control.Monad.Error.Class, Control.Monad.Reader.Class, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.Monad.Writer.Trans, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Function, Data.Functor, Data.Monoid, Data.Newtype, Data.Semigroup, Data.Tuple, Data.Unit, Effect.Class, Prelude
require_once __DIR__ . '/../Control.Alt/index.php';
require_once __DIR__ . '/../Control.Alternative/index.php';
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Apply/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Monad/index.php';
require_once __DIR__ . '/../Control.Monad.Cont.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Error.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Reader.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Rec.Class/index.php';
require_once __DIR__ . '/../Control.Monad.ST.Class/index.php';
require_once __DIR__ . '/../Control.Monad.State.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Trans.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Writer.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Writer.Trans/index.php';
require_once __DIR__ . '/../Control.MonadPlus/index.php';
require_once __DIR__ . '/../Control.Plus/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Functor/index.php';
require_once __DIR__ . '/../Data.Monoid/index.php';
require_once __DIR__ . '/../Data.Newtype/index.php';
require_once __DIR__ . '/../Data.Semigroup/index.php';
require_once __DIR__ . '/../Data.Tuple/index.php';
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




// Control_Monad_Writer_Trans_WriterT
function majControl_majMonad_majWriter_majTrans_majWritermajT($x_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_majWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $x_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_WriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_majWritermajT';

// Control_Monad_Writer_Trans_runWriterT
function majControl_majMonad_majWriter_majTrans_runmajWritermajT($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_runmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $v_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_runWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_runmajWritermajT';

// Control_Monad_Writer_Trans_newtypeWriterT
$GLOBALS['Control_Monad_Writer_Trans_newtypeWriterT'] = (object)["Coercible0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = null;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Writer_Trans_monadTransWriterT
function majControl_majMonad_majWriter_majTrans_monadmajTransmajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajTransmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["lift" => function($dictMonad_1) use ($dictMonoid_0) {
  $__num = \func_num_args();
  $Bind1_2_0 = (($dictMonad_1)->{'Bind1'})(null);
  $pure_3_1 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_1)->{'Applicative0'})(null));
  $__res = function($m_4) use ($Bind1_2_0, $dictMonoid_0, $pure_3_1) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_0))($m_4))(function($a_5) use ($dictMonoid_0, $pure_3_1) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_3_1))((($GLOBALS['Data_Tuple_Tuple'])($a_5))(($GLOBALS['Data_Monoid_mempty'])($dictMonoid_0)));
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
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadTransWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajTransmajWritermajT';

// Control_Monad_Writer_Trans_mapWriterT
function majControl_majMonad_majWriter_majTrans_mapmajWritermajT($f_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_mapmajWritermajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($f_0)($v_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_mapWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_mapmajWritermajT';

// Control_Monad_Writer_Trans_functorWriterT
function majControl_majMonad_majWriter_majTrans_functormajWritermajT($dictFunctor_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_functormajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["map" => function($f_1) use ($dictFunctor_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Writer_Trans_mapWriterT']))((($GLOBALS['Data_Functor_map'])($dictFunctor_0))(function($v_2) use ($f_1) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Tuple_Tuple'])(($f_1)(($v_2)->{'value0'})))(($v_2)->{'value1'});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_functorWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_functormajWritermajT';

// Control_Monad_Writer_Trans_execWriterT
function majControl_majMonad_majWriter_majTrans_execmajWritermajT($dictFunctor_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_execmajWritermajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ((($GLOBALS['Data_Functor_map'])($dictFunctor_0))($GLOBALS['Data_Tuple_snd']))($v_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_execWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_execmajWritermajT';

// Control_Monad_Writer_Trans_applyWriterT
function majControl_majMonad_majWriter_majTrans_applymajWritermajT($dictSemigroup_0, $dictApply_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_applymajWritermajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $Functor0_2_0 = (($dictApply_1)->{'Functor0'})(null);
  $functorWriterT1_3_1 = ($GLOBALS['Control_Monad_Writer_Trans_functorWriterT'])((($dictApply_1)->{'Functor0'})(null));
  $__res = (object)["apply" => function($v_4) use ($Functor0_2_0, $dictApply_1, $dictSemigroup_0) {
  $__num = \func_num_args();
  $__res = function($v1_5) use ($Functor0_2_0, $dictApply_1, $dictSemigroup_0, $v_4) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Apply_apply'])($dictApply_1))(((($GLOBALS['Data_Functor_map'])($Functor0_2_0))(function($v3_6) use ($dictSemigroup_0) {
  $__num = \func_num_args();
  $__res = function($v4_7) use ($dictSemigroup_0, $v3_6) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Tuple_Tuple'])((($v3_6)->{'value0'})(($v4_7)->{'value0'})))(((($GLOBALS['Data_Semigroup_append'])($dictSemigroup_0))(($v3_6)->{'value1'}))(($v4_7)->{'value1'}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($v_4)))($v1_5);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Functor0" => function($_dollar___unused_4) use ($functorWriterT1_3_1) {
  $__num = \func_num_args();
  $__res = $functorWriterT1_3_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_applyWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_applymajWritermajT';

// Control_Monad_Writer_Trans_bindWriterT
function majControl_majMonad_majWriter_majTrans_bindmajWritermajT($dictSemigroup_0, $dictBind_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_bindmajWritermajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $Apply0_2_0 = (($dictBind_1)->{'Apply0'})(null);
  $Functor0_3_1 = (($Apply0_2_0)->{'Functor0'})(null);
  $applyWriterT2_4_2 = (($GLOBALS['Control_Monad_Writer_Trans_applyWriterT'])($dictSemigroup_0))($Apply0_2_0);
  $__res = (object)["bind" => function($v_5) use ($Functor0_3_1, $dictBind_1, $dictSemigroup_0) {
  $__num = \func_num_args();
  $__res = function($k_6) use ($Functor0_3_1, $dictBind_1, $dictSemigroup_0, $v_5) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Writer_Trans_WriterT']))(((($GLOBALS['Control_Bind_bind'])($dictBind_1))($v_5))(function($v1_7) use ($Functor0_3_1, $dictSemigroup_0, $k_6) {
  $__num = \func_num_args();
  $__local_var_8_slot = ($v1_7)->{'value1'};
  $__res = ((($GLOBALS['Data_Functor_map'])($Functor0_3_1))(function($v3_9) use ($__local_var_8_slot, $dictSemigroup_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Tuple_Tuple'])(($v3_9)->{'value0'}))(((($GLOBALS['Data_Semigroup_append'])($dictSemigroup_0))($__local_var_8_slot))(($v3_9)->{'value1'}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(($k_6)(($v1_7)->{'value0'}));
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
}, "Apply0" => function($_dollar___unused_5) use ($applyWriterT2_4_2) {
  $__num = \func_num_args();
  $__res = $applyWriterT2_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_bindWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_bindmajWritermajT';

// Control_Monad_Writer_Trans_semigroupWriterT
function majControl_majMonad_majWriter_majTrans_semigroupmajWritermajT($dictApply_0, $dictSemigroup_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_semigroupmajWritermajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $applyWriterT1_2_0 = (($GLOBALS['Control_Monad_Writer_Trans_applyWriterT'])($dictSemigroup_1))($dictApply_0);
  $__res = function($dictSemigroup1_3) use ($applyWriterT1_2_0) {
  $__num = \func_num_args();
  $__res = (object)["append" => (($GLOBALS['Control_Apply_lift2'])($applyWriterT1_2_0))(($GLOBALS['Data_Semigroup_append'])($dictSemigroup1_3))];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_semigroupWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_semigroupmajWritermajT';

// Control_Monad_Writer_Trans_applicativeWriterT
function majControl_majMonad_majWriter_majTrans_applicativemajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_applicativemajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applyWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_applyWriterT'])((($dictMonoid_0)->{'Semigroup0'})(null));
  $__res = function($dictApplicative_2) use ($applyWriterT1_1_0, $dictMonoid_0) {
  $__num = \func_num_args();
  $pure_3_1 = ($GLOBALS['Control_Applicative_pure'])($dictApplicative_2);
  $applyWriterT2_4_2 = ($applyWriterT1_1_0)((($dictApplicative_2)->{'Apply0'})(null));
  $__res = (object)["pure" => function($a_5) use ($dictMonoid_0, $pure_3_1) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Writer_Trans_WriterT']))((($GLOBALS['Data_Function_apply'])($pure_3_1))((($GLOBALS['Data_Tuple_Tuple'])($a_5))(($GLOBALS['Data_Monoid_mempty'])($dictMonoid_0))));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Apply0" => function($_dollar___unused_5) use ($applyWriterT2_4_2) {
  $__num = \func_num_args();
  $__res = $applyWriterT2_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_applicativeWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_applicativemajWritermajT';

// Control_Monad_Writer_Trans_monadWriterT
function majControl_majMonad_majWriter_majTrans_monadmajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_applicativeWriterT'])($dictMonoid_0);
  $bindWriterT1_2_1 = ($GLOBALS['Control_Monad_Writer_Trans_bindWriterT'])((($dictMonoid_0)->{'Semigroup0'})(null));
  $__res = function($dictMonad_3) use ($applicativeWriterT1_1_0, $bindWriterT1_2_1) {
  $__num = \func_num_args();
  $applicativeWriterT2_4_2 = ($applicativeWriterT1_1_0)((($dictMonad_3)->{'Applicative0'})(null));
  $bindWriterT2_5_3 = ($bindWriterT1_2_1)((($dictMonad_3)->{'Bind1'})(null));
  $__res = (object)["Applicative0" => function($_dollar___unused_6) use ($applicativeWriterT2_4_2) {
  $__num = \func_num_args();
  $__res = $applicativeWriterT2_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Bind1" => function($_dollar___unused_6) use ($bindWriterT2_5_3) {
  $__num = \func_num_args();
  $__res = $bindWriterT2_5_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajWritermajT';

// Control_Monad_Writer_Trans_monadAskWriterT
function majControl_majMonad_majWriter_majTrans_monadmajAskmajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajAskmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadTransWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_monadTransWriterT'])($dictMonoid_0);
  $monadWriterT1_2_1 = ($GLOBALS['Control_Monad_Writer_Trans_monadWriterT'])($dictMonoid_0);
  $__res = function($dictMonadAsk_3) use ($monadTransWriterT1_1_0, $monadWriterT1_2_1) {
  $__num = \func_num_args();
  $monadWriterT2_4_2 = ($monadWriterT1_2_1)((($dictMonadAsk_3)->{'Monad0'})(null));
  $__res = (object)["ask" => ((($monadTransWriterT1_1_0)->{'lift'})((($dictMonadAsk_3)->{'Monad0'})(null)))(($GLOBALS['Control_Monad_Reader_Class_ask'])($dictMonadAsk_3)), "Monad0" => function($_dollar___unused_5) use ($monadWriterT2_4_2) {
  $__num = \func_num_args();
  $__res = $monadWriterT2_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadAskWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajAskmajWritermajT';

// Control_Monad_Writer_Trans_monadReaderWriterT
function majControl_majMonad_majWriter_majTrans_monadmajReadermajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajReadermajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadAskWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_monadAskWriterT'])($dictMonoid_0);
  $__res = function($dictMonadReader_2) use ($monadAskWriterT1_1_0) {
  $__num = \func_num_args();
  $monadAskWriterT2_3_1 = ($monadAskWriterT1_1_0)((($dictMonadReader_2)->{'MonadAsk0'})(null));
  $__res = (object)["local" => function($f_4) use ($dictMonadReader_2) {
  $__num = \func_num_args();
  $__local_var_5_2 = (($dictMonadReader_2)->{'local'})($f_4);
  $__res = function($v_6) use ($__local_var_5_2) {
  $__num = \func_num_args();
  $__res = ($__local_var_5_2)($v_6);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadAsk0" => function($_dollar___unused_4) use ($monadAskWriterT2_3_1) {
  $__num = \func_num_args();
  $__res = $monadAskWriterT2_3_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadReaderWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajReadermajWritermajT';

// Control_Monad_Writer_Trans_monadContWriterT
function majControl_majMonad_majWriter_majTrans_monadmajContmajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajContmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_monadWriterT'])($dictMonoid_0);
  $__res = function($dictMonadCont_2) use ($dictMonoid_0, $monadWriterT1_1_0) {
  $__num = \func_num_args();
  $monadWriterT2_3_1 = ($monadWriterT1_1_0)((($dictMonadCont_2)->{'Monad0'})(null));
  $__res = (object)["callCC" => function($f_4) use ($dictMonadCont_2, $dictMonoid_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Writer_Trans_WriterT']))((($dictMonadCont_2)->{'callCC'})(function($c_5) use ($dictMonoid_0, $f_4) {
  $__num = \func_num_args();
  $__res = ($f_4)(function($a_6) use ($c_5, $dictMonoid_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Writer_Trans_WriterT']))(($c_5)((($GLOBALS['Data_Tuple_Tuple'])($a_6))(($GLOBALS['Data_Monoid_mempty'])($dictMonoid_0))));
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
}, "Monad0" => function($_dollar___unused_4) use ($monadWriterT2_3_1) {
  $__num = \func_num_args();
  $__res = $monadWriterT2_3_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadContWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajContmajWritermajT';

// Control_Monad_Writer_Trans_monadEffectWriter
function majControl_majMonad_majWriter_majTrans_monadmajEffectmajWriter($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajEffectmajWriter';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $lift_1_0 = ($GLOBALS['Control_Monad_Trans_Class_lift'])(($GLOBALS['Control_Monad_Writer_Trans_monadTransWriterT'])($dictMonoid_0));
  $monadWriterT1_2_1 = ($GLOBALS['Control_Monad_Writer_Trans_monadWriterT'])($dictMonoid_0);
  $__res = function($dictMonadEffect_3) use ($lift_1_0, $monadWriterT1_2_1) {
  $__num = \func_num_args();
  $Monad0_4_2 = (($dictMonadEffect_3)->{'Monad0'})(null);
  $monadWriterT2_5_3 = ($monadWriterT1_2_1)($Monad0_4_2);
  $__res = (object)["liftEffect" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($lift_1_0)($Monad0_4_2)))(($GLOBALS['Effect_Class_liftEffect'])($dictMonadEffect_3)), "Monad0" => function($_dollar___unused_6) use ($monadWriterT2_5_3) {
  $__num = \func_num_args();
  $__res = $monadWriterT2_5_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadEffectWriter'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajEffectmajWriter';

// Control_Monad_Writer_Trans_monadRecWriterT
function majControl_majMonad_majWriter_majTrans_monadmajRecmajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajRecmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Semigroup0_1_0 = (($dictMonoid_0)->{'Semigroup0'})(null);
  $monadWriterT1_2_1 = ($GLOBALS['Control_Monad_Writer_Trans_monadWriterT'])($dictMonoid_0);
  $__res = function($dictMonadRec_3) use ($Semigroup0_1_0, $dictMonoid_0, $monadWriterT1_2_1) {
  $__num = \func_num_args();
  $Monad0_4_2 = (($dictMonadRec_3)->{'Monad0'})(null);
  $Bind1_5_3 = (($Monad0_4_2)->{'Bind1'})(null);
  $Applicative0_6_4 = (($Monad0_4_2)->{'Applicative0'})(null);
  $monadWriterT2_7_5 = ($monadWriterT1_2_1)($Monad0_4_2);
  $__res = (object)["tailRecM" => function($f_8) use ($Applicative0_6_4, $Bind1_5_3, $Semigroup0_1_0, $dictMonadRec_3, $dictMonoid_0) {
  $__num = \func_num_args();
  $__res = function($a_9) use ($Applicative0_6_4, $Bind1_5_3, $Semigroup0_1_0, $dictMonadRec_3, $dictMonoid_0, $f_8) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Writer_Trans_WriterT']))(((($GLOBALS['Control_Monad_Rec_Class_tailRecM'])($dictMonadRec_3))(function($v_10) use ($Applicative0_6_4, $Bind1_5_3, $Semigroup0_1_0, $f_8) {
  $__num = \func_num_args();
  $__local_var_11_slot = ($v_10)->{'value1'};
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_5_3))(($f_8)(($v_10)->{'value0'})))(function($v2_12) use ($Applicative0_6_4, $Semigroup0_1_0, $__local_var_11_slot) {
  $__num = \func_num_args();
  $__t7 = null;
  if (($v2_12)->{'value0'} instanceof \Control\Monad\Rec\Class\Control_Monad_Rec_Class_Loop) {
$__t7 = ($GLOBALS['Control_Monad_Rec_Class_Loop'])((($GLOBALS['Data_Tuple_Tuple'])((($v2_12)->{'value0'})->{'value0'}))(((($GLOBALS['Data_Semigroup_append'])($Semigroup0_1_0))($__local_var_11_slot))(($v2_12)->{'value1'})));
goto end_branch_7;;
};
  if (($v2_12)->{'value0'} instanceof \Control\Monad\Rec\Class\Control_Monad_Rec_Class_Done) {
$__t7 = ($GLOBALS['Control_Monad_Rec_Class_Done'])((($GLOBALS['Data_Tuple_Tuple'])((($v2_12)->{'value0'})->{'value0'}))(((($GLOBALS['Data_Semigroup_append'])($Semigroup0_1_0))($__local_var_11_slot))(($v2_12)->{'value1'})));
goto end_branch_7;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t7 = null;
  end_branch_7:;
  $__res = (($GLOBALS['Control_Applicative_pure'])($Applicative0_6_4))($__t7);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))((($GLOBALS['Data_Tuple_Tuple'])($a_9))(($GLOBALS['Data_Monoid_mempty'])($dictMonoid_0))));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_8) use ($monadWriterT2_7_5) {
  $__num = \func_num_args();
  $__res = $monadWriterT2_7_5;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadRecWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajRecmajWritermajT';

// Control_Monad_Writer_Trans_monadStateWriterT
function majControl_majMonad_majWriter_majTrans_monadmajStatemajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajStatemajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadTransWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_monadTransWriterT'])($dictMonoid_0);
  $monadWriterT1_2_1 = ($GLOBALS['Control_Monad_Writer_Trans_monadWriterT'])($dictMonoid_0);
  $__res = function($dictMonadState_3) use ($monadTransWriterT1_1_0, $monadWriterT1_2_1) {
  $__num = \func_num_args();
  $Monad0_4_2 = (($dictMonadState_3)->{'Monad0'})(null);
  $monadWriterT2_5_3 = ($monadWriterT1_2_1)((($dictMonadState_3)->{'Monad0'})(null));
  $__res = (object)["state" => function($f_6) use ($Monad0_4_2, $dictMonadState_3, $monadTransWriterT1_1_0) {
  $__num = \func_num_args();
  $__res = ((($monadTransWriterT1_1_0)->{'lift'})($Monad0_4_2))((($dictMonadState_3)->{'state'})($f_6));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_6) use ($monadWriterT2_5_3) {
  $__num = \func_num_args();
  $__res = $monadWriterT2_5_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadStateWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajStatemajWritermajT';

// Control_Monad_Writer_Trans_monadTellWriterT
function majControl_majMonad_majWriter_majTrans_monadmajTellmajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajTellmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Semigroup0_1_0 = (($dictMonoid_0)->{'Semigroup0'})(null);
  $monadWriterT1_2_1 = ($GLOBALS['Control_Monad_Writer_Trans_monadWriterT'])($dictMonoid_0);
  $__res = function($dictMonad_3) use ($Semigroup0_1_0, $monadWriterT1_2_1) {
  $__num = \func_num_args();
  $monadWriterT2_4_2 = ($monadWriterT1_2_1)($dictMonad_3);
  $__res = (object)["tell" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Writer_Trans_WriterT']))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($GLOBALS['Control_Applicative_pure'])((($dictMonad_3)->{'Applicative0'})(null))))(($GLOBALS['Data_Tuple_Tuple'])($GLOBALS['Data_Unit_unit']))), "Semigroup0" => function($_dollar___unused_5) use ($Semigroup0_1_0) {
  $__num = \func_num_args();
  $__res = $Semigroup0_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad1" => function($_dollar___unused_5) use ($monadWriterT2_4_2) {
  $__num = \func_num_args();
  $__res = $monadWriterT2_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadTellWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajTellmajWritermajT';

// Control_Monad_Writer_Trans_monadWriterWriterT
function majControl_majMonad_majWriter_majTrans_monadmajWritermajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajWritermajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadTellWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_monadTellWriterT'])($dictMonoid_0);
  $__res = function($dictMonad_2) use ($dictMonoid_0, $monadTellWriterT1_1_0) {
  $__num = \func_num_args();
  $Bind1_3_1 = (($dictMonad_2)->{'Bind1'})(null);
  $Applicative0_4_2 = (($dictMonad_2)->{'Applicative0'})(null);
  $pure_5_3 = ($GLOBALS['Control_Applicative_pure'])($Applicative0_4_2);
  $pure1_6_4 = ($GLOBALS['Control_Applicative_pure'])($Applicative0_4_2);
  $monadTellWriterT2_7_5 = ($monadTellWriterT1_1_0)($dictMonad_2);
  $__res = (object)["listen" => function($v_8) use ($Bind1_3_1, $pure_5_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_1))($v_8))(function($v1_9) use ($pure_5_3) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_5_3))((($GLOBALS['Data_Tuple_Tuple'])((($GLOBALS['Data_Tuple_Tuple'])(($v1_9)->{'value0'}))(($v1_9)->{'value1'})))(($v1_9)->{'value1'}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "pass" => function($v_8) use ($Bind1_3_1, $pure1_6_4) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_1))($v_8))(function($v1_9) use ($pure1_6_4) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure1_6_4))((($GLOBALS['Data_Tuple_Tuple'])((($v1_9)->{'value0'})->{'value0'}))(((($v1_9)->{'value0'})->{'value1'})(($v1_9)->{'value1'})));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monoid0" => function($_dollar___unused_8) use ($dictMonoid_0) {
  $__num = \func_num_args();
  $__res = $dictMonoid_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadTell1" => function($_dollar___unused_8) use ($monadTellWriterT2_7_5) {
  $__num = \func_num_args();
  $__res = $monadTellWriterT2_7_5;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadWriterWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajWritermajWritermajT';

// Control_Monad_Writer_Trans_monadThrowWriterT
function majControl_majMonad_majWriter_majTrans_monadmajThrowmajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajThrowmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadTransWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_monadTransWriterT'])($dictMonoid_0);
  $monadWriterT1_2_1 = ($GLOBALS['Control_Monad_Writer_Trans_monadWriterT'])($dictMonoid_0);
  $__res = function($dictMonadThrow_3) use ($monadTransWriterT1_1_0, $monadWriterT1_2_1) {
  $__num = \func_num_args();
  $Monad0_4_2 = (($dictMonadThrow_3)->{'Monad0'})(null);
  $monadWriterT2_5_3 = ($monadWriterT1_2_1)((($dictMonadThrow_3)->{'Monad0'})(null));
  $__res = (object)["throwError" => function($e_6) use ($Monad0_4_2, $dictMonadThrow_3, $monadTransWriterT1_1_0) {
  $__num = \func_num_args();
  $__res = ((($monadTransWriterT1_1_0)->{'lift'})($Monad0_4_2))((($dictMonadThrow_3)->{'throwError'})($e_6));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_6) use ($monadWriterT2_5_3) {
  $__num = \func_num_args();
  $__res = $monadWriterT2_5_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadThrowWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajThrowmajWritermajT';

// Control_Monad_Writer_Trans_monadErrorWriterT
function majControl_majMonad_majWriter_majTrans_monadmajErrormajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajErrormajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadThrowWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_monadThrowWriterT'])($dictMonoid_0);
  $__res = function($dictMonadError_2) use ($monadThrowWriterT1_1_0) {
  $__num = \func_num_args();
  $monadThrowWriterT2_3_1 = ($monadThrowWriterT1_1_0)((($dictMonadError_2)->{'MonadThrow0'})(null));
  $__res = (object)["catchError" => function($v_4) use ($dictMonadError_2) {
  $__num = \func_num_args();
  $__res = function($h_5) use ($dictMonadError_2, $v_4) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Writer_Trans_WriterT']))(((($dictMonadError_2)->{'catchError'})($v_4))(function($e_6) use ($h_5) {
  $__num = \func_num_args();
  $__res = ($h_5)($e_6);
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
}, "MonadThrow0" => function($_dollar___unused_4) use ($monadThrowWriterT2_3_1) {
  $__num = \func_num_args();
  $__res = $monadThrowWriterT2_3_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadErrorWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajErrormajWritermajT';

// Control_Monad_Writer_Trans_monadSTWriterT
function majControl_majMonad_majWriter_majTrans_monadmajSmajTmajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajSmajTmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $lift_1_0 = ($GLOBALS['Control_Monad_Trans_Class_lift'])(($GLOBALS['Control_Monad_Writer_Trans_monadTransWriterT'])($dictMonoid_0));
  $monadWriterT1_2_1 = ($GLOBALS['Control_Monad_Writer_Trans_monadWriterT'])($dictMonoid_0);
  $__res = function($dictMonadST_3) use ($lift_1_0, $monadWriterT1_2_1) {
  $__num = \func_num_args();
  $Monad0_4_2 = (($dictMonadST_3)->{'Monad0'})(null);
  $monadWriterT2_5_3 = ($monadWriterT1_2_1)($Monad0_4_2);
  $__res = (object)["liftST" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($lift_1_0)($Monad0_4_2)))(($GLOBALS['Control_Monad_ST_Class_liftST'])($dictMonadST_3)), "Monad0" => function($_dollar___unused_6) use ($monadWriterT2_5_3) {
  $__num = \func_num_args();
  $__res = $monadWriterT2_5_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadSTWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajSmajTmajWritermajT';

// Control_Monad_Writer_Trans_monoidWriterT
function majControl_majMonad_majWriter_majTrans_monoidmajWritermajT($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monoidmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $semigroupWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_semigroupWriterT'])((($dictApplicative_0)->{'Apply0'})(null));
  $__res = function($dictMonoid_2) use ($dictApplicative_0, $semigroupWriterT1_1_0) {
  $__num = \func_num_args();
  $applicativeWriterT1_3_1 = (($GLOBALS['Control_Monad_Writer_Trans_applicativeWriterT'])($dictMonoid_2))($dictApplicative_0);
  $semigroupWriterT2_4_2 = ($semigroupWriterT1_1_0)((($dictMonoid_2)->{'Semigroup0'})(null));
  $__res = function($dictMonoid1_5) use ($applicativeWriterT1_3_1, $semigroupWriterT2_4_2) {
  $__num = \func_num_args();
  $semigroupWriterT3_6_3 = ($semigroupWriterT2_4_2)((($dictMonoid1_5)->{'Semigroup0'})(null));
  $__res = (object)["mempty" => (($GLOBALS['Control_Applicative_pure'])($applicativeWriterT1_3_1))(($GLOBALS['Data_Monoid_mempty'])($dictMonoid1_5)), "Semigroup0" => function($_dollar___unused_7) use ($semigroupWriterT3_6_3) {
  $__num = \func_num_args();
  $__res = $semigroupWriterT3_6_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
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
$GLOBALS['Control_Monad_Writer_Trans_monoidWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monoidmajWritermajT';

// Control_Monad_Writer_Trans_altWriterT
function majControl_majMonad_majWriter_majTrans_altmajWritermajT($dictAlt_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_altmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_functorWriterT'])((($dictAlt_0)->{'Functor0'})(null));
  $__res = (object)["alt" => function($v_2) use ($dictAlt_0) {
  $__num = \func_num_args();
  $__res = function($v1_3) use ($dictAlt_0, $v_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Alt_alt'])($dictAlt_0))($v_2))($v1_3);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Functor0" => function($_dollar___unused_2) use ($functorWriterT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorWriterT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_altWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_altmajWritermajT';

// Control_Monad_Writer_Trans_plusWriterT
function majControl_majMonad_majWriter_majTrans_plusmajWritermajT($dictPlus_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_plusmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $altWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_altWriterT'])((($dictPlus_0)->{'Alt0'})(null));
  $__res = (object)["empty" => ($GLOBALS['Control_Plus_empty'])($dictPlus_0), "Alt0" => function($_dollar___unused_2) use ($altWriterT1_1_0) {
  $__num = \func_num_args();
  $__res = $altWriterT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_plusWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_plusmajWritermajT';

// Control_Monad_Writer_Trans_alternativeWriterT
function majControl_majMonad_majWriter_majTrans_alternativemajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_alternativemajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_applicativeWriterT'])($dictMonoid_0);
  $__res = function($dictAlternative_2) use ($applicativeWriterT1_1_0) {
  $__num = \func_num_args();
  $applicativeWriterT2_3_1 = ($applicativeWriterT1_1_0)((($dictAlternative_2)->{'Applicative0'})(null));
  $plusWriterT1_4_2 = ($GLOBALS['Control_Monad_Writer_Trans_plusWriterT'])((($dictAlternative_2)->{'Plus1'})(null));
  $__res = (object)["Applicative0" => function($_dollar___unused_5) use ($applicativeWriterT2_3_1) {
  $__num = \func_num_args();
  $__res = $applicativeWriterT2_3_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Plus1" => function($_dollar___unused_5) use ($plusWriterT1_4_2) {
  $__num = \func_num_args();
  $__res = $plusWriterT1_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_alternativeWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_alternativemajWritermajT';

// Control_Monad_Writer_Trans_monadPlusWriterT
function majControl_majMonad_majWriter_majTrans_monadmajPlusmajWritermajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majWriter_majTrans_monadmajPlusmajWritermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadWriterT1_1_0 = ($GLOBALS['Control_Monad_Writer_Trans_monadWriterT'])($dictMonoid_0);
  $alternativeWriterT1_2_1 = ($GLOBALS['Control_Monad_Writer_Trans_alternativeWriterT'])($dictMonoid_0);
  $__res = function($dictMonadPlus_3) use ($alternativeWriterT1_2_1, $monadWriterT1_1_0) {
  $__num = \func_num_args();
  $monadWriterT2_4_2 = ($monadWriterT1_1_0)((($dictMonadPlus_3)->{'Monad0'})(null));
  $alternativeWriterT2_5_3 = ($alternativeWriterT1_2_1)((($dictMonadPlus_3)->{'Alternative1'})(null));
  $__res = (object)["Monad0" => function($_dollar___unused_6) use ($monadWriterT2_4_2) {
  $__num = \func_num_args();
  $__res = $monadWriterT2_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Alternative1" => function($_dollar___unused_6) use ($alternativeWriterT2_5_3) {
  $__num = \func_num_args();
  $__res = $alternativeWriterT2_5_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Writer_Trans_monadPlusWriterT'] = __NAMESPACE__ . '\\majControl_majMonad_majWriter_majTrans_monadmajPlusmajWritermajT';

