<?php

namespace Control\Monad\RWS\Trans;

// ALL IMPORTS: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Lazy, Control.Monad, Control.Monad.Error.Class, Control.Monad.RWS.Trans, Control.Monad.Reader.Class, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.Plus, Control.Semigroupoid, Data.Function, Data.Functor, Data.Monoid, Data.Newtype, Data.Semigroup, Data.Tuple, Data.Unit, Effect.Class, Prelude, Prim
// TO REQUIRE: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Lazy, Control.Monad, Control.Monad.Error.Class, Control.Monad.RWS.Trans, Control.Monad.Reader.Class, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.Plus, Control.Semigroupoid, Data.Function, Data.Functor, Data.Monoid, Data.Newtype, Data.Semigroup, Data.Tuple, Data.Unit, Effect.Class, Prelude
require_once __DIR__ . '/../Control.Alt/index.php';
require_once __DIR__ . '/../Control.Alternative/index.php';
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Apply/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Lazy/index.php';
require_once __DIR__ . '/../Control.Monad/index.php';
require_once __DIR__ . '/../Control.Monad.Error.Class/index.php';
require_once __DIR__ . '/../Control.Monad.RWS.Trans/index.php';
require_once __DIR__ . '/../Control.Monad.Reader.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Rec.Class/index.php';
require_once __DIR__ . '/../Control.Monad.ST.Class/index.php';
require_once __DIR__ . '/../Control.Monad.State.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Trans.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Writer.Class/index.php';
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


final class Control_Monad_RWS_Trans_RWSResult { public $tag = 'RWSResult'; public function __construct(public  $value0, public  $value1, public  $value2) {} }

// Control_Monad_RWS_Trans_RWSResult
$GLOBALS['Control_Monad_RWS_Trans_RWSResult'] = (function() {
  $__fn = function($value0, $value1 = null, $value2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult($value0, $value1, $value2);
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})();

// Control_Monad_RWS_Trans_RWST
function majControl_majMonad_majRmajWmajS_majTrans_majRmajWmajSmajT($x_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_majRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $x_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_RWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_majRmajWmajSmajT';

// Control_Monad_RWS_Trans_withRWST
function majControl_majMonad_majRmajWmajS_majTrans_withmajRmajWmajSmajT($f_0, $m_1 = null, $r_2 = null, $s_3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_withmajRmajWmajSmajT';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = (($GLOBALS['Data_Tuple_uncurry'])($m_1))((($f_0)($r_2))($s_3));
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_withRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_withmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_runRWST
function majControl_majMonad_majRmajWmajS_majTrans_runmajRmajWmajSmajT($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_runmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $v_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_runRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_runmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_newtypeRWST
$GLOBALS['Control_Monad_RWS_Trans_newtypeRWST'] = (object)["Coercible0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = null;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_RWS_Trans_monadTransRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajTransmajRmajWmajSmajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajTransmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["lift" => function($dictMonad_1) use ($dictMonoid_0) {
  $__num = \func_num_args();
  $Bind1_2_0 = (($dictMonad_1)->{'Bind1'})(null);
  $pure_3_1 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_1)->{'Applicative0'})(null));
  $__res = function($m_4) use ($Bind1_2_0, $dictMonoid_0, $pure_3_1) {
  $__num = \func_num_args();
  $__res = function($v_5) use ($Bind1_2_0, $dictMonoid_0, $m_4, $pure_3_1) {
  $__num = \func_num_args();
  $__res = function($s_6) use ($Bind1_2_0, $dictMonoid_0, $m_4, $pure_3_1) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_0))($m_4))(function($a_7) use ($dictMonoid_0, $pure_3_1, $s_6) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_3_1))(new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult($s_6, $a_7, ($GLOBALS['Data_Monoid_mempty'])($dictMonoid_0)));
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
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_monadTransRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajTransmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_mapRWST
function majControl_majMonad_majRmajWmajS_majTrans_mapmajRmajWmajSmajT($f_0, $v_1 = null, $r_2 = null, $s_3 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_mapmajRmajWmajSmajT';
  if ($__num < 4) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 4);
  }
  $__res = ($f_0)((($v_1)($r_2))($s_3));
  goto __end;;
  __end:
  return 4 < $__num ? $__res(...\array_slice(\func_get_args(), 4)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_mapRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_mapmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_lazyRWST
$GLOBALS['Control_Monad_RWS_Trans_lazyRWST'] = (object)["defer" => function($f_0) {
  $__num = \func_num_args();
  $__res = function($r_1) use ($f_0) {
  $__num = \func_num_args();
  $__res = function($s_2) use ($f_0, $r_1) {
  $__num = \func_num_args();
  $__res = ((($f_0)($GLOBALS['Data_Unit_unit']))($r_1))($s_2);
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

// Control_Monad_RWS_Trans_functorRWST
function majControl_majMonad_majRmajWmajS_majTrans_functormajRmajWmajSmajT($dictFunctor_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_functormajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["map" => function($f_1) use ($dictFunctor_0) {
  $__num = \func_num_args();
  $__res = function($v_2) use ($dictFunctor_0, $f_1) {
  $__num = \func_num_args();
  $__res = function($r_3) use ($dictFunctor_0, $f_1, $v_2) {
  $__num = \func_num_args();
  $__res = function($s_4) use ($dictFunctor_0, $f_1, $r_3, $v_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Functor_map'])($dictFunctor_0))(function($v1_5) use ($f_1) {
  $__num = \func_num_args();
  $__res = new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult(($v1_5)->{'value0'}, ($f_1)(($v1_5)->{'value1'}), ($v1_5)->{'value2'});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))((($v_2)($r_3))($s_4));
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
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_functorRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_functormajRmajWmajSmajT';

// Control_Monad_RWS_Trans_execRWST
function majControl_majMonad_majRmajWmajS_majTrans_execmajRmajWmajSmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_execmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Bind1_1_0 = (($dictMonad_0)->{'Bind1'})(null);
  $Applicative0_2_1 = (($dictMonad_0)->{'Applicative0'})(null);
  $__res = function($v_3) use ($Applicative0_2_1, $Bind1_1_0) {
  $__num = \func_num_args();
  $__res = function($r_4) use ($Applicative0_2_1, $Bind1_1_0, $v_3) {
  $__num = \func_num_args();
  $__res = function($s_5) use ($Applicative0_2_1, $Bind1_1_0, $r_4, $v_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))((($v_3)($r_4))($s_5)))(function($v1_6) use ($Applicative0_2_1) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1))((($GLOBALS['Data_Tuple_Tuple'])(($v1_6)->{'value0'}))(($v1_6)->{'value2'}));
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
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_execRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_execmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_evalRWST
function majControl_majMonad_majRmajWmajS_majTrans_evalmajRmajWmajSmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_evalmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Bind1_1_0 = (($dictMonad_0)->{'Bind1'})(null);
  $Applicative0_2_1 = (($dictMonad_0)->{'Applicative0'})(null);
  $__res = function($v_3) use ($Applicative0_2_1, $Bind1_1_0) {
  $__num = \func_num_args();
  $__res = function($r_4) use ($Applicative0_2_1, $Bind1_1_0, $v_3) {
  $__num = \func_num_args();
  $__res = function($s_5) use ($Applicative0_2_1, $Bind1_1_0, $r_4, $v_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))((($v_3)($r_4))($s_5)))(function($v1_6) use ($Applicative0_2_1) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1))((($GLOBALS['Data_Tuple_Tuple'])(($v1_6)->{'value1'}))(($v1_6)->{'value2'}));
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
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_evalRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_evalmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_applyRWST
function majControl_majMonad_majRmajWmajS_majTrans_applymajRmajWmajSmajT($dictBind_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_applymajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Apply0_1_0 = (($dictBind_0)->{'Apply0'})(null);
  $Functor0_2_1 = (($Apply0_1_0)->{'Functor0'})(null);
  $functorRWST1_3_2 = ($GLOBALS['Control_Monad_RWS_Trans_functorRWST'])((($Apply0_1_0)->{'Functor0'})(null));
  $__res = function($dictMonoid_4) use ($Functor0_2_1, $dictBind_0, $functorRWST1_3_2) {
  $__num = \func_num_args();
  $Semigroup0_5_3 = (($dictMonoid_4)->{'Semigroup0'})(null);
  $__res = (object)["apply" => function($v_6) use ($Functor0_2_1, $Semigroup0_5_3, $dictBind_0) {
  $__num = \func_num_args();
  $__res = function($v1_7) use ($Functor0_2_1, $Semigroup0_5_3, $dictBind_0, $v_6) {
  $__num = \func_num_args();
  $__res = function($r_8) use ($Functor0_2_1, $Semigroup0_5_3, $dictBind_0, $v1_7, $v_6) {
  $__num = \func_num_args();
  $__res = function($s_9) use ($Functor0_2_1, $Semigroup0_5_3, $dictBind_0, $r_8, $v1_7, $v_6) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($dictBind_0))((($v_6)($r_8))($s_9)))(function($v2_10) use ($Functor0_2_1, $Semigroup0_5_3, $r_8, $v1_7) {
  $__num = \func_num_args();
  $__local_var_11_slot = ($v2_10)->{'value2'};
  $__res = ((($GLOBALS['Data_Functor_mapFlipped'])($Functor0_2_1))((($v1_7)($r_8))(($v2_10)->{'value0'})))(function($v3_12) use ($Semigroup0_5_3, $__local_var_11_slot, $v2_10) {
  $__num = \func_num_args();
  $__res = new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult(($v3_12)->{'value0'}, (($v2_10)->{'value1'})(($v3_12)->{'value1'}), ((($GLOBALS['Data_Semigroup_append'])($Semigroup0_5_3))($__local_var_11_slot))(($v3_12)->{'value2'}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
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
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Functor0" => function($_dollar___unused_6) use ($functorRWST1_3_2) {
  $__num = \func_num_args();
  $__res = $functorRWST1_3_2;
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
$GLOBALS['Control_Monad_RWS_Trans_applyRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_applymajRmajWmajSmajT';

// Control_Monad_RWS_Trans_bindRWST
function majControl_majMonad_majRmajWmajS_majTrans_bindmajRmajWmajSmajT($dictBind_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_bindmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Functor0_1_0 = (((($dictBind_0)->{'Apply0'})(null))->{'Functor0'})(null);
  $applyRWST1_2_1 = ($GLOBALS['Control_Monad_RWS_Trans_applyRWST'])($dictBind_0);
  $__res = function($dictMonoid_3) use ($Functor0_1_0, $applyRWST1_2_1, $dictBind_0) {
  $__num = \func_num_args();
  $Semigroup0_4_2 = (($dictMonoid_3)->{'Semigroup0'})(null);
  $applyRWST2_5_3 = ($applyRWST1_2_1)($dictMonoid_3);
  $__res = (object)["bind" => function($v_6) use ($Functor0_1_0, $Semigroup0_4_2, $dictBind_0) {
  $__num = \func_num_args();
  $__res = function($f_7) use ($Functor0_1_0, $Semigroup0_4_2, $dictBind_0, $v_6) {
  $__num = \func_num_args();
  $__res = function($r_8) use ($Functor0_1_0, $Semigroup0_4_2, $dictBind_0, $f_7, $v_6) {
  $__num = \func_num_args();
  $__res = function($s_9) use ($Functor0_1_0, $Semigroup0_4_2, $dictBind_0, $f_7, $r_8, $v_6) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($dictBind_0))((($v_6)($r_8))($s_9)))(function($v1_10) use ($Functor0_1_0, $Semigroup0_4_2, $f_7, $r_8) {
  $__num = \func_num_args();
  $__local_var_11_slot = ($v1_10)->{'value2'};
  $__res = ((($GLOBALS['Data_Functor_mapFlipped'])($Functor0_1_0))(((($f_7)(($v1_10)->{'value1'}))($r_8))(($v1_10)->{'value0'})))(function($v3_12) use ($Semigroup0_4_2, $__local_var_11_slot) {
  $__num = \func_num_args();
  $__res = new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult(($v3_12)->{'value0'}, ($v3_12)->{'value1'}, ((($GLOBALS['Data_Semigroup_append'])($Semigroup0_4_2))($__local_var_11_slot))(($v3_12)->{'value2'}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
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
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Apply0" => function($_dollar___unused_6) use ($applyRWST2_5_3) {
  $__num = \func_num_args();
  $__res = $applyRWST2_5_3;
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
$GLOBALS['Control_Monad_RWS_Trans_bindRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_bindmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_semigroupRWST
function majControl_majMonad_majRmajWmajS_majTrans_semigroupmajRmajWmajSmajT($dictBind_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_semigroupmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applyRWST1_1_0 = ($GLOBALS['Control_Monad_RWS_Trans_applyRWST'])($dictBind_0);
  $__res = function($dictMonoid_2) use ($applyRWST1_1_0) {
  $__num = \func_num_args();
  $applyRWST2_3_1 = ($applyRWST1_1_0)($dictMonoid_2);
  $__res = function($dictSemigroup_4) use ($applyRWST2_3_1) {
  $__num = \func_num_args();
  $__res = (object)["append" => (($GLOBALS['Control_Apply_lift2'])($applyRWST2_3_1))(($GLOBALS['Data_Semigroup_append'])($dictSemigroup_4))];
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
$GLOBALS['Control_Monad_RWS_Trans_semigroupRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_semigroupmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_applicativeRWST
function majControl_majMonad_majRmajWmajS_majTrans_applicativemajRmajWmajSmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_applicativemajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $applyRWST1_2_1 = ($GLOBALS['Control_Monad_RWS_Trans_applyRWST'])((($dictMonad_0)->{'Bind1'})(null));
  $__res = function($dictMonoid_3) use ($applyRWST1_2_1, $pure_1_0) {
  $__num = \func_num_args();
  $applyRWST2_4_2 = ($applyRWST1_2_1)($dictMonoid_3);
  $__res = (object)["pure" => function($a_5) use ($dictMonoid_3, $pure_1_0) {
  $__num = \func_num_args();
  $__res = function($v_6) use ($a_5, $dictMonoid_3, $pure_1_0) {
  $__num = \func_num_args();
  $__res = function($s_7) use ($a_5, $dictMonoid_3, $pure_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_1_0))(new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult($s_7, $a_5, ($GLOBALS['Data_Monoid_mempty'])($dictMonoid_3)));
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
}, "Apply0" => function($_dollar___unused_5) use ($applyRWST2_4_2) {
  $__num = \func_num_args();
  $__res = $applyRWST2_4_2;
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
$GLOBALS['Control_Monad_RWS_Trans_applicativeRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_applicativemajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monadRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajRmajWmajSmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeRWST1_1_0 = ($GLOBALS['Control_Monad_RWS_Trans_applicativeRWST'])($dictMonad_0);
  $bindRWST1_2_1 = ($GLOBALS['Control_Monad_RWS_Trans_bindRWST'])((($dictMonad_0)->{'Bind1'})(null));
  $__res = function($dictMonoid_3) use ($applicativeRWST1_1_0, $bindRWST1_2_1) {
  $__num = \func_num_args();
  $applicativeRWST2_4_2 = ($applicativeRWST1_1_0)($dictMonoid_3);
  $bindRWST2_5_3 = ($bindRWST1_2_1)($dictMonoid_3);
  $__res = (object)["Applicative0" => function($_dollar___unused_6) use ($applicativeRWST2_4_2) {
  $__num = \func_num_args();
  $__res = $applicativeRWST2_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Bind1" => function($_dollar___unused_6) use ($bindRWST2_5_3) {
  $__num = \func_num_args();
  $__res = $bindRWST2_5_3;
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
$GLOBALS['Control_Monad_RWS_Trans_monadRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monadAskRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajAskmajRmajWmajSmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajAskmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $monadRWST1_2_1 = ($GLOBALS['Control_Monad_RWS_Trans_monadRWST'])($dictMonad_0);
  $__res = function($dictMonoid_3) use ($monadRWST1_2_1, $pure_1_0) {
  $__num = \func_num_args();
  $monadRWST2_4_2 = ($monadRWST1_2_1)($dictMonoid_3);
  $__res = (object)["ask" => function($r_5) use ($dictMonoid_3, $pure_1_0) {
  $__num = \func_num_args();
  $__res = function($s_6) use ($dictMonoid_3, $pure_1_0, $r_5) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_1_0))(new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult($s_6, $r_5, ($GLOBALS['Data_Monoid_mempty'])($dictMonoid_3)));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_5) use ($monadRWST2_4_2) {
  $__num = \func_num_args();
  $__res = $monadRWST2_4_2;
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
$GLOBALS['Control_Monad_RWS_Trans_monadAskRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajAskmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monadReaderRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajReadermajRmajWmajSmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajReadermajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadAskRWST1_1_0 = ($GLOBALS['Control_Monad_RWS_Trans_monadAskRWST'])($dictMonad_0);
  $__res = function($dictMonoid_2) use ($monadAskRWST1_1_0) {
  $__num = \func_num_args();
  $monadAskRWST2_3_1 = ($monadAskRWST1_1_0)($dictMonoid_2);
  $__res = (object)["local" => function($f_4) {
  $__num = \func_num_args();
  $__res = function($m_5) use ($f_4) {
  $__num = \func_num_args();
  $__res = function($r_6) use ($f_4, $m_5) {
  $__num = \func_num_args();
  $__res = function($s_7) use ($f_4, $m_5, $r_6) {
  $__num = \func_num_args();
  $__res = (($m_5)(($f_4)($r_6)))($s_7);
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
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadAsk0" => function($_dollar___unused_4) use ($monadAskRWST2_3_1) {
  $__num = \func_num_args();
  $__res = $monadAskRWST2_3_1;
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
$GLOBALS['Control_Monad_RWS_Trans_monadReaderRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajReadermajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monadEffectRWS
function majControl_majMonad_majRmajWmajS_majTrans_monadmajEffectmajRmajWmajS($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajEffectmajRmajWmajS';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $lift_1_0 = ($GLOBALS['Control_Monad_Trans_Class_lift'])(($GLOBALS['Control_Monad_RWS_Trans_monadTransRWST'])($dictMonoid_0));
  $__res = function($dictMonadEffect_2) use ($dictMonoid_0, $lift_1_0) {
  $__num = \func_num_args();
  $Monad0_3_1 = (($dictMonadEffect_2)->{'Monad0'})(null);
  $monadRWST1_4_2 = (($GLOBALS['Control_Monad_RWS_Trans_monadRWST'])($Monad0_3_1))($dictMonoid_0);
  $__res = (object)["liftEffect" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($lift_1_0)($Monad0_3_1)))(($GLOBALS['Effect_Class_liftEffect'])($dictMonadEffect_2)), "Monad0" => function($_dollar___unused_5) use ($monadRWST1_4_2) {
  $__num = \func_num_args();
  $__res = $monadRWST1_4_2;
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
$GLOBALS['Control_Monad_RWS_Trans_monadEffectRWS'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajEffectmajRmajWmajS';

// Control_Monad_RWS_Trans_monadRecRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajRecmajRmajWmajSmajT($dictMonadRec_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajRecmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadRec_0)->{'Monad0'})(null);
  $Bind1_2_1 = (($Monad0_1_0)->{'Bind1'})(null);
  $Applicative0_3_2 = (($Monad0_1_0)->{'Applicative0'})(null);
  $monadRWST1_4_3 = ($GLOBALS['Control_Monad_RWS_Trans_monadRWST'])($Monad0_1_0);
  $__res = function($dictMonoid_5) use ($Applicative0_3_2, $Bind1_2_1, $dictMonadRec_0, $monadRWST1_4_3) {
  $__num = \func_num_args();
  $Semigroup0_6_4 = (($dictMonoid_5)->{'Semigroup0'})(null);
  $monadRWST2_7_5 = ($monadRWST1_4_3)($dictMonoid_5);
  $__res = (object)["tailRecM" => function($k_8) use ($Applicative0_3_2, $Bind1_2_1, $Semigroup0_6_4, $dictMonadRec_0, $dictMonoid_5) {
  $__num = \func_num_args();
  $__res = function($a_9) use ($Applicative0_3_2, $Bind1_2_1, $Semigroup0_6_4, $dictMonadRec_0, $dictMonoid_5, $k_8) {
  $__num = \func_num_args();
  $__res = function($r_10) use ($Applicative0_3_2, $Bind1_2_1, $Semigroup0_6_4, $a_9, $dictMonadRec_0, $dictMonoid_5, $k_8) {
  $__num = \func_num_args();
  $__res = function($s_11) use ($Applicative0_3_2, $Bind1_2_1, $Semigroup0_6_4, $a_9, $dictMonadRec_0, $dictMonoid_5, $k_8, $r_10) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Monad_Rec_Class_tailRecM'])($dictMonadRec_0))(function($v_12) use ($Applicative0_3_2, $Bind1_2_1, $Semigroup0_6_4, $k_8, $r_10) {
  $__num = \func_num_args();
  $__local_var_13_slot = ($v_12)->{'value2'};
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_1))(((($k_8)(($v_12)->{'value1'}))($r_10))(($v_12)->{'value0'})))(function($v2_14) use ($Applicative0_3_2, $Semigroup0_6_4, $__local_var_13_slot) {
  $__num = \func_num_args();
  $__t7 = null;
  if (($v2_14)->{'value1'} instanceof \Control\Monad\Rec\Class\Control_Monad_Rec_Class_Loop) {
$__t7 = ($GLOBALS['Control_Monad_Rec_Class_Loop'])(new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult(($v2_14)->{'value0'}, (($v2_14)->{'value1'})->{'value0'}, ((($GLOBALS['Data_Semigroup_append'])($Semigroup0_6_4))($__local_var_13_slot))(($v2_14)->{'value2'})));
goto end_branch_7;;
};
  if (($v2_14)->{'value1'} instanceof \Control\Monad\Rec\Class\Control_Monad_Rec_Class_Done) {
$__t7 = ($GLOBALS['Control_Monad_Rec_Class_Done'])(new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult(($v2_14)->{'value0'}, (($v2_14)->{'value1'})->{'value0'}, ((($GLOBALS['Data_Semigroup_append'])($Semigroup0_6_4))($__local_var_13_slot))(($v2_14)->{'value2'})));
goto end_branch_7;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t7 = null;
  end_branch_7:;
  $__res = (($GLOBALS['Control_Applicative_pure'])($Applicative0_3_2))($__t7);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult($s_11, $a_9, ($GLOBALS['Data_Monoid_mempty'])($dictMonoid_5)));
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
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_8) use ($monadRWST2_7_5) {
  $__num = \func_num_args();
  $__res = $monadRWST2_7_5;
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
$GLOBALS['Control_Monad_RWS_Trans_monadRecRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajRecmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monadStateRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajStatemajRmajWmajSmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajStatemajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $monadRWST1_2_1 = ($GLOBALS['Control_Monad_RWS_Trans_monadRWST'])($dictMonad_0);
  $__res = function($dictMonoid_3) use ($monadRWST1_2_1, $pure_1_0) {
  $__num = \func_num_args();
  $monadRWST2_4_2 = ($monadRWST1_2_1)($dictMonoid_3);
  $__res = (object)["state" => function($f_5) use ($dictMonoid_3, $pure_1_0) {
  $__num = \func_num_args();
  $__res = function($v_6) use ($dictMonoid_3, $f_5, $pure_1_0) {
  $__num = \func_num_args();
  $__res = function($s_7) use ($dictMonoid_3, $f_5, $pure_1_0) {
  $__num = \func_num_args();
  $v1_8_3 = ($f_5)($s_7);
  $__res = (($GLOBALS['Data_Function_apply'])($pure_1_0))(new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult(($v1_8_3)->{'value1'}, ($v1_8_3)->{'value0'}, ($GLOBALS['Data_Monoid_mempty'])($dictMonoid_3)));
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
}, "Monad0" => function($_dollar___unused_5) use ($monadRWST2_4_2) {
  $__num = \func_num_args();
  $__res = $monadRWST2_4_2;
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
$GLOBALS['Control_Monad_RWS_Trans_monadStateRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajStatemajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monadTellRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajTellmajRmajWmajSmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajTellmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $monadRWST1_2_1 = ($GLOBALS['Control_Monad_RWS_Trans_monadRWST'])($dictMonad_0);
  $__res = function($dictMonoid_3) use ($monadRWST1_2_1, $pure_1_0) {
  $__num = \func_num_args();
  $Semigroup0_4_2 = (($dictMonoid_3)->{'Semigroup0'})(null);
  $monadRWST2_5_3 = ($monadRWST1_2_1)($dictMonoid_3);
  $__res = (object)["tell" => function($w_6) use ($pure_1_0) {
  $__num = \func_num_args();
  $__res = function($v_7) use ($pure_1_0, $w_6) {
  $__num = \func_num_args();
  $__res = function($s_8) use ($pure_1_0, $w_6) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_1_0))(new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult($s_8, $GLOBALS['Data_Unit_unit'], $w_6));
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
}, "Semigroup0" => function($_dollar___unused_6) use ($Semigroup0_4_2) {
  $__num = \func_num_args();
  $__res = $Semigroup0_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad1" => function($_dollar___unused_6) use ($monadRWST2_5_3) {
  $__num = \func_num_args();
  $__res = $monadRWST2_5_3;
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
$GLOBALS['Control_Monad_RWS_Trans_monadTellRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajTellmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monadWriterRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajWritermajRmajWmajSmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajWritermajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Bind1_1_0 = (($dictMonad_0)->{'Bind1'})(null);
  $Applicative0_2_1 = (($dictMonad_0)->{'Applicative0'})(null);
  $pure_3_2 = ($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1);
  $pure1_4_3 = ($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1);
  $monadTellRWST1_5_4 = ($GLOBALS['Control_Monad_RWS_Trans_monadTellRWST'])($dictMonad_0);
  $__res = function($dictMonoid_6) use ($Bind1_1_0, $monadTellRWST1_5_4, $pure1_4_3, $pure_3_2) {
  $__num = \func_num_args();
  $monadTellRWST2_7_5 = ($monadTellRWST1_5_4)($dictMonoid_6);
  $__res = (object)["listen" => function($m_8) use ($Bind1_1_0, $pure_3_2) {
  $__num = \func_num_args();
  $__res = function($r_9) use ($Bind1_1_0, $m_8, $pure_3_2) {
  $__num = \func_num_args();
  $__res = function($s_10) use ($Bind1_1_0, $m_8, $pure_3_2, $r_9) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))((($m_8)($r_9))($s_10)))(function($v_11) use ($pure_3_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_3_2))(new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult(($v_11)->{'value0'}, (($GLOBALS['Data_Tuple_Tuple'])(($v_11)->{'value1'}))(($v_11)->{'value2'}), ($v_11)->{'value2'}));
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
}, "pass" => function($m_8) use ($Bind1_1_0, $pure1_4_3) {
  $__num = \func_num_args();
  $__res = function($r_9) use ($Bind1_1_0, $m_8, $pure1_4_3) {
  $__num = \func_num_args();
  $__res = function($s_10) use ($Bind1_1_0, $m_8, $pure1_4_3, $r_9) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))((($m_8)($r_9))($s_10)))(function($v_11) use ($pure1_4_3) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure1_4_3))(new \Control\Monad\RWS\Trans\Control_Monad_RWS_Trans_RWSResult(($v_11)->{'value0'}, (($v_11)->{'value1'})->{'value0'}, ((($v_11)->{'value1'})->{'value1'})(($v_11)->{'value2'})));
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
}, "Monoid0" => function($_dollar___unused_8) use ($dictMonoid_6) {
  $__num = \func_num_args();
  $__res = $dictMonoid_6;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadTell1" => function($_dollar___unused_8) use ($monadTellRWST2_7_5) {
  $__num = \func_num_args();
  $__res = $monadTellRWST2_7_5;
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
$GLOBALS['Control_Monad_RWS_Trans_monadWriterRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajWritermajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monadThrowRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajThrowmajRmajWmajSmajT($dictMonadThrow_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajThrowmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadThrow_0)->{'Monad0'})(null);
  $monadRWST1_2_1 = ($GLOBALS['Control_Monad_RWS_Trans_monadRWST'])((($dictMonadThrow_0)->{'Monad0'})(null));
  $__res = function($dictMonoid_3) use ($Monad0_1_0, $dictMonadThrow_0, $monadRWST1_2_1) {
  $__num = \func_num_args();
  $monadTransRWST1_4_2 = ($GLOBALS['Control_Monad_RWS_Trans_monadTransRWST'])($dictMonoid_3);
  $monadRWST2_5_3 = ($monadRWST1_2_1)($dictMonoid_3);
  $__res = (object)["throwError" => function($e_6) use ($Monad0_1_0, $dictMonadThrow_0, $monadTransRWST1_4_2) {
  $__num = \func_num_args();
  $__res = ((($monadTransRWST1_4_2)->{'lift'})($Monad0_1_0))((($dictMonadThrow_0)->{'throwError'})($e_6));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_6) use ($monadRWST2_5_3) {
  $__num = \func_num_args();
  $__res = $monadRWST2_5_3;
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
$GLOBALS['Control_Monad_RWS_Trans_monadThrowRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajThrowmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monadErrorRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajErrormajRmajWmajSmajT($dictMonadError_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajErrormajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadThrowRWST1_1_0 = ($GLOBALS['Control_Monad_RWS_Trans_monadThrowRWST'])((($dictMonadError_0)->{'MonadThrow0'})(null));
  $__res = function($dictMonoid_2) use ($dictMonadError_0, $monadThrowRWST1_1_0) {
  $__num = \func_num_args();
  $monadThrowRWST2_3_1 = ($monadThrowRWST1_1_0)($dictMonoid_2);
  $__res = (object)["catchError" => function($m_4) use ($dictMonadError_0) {
  $__num = \func_num_args();
  $__res = function($h_5) use ($dictMonadError_0, $m_4) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_RWS_Trans_RWST']))(function($r_6) use ($dictMonadError_0, $h_5, $m_4) {
  $__num = \func_num_args();
  $__res = function($s_7) use ($dictMonadError_0, $h_5, $m_4, $r_6) {
  $__num = \func_num_args();
  $__res = ((($dictMonadError_0)->{'catchError'})((($m_4)($r_6))($s_7)))(function($e_8) use ($h_5, $r_6, $s_7) {
  $__num = \func_num_args();
  $__res = ((($h_5)($e_8))($r_6))($s_7);
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
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadThrow0" => function($_dollar___unused_4) use ($monadThrowRWST2_3_1) {
  $__num = \func_num_args();
  $__res = $monadThrowRWST2_3_1;
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
$GLOBALS['Control_Monad_RWS_Trans_monadErrorRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajErrormajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monadSTRWST
function majControl_majMonad_majRmajWmajS_majTrans_monadmajSmajTmajRmajWmajSmajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monadmajSmajTmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $lift_1_0 = ($GLOBALS['Control_Monad_Trans_Class_lift'])(($GLOBALS['Control_Monad_RWS_Trans_monadTransRWST'])($dictMonoid_0));
  $__res = function($dictMonadST_2) use ($dictMonoid_0, $lift_1_0) {
  $__num = \func_num_args();
  $Monad0_3_1 = (($dictMonadST_2)->{'Monad0'})(null);
  $monadRWST1_4_2 = (($GLOBALS['Control_Monad_RWS_Trans_monadRWST'])($Monad0_3_1))($dictMonoid_0);
  $__res = (object)["liftST" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($lift_1_0)($Monad0_3_1)))(($GLOBALS['Control_Monad_ST_Class_liftST'])($dictMonadST_2)), "Monad0" => function($_dollar___unused_5) use ($monadRWST1_4_2) {
  $__num = \func_num_args();
  $__res = $monadRWST1_4_2;
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
$GLOBALS['Control_Monad_RWS_Trans_monadSTRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monadmajSmajTmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_monoidRWST
function majControl_majMonad_majRmajWmajS_majTrans_monoidmajRmajWmajSmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_monoidmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeRWST1_1_0 = ($GLOBALS['Control_Monad_RWS_Trans_applicativeRWST'])($dictMonad_0);
  $semigroupRWST1_2_1 = ($GLOBALS['Control_Monad_RWS_Trans_semigroupRWST'])((($dictMonad_0)->{'Bind1'})(null));
  $__res = function($dictMonoid_3) use ($applicativeRWST1_1_0, $semigroupRWST1_2_1) {
  $__num = \func_num_args();
  $applicativeRWST2_4_2 = ($applicativeRWST1_1_0)($dictMonoid_3);
  $semigroupRWST2_5_3 = ($semigroupRWST1_2_1)($dictMonoid_3);
  $__res = function($dictMonoid1_6) use ($applicativeRWST2_4_2, $semigroupRWST2_5_3) {
  $__num = \func_num_args();
  $semigroupRWST3_7_4 = ($semigroupRWST2_5_3)((($dictMonoid1_6)->{'Semigroup0'})(null));
  $__res = (object)["mempty" => (($GLOBALS['Control_Applicative_pure'])($applicativeRWST2_4_2))(($GLOBALS['Data_Monoid_mempty'])($dictMonoid1_6)), "Semigroup0" => function($_dollar___unused_8) use ($semigroupRWST3_7_4) {
  $__num = \func_num_args();
  $__res = $semigroupRWST3_7_4;
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
$GLOBALS['Control_Monad_RWS_Trans_monoidRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_monoidmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_altRWST
function majControl_majMonad_majRmajWmajS_majTrans_altmajRmajWmajSmajT($dictAlt_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_altmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorRWST1_1_0 = ($GLOBALS['Control_Monad_RWS_Trans_functorRWST'])((($dictAlt_0)->{'Functor0'})(null));
  $__res = (object)["alt" => function($v_2) use ($dictAlt_0) {
  $__num = \func_num_args();
  $__res = function($v1_3) use ($dictAlt_0, $v_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_RWS_Trans_RWST']))(function($r_4) use ($dictAlt_0, $v1_3, $v_2) {
  $__num = \func_num_args();
  $__res = function($s_5) use ($dictAlt_0, $r_4, $v1_3, $v_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Alt_alt'])($dictAlt_0))((($v_2)($r_4))($s_5)))((($v1_3)($r_4))($s_5));
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
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Functor0" => function($_dollar___unused_2) use ($functorRWST1_1_0) {
  $__num = \func_num_args();
  $__res = $functorRWST1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_altRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_altmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_plusRWST
function majControl_majMonad_majRmajWmajS_majTrans_plusmajRmajWmajSmajT($dictPlus_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_plusmajRmajWmajSmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $empty_1_0 = ($GLOBALS['Control_Plus_empty'])($dictPlus_0);
  $altRWST1_2_1 = ($GLOBALS['Control_Monad_RWS_Trans_altRWST'])((($dictPlus_0)->{'Alt0'})(null));
  $__res = (object)["empty" => function($v_3) use ($empty_1_0) {
  $__num = \func_num_args();
  $__res = function($v1_4) use ($empty_1_0) {
  $__num = \func_num_args();
  $__res = $empty_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Alt0" => function($_dollar___unused_3) use ($altRWST1_2_1) {
  $__num = \func_num_args();
  $__res = $altRWST1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_plusRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_plusmajRmajWmajSmajT';

// Control_Monad_RWS_Trans_alternativeRWST
function majControl_majMonad_majRmajWmajS_majTrans_alternativemajRmajWmajSmajT($dictMonoid_0, $dictAlternative_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majRmajWmajS_majTrans_alternativemajRmajWmajSmajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $plusRWST1_2_0 = ($GLOBALS['Control_Monad_RWS_Trans_plusRWST'])((($dictAlternative_1)->{'Plus1'})(null));
  $__res = function($dictMonad_3) use ($dictMonoid_0, $plusRWST1_2_0) {
  $__num = \func_num_args();
  $applicativeRWST1_4_1 = (($GLOBALS['Control_Monad_RWS_Trans_applicativeRWST'])($dictMonad_3))($dictMonoid_0);
  $__res = (object)["Applicative0" => function($_dollar___unused_5) use ($applicativeRWST1_4_1) {
  $__num = \func_num_args();
  $__res = $applicativeRWST1_4_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Plus1" => function($_dollar___unused_5) use ($plusRWST1_2_0) {
  $__num = \func_num_args();
  $__res = $plusRWST1_2_0;
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
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_RWS_Trans_alternativeRWST'] = __NAMESPACE__ . '\\majControl_majMonad_majRmajWmajS_majTrans_alternativemajRmajWmajSmajT';

