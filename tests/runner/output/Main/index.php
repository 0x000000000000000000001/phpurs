<?php

namespace Main;

// ALL IMPORTS: Control.Bind, Data.Eq, Data.EuclideanRing, Data.Ord, Data.Ring, Data.Semiring, Data.Show, Effect, Effect.Console, Main, Prelude, Prim, Test.Assert
// TO REQUIRE: Control.Bind, Data.Eq, Data.EuclideanRing, Data.Ord, Data.Ring, Data.Semiring, Data.Show, Effect, Effect.Console, Main, Prelude, Test.Assert
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Data.Eq/index.php';
require_once __DIR__ . '/../Data.EuclideanRing/index.php';
require_once __DIR__ . '/../Data.Ord/index.php';
require_once __DIR__ . '/../Data.Ring/index.php';
require_once __DIR__ . '/../Data.Semiring/index.php';
require_once __DIR__ . '/../Data.Show/index.php';
require_once __DIR__ . '/../Effect/index.php';
require_once __DIR__ . '/../Effect.Console/index.php';
require_once __DIR__ . '/../Main/index.php';
require_once __DIR__ . '/../Prelude/index.php';
require_once __DIR__ . '/../Test.Assert/index.php';

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
\PhpursThunks::$thunks['Main_tco4'] = function() { $v = (function() use (&$__fn) {
$f_0_0 = null;
$f_0_0 = (function() use (&$f_0_0) {
  $__fn = function($x_1, $y_2 = null) use (&$f_0_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "f_0_0"), recVars=["f_0_0"];
  while (true) {
if (( ! (is_object((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y_2))(0)) && (((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y_2))(0))->{'tag'} === "GT")))) {
$__t3 = $x_1;
} else {
$__tco_1 = ((($GLOBALS['Data_Semiring_intAdd'] ?? \PhpursThunks::eval('Data_Semiring_intAdd')))($x_1))(2);
$__tco_2 = ((($GLOBALS['Data_Ring_intSub'] ?? \PhpursThunks::eval('Data_Ring_intSub')))($y_2))(1);
$x_1 = $__tco_1;
$y_2 = $__tco_2;
continue ;
$__t3 = null;
};
$__res = $__t3;
goto __end;;
};
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
return ($f_0_0)(0);
})(); return $v; };
\PhpursThunks::$thunks['Main_tco3'] = function() { $v = function($y0_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Main_tco3"), recVars=[];
  $f_1_0 = null;
  $f_1_0 = (function() use (&$f_1_0, $y0_0) {
  $__fn = function($x_2, $y_3 = null) use (&$f_1_0, $y0_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "f_1_0"), recVars=["f_1_0"];
  while (true) {
$g_4_1 = null;
$g_4_1 = (function() use (&$f_1_0, &$g_4_1, $y0_0) {
  $__fn = function($x__prime___5, $y__prime___6 = null) use (&$f_1_0, &$g_4_1, $y0_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "g_4_1"), recVars=["f_1_0","g_4_1"];
  while (true) {
if (( ! (is_object((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y__prime___6))(0)) && (((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y__prime___6))(0))->{'tag'} === "GT")))) {
$__t2 = $x__prime___5;
} else {
if ((is_object((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y__prime___6))(((($GLOBALS['Data_EuclideanRing_intDiv'] ?? \PhpursThunks::eval('Data_EuclideanRing_intDiv')))($y0_0))(2))) && (((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y__prime___6))(((($GLOBALS['Data_EuclideanRing_intDiv'] ?? \PhpursThunks::eval('Data_EuclideanRing_intDiv')))($y0_0))(2)))->{'tag'} === "GT"))) {
$__tco_3 = ((($GLOBALS['Data_Semiring_intAdd'] ?? \PhpursThunks::eval('Data_Semiring_intAdd')))($x__prime___5))(3);
$__tco_4 = ((($GLOBALS['Data_Ring_intSub'] ?? \PhpursThunks::eval('Data_Ring_intSub')))($y__prime___6))(1);
$x__prime___5 = $__tco_3;
$y__prime___6 = $__tco_4;
continue ;
$__t2 = null;
} else {
$__t2 = (($f_1_0)(((($GLOBALS['Data_Semiring_intAdd'] ?? \PhpursThunks::eval('Data_Semiring_intAdd')))($x__prime___5))(2)))($y__prime___6);
};
};
$__res = $__t2;
goto __end;;
};
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
$__res = (($g_4_1)($x_2))(((($GLOBALS['Data_Ring_intSub'] ?? \PhpursThunks::eval('Data_Ring_intSub')))($y_3))(1));
goto __end;;
};
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
  $__res = (($f_1_0)(0))($y0_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Main_tco2'] = function() { $v = (function() use (&$__fn) {
$f_0_0 = null;
$f_0_0 = (function() use (&$f_0_0) {
  $__fn = function($x_1, $y_2 = null) use (&$f_0_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "f_0_0"), recVars=["f_0_0"];
  while (true) {
$__local_var_3_1 = ((($GLOBALS['Data_Semiring_intAdd'] ?? \PhpursThunks::eval('Data_Semiring_intAdd')))($x_1))(2);
$__local_var_4_2 = ((($GLOBALS['Data_Ring_intSub'] ?? \PhpursThunks::eval('Data_Ring_intSub')))($y_2))(1);
if (( ! (is_object((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($__local_var_4_2))(0)) && (((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($__local_var_4_2))(0))->{'tag'} === "GT")))) {
$__t5 = $__local_var_3_1;
} else {
$__tco_3 = $__local_var_3_1;
$__tco_4 = $__local_var_4_2;
$x_1 = $__tco_3;
$y_2 = $__tco_4;
continue ;
$__t5 = null;
};
$__res = $__t5;
goto __end;;
};
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
return ($f_0_0)(0);
})(); return $v; };
\PhpursThunks::$thunks['Main_tco1'] = function() { $v = (function() use (&$__fn) {
$f_0_0 = null;
$f_0_0 = (function() use (&$f_0_0) {
  $__fn = function($x_1, $y_2 = null) use (&$f_0_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "f_0_0"), recVars=["f_0_0"];
  while (true) {
$__local_var_3_1 = ((($GLOBALS['Data_Semiring_intAdd'] ?? \PhpursThunks::eval('Data_Semiring_intAdd')))($x_1))(2);
$__local_var_4_2 = ((($GLOBALS['Data_Ring_intSub'] ?? \PhpursThunks::eval('Data_Ring_intSub')))($y_2))(1);
if (( ! (is_object((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($__local_var_4_2))(0)) && (((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($__local_var_4_2))(0))->{'tag'} === "GT")))) {
$__t5 = $__local_var_3_1;
} else {
$__tco_3 = $__local_var_3_1;
$__tco_4 = $__local_var_4_2;
$x_1 = $__tco_3;
$y_2 = $__tco_4;
continue ;
$__t5 = null;
};
$__res = $__t5;
goto __end;;
};
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
return ($f_0_0)(0);
})(); return $v; };
\PhpursThunks::$thunks['Main_ntco4'] = function() { $v = (function() use (&$__fn) {
$f_0_0 = null;
$f_0_0 = (function() use (&$f_0_0) {
  $__fn = function($x_1, $y_2 = null) use (&$f_0_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "f_0_0"), recVars=["f_0_0"];
  while (true) {
if (( ! (is_object((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y_2))(0)) && (((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y_2))(0))->{'tag'} === "GT")))) {
$__t3 = $x_1;
} else {
$__tco_1 = ((($GLOBALS['Data_Semiring_intAdd'] ?? \PhpursThunks::eval('Data_Semiring_intAdd')))($x_1))(2);
$__tco_2 = ((($GLOBALS['Data_Ring_intSub'] ?? \PhpursThunks::eval('Data_Ring_intSub')))($y_2))(1);
$x_1 = $__tco_1;
$y_2 = $__tco_2;
continue ;
$__t3 = null;
};
$__res = $__t3;
goto __end;;
};
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
return ($f_0_0)(0);
})(); return $v; };
\PhpursThunks::$thunks['Main_ntco3'] = function() { $v = (function() use (&$__fn) {
$f_0_0 = null;
$f_0_0 = (function() use (&$f_0_0) {
  $__fn = function($x_1, $y_2 = null) use (&$f_0_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "f_0_0"), recVars=["f_0_0"];
  while (true) {
$g_3_1 = ($f_0_0)(((($GLOBALS['Data_Semiring_intAdd'] ?? \PhpursThunks::eval('Data_Semiring_intAdd')))($x_1))(2));
if (( ! (is_object((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y_2))(0)) && (((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y_2))(0))->{'tag'} === "GT")))) {
$__t2 = $x_1;
} else {
$__t2 = ($g_3_1)(((($GLOBALS['Data_Ring_intSub'] ?? \PhpursThunks::eval('Data_Ring_intSub')))($y_2))(1));
};
$__res = $__t2;
goto __end;;
};
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
return ($f_0_0)(0);
})(); return $v; };
\PhpursThunks::$thunks['Main_ntco2'] = function() { $v = (function() use (&$__fn) {
$f_0_0 = null;
$f_0_0 = (function() use (&$f_0_0) {
  $__fn = function($x_1, $y_2 = null) use (&$f_0_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "f_0_0"), recVars=["f_0_0"];
  while (true) {
if (( ! (is_object((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y_2))(0)) && (((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($y_2))(0))->{'tag'} === "GT")))) {
$__t3 = $x_1;
} else {
$__tco_1 = ((($GLOBALS['Data_Semiring_intAdd'] ?? \PhpursThunks::eval('Data_Semiring_intAdd')))($x_1))(2);
$__tco_2 = ((($GLOBALS['Data_Ring_intSub'] ?? \PhpursThunks::eval('Data_Ring_intSub')))($y_2))(1);
$x_1 = $__tco_1;
$y_2 = $__tco_2;
continue ;
$__t3 = null;
};
$__res = $__t3;
goto __end;;
};
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
return ($f_0_0)(0);
})(); return $v; };
\PhpursThunks::$thunks['Main_ntco1'] = function() { $v = function($y0_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Main_ntco1"), recVars=[];
  $f_1_0 = null;
  $f_1_0 = function($x_2) use (&$f_1_0, $y0_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "f_1_0"), recVars=["f_1_0"];
  while (true) {
if ((is_object((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($x_2))(((($GLOBALS['Data_Semiring_intMul'] ?? \PhpursThunks::eval('Data_Semiring_intMul')))(10))($y0_0))) && (((((($GLOBALS['Data_Ord_ordInt'] ?? \PhpursThunks::eval('Data_Ord_ordInt')))->{'compare'})($x_2))(((($GLOBALS['Data_Semiring_intMul'] ?? \PhpursThunks::eval('Data_Semiring_intMul')))(10))($y0_0)))->{'tag'} === "GT"))) {
$__t1 = function($v_3) use ($x_2) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["f_1_0"];
  $__res = ((($GLOBALS['Data_Semiring_intAdd'] ?? \PhpursThunks::eval('Data_Semiring_intAdd')))($x_2))($v_3);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
} else {
$__t1 = function($y__prime___3) use (&$f_1_0, $x_2) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["f_1_0"];
  $__res = (($f_1_0)(((($GLOBALS['Data_Semiring_intAdd'] ?? \PhpursThunks::eval('Data_Semiring_intAdd')))($x_2))(10)))(((($GLOBALS['Data_Ring_intSub'] ?? \PhpursThunks::eval('Data_Ring_intSub')))($y__prime___3))(1));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
};
$__res = $__t1;
goto __end;;
};
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  $__res = (($f_1_0)(0))($y0_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Main_main'] = function() { $v = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqInt'] ?? \PhpursThunks::eval('Data_Eq_eqInt'))))(($GLOBALS['Data_Show_showInt'] ?? \PhpursThunks::eval('Data_Show_showInt'))))(""))((object)["expected" => 200000, "actual" => (($GLOBALS['Main_tco1'] ?? \PhpursThunks::eval('Main_tco1')))(100000)])))(function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqInt'] ?? \PhpursThunks::eval('Data_Eq_eqInt'))))(($GLOBALS['Data_Show_showInt'] ?? \PhpursThunks::eval('Data_Show_showInt'))))(""))((object)["expected" => 200000, "actual" => (($GLOBALS['Main_tco2'] ?? \PhpursThunks::eval('Main_tco2')))(100000)])))(function($dollar__unused_1) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqInt'] ?? \PhpursThunks::eval('Data_Eq_eqInt'))))(($GLOBALS['Data_Show_showInt'] ?? \PhpursThunks::eval('Data_Show_showInt'))))(""))((object)["expected" => 249997, "actual" => (($GLOBALS['Main_tco3'] ?? \PhpursThunks::eval('Main_tco3')))(100000)])))(function($dollar__unused_2) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqInt'] ?? \PhpursThunks::eval('Data_Eq_eqInt'))))(($GLOBALS['Data_Show_showInt'] ?? \PhpursThunks::eval('Data_Show_showInt'))))(""))((object)["expected" => 200000, "actual" => (($GLOBALS['Main_tco4'] ?? \PhpursThunks::eval('Main_tco4')))(100000)])))(function($dollar__unused_3) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqInt'] ?? \PhpursThunks::eval('Data_Eq_eqInt'))))(($GLOBALS['Data_Show_showInt'] ?? \PhpursThunks::eval('Data_Show_showInt'))))(""))((object)["expected" => 1009, "actual" => (($GLOBALS['Main_ntco1'] ?? \PhpursThunks::eval('Main_ntco1')))(100)])))(function($dollar__unused_4) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((($GLOBALS['Test_Assert_checkThrows'] ?? \PhpursThunks::eval('Test_Assert_checkThrows')))(function($v_5) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Main_ntco1'] ?? \PhpursThunks::eval('Main_ntco1')))(100000);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
})))((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("Assertion failed: An error should have been thrown"))))(function($dollar__unused_5) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqInt'] ?? \PhpursThunks::eval('Data_Eq_eqInt'))))(($GLOBALS['Data_Show_showInt'] ?? \PhpursThunks::eval('Data_Show_showInt'))))(""))((object)["expected" => 200, "actual" => (($GLOBALS['Main_ntco2'] ?? \PhpursThunks::eval('Main_ntco2')))(100)])))(function($dollar__unused_6) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((($GLOBALS['Test_Assert_checkThrows'] ?? \PhpursThunks::eval('Test_Assert_checkThrows')))(function($v_7) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Main_ntco2'] ?? \PhpursThunks::eval('Main_ntco2')))(100000);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
})))((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("Assertion failed: An error should have been thrown"))))(function($dollar__unused_7) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqInt'] ?? \PhpursThunks::eval('Data_Eq_eqInt'))))(($GLOBALS['Data_Show_showInt'] ?? \PhpursThunks::eval('Data_Show_showInt'))))(""))((object)["expected" => 200, "actual" => (($GLOBALS['Main_ntco3'] ?? \PhpursThunks::eval('Main_ntco3')))(100)])))(function($dollar__unused_8) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((($GLOBALS['Test_Assert_checkThrows'] ?? \PhpursThunks::eval('Test_Assert_checkThrows')))(function($v_9) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Main_ntco3'] ?? \PhpursThunks::eval('Main_ntco3')))(100000);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
})))((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("Assertion failed: An error should have been thrown"))))(function($dollar__unused_9) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((((($GLOBALS['Test_Assert_assertEqual__prime__'] ?? \PhpursThunks::eval('Test_Assert_assertEqual__prime__')))(($GLOBALS['Data_Eq_eqInt'] ?? \PhpursThunks::eval('Data_Eq_eqInt'))))(($GLOBALS['Data_Show_showInt'] ?? \PhpursThunks::eval('Data_Show_showInt'))))(""))((object)["expected" => 200, "actual" => (($GLOBALS['Main_ntco4'] ?? \PhpursThunks::eval('Main_ntco4')))(100)])))(function($dollar__unused_10) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))(((($GLOBALS['Effect_bindE'] ?? \PhpursThunks::eval('Effect_bindE')))((($GLOBALS['Test_Assert_checkThrows'] ?? \PhpursThunks::eval('Test_Assert_checkThrows')))(function($v_11) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Main_ntco4'] ?? \PhpursThunks::eval('Main_ntco4')))(100000);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
})))((($GLOBALS['Test_Assert_assertImpl'] ?? \PhpursThunks::eval('Test_Assert_assertImpl')))("Assertion failed: An error should have been thrown"))))(function($dollar__unused_11) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Effect_Console_log'] ?? \PhpursThunks::eval('Effect_Console_log')))("Done");
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
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}); return $v; };
$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };











