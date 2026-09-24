<?php

namespace Control\Monad\Error\Class;

// ALL IMPORTS: Control.Applicative, Control.Bind, Control.Monad.Error.Class, Control.Semigroupoid, Data.Either, Data.Function, Data.Functor, Data.Maybe, Data.Unit, Effect, Effect.Exception, Prelude, Prim
// TO REQUIRE: Control.Applicative, Control.Bind, Control.Monad.Error.Class, Control.Semigroupoid, Data.Either, Data.Function, Data.Functor, Data.Maybe, Data.Unit, Effect, Effect.Exception, Prelude
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Monad.Error.Class/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Either/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Functor/index.php';
require_once __DIR__ . '/../Data.Maybe/index.php';
require_once __DIR__ . '/../Data.Unit/index.php';
require_once __DIR__ . '/../Effect/index.php';
require_once __DIR__ . '/../Effect.Exception/index.php';
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




// Control_Monad_Error_Class_throwError
function majControl_majMonad_majError_majClass_throwmajError($dict_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majError_majClass_throwmajError';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($dict_0)->{'throwError'};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Error_Class_throwError'] = __NAMESPACE__ . '\\majControl_majMonad_majError_majClass_throwmajError';

// Control_Monad_Error_Class_monadThrowMaybe
$GLOBALS['Control_Monad_Error_Class_monadThrowMaybe'] = (object)["throwError" => ($GLOBALS['Data_Function_const'])($GLOBALS['Data_Maybe_Nothing']), "Monad0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Data_Maybe_monadMaybe'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Error_Class_monadThrowEither
$GLOBALS['Control_Monad_Error_Class_monadThrowEither'] = (object)["throwError" => $GLOBALS['Data_Either_Left'], "Monad0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Data_Either_monadEither'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Error_Class_monadThrowEffect
$GLOBALS['Control_Monad_Error_Class_monadThrowEffect'] = (object)["throwError" => $GLOBALS['Effect_Exception_throwException'], "Monad0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Effect_monadEffect'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Error_Class_monadErrorMaybe
$GLOBALS['Control_Monad_Error_Class_monadErrorMaybe'] = (object)["catchError" => function($v_0) {
  $__num = \func_num_args();
  $__res = function($v1_1) use ($v_0) {
  $__num = \func_num_args();
  $__t0 = null;
  if ($v_0 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t0 = ($v1_1)($GLOBALS['Data_Unit_unit']);
goto end_branch_0;;
};
  if ($v_0 instanceof \Data\Maybe\Data_Maybe_Just) {
$__t0 = ($GLOBALS['Data_Maybe_Just'])(($v_0)->{'value0'});
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadThrow0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Control_Monad_Error_Class_monadThrowMaybe'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Error_Class_monadErrorEither
$GLOBALS['Control_Monad_Error_Class_monadErrorEither'] = (object)["catchError" => function($v_0) {
  $__num = \func_num_args();
  $__res = function($v1_1) use ($v_0) {
  $__num = \func_num_args();
  $__t0 = null;
  if ($v_0 instanceof \Data\Either\Data_Either_Left) {
$__t0 = ($v1_1)(($v_0)->{'value0'});
goto end_branch_0;;
};
  if ($v_0 instanceof \Data\Either\Data_Either_Right) {
$__t0 = ($GLOBALS['Data_Either_Right'])(($v_0)->{'value0'});
goto end_branch_0;;
};
  throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
  $__t0 = null;
  end_branch_0:;
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "MonadThrow0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Control_Monad_Error_Class_monadThrowEither'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Error_Class_monadErrorEffect
$GLOBALS['Control_Monad_Error_Class_monadErrorEffect'] = (object)["catchError" => ($GLOBALS['Data_Function_flip'])($GLOBALS['Effect_Exception_catchException']), "MonadThrow0" => function($_dollar___unused_0) {
  $__num = \func_num_args();
  $__res = $GLOBALS['Control_Monad_Error_Class_monadThrowEffect'];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];

// Control_Monad_Error_Class_liftMaybe
function majControl_majMonad_majError_majClass_liftmajMaybe($dictMonadThrow_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majError_majClass_liftmajMaybe';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $pure_1_0 = ($GLOBALS['Control_Applicative_pure'])((((($dictMonadThrow_0)->{'Monad0'})(null))->{'Applicative0'})(null));
  $__res = function($error_2) use ($dictMonadThrow_0, $pure_1_0) {
  $__num = \func_num_args();
  $__res = (($GLOBALS['Data_Maybe_maybe'])((($dictMonadThrow_0)->{'throwError'})($error_2)))($pure_1_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Error_Class_liftMaybe'] = __NAMESPACE__ . '\\majControl_majMonad_majError_majClass_liftmajMaybe';

// Control_Monad_Error_Class_liftEither
function majControl_majMonad_majError_majClass_liftmajEither($dictMonadThrow_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majError_majClass_liftmajEither';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = (($GLOBALS['Data_Either_either'])(($GLOBALS['Control_Monad_Error_Class_throwError'])($dictMonadThrow_0)))(($GLOBALS['Control_Applicative_pure'])((((($dictMonadThrow_0)->{'Monad0'})(null))->{'Applicative0'})(null)));
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Error_Class_liftEither'] = __NAMESPACE__ . '\\majControl_majMonad_majError_majClass_liftmajEither';

// Control_Monad_Error_Class_catchError
function majControl_majMonad_majError_majClass_catchmajError($dict_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majError_majClass_catchmajError';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $__res = ($dict_0)->{'catchError'};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Error_Class_catchError'] = __NAMESPACE__ . '\\majControl_majMonad_majError_majClass_catchmajError';

// Control_Monad_Error_Class_catchJust
function majControl_majMonad_majError_majClass_catchmajJust($dictMonadError_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majError_majClass_catchmajJust';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $MonadThrow0_1_0 = (($dictMonadError_0)->{'MonadThrow0'})(null);
  $__res = function($p_2) use ($MonadThrow0_1_0, $dictMonadError_0) {
  $__num = \func_num_args();
  $__res = function($act_3) use ($MonadThrow0_1_0, $dictMonadError_0, $p_2) {
  $__num = \func_num_args();
  $__res = function($handler_4) use ($MonadThrow0_1_0, $act_3, $dictMonadError_0, $p_2) {
  $__num = \func_num_args();
  $__res = ((($dictMonadError_0)->{'catchError'})($act_3))(function($e_5) use ($MonadThrow0_1_0, $handler_4, $p_2) {
  $__num = \func_num_args();
  $v_6_1 = ($p_2)($e_5);
  $__t2 = null;
  if ($v_6_1 instanceof \Data\Maybe\Data_Maybe_Nothing) {
$__t2 = (($MonadThrow0_1_0)->{'throwError'})($e_5);
goto end_branch_2;;
};
  if ($v_6_1 instanceof \Data\Maybe\Data_Maybe_Just) {
$__t2 = ($handler_4)(($v_6_1)->{'value0'});
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
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Error_Class_catchJust'] = __NAMESPACE__ . '\\majControl_majMonad_majError_majClass_catchmajJust';

// Control_Monad_Error_Class_try
function majControl_majMonad_majError_majClass_try($dictMonadError_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majError_majClass_try';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $Monad0_1_0 = (((($dictMonadError_0)->{'MonadThrow0'})(null))->{'Monad0'})(null);
  $Functor0_2_1 = (((((($Monad0_1_0)->{'Bind1'})(null))->{'Apply0'})(null))->{'Functor0'})(null);
  $pure_3_2 = ($GLOBALS['Control_Applicative_pure'])((($Monad0_1_0)->{'Applicative0'})(null));
  $__res = function($a_4) use ($Functor0_2_1, $dictMonadError_0, $pure_3_2) {
  $__num = \func_num_args();
  $__res = ((($dictMonadError_0)->{'catchError'})(((($GLOBALS['Data_Functor_map'])($Functor0_2_1))($GLOBALS['Data_Either_Right']))($a_4)))(((($GLOBALS['Control_Semigroupoid_compose'])($GLOBALS['Control_Semigroupoid_semigroupoidFn']))($pure_3_2))($GLOBALS['Data_Either_Left']));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return 1 < $__num ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}
$GLOBALS['Control_Monad_Error_Class_try'] = __NAMESPACE__ . '\\majControl_majMonad_majError_majClass_try';

// Control_Monad_Error_Class_withResource
function majControl_majMonad_majError_majClass_withmajResource($dictMonadError_0) {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majControl_majMonad_majError_majClass_withmajResource';
  if ($__num < 1) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 1);
  }
  $MonadThrow0_1_0 = (($dictMonadError_0)->{'MonadThrow0'})(null);
  $Monad0_2_1 = (($MonadThrow0_1_0)->{'Monad0'})(null);
  $Bind1_3_2 = (($Monad0_2_1)->{'Bind1'})(null);
  $try1_4_3 = ($GLOBALS['Control_Monad_Error_Class_try'])($dictMonadError_0);
  $throwError1_5_4 = ($GLOBALS['Control_Monad_Error_Class_throwError'])($MonadThrow0_1_0);
  $pure_6_5 = ($GLOBALS['Control_Applicative_pure'])((($Monad0_2_1)->{'Applicative0'})(null));
  $__res = function($acquire_7) use ($Bind1_3_2, $pure_6_5, $throwError1_5_4, $try1_4_3) {
  $__num = \func_num_args();
  $__res = function($release_8) use ($Bind1_3_2, $acquire_7, $pure_6_5, $throwError1_5_4, $try1_4_3) {
  $__num = \func_num_args();
  $__res = function($kleisli_9) use ($Bind1_3_2, $acquire_7, $pure_6_5, $release_8, $throwError1_5_4, $try1_4_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))($acquire_7))(function($resource_10) use ($Bind1_3_2, $kleisli_9, $pure_6_5, $release_8, $throwError1_5_4, $try1_4_3) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Control_Bind_bind'])($Bind1_3_2))((($GLOBALS['Data_Function_apply'])($try1_4_3))(($kleisli_9)($resource_10))))(function($result_11) use ($Bind1_3_2, $pure_6_5, $release_8, $resource_10, $throwError1_5_4) {
  $__num = \func_num_args();
  $__res = (((($GLOBALS['Control_Bind_discard'])($GLOBALS['Control_Bind_discardUnit']))($Bind1_3_2))(($release_8)($resource_10)))(function($_dollar___unused_12) use ($pure_6_5, $result_11, $throwError1_5_4) {
  $__num = \func_num_args();
  $__res = ((($GLOBALS['Data_Either_either'])($throwError1_5_4))($pure_6_5))($result_11);
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
$GLOBALS['Control_Monad_Error_Class_withResource'] = __NAMESPACE__ . '\\majControl_majMonad_majError_majClass_withmajResource';

