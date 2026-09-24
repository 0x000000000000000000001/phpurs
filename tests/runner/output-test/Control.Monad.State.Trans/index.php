<?php

namespace Control\Monad\State\Trans;

// ALL IMPORTS: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Lazy, Control.Monad, Control.Monad.Cont.Class, Control.Monad.Error.Class, Control.Monad.Reader.Class, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.State.Trans, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Function, Data.Functor, Data.Monoid, Data.Newtype, Data.Semigroup, Data.Tuple, Data.Unit, Effect.Class, Prelude, Prim
// TO REQUIRE: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Lazy, Control.Monad, Control.Monad.Cont.Class, Control.Monad.Error.Class, Control.Monad.Reader.Class, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.State.Trans, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Function, Data.Functor, Data.Monoid, Data.Newtype, Data.Semigroup, Data.Tuple, Data.Unit, Effect.Class, Prelude
require_once __DIR__ . '/../Control.Alt/index.php';
require_once __DIR__ . '/../Control.Alternative/index.php';
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Apply/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Lazy/index.php';
require_once __DIR__ . '/../Control.Monad/index.php';
require_once __DIR__ . '/../Control.Monad.Cont.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Error.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Reader.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Rec.Class/index.php';
require_once __DIR__ . '/../Control.Monad.ST.Class/index.php';
require_once __DIR__ . '/../Control.Monad.State.Class/index.php';
require_once __DIR__ . '/../Control.Monad.State.Trans/index.php';
require_once __DIR__ . '/../Control.Monad.Trans.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Writer.Class/index.php';
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




// Control_Monad_State_Trans_StateT
function majControl_majMonad_majState_majTrans_majStatemajT($x_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_majStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $x_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_StateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_majStatemajT';

// Control_Monad_State_Trans_withStateT
function majControl_majMonad_majState_majTrans_withmajStatemajT($f_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_withmajStatemajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($v_1))($f_0);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_withStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_withmajStatemajT';

// Control_Monad_State_Trans_runStateT
function majControl_majMonad_majState_majTrans_runmajStatemajT($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_runmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $v_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_runStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_runmajStatemajT';

// Control_Monad_State_Trans_newtypeStateT
$GLOBALS['Control_Monad_State_Trans_newtypeStateT'] = (object)["Coercible0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = null;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_State_Trans_monadTransStateT
$GLOBALS['Control_Monad_State_Trans_monadTransStateT'] = (object)["lift" => function($dictMonad_0) {
  $__num = \func_num_args();
  $Bind1_1_0 = (($dictMonad_0)->{'Bind1'})(null);
  $pure_2_1 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $__res = function($m_3) use ($Bind1_1_0, $pure_2_1) {
  $__num = \func_num_args();
  $__res = function($s_4) use ($Bind1_1_0, $m_3, $pure_2_1) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))($m_3))(function($x_5) use ($pure_2_1, $s_4) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_2_1))((($GLOBALS['Data_Tuple_Tuple'])($x_5))($s_4));
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
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_State_Trans_mapStateT
function majControl_majMonad_majState_majTrans_mapmajStatemajT($f_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_mapmajStatemajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($f_0))($v_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_mapStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_mapmajStatemajT';

// Control_Monad_State_Trans_lazyStateT
$GLOBALS['Control_Monad_State_Trans_lazyStateT'] = (object)["defer" => function($f_0) {
  $__num = \func_num_args();
  $__res = function($s_1) use ($f_0) {
  $__num = \func_num_args();
  $__res = (($f_0)($GLOBALS['Data_Unit_unit']))($s_1);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_State_Trans_functorStateT
function majControl_majMonad_majState_majTrans_functormajStatemajT($dictFunctor_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_functormajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["map" => function($f_1) use ($dictFunctor_0) {
  $__num = \func_num_args();
  $__res = function($v_2) use ($dictFunctor_0, $f_1) {
  $__num = \func_num_args();
  $__res = function($s_3) use ($dictFunctor_0, $f_1, $v_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Functor_map'])($dictFunctor_0))(function($v1_4) use ($f_1) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Tuple_Tuple'])(($f_1)(($v1_4)->{'value0'})))(($v1_4)->{'value1'});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(($v_2)($s_3));
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
$GLOBALS['Control_Monad_State_Trans_functorStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_functormajStatemajT';

// Control_Monad_State_Trans_execStateT
function majControl_majMonad_majState_majTrans_execmajStatemajT($dictFunctor_0, $v_1 = null, $s_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_execmajStatemajT';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = ((($GLOBALS['Data_Functor_map'])($dictFunctor_0))($GLOBALS['Data_Tuple_snd']))(($v_1)($s_2));
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_execStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_execmajStatemajT';

// Control_Monad_State_Trans_evalStateT
function majControl_majMonad_majState_majTrans_evalmajStatemajT($dictFunctor_0, $v_1 = null, $s_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_evalmajStatemajT';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = ((($GLOBALS['Data_Functor_map'])($dictFunctor_0))($GLOBALS['Data_Tuple_fst']))(($v_1)($s_2));
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_evalStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_evalmajStatemajT';

// Control_Monad_State_Trans_monadStateT
function majControl_majMonad_majState_majTrans_monadmajStatemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["Applicative0" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_State_Trans_applicativeStateT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Bind1" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_State_Trans_bindStateT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajStatemajT';

// Control_Monad_State_Trans_bindStateT
function majControl_majMonad_majState_majTrans_bindmajStatemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_bindmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Bind1_1_0 = (($dictMonad_0)->{'Bind1'})(null);
  $__res = (object)["bind" => function($v_2) use ($Bind1_1_0) {
  $__num = \func_num_args();
  $__res = function($f_3) use ($Bind1_1_0, $v_2) {
  $__num = \func_num_args();
  $__res = function($s_4) use ($Bind1_1_0, $f_3, $v_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))(($v_2)($s_4)))(function($v1_5) use ($f_3) {
  $__num = \func_num_args();
  $__res = (($f_3)(($v1_5)->{'value0'}))(($v1_5)->{'value1'});
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
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Apply0" => function($_dollar___unused_2) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_State_Trans_applyStateT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_bindStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_bindmajStatemajT';

// Control_Monad_State_Trans_applyStateT
function majControl_majMonad_majState_majTrans_applymajStatemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_applymajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorStateT1_1_0 = ($GLOBALS['Control_Monad_State_Trans_functorStateT'])((((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null));
  $__res = (object)["apply" => ($GLOBALS['Control_Monad_ap'])(($GLOBALS['Control_Monad_State_Trans_monadStateT'])($dictMonad_0)), "Functor0" => function($_dollar___unused_2) use ($functorStateT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorStateT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_applyStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_applymajStatemajT';

// Control_Monad_State_Trans_applicativeStateT
function majControl_majMonad_majState_majTrans_applicativemajStatemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_applicativemajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $__res = (object)["pure" => function($a_2) use ($pure_1_0) {
  $__num = \func_num_args();
  $__res = function($s_3) use ($a_2, $pure_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_1_0))((($GLOBALS['Data_Tuple_Tuple'])($a_2))($s_3));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Apply0" => function($_dollar___unused_2) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_State_Trans_applyStateT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_applicativeStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_applicativemajStatemajT';

// Control_Monad_State_Trans_semigroupStateT
function majControl_majMonad_majState_majTrans_semigroupmajStatemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_semigroupmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applyStateT1_1_0 = ($GLOBALS['Control_Monad_State_Trans_applyStateT'])($dictMonad_0);
  $__res = function($dictSemigroup_2) use ($applyStateT1_1_0) {
  $__num = \func_num_args();
  $__res = (object)["append" => (($GLOBALS['Control_Apply_lift2'])($applyStateT1_1_0))(($GLOBALS['Data_Semigroup_append'])($dictSemigroup_2))];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_semigroupStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_semigroupmajStatemajT';

// Control_Monad_State_Trans_monadAskStateT
function majControl_majMonad_majState_majTrans_monadmajAskmajStatemajT($dictMonadAsk_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajAskmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadStateT1_1_0 = ($GLOBALS['Control_Monad_State_Trans_monadStateT'])((($dictMonadAsk_0)->{'Monad0'})(null));
  $__local_var_2_1 = (($dictMonadAsk_0)->{'Monad0'})(null);
  $Bind1_3_2 = (($__local_var_2_1)->{'Bind1'})(null);
  $pure_4_3 = ($GLOBALS['Control_Applicative_pure'])((($__local_var_2_1)->{'Applicative0'})(null));
  $__local_var_5_4 = ($GLOBALS['Control_Monad_Reader_Class_ask'])($dictMonadAsk_0);
  $__res = (object)["ask" => function($s_6) use ($Bind1_3_2, $__local_var_5_4, $pure_4_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))($__local_var_5_4))(function($x_7) use ($pure_4_3, $s_6) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_4_3))((($GLOBALS['Data_Tuple_Tuple'])($x_7))($s_6));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_2) use ($monadStateT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadStateT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadAskStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajAskmajStatemajT';

// Control_Monad_State_Trans_monadReaderStateT
function majControl_majMonad_majState_majTrans_monadmajReadermajStatemajT($dictMonadReader_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajReadermajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadAskStateT1_1_0 = ($GLOBALS['Control_Monad_State_Trans_monadAskStateT'])((($dictMonadReader_0)->{'MonadAsk0'})(null));
  $__res = (object)["local" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_State_Trans_mapStateT']))(($GLOBALS['Control_Monad_Reader_Class_local'])($dictMonadReader_0)), "MonadAsk0" => function($_dollar___unused_2) use ($monadAskStateT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadAskStateT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadReaderStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajReadermajStatemajT';

// Control_Monad_State_Trans_monadContStateT
function majControl_majMonad_majState_majTrans_monadmajContmajStatemajT($dictMonadCont_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajContmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadStateT1_1_0 = ($GLOBALS['Control_Monad_State_Trans_monadStateT'])((($dictMonadCont_0)->{'Monad0'})(null));
  $__res = (object)["callCC" => function($f_2) use ($dictMonadCont_0) {
  $__num = \func_num_args();
  $__res = function($s_3) use ($dictMonadCont_0, $f_2) {
  $__num = \func_num_args();
  $__res = (($dictMonadCont_0)->{'callCC'})(function($c_4) use ($f_2, $s_3) {
  $__num = \func_num_args();
  $__res = (($f_2)(function($a_5) use ($c_4) {
  $__num = \func_num_args();
  $__res = function($s_prime__6) use ($a_5, $c_4) {
  $__num = \func_num_args();
  $__res = ($c_4)((($GLOBALS['Data_Tuple_Tuple'])($a_5))($s_prime__6));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($s_3);
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
}, "Monad0" => function($_dollar___unused_2) use ($monadStateT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadStateT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadContStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajContmajStatemajT';

// Control_Monad_State_Trans_monadEffectState
function majControl_majMonad_majState_majTrans_monadmajEffectmajState($dictMonadEffect_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajEffectmajState';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadEffect_0)->{'Monad0'})(null);
  $monadStateT1_2_1 = ($GLOBALS['Control_Monad_State_Trans_monadStateT'])($Monad0_1_0);
  $__res = (object)["liftEffect" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_State_Trans_monadTransStateT']))($Monad0_1_0)))(($GLOBALS['Effect_Class_liftEffect'])($dictMonadEffect_0)), "Monad0" => function($_dollar___unused_3) use ($monadStateT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadStateT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadEffectState'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajEffectmajState';

// Control_Monad_State_Trans_monadRecStateT
function majControl_majMonad_majState_majTrans_monadmajRecmajStatemajT($dictMonadRec_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajRecmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadRec_0)->{'Monad0'})(null);
  $Bind1_2_1 = (($Monad0_1_0)->{'Bind1'})(null);
  $Applicative0_3_2 = (($Monad0_1_0)->{'Applicative0'})(null);
  $monadStateT1_4_3 = ($GLOBALS['Control_Monad_State_Trans_monadStateT'])($Monad0_1_0);
  $__res = (object)["tailRecM" => function($f_5) use ($Applicative0_3_2, $Bind1_2_1, $dictMonadRec_0) {
  $__num = \func_num_args();
  $__res = function($a_6) use ($Applicative0_3_2, $Bind1_2_1, $dictMonadRec_0, $f_5) {
  $__num = \func_num_args();
  $__res = function($s_7) use ($Applicative0_3_2, $Bind1_2_1, $a_6, $dictMonadRec_0, $f_5) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Monad_Rec_Class_tailRecM'])($dictMonadRec_0))(function($v_8) use ($Applicative0_3_2, $Bind1_2_1, $f_5) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_1))((($f_5)(($v_8)->{'value0'}))(($v_8)->{'value1'})))(function($v2_9) use ($Applicative0_3_2) {
  $__num = \func_num_args();
  $__t4 = null;
  if (($v2_9)->{'value0'} instanceof \Control\Monad\Rec\Class\Control_Monad_Rec_Class_Loop) {
$__t4 = ($GLOBALS['Control_Monad_Rec_Class_Loop'])((($GLOBALS['Data_Tuple_Tuple'])((($v2_9)->{'value0'})->{'value0'}))(($v2_9)->{'value1'}));
goto end_branch_4;;
};
  if (($v2_9)->{'value0'} instanceof \Control\Monad\Rec\Class\Control_Monad_Rec_Class_Done) {
$__t4 = ($GLOBALS['Control_Monad_Rec_Class_Done'])((($GLOBALS['Data_Tuple_Tuple'])((($v2_9)->{'value0'})->{'value0'}))(($v2_9)->{'value1'}));
goto end_branch_4;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t4 = null;
  end_branch_4:;
  $__res = (($GLOBALS['Control_Applicative_pure'])($Applicative0_3_2))($__t4);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))((($GLOBALS['Data_Tuple_Tuple'])($a_6))($s_7));
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
}, "Monad0" => function($_dollar___unused_5) use ($monadStateT1_4_3) {
  $__num = \func_num_args();
  $__res = $monadStateT1_4_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadRecStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajRecmajStatemajT';

// Control_Monad_State_Trans_monadStateStateT
function majControl_majMonad_majState_majTrans_monadmajStatemajStatemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajStatemajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $monadStateT1_2_1 = ($GLOBALS['Control_Monad_State_Trans_monadStateT'])($dictMonad_0);
  $__res = (object)["state" => function($f_3) use ($pure_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_State_Trans_StateT']))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($pure_1_0))($f_3));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_3) use ($monadStateT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadStateT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadStateStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajStatemajStatemajT';

// Control_Monad_State_Trans_monadTellStateT
function majControl_majMonad_majState_majTrans_monadmajTellmajStatemajT($dictMonadTell_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajTellmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad1_1_0 = (($dictMonadTell_0)->{'Monad1'})(null);
  $Semigroup0_2_1 = (($dictMonadTell_0)->{'Semigroup0'})(null);
  $monadStateT1_3_2 = ($GLOBALS['Control_Monad_State_Trans_monadStateT'])($Monad1_1_0);
  $__res = (object)["tell" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_State_Trans_monadTransStateT']))($Monad1_1_0)))(($GLOBALS['Control_Monad_Writer_Class_tell'])($dictMonadTell_0)), "Semigroup0" => function($_dollar___unused_4) use ($Semigroup0_2_1) {
  $__num = \func_num_args();
  $__res = $Semigroup0_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad1" => function($_dollar___unused_4) use ($monadStateT1_3_2) {
  $__num = \func_num_args();
  $__res = $monadStateT1_3_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadTellStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajTellmajStatemajT';

// Control_Monad_State_Trans_monadWriterStateT
function majControl_majMonad_majState_majTrans_monadmajWritermajStatemajT($dictMonadWriter_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajWritermajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $MonadTell1_1_0 = (($dictMonadWriter_0)->{'MonadTell1'})(null);
  $Monad1_2_1 = (($MonadTell1_1_0)->{'Monad1'})(null);
  $Bind1_3_2 = (($Monad1_2_1)->{'Bind1'})(null);
  $Applicative0_4_3 = (($Monad1_2_1)->{'Applicative0'})(null);
  $pure_5_4 = ($GLOBALS['Control_Applicative_pure'])($Applicative0_4_3);
  $pure1_6_5 = ($GLOBALS['Control_Applicative_pure'])($Applicative0_4_3);
  $Monoid0_7_6 = (($dictMonadWriter_0)->{'Monoid0'})(null);
  $monadTellStateT1_8_7 = ($GLOBALS['Control_Monad_State_Trans_monadTellStateT'])($MonadTell1_1_0);
  $__res = (object)["listen" => function($m_9) use ($Bind1_3_2, $dictMonadWriter_0, $pure_5_4) {
  $__num = \func_num_args();
  $__res = function($s_10) use ($Bind1_3_2, $dictMonadWriter_0, $m_9, $pure_5_4) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))((($dictMonadWriter_0)->{'listen'})(($m_9)($s_10))))(function($v_11) use ($pure_5_4) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_5_4))((($GLOBALS['Data_Tuple_Tuple'])((($GLOBALS['Data_Tuple_Tuple'])((($v_11)->{'value0'})->{'value0'}))(($v_11)->{'value1'})))((($v_11)->{'value0'})->{'value1'}));
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
}, "pass" => function($m_9) use ($Bind1_3_2, $dictMonadWriter_0, $pure1_6_5) {
  $__num = \func_num_args();
  $__res = function($s_10) use ($Bind1_3_2, $dictMonadWriter_0, $m_9, $pure1_6_5) {
  $__num = \func_num_args();
  $__res = (($dictMonadWriter_0)->{'pass'})(((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))(($m_9)($s_10)))(function($v_11) use ($pure1_6_5) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure1_6_5))((($GLOBALS['Data_Tuple_Tuple'])((($GLOBALS['Data_Tuple_Tuple'])((($v_11)->{'value0'})->{'value0'}))(($v_11)->{'value1'})))((($v_11)->{'value0'})->{'value1'}));
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
}, "Monoid0" => function($_dollar___unused_9) use ($Monoid0_7_6) {
  $__num = \func_num_args();
  $__res = $Monoid0_7_6;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadTell1" => function($_dollar___unused_9) use ($monadTellStateT1_8_7) {
  $__num = \func_num_args();
  $__res = $monadTellStateT1_8_7;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadWriterStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajWritermajStatemajT';

// Control_Monad_State_Trans_monadThrowStateT
function majControl_majMonad_majState_majTrans_monadmajThrowmajStatemajT($dictMonadThrow_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajThrowmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadThrow_0)->{'Monad0'})(null);
  $monadStateT1_2_1 = ($GLOBALS['Control_Monad_State_Trans_monadStateT'])((($dictMonadThrow_0)->{'Monad0'})(null));
  $__res = (object)["throwError" => function($e_3) use ($Monad0_1_0, $dictMonadThrow_0) {
  $__num = \func_num_args();
  $Bind1_4_2 = (($Monad0_1_0)->{'Bind1'})(null);
  $pure_5_3 = ($GLOBALS['Control_Applicative_pure'])((($Monad0_1_0)->{'Applicative0'})(null));
  $__local_var_6_4 = (($dictMonadThrow_0)->{'throwError'})($e_3);
  $__res = function($s_7) use ($Bind1_4_2, $__local_var_6_4, $pure_5_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_4_2))($__local_var_6_4))(function($x_8) use ($pure_5_3, $s_7) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_5_3))((($GLOBALS['Data_Tuple_Tuple'])($x_8))($s_7));
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
}, "Monad0" => function($_dollar___unused_3) use ($monadStateT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadStateT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadThrowStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajThrowmajStatemajT';

// Control_Monad_State_Trans_monadErrorStateT
function majControl_majMonad_majState_majTrans_monadmajErrormajStatemajT($dictMonadError_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajErrormajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadThrowStateT1_1_0 = ($GLOBALS['Control_Monad_State_Trans_monadThrowStateT'])((($dictMonadError_0)->{'MonadThrow0'})(null));
  $__res = (object)["catchError" => function($v_2) use ($dictMonadError_0) {
  $__num = \func_num_args();
  $__res = function($h_3) use ($dictMonadError_0, $v_2) {
  $__num = \func_num_args();
  $__res = function($s_4) use ($dictMonadError_0, $h_3, $v_2) {
  $__num = \func_num_args();
  $__res = ((($dictMonadError_0)->{'catchError'})(($v_2)($s_4)))(function($e_5) use ($h_3, $s_4) {
  $__num = \func_num_args();
  $__res = (($h_3)($e_5))($s_4);
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
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadThrow0" => function($_dollar___unused_2) use ($monadThrowStateT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadThrowStateT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadErrorStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajErrormajStatemajT';

// Control_Monad_State_Trans_monadSTStateT
function majControl_majMonad_majState_majTrans_monadmajSmajTmajStatemajT($dictMonadST_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajSmajTmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadST_0)->{'Monad0'})(null);
  $monadStateT1_2_1 = ($GLOBALS['Control_Monad_State_Trans_monadStateT'])($Monad0_1_0);
  $__res = (object)["liftST" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_State_Trans_monadTransStateT']))($Monad0_1_0)))(($GLOBALS['Control_Monad_ST_Class_liftST'])($dictMonadST_0)), "Monad0" => function($_dollar___unused_3) use ($monadStateT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadStateT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadSTStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajSmajTmajStatemajT';

// Control_Monad_State_Trans_monoidStateT
function majControl_majMonad_majState_majTrans_monoidmajStatemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monoidmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeStateT1_1_0 = ($GLOBALS['Control_Monad_State_Trans_applicativeStateT'])($dictMonad_0);
  $semigroupStateT1_2_1 = ($GLOBALS['Control_Monad_State_Trans_semigroupStateT'])($dictMonad_0);
  $__res = function($dictMonoid_3) use ($applicativeStateT1_1_0, $semigroupStateT1_2_1) {
  $__num = \func_num_args();
  $semigroupStateT2_4_2 = ($semigroupStateT1_2_1)((($dictMonoid_3)->{'Semigroup0'})(null));
  $__res = (object)["mempty" => (($GLOBALS['Control_Applicative_pure'])($applicativeStateT1_1_0))(($GLOBALS['Data_Monoid_mempty'])($dictMonoid_3)), "Semigroup0" => function($_dollar___unused_5) use ($semigroupStateT2_4_2) {
  $__num = \func_num_args();
  $__res = $semigroupStateT2_4_2;
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
$GLOBALS['Control_Monad_State_Trans_monoidStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monoidmajStatemajT';

// Control_Monad_State_Trans_altStateT
function majControl_majMonad_majState_majTrans_altmajStatemajT($dictMonad_0, $dictAlt_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_altmajStatemajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $functorStateT1_2_0 = ($GLOBALS['Control_Monad_State_Trans_functorStateT'])((($dictAlt_1)->{'Functor0'})(null));
  $__res = (object)["alt" => function($v_3) use ($dictAlt_1) {
  $__num = \func_num_args();
  $__res = function($v1_4) use ($dictAlt_1, $v_3) {
  $__num = \func_num_args();
  $__res = function($s_5) use ($dictAlt_1, $v1_4, $v_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Alt_alt'])($dictAlt_1))(($v_3)($s_5)))(($v1_4)($s_5));
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
}, "Functor0" => function($_dollar___unused_3) use ($functorStateT1_2_0) {
  $__num = \func_num_args();
  $__res = $functorStateT1_2_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_altStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_altmajStatemajT';

// Control_Monad_State_Trans_plusStateT
function majControl_majMonad_majState_majTrans_plusmajStatemajT($dictMonad_0, $dictPlus_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_plusmajStatemajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $empty_2_0 = ($GLOBALS['Control_Plus_empty'])($dictPlus_1);
  $altStateT2_3_1 = (($GLOBALS['Control_Monad_State_Trans_altStateT'])($dictMonad_0))((($dictPlus_1)->{'Alt0'})(null));
  $__res = (object)["empty" => function($v_4) use ($empty_2_0) {
  $__num = \func_num_args();
  $__res = $empty_2_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Alt0" => function($_dollar___unused_4) use ($altStateT2_3_1) {
  $__num = \func_num_args();
  $__res = $altStateT2_3_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_plusStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_plusmajStatemajT';

// Control_Monad_State_Trans_alternativeStateT
function majControl_majMonad_majState_majTrans_alternativemajStatemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_alternativemajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeStateT1_1_0 = ($GLOBALS['Control_Monad_State_Trans_applicativeStateT'])($dictMonad_0);
  $__res = function($dictAlternative_2) use ($applicativeStateT1_1_0, $dictMonad_0) {
  $__num = \func_num_args();
  $plusStateT2_3_1 = (($GLOBALS['Control_Monad_State_Trans_plusStateT'])($dictMonad_0))((($dictAlternative_2)->{'Plus1'})(null));
  $__res = (object)["Applicative0" => function($_dollar___unused_4) use ($applicativeStateT1_1_0) {
  $__num = \func_num_args();
  $__res = $applicativeStateT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Plus1" => function($_dollar___unused_4) use ($plusStateT2_3_1) {
  $__num = \func_num_args();
  $__res = $plusStateT2_3_1;
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
$GLOBALS['Control_Monad_State_Trans_alternativeStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_alternativemajStatemajT';

// Control_Monad_State_Trans_monadPlusStateT
function majControl_majMonad_majState_majTrans_monadmajPlusmajStatemajT($dictMonadPlus_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majState_majTrans_monadmajPlusmajStatemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadPlus_0)->{'Monad0'})(null);
  $monadStateT1_2_1 = ($GLOBALS['Control_Monad_State_Trans_monadStateT'])($Monad0_1_0);
  $alternativeStateT1_3_2 = (($GLOBALS['Control_Monad_State_Trans_alternativeStateT'])($Monad0_1_0))((($dictMonadPlus_0)->{'Alternative1'})(null));
  $__res = (object)["Monad0" => function($_dollar___unused_4) use ($monadStateT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadStateT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Alternative1" => function($_dollar___unused_4) use ($alternativeStateT1_3_2) {
  $__num = \func_num_args();
  $__res = $alternativeStateT1_3_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_State_Trans_monadPlusStateT'] = __NAMESPACE__ . '\\majControl_majMonad_majState_majTrans_monadmajPlusmajStatemajT';

