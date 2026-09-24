<?php

namespace Control\Monad\Except\Trans;

// ALL IMPORTS: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Category, Control.Monad, Control.Monad.Cont.Class, Control.Monad.Error.Class, Control.Monad.Except.Trans, Control.Monad.Reader.Class, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Either, Data.Function, Data.Functor, Data.Monoid, Data.Newtype, Data.Semigroup, Data.Tuple, Effect.Class, Prelude, Prim
// TO REQUIRE: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Category, Control.Monad, Control.Monad.Cont.Class, Control.Monad.Error.Class, Control.Monad.Except.Trans, Control.Monad.Reader.Class, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Either, Data.Function, Data.Functor, Data.Monoid, Data.Newtype, Data.Semigroup, Data.Tuple, Effect.Class, Prelude
require_once __DIR__ . '/../Control.Alt/index.php';
require_once __DIR__ . '/../Control.Alternative/index.php';
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Apply/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Category/index.php';
require_once __DIR__ . '/../Control.Monad/index.php';
require_once __DIR__ . '/../Control.Monad.Cont.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Error.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Except.Trans/index.php';
require_once __DIR__ . '/../Control.Monad.Reader.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Rec.Class/index.php';
require_once __DIR__ . '/../Control.Monad.ST.Class/index.php';
require_once __DIR__ . '/../Control.Monad.State.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Trans.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Writer.Class/index.php';
require_once __DIR__ . '/../Control.MonadPlus/index.php';
require_once __DIR__ . '/../Control.Plus/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Either/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Functor/index.php';
require_once __DIR__ . '/../Data.Monoid/index.php';
require_once __DIR__ . '/../Data.Newtype/index.php';
require_once __DIR__ . '/../Data.Semigroup/index.php';
require_once __DIR__ . '/../Data.Tuple/index.php';
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




// Control_Monad_Except_Trans_ExceptT
function majControl_majMonad_majExcept_majTrans_majExceptmajT($x_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_majExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $x_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_ExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_majExceptmajT';

// Control_Monad_Except_Trans_withExceptT
function majControl_majMonad_majExcept_majTrans_withmajExceptmajT($dictFunctor_0, $f_1 = null, $v_2 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_withmajExceptmajT';
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Except_Trans_ExceptT']))(((($GLOBALS['Data_Functor_map'])($dictFunctor_0))(function($v2_3) use ($f_1) {
  $__num = \func_num_args();
  $__t0 = null;
  if ($v2_3 instanceof \Data\Either\Data_Either_Right) {
$__t0 = ($GLOBALS['Data_Either_Right'])(($v2_3)->{'value0'});
goto end_branch_0;;
};
  if ($v2_3 instanceof \Data\Either\Data_Either_Left) {
$__t0 = ($GLOBALS['Data_Either_Left'])(($f_1)(($v2_3)->{'value0'}));
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($v_2));
  goto __end;;
  __end:
  return 3 < $__num ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_withExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_withmajExceptmajT';

// Control_Monad_Except_Trans_runExceptT
function majControl_majMonad_majExcept_majTrans_runmajExceptmajT($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_runmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $v_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_runExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_runmajExceptmajT';

// Control_Monad_Except_Trans_newtypeExceptT
$GLOBALS['Control_Monad_Except_Trans_newtypeExceptT'] = (object)["Coercible0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = null;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Except_Trans_monadTransExceptT
$GLOBALS['Control_Monad_Except_Trans_monadTransExceptT'] = (object)["lift" => function($dictMonad_0) {
  $__num = \func_num_args();
  $Bind1_1_0 = (($dictMonad_0)->{'Bind1'})(null);
  $pure_2_1 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $__res = function($m_3) use ($Bind1_1_0, $pure_2_1) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))($m_3))(function($a_4) use ($pure_2_1) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_2_1))(($GLOBALS['Data_Either_Right'])($a_4));
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

// Control_Monad_Except_Trans_mapExceptT
function majControl_majMonad_majExcept_majTrans_mapmajExceptmajT($f_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_mapmajExceptmajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($f_0)($v_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_mapExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_mapmajExceptmajT';

// Control_Monad_Except_Trans_functorExceptT
function majControl_majMonad_majExcept_majTrans_functormajExceptmajT($dictFunctor_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_functormajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["map" => function($f_1) use ($dictFunctor_0) {
  $__num = \func_num_args();
  $__local_var_2_0 = (($GLOBALS['Data_Functor_map'])($dictFunctor_0))((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Either_functorEither']))($f_1));
  $__res = function($v_3) use ($__local_var_2_0) {
  $__num = \func_num_args();
  $__res = ($__local_var_2_0)($v_3);
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
$GLOBALS['Control_Monad_Except_Trans_functorExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_functormajExceptmajT';

// Control_Monad_Except_Trans_except
function majControl_majMonad_majExcept_majTrans_except($dictApplicative_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_except';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Except_Trans_ExceptT']))(($GLOBALS['Control_Applicative_pure'])($dictApplicative_0));
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_except'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_except';

// Control_Monad_Except_Trans_monadExceptT
function majControl_majMonad_majExcept_majTrans_monadmajExceptmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["Applicative0" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_Except_Trans_applicativeExceptT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Bind1" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_Except_Trans_bindExceptT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajExceptmajT';

// Control_Monad_Except_Trans_bindExceptT
function majControl_majMonad_majExcept_majTrans_bindmajExceptmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_bindmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Bind1_1_0 = (($dictMonad_0)->{'Bind1'})(null);
  $pure_2_1 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $__res = (object)["bind" => function($v_3) use ($Bind1_1_0, $pure_2_1) {
  $__num = \func_num_args();
  $__res = function($k_4) use ($Bind1_1_0, $pure_2_1, $v_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))($v_3))((($GLOBALS['Data_Either_either'])(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($pure_2_1))($GLOBALS['Data_Either_Left'])))(function($a_5) use ($k_4) {
  $__num = \func_num_args();
  $__res = ($k_4)($a_5);
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
}, "Apply0" => function($_dollar___unused_3) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_Except_Trans_applyExceptT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_bindExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_bindmajExceptmajT';

// Control_Monad_Except_Trans_applyExceptT
function majControl_majMonad_majExcept_majTrans_applymajExceptmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_applymajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorExceptT1_1_0 = ($GLOBALS['Control_Monad_Except_Trans_functorExceptT'])((((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null));
  $__res = (object)["apply" => ($GLOBALS['Control_Monad_ap'])(($GLOBALS['Control_Monad_Except_Trans_monadExceptT'])($dictMonad_0)), "Functor0" => function($_dollar___unused_2) use ($functorExceptT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorExceptT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_applyExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_applymajExceptmajT';

// Control_Monad_Except_Trans_applicativeExceptT
function majControl_majMonad_majExcept_majTrans_applicativemajExceptmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_applicativemajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["pure" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Except_Trans_ExceptT']))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null))))($GLOBALS['Data_Either_Right'])), "Apply0" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_Except_Trans_applyExceptT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_applicativeExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_applicativemajExceptmajT';

// Control_Monad_Except_Trans_semigroupExceptT
function majControl_majMonad_majExcept_majTrans_semigroupmajExceptmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_semigroupmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applyExceptT1_1_0 = ($GLOBALS['Control_Monad_Except_Trans_applyExceptT'])($dictMonad_0);
  $__res = function($dictSemigroup_2) use ($applyExceptT1_1_0) {
  $__num = \func_num_args();
  $__res = (object)["append" => (($GLOBALS['Control_Apply_lift2'])($applyExceptT1_1_0))(($GLOBALS['Data_Semigroup_append'])($dictSemigroup_2))];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_semigroupExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_semigroupmajExceptmajT';

// Control_Monad_Except_Trans_monadAskExceptT
function majControl_majMonad_majExcept_majTrans_monadmajAskmajExceptmajT($dictMonadAsk_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajAskmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadExceptT1_1_0 = ($GLOBALS['Control_Monad_Except_Trans_monadExceptT'])((($dictMonadAsk_0)->{'Monad0'})(null));
  $__local_var_2_1 = (($dictMonadAsk_0)->{'Monad0'})(null);
  $pure_3_2 = ($GLOBALS['Control_Applicative_pure'])((($__local_var_2_1)->{'Applicative0'})(null));
  $__res = (object)["ask" => ((($GLOBALS['Control_Bind_bind'])((($__local_var_2_1)->{'Bind1'})(null)))(($GLOBALS['Control_Monad_Reader_Class_ask'])($dictMonadAsk_0)))(function($a_4) use ($pure_3_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_3_2))(($GLOBALS['Data_Either_Right'])($a_4));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}), "Monad0" => function($_dollar___unused_2) use ($monadExceptT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadExceptT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadAskExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajAskmajExceptmajT';

// Control_Monad_Except_Trans_monadReaderExceptT
function majControl_majMonad_majExcept_majTrans_monadmajReadermajExceptmajT($dictMonadReader_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajReadermajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadAskExceptT1_1_0 = ($GLOBALS['Control_Monad_Except_Trans_monadAskExceptT'])((($dictMonadReader_0)->{'MonadAsk0'})(null));
  $__res = (object)["local" => function($f_2) use ($dictMonadReader_0) {
  $__num = \func_num_args();
  $__local_var_3_1 = (($dictMonadReader_0)->{'local'})($f_2);
  $__res = function($v_4) use ($__local_var_3_1) {
  $__num = \func_num_args();
  $__res = ($__local_var_3_1)($v_4);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadAsk0" => function($_dollar___unused_2) use ($monadAskExceptT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadAskExceptT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadReaderExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajReadermajExceptmajT';

// Control_Monad_Except_Trans_monadContExceptT
function majControl_majMonad_majExcept_majTrans_monadmajContmajExceptmajT($dictMonadCont_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajContmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadExceptT1_1_0 = ($GLOBALS['Control_Monad_Except_Trans_monadExceptT'])((($dictMonadCont_0)->{'Monad0'})(null));
  $__res = (object)["callCC" => function($f_2) use ($dictMonadCont_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Except_Trans_ExceptT']))((($dictMonadCont_0)->{'callCC'})(function($c_3) use ($f_2) {
  $__num = \func_num_args();
  $__res = ($f_2)(function($a_4) use ($c_3) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Except_Trans_ExceptT']))(($c_3)(($GLOBALS['Data_Either_Right'])($a_4)));
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
}, "Monad0" => function($_dollar___unused_2) use ($monadExceptT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadExceptT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadContExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajContmajExceptmajT';

// Control_Monad_Except_Trans_monadEffectExceptT
function majControl_majMonad_majExcept_majTrans_monadmajEffectmajExceptmajT($dictMonadEffect_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajEffectmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadEffect_0)->{'Monad0'})(null);
  $monadExceptT1_2_1 = ($GLOBALS['Control_Monad_Except_Trans_monadExceptT'])($Monad0_1_0);
  $__res = (object)["liftEffect" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Except_Trans_monadTransExceptT']))($Monad0_1_0)))(($GLOBALS['Effect_Class_liftEffect'])($dictMonadEffect_0)), "Monad0" => function($_dollar___unused_3) use ($monadExceptT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadExceptT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadEffectExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajEffectmajExceptmajT';

// Control_Monad_Except_Trans_monadRecExceptT
function majControl_majMonad_majExcept_majTrans_monadmajRecmajExceptmajT($dictMonadRec_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajRecmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadRec_0)->{'Monad0'})(null);
  $Bind1_2_1 = (($Monad0_1_0)->{'Bind1'})(null);
  $Applicative0_3_2 = (($Monad0_1_0)->{'Applicative0'})(null);
  $monadExceptT1_4_3 = ($GLOBALS['Control_Monad_Except_Trans_monadExceptT'])($Monad0_1_0);
  $__res = (object)["tailRecM" => function($f_5) use ($Applicative0_3_2, $Bind1_2_1, $dictMonadRec_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Except_Trans_ExceptT']))((($GLOBALS['Control_Monad_Rec_Class_tailRecM'])($dictMonadRec_0))(function($a_6) use ($Applicative0_3_2, $Bind1_2_1, $f_5) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_1))(($f_5)($a_6)))(function($m_prime__7) use ($Applicative0_3_2) {
  $__num = \func_num_args();
  $__t4 = null;
  if ($m_prime__7 instanceof \Data\Either\Data_Either_Left) {
$__t4 = ($GLOBALS['Control_Monad_Rec_Class_Done'])(($GLOBALS['Data_Either_Left'])(($m_prime__7)->{'value0'}));
goto end_branch_4;;
};
  if ($m_prime__7 instanceof \Data\Either\Data_Either_Right) {
$__t5 = null;
if (($m_prime__7)->{'value0'} instanceof \Control\Monad\Rec\Class\Control_Monad_Rec_Class_Loop) {
$__t5 = ($GLOBALS['Control_Monad_Rec_Class_Loop'])((($m_prime__7)->{'value0'})->{'value0'});
goto end_branch_5;;
};
if (($m_prime__7)->{'value0'} instanceof \Control\Monad\Rec\Class\Control_Monad_Rec_Class_Done) {
$__t5 = ($GLOBALS['Control_Monad_Rec_Class_Done'])(($GLOBALS['Data_Either_Right'])((($m_prime__7)->{'value0'})->{'value0'}));
goto end_branch_5;;
};
throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
$__t5 = null;
end_branch_5:;
$__t4 = $__t5;
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
}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_5) use ($monadExceptT1_4_3) {
  $__num = \func_num_args();
  $__res = $monadExceptT1_4_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadRecExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajRecmajExceptmajT';

// Control_Monad_Except_Trans_monadStateExceptT
function majControl_majMonad_majExcept_majTrans_monadmajStatemajExceptmajT($dictMonadState_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajStatemajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadState_0)->{'Monad0'})(null);
  $monadExceptT1_2_1 = ($GLOBALS['Control_Monad_Except_Trans_monadExceptT'])((($dictMonadState_0)->{'Monad0'})(null));
  $__res = (object)["state" => function($f_3) use ($Monad0_1_0, $dictMonadState_0) {
  $__num = \func_num_args();
  $pure_4_2 = ($GLOBALS['Control_Applicative_pure'])((($Monad0_1_0)->{'Applicative0'})(null));
  $__res = ((($GLOBALS['Control_Bind_bind'])((($Monad0_1_0)->{'Bind1'})(null)))((($dictMonadState_0)->{'state'})($f_3)))(function($a_5) use ($pure_4_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($pure_4_2))(($GLOBALS['Data_Either_Right'])($a_5));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_3) use ($monadExceptT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadExceptT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadStateExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajStatemajExceptmajT';

// Control_Monad_Except_Trans_monadTellExceptT
function majControl_majMonad_majExcept_majTrans_monadmajTellmajExceptmajT($dictMonadTell_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajTellmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad1_1_0 = (($dictMonadTell_0)->{'Monad1'})(null);
  $Semigroup0_2_1 = (($dictMonadTell_0)->{'Semigroup0'})(null);
  $monadExceptT1_3_2 = ($GLOBALS['Control_Monad_Except_Trans_monadExceptT'])($Monad1_1_0);
  $__res = (object)["tell" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Except_Trans_monadTransExceptT']))($Monad1_1_0)))(($GLOBALS['Control_Monad_Writer_Class_tell'])($dictMonadTell_0)), "Semigroup0" => function($_dollar___unused_4) use ($Semigroup0_2_1) {
  $__num = \func_num_args();
  $__res = $Semigroup0_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad1" => function($_dollar___unused_4) use ($monadExceptT1_3_2) {
  $__num = \func_num_args();
  $__res = $monadExceptT1_3_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadTellExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajTellmajExceptmajT';

// Control_Monad_Except_Trans_monadWriterExceptT
function majControl_majMonad_majExcept_majTrans_monadmajWritermajExceptmajT($dictMonadWriter_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajWritermajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $MonadTell1_1_0 = (($dictMonadWriter_0)->{'MonadTell1'})(null);
  $Monad1_2_1 = (($MonadTell1_1_0)->{'Monad1'})(null);
  $Bind1_3_2 = (($Monad1_2_1)->{'Bind1'})(null);
  $pure_4_3 = ($GLOBALS['Control_Applicative_pure'])((($Monad1_2_1)->{'Applicative0'})(null));
  $Applicative0_5_4 = (($Monad1_2_1)->{'Applicative0'})(null);
  $Monoid0_6_5 = (($dictMonadWriter_0)->{'Monoid0'})(null);
  $monadTellExceptT1_7_6 = ($GLOBALS['Control_Monad_Except_Trans_monadTellExceptT'])($MonadTell1_1_0);
  $__res = (object)["listen" => function($v_8) use ($Bind1_3_2, $dictMonadWriter_0, $pure_4_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))((($dictMonadWriter_0)->{'listen'})($v_8)))(function($v_9) use ($pure_4_3) {
  $__num = \func_num_args();
  $__local_var_10_slot = ($v_9)->{'value1'};
  $__res = (($GLOBALS['Data_Function_apply'])($pure_4_3))(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Either_functorEither']))(function($r_11) use ($__local_var_10_slot) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Tuple_Tuple'])($r_11))($__local_var_10_slot);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(($v_9)->{'value0'}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "pass" => function($v_8) use ($Applicative0_5_4, $Bind1_3_2, $dictMonadWriter_0) {
  $__num = \func_num_args();
  $__res = (($dictMonadWriter_0)->{'pass'})(((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))($v_8))(function($a_9) use ($Applicative0_5_4) {
  $__num = \func_num_args();
  $__t8 = null;
  if ($a_9 instanceof \Data\Either\Data_Either_Left) {
$__t8 = (($GLOBALS['Data_Tuple_Tuple'])(($GLOBALS['Data_Either_Left'])(($a_9)->{'value0'})))(($GLOBALS['Control_Category_identity'])($GLOBALS['Control_Category_categoryFn']));
goto end_branch_8;;
};
  if ($a_9 instanceof \Data\Either\Data_Either_Right) {
$__t8 = (($GLOBALS['Data_Tuple_Tuple'])(($GLOBALS['Data_Either_Right'])((($a_9)->{'value0'})->{'value0'})))((($a_9)->{'value0'})->{'value1'});
goto end_branch_8;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t8 = null;
  end_branch_8:;
  $__res = (($GLOBALS['Control_Applicative_pure'])($Applicative0_5_4))($__t8);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monoid0" => function($_dollar___unused_8) use ($Monoid0_6_5) {
  $__num = \func_num_args();
  $__res = $Monoid0_6_5;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadTell1" => function($_dollar___unused_8) use ($monadTellExceptT1_7_6) {
  $__num = \func_num_args();
  $__res = $monadTellExceptT1_7_6;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadWriterExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajWritermajExceptmajT';

// Control_Monad_Except_Trans_monadThrowExceptT
function majControl_majMonad_majExcept_majTrans_monadmajThrowmajExceptmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajThrowmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadExceptT1_1_0 = ($GLOBALS['Control_Monad_Except_Trans_monadExceptT'])($dictMonad_0);
  $__res = (object)["throwError" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Except_Trans_ExceptT']))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null))))($GLOBALS['Data_Either_Left'])), "Monad0" => function($_dollar___unused_2) use ($monadExceptT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadExceptT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadThrowExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajThrowmajExceptmajT';

// Control_Monad_Except_Trans_monadErrorExceptT
function majControl_majMonad_majExcept_majTrans_monadmajErrormajExceptmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajErrormajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Bind1_1_0 = (($dictMonad_0)->{'Bind1'})(null);
  $pure_2_1 = ($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null));
  $monadThrowExceptT1_3_2 = ($GLOBALS['Control_Monad_Except_Trans_monadThrowExceptT'])($dictMonad_0);
  $__res = (object)["catchError" => function($v_4) use ($Bind1_1_0, $pure_2_1) {
  $__num = \func_num_args();
  $__res = function($k_5) use ($Bind1_1_0, $pure_2_1, $v_4) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))($v_4))((($GLOBALS['Data_Either_either'])(function($a_6) use ($k_5) {
  $__num = \func_num_args();
  $__res = ($k_5)($a_6);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($pure_2_1))($GLOBALS['Data_Either_Right'])));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadThrow0" => function($_dollar___unused_4) use ($monadThrowExceptT1_3_2) {
  $__num = \func_num_args();
  $__res = $monadThrowExceptT1_3_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadErrorExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajErrormajExceptmajT';

// Control_Monad_Except_Trans_monadSTExceptT
function majControl_majMonad_majExcept_majTrans_monadmajSmajTmajExceptmajT($dictMonadST_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajSmajTmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadST_0)->{'Monad0'})(null);
  $monadExceptT1_2_1 = ($GLOBALS['Control_Monad_Except_Trans_monadExceptT'])($Monad0_1_0);
  $__res = (object)["liftST" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Except_Trans_monadTransExceptT']))($Monad0_1_0)))(($GLOBALS['Control_Monad_ST_Class_liftST'])($dictMonadST_0)), "Monad0" => function($_dollar___unused_3) use ($monadExceptT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadExceptT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_monadSTExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajSmajTmajExceptmajT';

// Control_Monad_Except_Trans_monoidExceptT
function majControl_majMonad_majExcept_majTrans_monoidmajExceptmajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monoidmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeExceptT1_1_0 = ($GLOBALS['Control_Monad_Except_Trans_applicativeExceptT'])($dictMonad_0);
  $semigroupExceptT1_2_1 = ($GLOBALS['Control_Monad_Except_Trans_semigroupExceptT'])($dictMonad_0);
  $__res = function($dictMonoid_3) use ($applicativeExceptT1_1_0, $semigroupExceptT1_2_1) {
  $__num = \func_num_args();
  $semigroupExceptT2_4_2 = ($semigroupExceptT1_2_1)((($dictMonoid_3)->{'Semigroup0'})(null));
  $__res = (object)["mempty" => (($GLOBALS['Control_Applicative_pure'])($applicativeExceptT1_1_0))(($GLOBALS['Data_Monoid_mempty'])($dictMonoid_3)), "Semigroup0" => function($_dollar___unused_5) use ($semigroupExceptT2_4_2) {
  $__num = \func_num_args();
  $__res = $semigroupExceptT2_4_2;
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
$GLOBALS['Control_Monad_Except_Trans_monoidExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monoidmajExceptmajT';

// Control_Monad_Except_Trans_altExceptT
function majControl_majMonad_majExcept_majTrans_altmajExceptmajT($dictSemigroup_0, $dictMonad_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_altmajExceptmajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $Bind1_2_0 = (($dictMonad_1)->{'Bind1'})(null);
  $Applicative0_3_1 = (($dictMonad_1)->{'Applicative0'})(null);
  $functorExceptT1_4_2 = ($GLOBALS['Control_Monad_Except_Trans_functorExceptT'])((((((($dictMonad_1)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null));
  $__res = (object)["alt" => function($v_5) use ($Applicative0_3_1, $Bind1_2_0, $dictSemigroup_0) {
  $__num = \func_num_args();
  $__res = function($v1_6) use ($Applicative0_3_1, $Bind1_2_0, $dictSemigroup_0, $v_5) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_0))($v_5))(function($rm_7) use ($Applicative0_3_1, $Bind1_2_0, $dictSemigroup_0, $v1_6) {
  $__num = \func_num_args();
  $__t3 = null;
  if ($rm_7 instanceof \Data\Either\Data_Either_Right) {
$__t3 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_3_1))(($GLOBALS['Data_Either_Right'])(($rm_7)->{'value0'}));
goto end_branch_3;;
};
  if ($rm_7 instanceof \Data\Either\Data_Either_Left) {
$__local_var_8_slot = ($rm_7)->{'value0'};
$__t3 = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_0))($v1_6))(function($rn_9) use ($Applicative0_3_1, $__local_var_8_slot, $dictSemigroup_0) {
  $__num = \func_num_args();
  $__t5 = null;
  if ($rn_9 instanceof \Data\Either\Data_Either_Right) {
$__t5 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_3_1))(($GLOBALS['Data_Either_Right'])(($rn_9)->{'value0'}));
goto end_branch_5;;
};
  if ($rn_9 instanceof \Data\Either\Data_Either_Left) {
$__t5 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_3_1))(($GLOBALS['Data_Either_Left'])(((($GLOBALS['Data_Semigroup_append'])($dictSemigroup_0))($__local_var_8_slot))(($rn_9)->{'value0'})));
goto end_branch_5;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t5 = null;
  end_branch_5:;
  $__res = $__t5;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
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
}, "Functor0" => function($_dollar___unused_5) use ($functorExceptT1_4_2) {
  $__num = \func_num_args();
  $__res = $functorExceptT1_4_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_Except_Trans_altExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_altmajExceptmajT';

// Control_Monad_Except_Trans_plusExceptT
function majControl_majMonad_majExcept_majTrans_plusmajExceptmajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_plusmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $mempty_1_0 = ($GLOBALS['Data_Monoid_mempty'])($dictMonoid_0);
  $altExceptT1_2_1 = ($GLOBALS['Control_Monad_Except_Trans_altExceptT'])((($dictMonoid_0)->{'Semigroup0'})(null));
  $__res = function($dictMonad_3) use ($altExceptT1_2_1, $mempty_1_0) {
  $__num = \func_num_args();
  $altExceptT2_4_2 = ($altExceptT1_2_1)($dictMonad_3);
  $__res = (object)["empty" => ((($GLOBALS['Control_Monad_Except_Trans_monadThrowExceptT'])($dictMonad_3))->{'throwError'})($mempty_1_0), "Alt0" => function($_dollar___unused_5) use ($altExceptT2_4_2) {
  $__num = \func_num_args();
  $__res = $altExceptT2_4_2;
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
$GLOBALS['Control_Monad_Except_Trans_plusExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_plusmajExceptmajT';

// Control_Monad_Except_Trans_alternativeExceptT
function majControl_majMonad_majExcept_majTrans_alternativemajExceptmajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_alternativemajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $plusExceptT1_1_0 = ($GLOBALS['Control_Monad_Except_Trans_plusExceptT'])($dictMonoid_0);
  $__res = function($dictMonad_2) use ($plusExceptT1_1_0) {
  $__num = \func_num_args();
  $applicativeExceptT1_3_1 = ($GLOBALS['Control_Monad_Except_Trans_applicativeExceptT'])($dictMonad_2);
  $plusExceptT2_4_2 = ($plusExceptT1_1_0)($dictMonad_2);
  $__res = (object)["Applicative0" => function($_dollar___unused_5) use ($applicativeExceptT1_3_1) {
  $__num = \func_num_args();
  $__res = $applicativeExceptT1_3_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Plus1" => function($_dollar___unused_5) use ($plusExceptT2_4_2) {
  $__num = \func_num_args();
  $__res = $plusExceptT2_4_2;
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
$GLOBALS['Control_Monad_Except_Trans_alternativeExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_alternativemajExceptmajT';

// Control_Monad_Except_Trans_monadPlusExceptT
function majControl_majMonad_majExcept_majTrans_monadmajPlusmajExceptmajT($dictMonoid_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majExcept_majTrans_monadmajPlusmajExceptmajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $alternativeExceptT1_1_0 = ($GLOBALS['Control_Monad_Except_Trans_alternativeExceptT'])($dictMonoid_0);
  $__res = function($dictMonad_2) use ($alternativeExceptT1_1_0) {
  $__num = \func_num_args();
  $monadExceptT1_3_1 = ($GLOBALS['Control_Monad_Except_Trans_monadExceptT'])($dictMonad_2);
  $alternativeExceptT2_4_2 = ($alternativeExceptT1_1_0)($dictMonad_2);
  $__res = (object)["Monad0" => function($_dollar___unused_5) use ($monadExceptT1_3_1) {
  $__num = \func_num_args();
  $__res = $monadExceptT1_3_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Alternative1" => function($_dollar___unused_5) use ($alternativeExceptT2_4_2) {
  $__num = \func_num_args();
  $__res = $alternativeExceptT2_4_2;
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
$GLOBALS['Control_Monad_Except_Trans_monadPlusExceptT'] = __NAMESPACE__ . '\\majControl_majMonad_majExcept_majTrans_monadmajPlusmajExceptmajT';

