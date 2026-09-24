<?php

namespace Control\Monad\Maybe\Trans;

// ALL IMPORTS: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Category, Control.Monad, Control.Monad.Cont.Class, Control.Monad.Error.Class, Control.Monad.Maybe.Trans, Control.Monad.Reader.Class, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Function, Data.Functor, Data.Maybe, Data.Monoid, Data.Newtype, Data.Semigroup, Data.Tuple, Effect.Class, Prelude, Prim
// TO REQUIRE: Control.Alt, Control.Alternative, Control.Applicative, Control.Apply, Control.Bind, Control.Category, Control.Monad, Control.Monad.Cont.Class, Control.Monad.Error.Class, Control.Monad.Maybe.Trans, Control.Monad.Reader.Class, Control.Monad.Rec.Class, Control.Monad.ST.Class, Control.Monad.State.Class, Control.Monad.Trans.Class, Control.Monad.Writer.Class, Control.MonadPlus, Control.Plus, Control.Semigroupoid, Data.Function, Data.Functor, Data.Maybe, Data.Monoid, Data.Newtype, Data.Semigroup, Data.Tuple, Effect.Class, Prelude
require_once __DIR__ . '/../Control.Alt/index.php';
require_once __DIR__ . '/../Control.Alternative/index.php';
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Apply/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Category/index.php';
require_once __DIR__ . '/../Control.Monad/index.php';
require_once __DIR__ . '/../Control.Monad.Cont.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Error.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Maybe.Trans/index.php';
require_once __DIR__ . '/../Control.Monad.Reader.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Rec.Class/index.php';
require_once __DIR__ . '/../Control.Monad.ST.Class/index.php';
require_once __DIR__ . '/../Control.Monad.State.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Trans.Class/index.php';
require_once __DIR__ . '/../Control.Monad.Writer.Class/index.php';
require_once __DIR__ . '/../Control.MonadPlus/index.php';
require_once __DIR__ . '/../Control.Plus/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Functor/index.php';
require_once __DIR__ . '/../Data.Maybe/index.php';
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




// Control_Monad_Maybe_Trans_MaybeT
function majControl_majMonad_majMaybe_majTrans_majMaybemajT($x_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_majMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $x_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_MaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_majMaybemajT';

// Control_Monad_Maybe_Trans_runMaybeT
function majControl_majMonad_majMaybe_majTrans_runmajMaybemajT($v_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_runmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = $v_0;
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_runMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_runmajMaybemajT';

// Control_Monad_Maybe_Trans_newtypeMaybeT
$GLOBALS['Control_Monad_Maybe_Trans_newtypeMaybeT'] = (object)["Coercible0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = null;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Maybe_Trans_monadTransMaybeT
$GLOBALS['Control_Monad_Maybe_Trans_monadTransMaybeT'] = (object)["lift" => function($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Maybe_Trans_MaybeT']))((($GLOBALS['Control_Monad_liftM1'])($dictMonad_0))($GLOBALS['Data_Maybe_Just']));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Maybe_Trans_mapMaybeT
function majControl_majMonad_majMaybe_majTrans_mapmajMaybemajT($f_0, $v_1 = null) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_mapmajMaybemajT';
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  $__res = ($f_0)($v_1);
  goto __end;;
  __end:
  return 2 < $__num ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_mapMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_mapmajMaybemajT';

// Control_Monad_Maybe_Trans_functorMaybeT
function majControl_majMonad_majMaybe_majTrans_functormajMaybemajT($dictFunctor_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_functormajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["map" => function($f_1) use ($dictFunctor_0) {
  $__num = \func_num_args();
  $__res = function($v_2) use ($dictFunctor_0, $f_1) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Functor_map'])($dictFunctor_0))((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Maybe_functorMaybe']))($f_1)))($v_2);
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
$GLOBALS['Control_Monad_Maybe_Trans_functorMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_functormajMaybemajT';

// Control_Monad_Maybe_Trans_monadMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajMaybemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["Applicative0" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_Maybe_Trans_applicativeMaybeT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Bind1" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_Maybe_Trans_bindMaybeT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajMaybemajT';

// Control_Monad_Maybe_Trans_bindMaybeT
function majControl_majMonad_majMaybe_majTrans_bindmajMaybemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_bindmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Bind1_1_0 = (($dictMonad_0)->{'Bind1'})(null);
  $Applicative0_2_1 = (($dictMonad_0)->{'Applicative0'})(null);
  $__res = (object)["bind" => function($v_3) use ($Applicative0_2_1, $Bind1_1_0) {
  $__num = \func_num_args();
  $__res = function($f_4) use ($Applicative0_2_1, $Bind1_1_0, $v_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))($v_3))(function($v1_5) use ($Applicative0_2_1, $f_4) {
  $__num = \func_num_args();
  $__t2 = null;
  if ($v1_5 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t2 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1))($GLOBALS['Data_Maybe_Nothing']);
goto end_branch_2;;
};
  if ($v1_5 instanceof \Data\Maybe\Data_Maybe_Just) {
$__t2 = ($f_4)(($v1_5)->{'value0'});
goto end_branch_2;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t2 = null;
  end_branch_2:;
  $__res = $__t2;
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
}, "Apply0" => function($_dollar___unused_3) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_Maybe_Trans_applyMaybeT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_bindMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_bindmajMaybemajT';

// Control_Monad_Maybe_Trans_applyMaybeT
function majControl_majMonad_majMaybe_majTrans_applymajMaybemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_applymajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $functorMaybeT1_1_0 = ($GLOBALS['Control_Monad_Maybe_Trans_functorMaybeT'])((((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null));
  $__res = (object)["apply" => ($GLOBALS['Control_Monad_ap'])(($GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'])($dictMonad_0)), "Functor0" => function($_dollar___unused_2) use ($functorMaybeT1_1_0) {
  $__num = \func_num_args();
  $__res = $functorMaybeT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_applyMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_applymajMaybemajT';

// Control_Monad_Maybe_Trans_applicativeMaybeT
function majControl_majMonad_majMaybe_majTrans_applicativemajMaybemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_applicativemajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (object)["pure" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Maybe_Trans_MaybeT']))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))(($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null))))($GLOBALS['Data_Maybe_Just'])), "Apply0" => function($_dollar___unused_1) use ($dictMonad_0) {
  $__num = \func_num_args();
  $__res = ($GLOBALS['Control_Monad_Maybe_Trans_applyMaybeT'])($dictMonad_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_applicativeMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_applicativemajMaybemajT';

// Control_Monad_Maybe_Trans_semigroupMaybeT
function majControl_majMonad_majMaybe_majTrans_semigroupmajMaybemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_semigroupmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applyMaybeT1_1_0 = ($GLOBALS['Control_Monad_Maybe_Trans_applyMaybeT'])($dictMonad_0);
  $__res = function($dictSemigroup_2) use ($applyMaybeT1_1_0) {
  $__num = \func_num_args();
  $__res = (object)["append" => (($GLOBALS['Control_Apply_lift2'])($applyMaybeT1_1_0))(($GLOBALS['Data_Semigroup_append'])($dictSemigroup_2))];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_semigroupMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_semigroupmajMaybemajT';

// Control_Monad_Maybe_Trans_monadAskMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajAskmajMaybemajT($dictMonadAsk_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajAskmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadMaybeT1_1_0 = ($GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'])((($dictMonadAsk_0)->{'Monad0'})(null));
  $__res = (object)["ask" => (((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Maybe_Trans_MaybeT']))((($GLOBALS['Control_Monad_liftM1'])((($dictMonadAsk_0)->{'Monad0'})(null)))($GLOBALS['Data_Maybe_Just'])))(($GLOBALS['Control_Monad_Reader_Class_ask'])($dictMonadAsk_0)), "Monad0" => function($_dollar___unused_2) use ($monadMaybeT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadMaybeT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadAskMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajAskmajMaybemajT';

// Control_Monad_Maybe_Trans_monadReaderMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajReadermajMaybemajT($dictMonadReader_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajReadermajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadAskMaybeT1_1_0 = ($GLOBALS['Control_Monad_Maybe_Trans_monadAskMaybeT'])((($dictMonadReader_0)->{'MonadAsk0'})(null));
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
}, "MonadAsk0" => function($_dollar___unused_2) use ($monadAskMaybeT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadAskMaybeT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadReaderMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajReadermajMaybemajT';

// Control_Monad_Maybe_Trans_monadContMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajContmajMaybemajT($dictMonadCont_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajContmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadMaybeT1_1_0 = ($GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'])((($dictMonadCont_0)->{'Monad0'})(null));
  $__res = (object)["callCC" => function($f_2) use ($dictMonadCont_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Maybe_Trans_MaybeT']))((($dictMonadCont_0)->{'callCC'})(function($c_3) use ($f_2) {
  $__num = \func_num_args();
  $__res = ($f_2)(function($a_4) use ($c_3) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Maybe_Trans_MaybeT']))((($GLOBALS['Data_Function_apply'])($c_3))(($GLOBALS['Data_Maybe_Just'])($a_4)));
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
}, "Monad0" => function($_dollar___unused_2) use ($monadMaybeT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadMaybeT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadContMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajContmajMaybemajT';

// Control_Monad_Maybe_Trans_monadEffectMaybe
function majControl_majMonad_majMaybe_majTrans_monadmajEffectmajMaybe($dictMonadEffect_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajEffectmajMaybe';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadEffect_0)->{'Monad0'})(null);
  $monadMaybeT1_2_1 = ($GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'])($Monad0_1_0);
  $__res = (object)["liftEffect" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Maybe_Trans_monadTransMaybeT']))($Monad0_1_0)))(($GLOBALS['Effect_Class_liftEffect'])($dictMonadEffect_0)), "Monad0" => function($_dollar___unused_3) use ($monadMaybeT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadMaybeT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadEffectMaybe'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajEffectmajMaybe';

// Control_Monad_Maybe_Trans_monadRecMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajRecmajMaybemajT($dictMonadRec_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajRecmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadRec_0)->{'Monad0'})(null);
  $Bind1_2_1 = (($Monad0_1_0)->{'Bind1'})(null);
  $Applicative0_3_2 = (($Monad0_1_0)->{'Applicative0'})(null);
  $monadMaybeT1_4_3 = ($GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'])($Monad0_1_0);
  $__res = (object)["tailRecM" => function($f_5) use ($Applicative0_3_2, $Bind1_2_1, $dictMonadRec_0) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Maybe_Trans_MaybeT']))((($GLOBALS['Control_Monad_Rec_Class_tailRecM'])($dictMonadRec_0))(function($a_6) use ($Applicative0_3_2, $Bind1_2_1, $f_5) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_2_1))(($f_5)($a_6)))(function($m_prime__7) use ($Applicative0_3_2) {
  $__num = \func_num_args();
  $__t4 = null;
  if ($m_prime__7 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t4 = ($GLOBALS['Control_Monad_Rec_Class_Done'])($GLOBALS['Data_Maybe_Nothing']);
goto end_branch_4;;
};
  if ($m_prime__7 instanceof \Data\Maybe\Data_Maybe_Just) {
$__t5 = null;
if (($m_prime__7)->{'value0'} instanceof \Control\Monad\Rec\Class\Control_Monad_Rec_Class_Loop) {
$__t5 = ($GLOBALS['Control_Monad_Rec_Class_Loop'])((($m_prime__7)->{'value0'})->{'value0'});
goto end_branch_5;;
};
if (($m_prime__7)->{'value0'} instanceof \Control\Monad\Rec\Class\Control_Monad_Rec_Class_Done) {
$__t5 = ($GLOBALS['Control_Monad_Rec_Class_Done'])(($GLOBALS['Data_Maybe_Just'])((($m_prime__7)->{'value0'})->{'value0'}));
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
}, "Monad0" => function($_dollar___unused_5) use ($monadMaybeT1_4_3) {
  $__num = \func_num_args();
  $__res = $monadMaybeT1_4_3;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadRecMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajRecmajMaybemajT';

// Control_Monad_Maybe_Trans_monadStateMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajStatemajMaybemajT($dictMonadState_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajStatemajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadState_0)->{'Monad0'})(null);
  $monadMaybeT1_2_1 = ($GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'])((($dictMonadState_0)->{'Monad0'})(null));
  $__res = (object)["state" => function($f_3) use ($Monad0_1_0, $dictMonadState_0) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Maybe_Trans_MaybeT']))((($GLOBALS['Control_Monad_liftM1'])($Monad0_1_0))($GLOBALS['Data_Maybe_Just'])))((($dictMonadState_0)->{'state'})($f_3));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_3) use ($monadMaybeT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadMaybeT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadStateMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajStatemajMaybemajT';

// Control_Monad_Maybe_Trans_monadTellMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajTellmajMaybemajT($dictMonadTell_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajTellmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad1_1_0 = (($dictMonadTell_0)->{'Monad1'})(null);
  $Semigroup0_2_1 = (($dictMonadTell_0)->{'Semigroup0'})(null);
  $monadMaybeT1_3_2 = ($GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'])($Monad1_1_0);
  $__res = (object)["tell" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Maybe_Trans_monadTransMaybeT']))($Monad1_1_0)))(($GLOBALS['Control_Monad_Writer_Class_tell'])($dictMonadTell_0)), "Semigroup0" => function($_dollar___unused_4) use ($Semigroup0_2_1) {
  $__num = \func_num_args();
  $__res = $Semigroup0_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad1" => function($_dollar___unused_4) use ($monadMaybeT1_3_2) {
  $__num = \func_num_args();
  $__res = $monadMaybeT1_3_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadTellMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajTellmajMaybemajT';

// Control_Monad_Maybe_Trans_monadWriterMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajWritermajMaybemajT($dictMonadWriter_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajWritermajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $MonadTell1_1_0 = (($dictMonadWriter_0)->{'MonadTell1'})(null);
  $Monad1_2_1 = (($MonadTell1_1_0)->{'Monad1'})(null);
  $Bind1_3_2 = (($Monad1_2_1)->{'Bind1'})(null);
  $pure_4_3 = ($GLOBALS['Control_Applicative_pure'])((($Monad1_2_1)->{'Applicative0'})(null));
  $Applicative0_5_4 = (($Monad1_2_1)->{'Applicative0'})(null);
  $Monoid0_6_5 = (($dictMonadWriter_0)->{'Monoid0'})(null);
  $monadTellMaybeT1_7_6 = ($GLOBALS['Control_Monad_Maybe_Trans_monadTellMaybeT'])($MonadTell1_1_0);
  $__res = (object)["listen" => function($v_8) use ($Bind1_3_2, $dictMonadWriter_0, $pure_4_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))((($dictMonadWriter_0)->{'listen'})($v_8)))(function($v_9) use ($pure_4_3) {
  $__num = \func_num_args();
  $__local_var_10_slot = ($v_9)->{'value1'};
  $__res = (($GLOBALS['Data_Function_apply'])($pure_4_3))(((($GLOBALS['Data_Functor_map'])($GLOBALS['Data_Maybe_functorMaybe']))(function($r_11) use ($__local_var_10_slot) {
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
  if ($a_9 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t8 = (($GLOBALS['Data_Tuple_Tuple'])($GLOBALS['Data_Maybe_Nothing']))(($GLOBALS['Control_Category_identity'])($GLOBALS['Control_Category_categoryFn']));
goto end_branch_8;;
};
  if ($a_9 instanceof \Data\Maybe\Data_Maybe_Just) {
$__t8 = (($GLOBALS['Data_Tuple_Tuple'])(($GLOBALS['Data_Maybe_Just'])((($a_9)->{'value0'})->{'value0'})))((($a_9)->{'value0'})->{'value1'});
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
}, "MonadTell1" => function($_dollar___unused_8) use ($monadTellMaybeT1_7_6) {
  $__num = \func_num_args();
  $__res = $monadTellMaybeT1_7_6;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadWriterMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajWritermajMaybemajT';

// Control_Monad_Maybe_Trans_monadThrowMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajThrowmajMaybemajT($dictMonadThrow_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajThrowmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadThrow_0)->{'Monad0'})(null);
  $monadMaybeT1_2_1 = ($GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'])((($dictMonadThrow_0)->{'Monad0'})(null));
  $__res = (object)["throwError" => function($e_3) use ($Monad0_1_0, $dictMonadThrow_0) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($GLOBALS['Control_Monad_Maybe_Trans_MaybeT']))((($GLOBALS['Control_Monad_liftM1'])($Monad0_1_0))($GLOBALS['Data_Maybe_Just'])))((($dictMonadThrow_0)->{'throwError'})($e_3));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Monad0" => function($_dollar___unused_3) use ($monadMaybeT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadMaybeT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadThrowMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajThrowmajMaybemajT';

// Control_Monad_Maybe_Trans_monadErrorMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajErrormajMaybemajT($dictMonadError_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajErrormajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadThrowMaybeT1_1_0 = ($GLOBALS['Control_Monad_Maybe_Trans_monadThrowMaybeT'])((($dictMonadError_0)->{'MonadThrow0'})(null));
  $__res = (object)["catchError" => function($v_2) use ($dictMonadError_0) {
  $__num = \func_num_args();
  $__res = function($h_3) use ($dictMonadError_0, $v_2) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Function_apply'])($GLOBALS['Control_Monad_Maybe_Trans_MaybeT']))(((($dictMonadError_0)->{'catchError'})($v_2))(function($a_4) use ($h_3) {
  $__num = \func_num_args();
  $__res = ($h_3)($a_4);
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
}, "MonadThrow0" => function($_dollar___unused_2) use ($monadThrowMaybeT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadThrowMaybeT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadErrorMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajErrormajMaybemajT';

// Control_Monad_Maybe_Trans_monadSTMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajSmajTmajMaybemajT($dictMonadST_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajSmajTmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (($dictMonadST_0)->{'Monad0'})(null);
  $monadMaybeT1_2_1 = ($GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'])($Monad0_1_0);
  $__res = (object)["liftST" => ((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))((($GLOBALS['Control_Monad_Trans_Class_lift'])($GLOBALS['Control_Monad_Maybe_Trans_monadTransMaybeT']))($Monad0_1_0)))(($GLOBALS['Control_Monad_ST_Class_liftST'])($dictMonadST_0)), "Monad0" => function($_dollar___unused_3) use ($monadMaybeT1_2_1) {
  $__num = \func_num_args();
  $__res = $monadMaybeT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadSTMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajSmajTmajMaybemajT';

// Control_Monad_Maybe_Trans_monoidMaybeT
function majControl_majMonad_majMaybe_majTrans_monoidmajMaybemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monoidmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeMaybeT1_1_0 = ($GLOBALS['Control_Monad_Maybe_Trans_applicativeMaybeT'])($dictMonad_0);
  $semigroupMaybeT1_2_1 = ($GLOBALS['Control_Monad_Maybe_Trans_semigroupMaybeT'])($dictMonad_0);
  $__res = function($dictMonoid_3) use ($applicativeMaybeT1_1_0, $semigroupMaybeT1_2_1) {
  $__num = \func_num_args();
  $semigroupMaybeT2_4_2 = ($semigroupMaybeT1_2_1)((($dictMonoid_3)->{'Semigroup0'})(null));
  $__res = (object)["mempty" => (($GLOBALS['Control_Applicative_pure'])($applicativeMaybeT1_1_0))(($GLOBALS['Data_Monoid_mempty'])($dictMonoid_3)), "Semigroup0" => function($_dollar___unused_5) use ($semigroupMaybeT2_4_2) {
  $__num = \func_num_args();
  $__res = $semigroupMaybeT2_4_2;
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
$GLOBALS['Control_Monad_Maybe_Trans_monoidMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monoidmajMaybemajT';

// Control_Monad_Maybe_Trans_altMaybeT
function majControl_majMonad_majMaybe_majTrans_altmajMaybemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_altmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Bind1_1_0 = (($dictMonad_0)->{'Bind1'})(null);
  $Applicative0_2_1 = (($dictMonad_0)->{'Applicative0'})(null);
  $functorMaybeT1_3_2 = ($GLOBALS['Control_Monad_Maybe_Trans_functorMaybeT'])((((((($dictMonad_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null));
  $__res = (object)["alt" => function($v_4) use ($Applicative0_2_1, $Bind1_1_0) {
  $__num = \func_num_args();
  $__res = function($v1_5) use ($Applicative0_2_1, $Bind1_1_0, $v_4) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_1_0))($v_4))(function($m_6) use ($Applicative0_2_1, $v1_5) {
  $__num = \func_num_args();
  $__t3 = null;
  if ($m_6 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t3 = $v1_5;
goto end_branch_3;;
};
  $__t3 = (($GLOBALS['Control_Applicative_pure'])($Applicative0_2_1))($m_6);
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
}, "Functor0" => function($_dollar___unused_4) use ($functorMaybeT1_3_2) {
  $__num = \func_num_args();
  $__res = $functorMaybeT1_3_2;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_altMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_altmajMaybemajT';

// Control_Monad_Maybe_Trans_plusMaybeT
function majControl_majMonad_majMaybe_majTrans_plusmajMaybemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_plusmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $altMaybeT1_1_0 = ($GLOBALS['Control_Monad_Maybe_Trans_altMaybeT'])($dictMonad_0);
  $__res = (object)["empty" => (($GLOBALS['Control_Applicative_pure'])((($dictMonad_0)->{'Applicative0'})(null)))($GLOBALS['Data_Maybe_Nothing']), "Alt0" => function($_dollar___unused_2) use ($altMaybeT1_1_0) {
  $__num = \func_num_args();
  $__res = $altMaybeT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_plusMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_plusmajMaybemajT';

// Control_Monad_Maybe_Trans_alternativeMaybeT
function majControl_majMonad_majMaybe_majTrans_alternativemajMaybemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_alternativemajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $applicativeMaybeT1_1_0 = ($GLOBALS['Control_Monad_Maybe_Trans_applicativeMaybeT'])($dictMonad_0);
  $plusMaybeT1_2_1 = ($GLOBALS['Control_Monad_Maybe_Trans_plusMaybeT'])($dictMonad_0);
  $__res = (object)["Applicative0" => function($_dollar___unused_3) use ($applicativeMaybeT1_1_0) {
  $__num = \func_num_args();
  $__res = $applicativeMaybeT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Plus1" => function($_dollar___unused_3) use ($plusMaybeT1_2_1) {
  $__num = \func_num_args();
  $__res = $plusMaybeT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_alternativeMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_alternativemajMaybemajT';

// Control_Monad_Maybe_Trans_monadPlusMaybeT
function majControl_majMonad_majMaybe_majTrans_monadmajPlusmajMaybemajT($dictMonad_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majMaybe_majTrans_monadmajPlusmajMaybemajT';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $monadMaybeT1_1_0 = ($GLOBALS['Control_Monad_Maybe_Trans_monadMaybeT'])($dictMonad_0);
  $alternativeMaybeT1_2_1 = ($GLOBALS['Control_Monad_Maybe_Trans_alternativeMaybeT'])($dictMonad_0);
  $__res = (object)["Monad0" => function($_dollar___unused_3) use ($monadMaybeT1_1_0) {
  $__num = \func_num_args();
  $__res = $monadMaybeT1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Alternative1" => function($_dollar___unused_3) use ($alternativeMaybeT1_2_1) {
  $__num = \func_num_args();
  $__res = $alternativeMaybeT1_2_1;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Maybe_Trans_monadPlusMaybeT'] = __NAMESPACE__ . '\\majControl_majMonad_majMaybe_majTrans_monadmajPlusmajMaybemajT';

