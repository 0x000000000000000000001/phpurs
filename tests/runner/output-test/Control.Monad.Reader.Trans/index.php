<?php

namespace Control\Monad\Reader\Trans;

// ALL IMPORTS: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Monad, Control.Monad.Cont.Class, Control.Monad.Error.Class, Control.Monad.Reader.Class, Control.Monad.Reader.Trans, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Distributive, Data.Function, Data.Functor, Data.Monoid, Data.Newtype, Data.Semigroup, Effect.Class, Prelude, Prim
// TO REQUIRE: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Monad, Control.Monad.Cont.Class, Control.Monad.Error.Class, Control.Monad.Reader.Class, Control.Monad.Reader.Trans, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Distributive, Data.Function, Data.Functor, Data.Monoid, Data.Newtype, Data.Semigroup, Effect.Class, Prelude
require_once __DIR__ . '/../Control.Alt/index.php';
require_once __DIR__ . '/../Control.Alternative/index.php';
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Apply/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Monad/index.php';
require_once __DIR__ . '/../Control.Monad.Cont.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Error.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Reader.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Reader.Trans/index.php';
require_once __DIR__ . '/../Control.Monad.Rec.Class/index.php';
require_once __DIR__ . '/../Control.Monad.ST.Class/index.php';
require_once __DIR__ . '/../Control.Monad.State.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Trans.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Writer.Class/index.php';
require_once __DIR__ . '/../Control.MonadPlus/index.php';
require_once __DIR__ . '/../Control.Plus/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Distributive/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Functor/index.php';
require_once __DIR__ . '/../Data.Monoid/index.php';
require_once __DIR__ . '/../Data.Newtype/index.php';
require_once __DIR__ . '/../Data.Semigroup/index.php';
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




// Control_Monad_Reader_Trans_ReaderT
function majControl_majMonad_majReader_majTrans_majReadermajT($x_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_majReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $x_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_ReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_majReadermajT';

// Control_Monad_Reader_Trans_withReaderT
function majControl_majMonad_majReader_majTrans_withmajReadermajT($f_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_withmajReadermajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($v_1))($f_0);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_withReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_withmajReadermajT';

// Control_Monad_Reader_Trans_runReaderT
function majControl_majMonad_majReader_majTrans_runmajReadermajT($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_runmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $v_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_runReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_runmajReadermajT';

// Control_Monad_Reader_Trans_newtypeReaderT
$GLOBALS['Control_Monad_Reader_Trans_newtypeReaderT'] = (object)["Coercible0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = null;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Reader_Trans_monadTransReaderT
$GLOBALS['Control_Monad_Reader_Trans_monadTransReaderT'] = (object)["lift" => function($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Reader_Trans_ReaderT']))($GLOBALS['Data_Function_const']);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Reader_Trans_mapReaderT
function majControl_majMonad_majReader_majTrans_mapmajReadermajT($f_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_mapmajReadermajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($f_0))($v_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_mapReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_mapmajReadermajT';

// Control_Monad_Reader_Trans_functorReaderT
function majControl_majMonad_majReader_majTrans_functormajReadermajT($dictFunctor_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_functormajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["map" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Reader_Trans_mapReaderT']))(($GLOBALS['Data_Functor_map'])($dictFunctor_0))];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_functorReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_functormajReadermajT';

// Control_Monad_Reader_Trans_distributiveReaderT
function majControl_majMonad_majReader_majTrans_distributivemajReadermajT($dictDistributive_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_distributivemajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__tco_var_Control_Monad_Reader_Trans_distributiveReaderT_dictDistributive_0 = $dictDistributive_0;
  tco_loop_Control_Monad_Reader_Trans_distributiveReaderT:;
  $dictDistributive_0 = $__tco_var_Control_Monad_Reader_Trans_distributiveReaderT_dictDistributive_0;
  $functorReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_functorReaderT'])((($dictDistributive_0)->{'Functor0'})(null));
  $__res = (object)["distribute" => function($dictFunctor_2) use ($dictDistributive_0) {
  $__num = \func_num_args();
  $__res = function($a_3) use ($dictDistributive_0, $dictFunctor_2) {
  $__num = \func_num_args();
  $__res = function($e_4) use ($a_3, $dictDistributive_0, $dictFunctor_2) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Data_Distributive_collect'])($dictDistributive_0))($dictFunctor_2))(function($r_5) use ($e_4) {
  $__num = \func_num_args();
  $__res = ($r_5)($e_4);
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
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "collect" => function($dictFunctor_2) use ($dictDistributive_0) {
  $__num = \func_num_args();
  $__res = function($f_3) use ($dictDistributive_0, $dictFunctor_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Data_Distributive_distribute'])(($GLOBALS['Control_Monad_Reader_Trans_distributiveReaderT'])($dictDistributive_0)))($dictFunctor_2)))((($GLOBALS['Data_Functor_map'])($dictFunctor_2))($f_3));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Functor0" => function($_dollar___unused_2) use ($functorReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_distributiveReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_distributivemajReadermajT';

// Control_Monad_Reader_Trans_applyReaderT
function majControl_majMonad_majReader_majTrans_applymajReadermajT($dictApply_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_applymajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_functorReaderT'])((($dictApply_0)->{'Functor0'})(null));
  $__res = (object)["apply" => function($v_2) use ($dictApply_0) {
  $__num = \func_num_args();
  $__res = function($v1_3) use ($dictApply_0, $v_2) {
  $__num = \func_num_args();
  $__res = function($r_4) use ($dictApply_0, $v1_3, $v_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Apply_apply'])($dictApply_0))(($v_2)($r_4)))(($v1_3)($r_4));
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
}, "Functor0" => function($_dollar___unused_2) use ($functorReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_applyReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_applymajReadermajT';

// Control_Monad_Reader_Trans_bindReaderT
function majControl_majMonad_majReader_majTrans_bindmajReadermajT($dictBind_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_bindmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applyReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_applyReaderT'])((($dictBind_0)->{'Apply0'})(null));
  $__res = (object)["bind" => function($v_2) use ($dictBind_0) {
  $__num = \func_num_args();
  $__res = function($k_3) use ($dictBind_0, $v_2) {
  $__num = \func_num_args();
  $__res = function($r_4) use ($dictBind_0, $k_3, $v_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($dictBind_0))(($v_2)($r_4)))(function($a_5) use ($k_3, $r_4) {
  $__num = \func_num_args();
  $__res = (($k_3)($a_5))($r_4);
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
}, "Apply0" => function($_dollar___unused_2) use ($applyReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $applyReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_bindReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_bindmajReadermajT';

// Control_Monad_Reader_Trans_semigroupReaderT
function majControl_majMonad_majReader_majTrans_semigroupmajReadermajT($dictApply_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_semigroupmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applyReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_applyReaderT'])($dictApply_0);
  $__res = function($dictSemigroup_2) use ($applyReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = (object)["append" => (($GLOBALS['Control_Apply_lift2'])($applyReaderT1_1_0))(($GLOBALS['Data_Semigroup_append'])($dictSemigroup_2))];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_semigroupReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_semigroupmajReadermajT';

// Control_Monad_Reader_Trans_applicativeReaderT
function majControl_majMonad_majReader_majTrans_applicativemajReadermajT($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_applicativemajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applyReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_applyReaderT'])((($dictApplicative_0)->{'Apply0'})(null));
  $__res = (object)["pure" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Reader_Trans_ReaderT']))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Data_Function_const']))(($GLOBALS['Control_Applicative_pure'])($dictApplicative_0))), "Apply0" => function($_dollar___unused_2) use ($applyReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $applyReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_applicativeReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_applicativemajReadermajT';

// Control_Monad_Reader_Trans_monadReaderT
function majControl_majMonad_majReader_majTrans_monadmajReadermajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_applicativeReaderT'])((($dictMonad_0)->{'Applicative0'})(null));
  $bindReaderT1_2_1 = ($GLOBALS['Control_Monad_Reader_Trans_bindReaderT'])((($dictMonad_0)->{'Bind1'})(null));
  $__res = (object)["Applicative0" => function($_dollar___unused_3) use ($applicativeReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $applicativeReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Bind1" => function($_dollar___unused_3) use ($bindReaderT1_2_1) {
  $__num = \func_num_args();
  $__res = $bindReaderT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajReadermajT';

// Control_Monad_Reader_Trans_monadAskReaderT
function majControl_majMonad_majReader_majTrans_monadmajAskmajReadermajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajAskmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_monadReaderT'])($dictMonad_0);
  $__res = (object)["ask" => ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null)), "Monad0" => function($_dollar___unused_2) use ($monadReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadAskReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajAskmajReadermajT';

// Control_Monad_Reader_Trans_monadReaderReaderT
function majControl_majMonad_majReader_majTrans_monadmajReadermajReadermajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajReadermajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadAskReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_monadAskReaderT'])($dictMonad_0);
  $__res = (object)["local" => $GLOBALS['Control_Monad_Reader_Trans_withReaderT'], "MonadAsk0" => function($_dollar___unused_2) use ($monadAskReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadAskReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadReaderReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajReadermajReadermajT';

// Control_Monad_Reader_Trans_monadContReaderT
function majControl_majMonad_majReader_majTrans_monadmajContmajReadermajT($dictMonadCont_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajContmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_monadReaderT'])((($dictMonadCont_0)->{'Monad0'})(null));
  $__res = (object)["callCC" => function($f_2) use ($dictMonadCont_0) {
  $__num = \func_num_args();
  $__res = function($r_3) use ($dictMonadCont_0, $f_2) {
  $__num = \func_num_args();
  $__res = (($dictMonadCont_0)->{'callCC'})(function($c_4) use ($f_2, $r_3) {
  $__num = \func_num_args();
  $__res = (($f_2)(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Reader_Trans_ReaderT']))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Data_Function_const']))($c_4))))($r_3);
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
}, "Monad0" => function($_dollar___unused_2) use ($monadReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadContReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajContmajReadermajT';

// Control_Monad_Reader_Trans_monadEffectReader
function majControl_majMonad_majReader_majTrans_monadmajEffectmajReader($dictMonadEffect_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajEffectmajReader';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadEffect_0)->{'Monad0'})(null);
  $monadReaderT1_2_1 = ($GLOBALS['Control_Monad_Reader_Trans_monadReaderT'])($Monad0_1_0);
  $__res = (object)["liftEffect" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Reader_Trans_monadTransReaderT']))($Monad0_1_0)))(($GLOBALS['Effect_Class_liftEffect'])($dictMonadEffect_0)), "Monad0" => function($_dollar___unused_3) use ($monadReaderT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadReaderT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadEffectReader'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajEffectmajReader';

// Control_Monad_Reader_Trans_monadRecReaderT
function majControl_majMonad_majReader_majTrans_monadmajRecmajReadermajT($dictMonadRec_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajRecmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadRec_0)->{'Monad0'})(null);
  $Bind1_2_1 = (($Monad0_1_0)->{'Bind1'})(null);
  $pure_3_2 = ($GLOBALS['Control_Applicative_pure'])((($Monad0_1_0)->{'Applicative0'})(null));
  $monadReaderT1_4_3 = ($GLOBALS['Control_Monad_Reader_Trans_monadReaderT'])($Monad0_1_0);
  $__res = (object)["tailRecM" => function($k_5) use ($Bind1_2_1, $dictMonadRec_0, $pure_3_2) {
  $__num = \func_num_args();
  $__res = function($a_6) use ($Bind1_2_1, $dictMonadRec_0, $k_5, $pure_3_2) {
  $__num = \func_num_args();
  $__res = function($r_7) use ($Bind1_2_1, $a_6, $dictMonadRec_0, $k_5, $pure_3_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Monad_Rec_Class_tailRecM'])($dictMonadRec_0))(function($a_prime__8) use ($Bind1_2_1, $k_5, $pure_3_2, $r_7) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bindFlipped'])($Bind1_2_1))($pure_3_2))((($k_5)($a_prime__8))($r_7));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($a_6);
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
}, "Monad0" => function($_dollar___unused_5) use ($monadReaderT1_4_3) {
  $__num = \func_num_args();
  $__res = $monadReaderT1_4_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadRecReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajRecmajReadermajT';

// Control_Monad_Reader_Trans_monadStateReaderT
function majControl_majMonad_majReader_majTrans_monadmajStatemajReadermajT($dictMonadState_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajStatemajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadState_0)->{'Monad0'})(null);
  $monadReaderT1_2_1 = ($GLOBALS['Control_Monad_Reader_Trans_monadReaderT'])($Monad0_1_0);
  $__res = (object)["state" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Reader_Trans_monadTransReaderT']))($Monad0_1_0)))(($GLOBALS['Control_Monad_State_Class_state'])($dictMonadState_0)), "Monad0" => function($_dollar___unused_3) use ($monadReaderT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadReaderT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadStateReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajStatemajReadermajT';

// Control_Monad_Reader_Trans_monadTellReaderT
function majControl_majMonad_majReader_majTrans_monadmajTellmajReadermajT($dictMonadTell_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajTellmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad1_1_0 = (($dictMonadTell_0)->{'Monad1'})(null);
  $Semigroup0_2_1 = (($dictMonadTell_0)->{'Semigroup0'})(null);
  $monadReaderT1_3_2 = ($GLOBALS['Control_Monad_Reader_Trans_monadReaderT'])($Monad1_1_0);
  $__res = (object)["tell" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Reader_Trans_monadTransReaderT']))($Monad1_1_0)))(($GLOBALS['Control_Monad_Writer_Class_tell'])($dictMonadTell_0)), "Semigroup0" => function($_dollar___unused_4) use ($Semigroup0_2_1) {
  $__num = \func_num_args();
  $__res = $Semigroup0_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad1" => function($_dollar___unused_4) use ($monadReaderT1_3_2) {
  $__num = \func_num_args();
  $__res = $monadReaderT1_3_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadTellReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajTellmajReadermajT';

// Control_Monad_Reader_Trans_monadWriterReaderT
function majControl_majMonad_majReader_majTrans_monadmajWritermajReadermajT($dictMonadWriter_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajWritermajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monoid0_1_0 = (($dictMonadWriter_0)->{'Monoid0'})(null);
  $monadTellReaderT1_2_1 = ($GLOBALS['Control_Monad_Reader_Trans_monadTellReaderT'])((($dictMonadWriter_0)->{'MonadTell1'})(null));
  $__res = (object)["listen" => ($GLOBALS['Control_Monad_Reader_Trans_mapReaderT'])(($GLOBALS['Control_Monad_Writer_Class_listen'])($dictMonadWriter_0)), "pass" => ($GLOBALS['Control_Monad_Reader_Trans_mapReaderT'])(($GLOBALS['Control_Monad_Writer_Class_pass'])($dictMonadWriter_0)), "Monoid0" => function($_dollar___unused_3) use ($Monoid0_1_0) {
  $__num = \func_num_args();
  $__res = $Monoid0_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadTell1" => function($_dollar___unused_3) use ($monadTellReaderT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadTellReaderT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadWriterReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajWritermajReadermajT';

// Control_Monad_Reader_Trans_monadThrowReaderT
function majControl_majMonad_majReader_majTrans_monadmajThrowmajReadermajT($dictMonadThrow_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajThrowmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadThrow_0)->{'Monad0'})(null);
  $monadReaderT1_2_1 = ($GLOBALS['Control_Monad_Reader_Trans_monadReaderT'])($Monad0_1_0);
  $__res = (object)["throwError" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Reader_Trans_monadTransReaderT']))($Monad0_1_0)))(($GLOBALS['Control_Monad_Error_Class_throwError'])($dictMonadThrow_0)), "Monad0" => function($_dollar___unused_3) use ($monadReaderT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadReaderT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadThrowReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajThrowmajReadermajT';

// Control_Monad_Reader_Trans_monadErrorReaderT
function majControl_majMonad_majReader_majTrans_monadmajErrormajReadermajT($dictMonadError_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajErrormajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadThrowReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_monadThrowReaderT'])((($dictMonadError_0)->{'MonadThrow0'})(null));
  $__res = (object)["catchError" => function($v_2) use ($dictMonadError_0) {
  $__num = \func_num_args();
  $__res = function($h_3) use ($dictMonadError_0, $v_2) {
  $__num = \func_num_args();
  $__res = function($r_4) use ($dictMonadError_0, $h_3, $v_2) {
  $__num = \func_num_args();
  $__res = ((($dictMonadError_0)->{'catchError'})(($v_2)($r_4)))(function($e_5) use ($h_3, $r_4) {
  $__num = \func_num_args();
  $__res = (($h_3)($e_5))($r_4);
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
}, "MonadThrow0" => function($_dollar___unused_2) use ($monadThrowReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadThrowReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadErrorReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajErrormajReadermajT';

// Control_Monad_Reader_Trans_monadSTReaderT
function majControl_majMonad_majReader_majTrans_monadmajSmajTmajReadermajT($dictMonadST_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajSmajTmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadST_0)->{'Monad0'})(null);
  $monadReaderT1_2_1 = ($GLOBALS['Control_Monad_Reader_Trans_monadReaderT'])($Monad0_1_0);
  $__res = (object)["liftST" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Reader_Trans_monadTransReaderT']))($Monad0_1_0)))(($GLOBALS['Control_Monad_ST_Class_liftST'])($dictMonadST_0)), "Monad0" => function($_dollar___unused_3) use ($monadReaderT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadReaderT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadSTReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajSmajTmajReadermajT';

// Control_Monad_Reader_Trans_monoidReaderT
function majControl_majMonad_majReader_majTrans_monoidmajReadermajT($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monoidmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_applicativeReaderT'])($dictApplicative_0);
  $semigroupReaderT1_2_1 = ($GLOBALS['Control_Monad_Reader_Trans_semigroupReaderT'])((($dictApplicative_0)->{'Apply0'})(null));
  $__res = function($dictMonoid_3) use ($applicativeReaderT1_1_0, $semigroupReaderT1_2_1) {
  $__num = \func_num_args();
  $semigroupReaderT2_4_2 = ($semigroupReaderT1_2_1)((($dictMonoid_3)->{'Semigroup0'})(null));
  $__res = (object)["mempty" => (($GLOBALS['Control_Applicative_pure'])($applicativeReaderT1_1_0))(($GLOBALS['Data_Monoid_mempty'])($dictMonoid_3)), "Semigroup0" => function($_dollar___unused_5) use ($semigroupReaderT2_4_2) {
  $__num = \func_num_args();
  $__res = $semigroupReaderT2_4_2;
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
$GLOBALS['Control_Monad_Reader_Trans_monoidReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monoidmajReadermajT';

// Control_Monad_Reader_Trans_altReaderT
function majControl_majMonad_majReader_majTrans_altmajReadermajT($dictAlt_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_altmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_functorReaderT'])((($dictAlt_0)->{'Functor0'})(null));
  $__res = (object)["alt" => function($v_2) use ($dictAlt_0) {
  $__num = \func_num_args();
  $__res = function($v1_3) use ($dictAlt_0, $v_2) {
  $__num = \func_num_args();
  $__res = function($r_4) use ($dictAlt_0, $v1_3, $v_2) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Alt_alt'])($dictAlt_0))(($v_2)($r_4)))(($v1_3)($r_4));
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
}, "Functor0" => function($_dollar___unused_2) use ($functorReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_altReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_altmajReadermajT';

// Control_Monad_Reader_Trans_plusReaderT
function majControl_majMonad_majReader_majTrans_plusmajReadermajT($dictPlus_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_plusmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $altReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_altReaderT'])((($dictPlus_0)->{'Alt0'})(null));
  $__res = (object)["empty" => ($GLOBALS['Data_Function_const'])(($GLOBALS['Control_Plus_empty'])($dictPlus_0)), "Alt0" => function($_dollar___unused_2) use ($altReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $altReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_plusReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_plusmajReadermajT';

// Control_Monad_Reader_Trans_alternativeReaderT
function majControl_majMonad_majReader_majTrans_alternativemajReadermajT($dictAlternative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_alternativemajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_applicativeReaderT'])((($dictAlternative_0)->{'Applicative0'})(null));
  $plusReaderT1_2_1 = ($GLOBALS['Control_Monad_Reader_Trans_plusReaderT'])((($dictAlternative_0)->{'Plus1'})(null));
  $__res = (object)["Applicative0" => function($_dollar___unused_3) use ($applicativeReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $applicativeReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Plus1" => function($_dollar___unused_3) use ($plusReaderT1_2_1) {
  $__num = \func_num_args();
  $__res = $plusReaderT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_alternativeReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_alternativemajReadermajT';

// Control_Monad_Reader_Trans_monadPlusReaderT
function majControl_majMonad_majReader_majTrans_monadmajPlusmajReadermajT($dictMonadPlus_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majReader_majTrans_monadmajPlusmajReadermajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadReaderT1_1_0 = ($GLOBALS['Control_Monad_Reader_Trans_monadReaderT'])((($dictMonadPlus_0)->{'Monad0'})(null));
  $alternativeReaderT1_2_1 = ($GLOBALS['Control_Monad_Reader_Trans_alternativeReaderT'])((($dictMonadPlus_0)->{'Alternative1'})(null));
  $__res = (object)["Monad0" => function($_dollar___unused_3) use ($monadReaderT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadReaderT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Alternative1" => function($_dollar___unused_3) use ($alternativeReaderT1_2_1) {
  $__num = \func_num_args();
  $__res = $alternativeReaderT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Reader_Trans_monadPlusReaderT'] = __NAMESPACE__ . '\\majControl_majMonad_majReader_majTrans_monadmajPlusmajReadermajT';

