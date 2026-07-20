<?php

namespace Foreign\Object;

// ALL IMPORTS: Control.Applicative, Control.Apply, Control.Bind, Control.Category, Control.Monad.ST, Control.Monad.ST.Internal, Control.Semigroupoid, Data.Array, Data.Eq, Data.Foldable, Data.FoldableWithIndex, Data.Function, Data.Function.Uncurried, Data.Functor, Data.FunctorWithIndex, Data.HeytingAlgebra, Data.Maybe, Data.Monoid, Data.Ord, Data.Semigroup, Data.Show, Data.Traversable, Data.TraversableWithIndex, Data.Tuple, Data.Unfoldable, Foreign.Object, Foreign.Object.ST, Prelude, Prim, Type.Row.Homogeneous, Unsafe.Coerce
// TO REQUIRE: Control.Applicative, Control.Apply, Control.Bind, Control.Category, Control.Monad.ST, Control.Monad.ST.Internal, Control.Semigroupoid, Data.Array, Data.Eq, Data.Foldable, Data.FoldableWithIndex, Data.Function, Data.Function.Uncurried, Data.Functor, Data.FunctorWithIndex, Data.HeytingAlgebra, Data.Maybe, Data.Monoid, Data.Ord, Data.Semigroup, Data.Show, Data.Traversable, Data.TraversableWithIndex, Data.Tuple, Data.Unfoldable, Foreign.Object, Foreign.Object.ST, Prelude, Type.Row.Homogeneous, Unsafe.Coerce
require_once __DIR__ . '/../Control.Applicative/index.php';
require_once __DIR__ . '/../Control.Apply/index.php';
require_once __DIR__ . '/../Control.Bind/index.php';
require_once __DIR__ . '/../Control.Category/index.php';
require_once __DIR__ . '/../Control.Monad.ST/index.php';
require_once __DIR__ . '/../Control.Monad.ST.Internal/index.php';
require_once __DIR__ . '/../Control.Semigroupoid/index.php';
require_once __DIR__ . '/../Data.Array/index.php';
require_once __DIR__ . '/../Data.Eq/index.php';
require_once __DIR__ . '/../Data.Foldable/index.php';
require_once __DIR__ . '/../Data.FoldableWithIndex/index.php';
require_once __DIR__ . '/../Data.Function/index.php';
require_once __DIR__ . '/../Data.Function.Uncurried/index.php';
require_once __DIR__ . '/../Data.Functor/index.php';
require_once __DIR__ . '/../Data.FunctorWithIndex/index.php';
require_once __DIR__ . '/../Data.HeytingAlgebra/index.php';
require_once __DIR__ . '/../Data.Maybe/index.php';
require_once __DIR__ . '/../Data.Monoid/index.php';
require_once __DIR__ . '/../Data.Ord/index.php';
require_once __DIR__ . '/../Data.Semigroup/index.php';
require_once __DIR__ . '/../Data.Show/index.php';
require_once __DIR__ . '/../Data.Traversable/index.php';
require_once __DIR__ . '/../Data.TraversableWithIndex/index.php';
require_once __DIR__ . '/../Data.Tuple/index.php';
require_once __DIR__ . '/../Data.Unfoldable/index.php';
require_once __DIR__ . '/../Foreign.Object/index.php';
require_once __DIR__ . '/../Foreign.Object.ST/index.php';
require_once __DIR__ . '/../Prelude/index.php';
require_once __DIR__ . '/../Type.Row.Homogeneous/index.php';
require_once __DIR__ . '/../Unsafe.Coerce/index.php';

if (!class_exists(__NAMESPACE__ . '\\Phpurs_Data0')) {
  class Phpurs_Data0 { public $tag; public function __construct($t) { $this->tag = $t; } }
  class Phpurs_Data1 { public $tag; public $value0; public function __construct($t, $value0) { $this->tag = $t; $this->value0 = $value0; } }
  class Phpurs_Data2 { public $tag; public $value0, $value1; public function __construct($t, $value0, $value1) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; } }
  class Phpurs_Data3 { public $tag; public $value0, $value1, $value2; public function __construct($t, $value0, $value1, $value2) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; } }
  class Phpurs_Data4 { public $tag; public $value0, $value1, $value2, $value3; public function __construct($t, $value0, $value1, $value2, $value3) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; } }
  class Phpurs_Data5 { public $tag; public $value0, $value1, $value2, $value3, $value4; public function __construct($t, $value0, $value1, $value2, $value3, $value4) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; } }
  class Phpurs_Data6 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; } }
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
\PhpursThunks::$thunks['Foreign_Object_forWithIndex_'] = function() { $v = (($GLOBALS['Data_FoldableWithIndex_forWithIndex_'] ?? \PhpursThunks::eval('Data_FoldableWithIndex_forWithIndex_')))(($GLOBALS['Control_Monad_ST_Internal_applicativeST'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_applicativeST'))); return $v; };
\PhpursThunks::$thunks['Foreign_Object_for_'] = function() { $v = (($GLOBALS['Data_Foldable_for_'] ?? \PhpursThunks::eval('Data_Foldable_for_')))(($GLOBALS['Control_Monad_ST_Internal_applicativeST'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_applicativeST'))); return $v; };
\PhpursThunks::$thunks['Foreign_Object_void'] = function() { $v = (($GLOBALS['Control_Monad_ST_Internal_map_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_map_')))(function($v_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ($GLOBALS['Data_Unit_unit'] ?? \PhpursThunks::eval('Data_Unit_unit'));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}); return $v; };
\PhpursThunks::$thunks['Foreign_Object_ordTuple'] = function() { $v = (($GLOBALS['Data_Tuple_ordTuple'] ?? \PhpursThunks::eval('Data_Tuple_ordTuple')))(($GLOBALS['Data_Ord_ordString'] ?? \PhpursThunks::eval('Data_Ord_ordString'))); return $v; };
\PhpursThunks::$thunks['Foreign_Object_values'] = function() { $v = (($GLOBALS['Foreign_Object_toArrayWithKey'] ?? \PhpursThunks::eval('Foreign_Object_toArrayWithKey')))((function() {
  $__fn = function($v_0, $v1_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = $v1_1;
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()); return $v; };
\PhpursThunks::$thunks['Foreign_Object_toUnfoldable'] = function() { $v = function($dictUnfoldable_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_toUnfoldable"), recVars=[];
  $__res = ((($GLOBALS['Control_Semigroupoid_composeImpl'] ?? \PhpursThunks::eval('Control_Semigroupoid_composeImpl')))((($GLOBALS['Data_Array_toUnfoldable'] ?? \PhpursThunks::eval('Data_Array_toUnfoldable')))($dictUnfoldable_0)))((($GLOBALS['Foreign_Object_toArrayWithKey'] ?? \PhpursThunks::eval('Foreign_Object_toArrayWithKey')))(($GLOBALS['Data_Tuple_Tuple'] ?? \PhpursThunks::eval('Data_Tuple_Tuple'))));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_toAscUnfoldable'] = function() { $v = function($dictUnfoldable_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_toAscUnfoldable"), recVars=[];
  $__res = ((($GLOBALS['Control_Semigroupoid_composeImpl'] ?? \PhpursThunks::eval('Control_Semigroupoid_composeImpl')))((($GLOBALS['Data_Array_toUnfoldable'] ?? \PhpursThunks::eval('Data_Array_toUnfoldable')))($dictUnfoldable_0)))(((($GLOBALS['Control_Semigroupoid_composeImpl'] ?? \PhpursThunks::eval('Control_Semigroupoid_composeImpl')))(((($GLOBALS['Data_Array_sortWith'] ?? \PhpursThunks::eval('Data_Array_sortWith')))(($GLOBALS['Data_Ord_ordString'] ?? \PhpursThunks::eval('Data_Ord_ordString'))))(($GLOBALS['Data_Tuple_fst'] ?? \PhpursThunks::eval('Data_Tuple_fst')))))((($GLOBALS['Foreign_Object_toArrayWithKey'] ?? \PhpursThunks::eval('Foreign_Object_toArrayWithKey')))(($GLOBALS['Data_Tuple_Tuple'] ?? \PhpursThunks::eval('Data_Tuple_Tuple')))));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_toAscArray'] = function() { $v = (($GLOBALS['Foreign_Object_toAscUnfoldable'] ?? \PhpursThunks::eval('Foreign_Object_toAscUnfoldable')))(($GLOBALS['Data_Unfoldable_unfoldableArray'] ?? \PhpursThunks::eval('Data_Unfoldable_unfoldableArray'))); return $v; };
\PhpursThunks::$thunks['Foreign_Object_toArray'] = function() { $v = (($GLOBALS['Foreign_Object_toArrayWithKey'] ?? \PhpursThunks::eval('Foreign_Object_toArrayWithKey')))(($GLOBALS['Data_Tuple_Tuple'] ?? \PhpursThunks::eval('Data_Tuple_Tuple'))); return $v; };
\PhpursThunks::$thunks['Foreign_Object_thawST'] = function() { $v = ($GLOBALS['Foreign_Object__copyST'] ?? \PhpursThunks::eval('Foreign_Object__copyST')); return $v; };
\PhpursThunks::$thunks['Foreign_Object_singleton'] = function() { $v = (function() {
  $__fn = function($k_0, $v_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_singleton"), recVars=[];
  $__res = (($GLOBALS['Foreign_Object_runST'] ?? \PhpursThunks::eval('Foreign_Object_runST')))(((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))(($GLOBALS['Foreign_Object_ST_new'] ?? \PhpursThunks::eval('Foreign_Object_ST_new'))))(((($GLOBALS['Foreign_Object_ST_poke'] ?? \PhpursThunks::eval('Foreign_Object_ST_poke')))($k_0))($v_1)));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Foreign_Object_showObject'] = function() { $v = function($dictShow_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_showObject"), recVars=[];
  $show_1_0 = (($GLOBALS['Data_Show_showArrayImpl'] ?? \PhpursThunks::eval('Data_Show_showArrayImpl')))((((($GLOBALS['Data_Tuple_showTuple'] ?? \PhpursThunks::eval('Data_Tuple_showTuple')))(($GLOBALS['Data_Show_showString'] ?? \PhpursThunks::eval('Data_Show_showString'))))($dictShow_0))->{'show'});
  $__res = (object)["show" => function($m_2) use ($show_1_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Data_Semigroup_concatString'] ?? \PhpursThunks::eval('Data_Semigroup_concatString')))("(fromFoldable "))(((($GLOBALS['Data_Semigroup_concatString'] ?? \PhpursThunks::eval('Data_Semigroup_concatString')))(($show_1_0)((($GLOBALS['Foreign_Object_toArray'] ?? \PhpursThunks::eval('Foreign_Object_toArray')))($m_2))))(")"));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_mutate'] = function() { $v = (function() {
  $__fn = function($f_0, $m_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_mutate"), recVars=[];
  $__res = (($GLOBALS['Foreign_Object_runST'] ?? \PhpursThunks::eval('Foreign_Object_runST')))(((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))((($GLOBALS['Foreign_Object__copyST'] ?? \PhpursThunks::eval('Foreign_Object__copyST')))($m_1)))(function($s_2) use ($f_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))(($f_0)($s_2)))(function($dollar__unused_3) use ($s_2) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Control_Monad_ST_Internal_pure_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_pure_')))($s_2);
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
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Foreign_Object_member'] = function() { $v = (((($GLOBALS['Data_Function_Uncurried_runFn4'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn4')))(($GLOBALS['Foreign_Object__lookup'] ?? \PhpursThunks::eval('Foreign_Object__lookup'))))(false))(function($v_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = true;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}); return $v; };
\PhpursThunks::$thunks['Foreign_Object_mapWithKey'] = function() { $v = (function() {
  $__fn = function($f_0, $m_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_mapWithKey"), recVars=[];
  $__res = (((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))(($GLOBALS['Foreign_Object__mapWithKey'] ?? \PhpursThunks::eval('Foreign_Object__mapWithKey'))))($m_1))($f_0);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Foreign_Object_lookup'] = function() { $v = (((($GLOBALS['Data_Function_Uncurried_runFn4'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn4')))(($GLOBALS['Foreign_Object__lookup'] ?? \PhpursThunks::eval('Foreign_Object__lookup'))))(new Phpurs_Data0("Nothing")))(($GLOBALS['Data_Maybe_Just'] ?? \PhpursThunks::eval('Data_Maybe_Just'))); return $v; };
\PhpursThunks::$thunks['Foreign_Object_isSubmap'] = function() { $v = (function() {
  $__fn = function($dictEq_0, $m1_1 = null, $m2_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_isSubmap"), recVars=[];
  $__res = ((($GLOBALS['Foreign_Object_all'] ?? \PhpursThunks::eval('Foreign_Object_all')))((function() use ($dictEq_0, $m2_2) {
  $__fn = function($k_3, $v_4 = null) use ($dictEq_0, $m2_2, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((((($GLOBALS['Data_Function_Uncurried_runFn4'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn4')))(($GLOBALS['Foreign_Object__lookup'] ?? \PhpursThunks::eval('Foreign_Object__lookup'))))(false))((($dictEq_0)->{'eq'})($v_4)))($k_3))($m2_2);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()))($m1_1);
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Foreign_Object_isEmpty'] = function() { $v = (($GLOBALS['Foreign_Object_all'] ?? \PhpursThunks::eval('Foreign_Object_all')))((function() {
  $__fn = function($v_0, $v1_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = false;
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()); return $v; };
\PhpursThunks::$thunks['Foreign_Object_insert'] = function() { $v = (function() {
  $__fn = function($k_0, $v_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_insert"), recVars=[];
  $__res = (($GLOBALS['Foreign_Object_mutate'] ?? \PhpursThunks::eval('Foreign_Object_mutate')))(((($GLOBALS['Foreign_Object_ST_poke'] ?? \PhpursThunks::eval('Foreign_Object_ST_poke')))($k_0))($v_1));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Foreign_Object_functorObject'] = function() { $v = (object)["map" => (function() {
  $__fn = function($f_0, $m_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))(($GLOBALS['Foreign_Object__fmapObject'] ?? \PhpursThunks::eval('Foreign_Object__fmapObject'))))($m_1))($f_0);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()]; return $v; };
\PhpursThunks::$thunks['Foreign_Object_functorWithIndexObject'] = function() { $v = (object)["mapWithIndex" => ($GLOBALS['Foreign_Object_mapWithKey'] ?? \PhpursThunks::eval('Foreign_Object_mapWithKey')), "Functor0" => function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ($GLOBALS['Foreign_Object_functorObject'] ?? \PhpursThunks::eval('Foreign_Object_functorObject'));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}]; return $v; };
\PhpursThunks::$thunks['Foreign_Object_fromHomogeneous'] = function() { $v = function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_fromHomogeneous"), recVars=[];
  $__res = ($GLOBALS['Unsafe_Coerce_unsafeCoerce'] ?? \PhpursThunks::eval('Unsafe_Coerce_unsafeCoerce'));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_fromFoldableWithIndex'] = function() { $v = function($dictFoldableWithIndex_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_fromFoldableWithIndex"), recVars=[];
  $forWithIndex_1_1_0 = (($GLOBALS['Foreign_Object_forWithIndex_'] ?? \PhpursThunks::eval('Foreign_Object_forWithIndex_')))($dictFoldableWithIndex_0);
  $__res = function($l_2) use ($forWithIndex_1_1_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Foreign_Object_runST'] ?? \PhpursThunks::eval('Foreign_Object_runST')))(((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))(($GLOBALS['Foreign_Object_ST_new'] ?? \PhpursThunks::eval('Foreign_Object_ST_new'))))(function($s_3) use ($forWithIndex_1_1_0, $l_2) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))((($forWithIndex_1_1_0)($l_2))((function() use ($s_3) {
  $__fn = function($k_4, $v_5 = null) use ($s_3, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Foreign_Object_ST_poke'] ?? \PhpursThunks::eval('Foreign_Object_ST_poke')))($k_4))($v_5))($s_3);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})())))(function($dollar__unused_4) use ($s_3) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Control_Monad_ST_Internal_pure_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_pure_')))($s_3);
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
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_fromFoldableWith'] = function() { $v = function($dictFoldable_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_fromFoldableWith"), recVars=[];
  $for_1_1_0 = (($GLOBALS['Foreign_Object_for_'] ?? \PhpursThunks::eval('Foreign_Object_for_')))($dictFoldable_0);
  $__res = (function() use ($for_1_1_0) {
  $__fn = function($f_2, $l_3 = null) use ($for_1_1_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Foreign_Object_runST'] ?? \PhpursThunks::eval('Foreign_Object_runST')))(((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))(($GLOBALS['Foreign_Object_ST_new'] ?? \PhpursThunks::eval('Foreign_Object_ST_new'))))(function($s_4) use ($f_2, $for_1_1_0, $l_3) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))((($for_1_1_0)($l_3))(function($v_5) use ($f_2, $s_4) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__local_var_6_1 = ($v_5)->{'value0'};
  $__res = ((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))((((((($GLOBALS['Data_Function_Uncurried_runFn4'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn4')))(($GLOBALS['Foreign_Object__lookupST'] ?? \PhpursThunks::eval('Foreign_Object__lookupST'))))(($v_5)->{'value1'}))(($f_2)(($v_5)->{'value1'})))($__local_var_6_1))($s_4)))(function($v__prime___7) use ($__local_var_6_1, $s_4) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Foreign_Object_ST_poke'] ?? \PhpursThunks::eval('Foreign_Object_ST_poke')))($__local_var_6_1))($v__prime___7))($s_4);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
})))(function($dollar__unused_5) use ($s_4) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Control_Monad_ST_Internal_pure_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_pure_')))($s_4);
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
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_fromFoldable'] = function() { $v = function($dictFoldable_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_fromFoldable"), recVars=[];
  $fromFoldable1_1_0 = ((($GLOBALS['Data_Function_Uncurried_runFn2'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn2')))(($GLOBALS['Data_Array_fromFoldableImpl'] ?? \PhpursThunks::eval('Data_Array_fromFoldableImpl'))))(($dictFoldable_0)->{'foldr'});
  $__res = function($l_2) use ($fromFoldable1_1_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Foreign_Object_runST'] ?? \PhpursThunks::eval('Foreign_Object_runST')))(((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))(($GLOBALS['Foreign_Object_ST_new'] ?? \PhpursThunks::eval('Foreign_Object_ST_new'))))(function($s_3) use ($fromFoldable1_1_0, $l_2) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))(((($GLOBALS['Control_Monad_ST_Internal_foreach'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_foreach')))(($fromFoldable1_1_0)($l_2)))(function($v_4) use ($s_3) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Foreign_Object_void'] ?? \PhpursThunks::eval('Foreign_Object_void')))((((($GLOBALS['Foreign_Object_ST_poke'] ?? \PhpursThunks::eval('Foreign_Object_ST_poke')))(($v_4)->{'value0'}))(($v_4)->{'value1'}))($s_3));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
})))(function($dollar__unused_4) use ($s_3) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Control_Monad_ST_Internal_pure_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_pure_')))($s_3);
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
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_freezeST'] = function() { $v = ($GLOBALS['Foreign_Object__copyST'] ?? \PhpursThunks::eval('Foreign_Object__copyST')); return $v; };
\PhpursThunks::$thunks['Foreign_Object_foldMaybe'] = function() { $v = (function() {
  $__fn = function($f_0, $z_1 = null, $m_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_foldMaybe"), recVars=[];
  $__res = (((((($GLOBALS['Data_Function_Uncurried_runFn4'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn4')))(($GLOBALS['Foreign_Object__foldSCObject'] ?? \PhpursThunks::eval('Foreign_Object__foldSCObject'))))($m_2))($z_1))($f_0))(($GLOBALS['Data_Maybe_fromMaybe'] ?? \PhpursThunks::eval('Data_Maybe_fromMaybe')));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Foreign_Object_foldM'] = function() { $v = function($dictMonad_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_foldM"), recVars=[];
  $bind1_1_0 = ((($dictMonad_0)->{'Bind1'})(($GLOBALS['Prim_undefined'] ?? \PhpursThunks::eval('Prim_undefined'))))->{'bind'};
  $__res = (function() use ($bind1_1_0, $dictMonad_0) {
  $__fn = function($f_2, $z_3 = null) use ($bind1_1_0, $dictMonad_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Foreign_Object__foldM'] ?? \PhpursThunks::eval('Foreign_Object__foldM')))($bind1_1_0))($f_2))((((($dictMonad_0)->{'Applicative0'})(($GLOBALS['Prim_undefined'] ?? \PhpursThunks::eval('Prim_undefined'))))->{'pure'})($z_3));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_foldM1'] = function() { $v = (($GLOBALS['Foreign_Object_foldM'] ?? \PhpursThunks::eval('Foreign_Object_foldM')))(($GLOBALS['Control_Monad_ST_Internal_monadST'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_monadST'))); return $v; };
\PhpursThunks::$thunks['Foreign_Object_union'] = function() { $v = function($m_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_union"), recVars=[];
  $__res = (($GLOBALS['Foreign_Object_mutate'] ?? \PhpursThunks::eval('Foreign_Object_mutate')))(function($s_1) use ($m_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Foreign_Object_foldM1'] ?? \PhpursThunks::eval('Foreign_Object_foldM1')))((function() {
  $__fn = function($s__prime___2, $k_3 = null, $v_4 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Foreign_Object_ST_poke'] ?? \PhpursThunks::eval('Foreign_Object_ST_poke')))($k_3))($v_4))($s__prime___2);
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()))($s_1))($m_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_unions'] = function() { $v = function($dictFoldable_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_unions"), recVars=[];
  $__res = ((($dictFoldable_0)->{'foldl'})(($GLOBALS['Foreign_Object_union'] ?? \PhpursThunks::eval('Foreign_Object_union'))))(($GLOBALS['Foreign_Object_empty'] ?? \PhpursThunks::eval('Foreign_Object_empty')));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_unionWith'] = function() { $v = (function() {
  $__fn = function($f_0, $m1_1 = null, $m2_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_unionWith"), recVars=[];
  $__res = ((($GLOBALS['Foreign_Object_mutate'] ?? \PhpursThunks::eval('Foreign_Object_mutate')))(function($s1_3) use ($f_0, $m1_1, $m2_2) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Foreign_Object_foldM1'] ?? \PhpursThunks::eval('Foreign_Object_foldM1')))((function() use ($f_0, $m2_2) {
  $__fn = function($s2_4, $k_5 = null, $v1_6 = null) use ($f_0, $m2_2, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Foreign_Object_ST_poke'] ?? \PhpursThunks::eval('Foreign_Object_ST_poke')))($k_5))((((((($GLOBALS['Data_Function_Uncurried_runFn4'] ?? \PhpursThunks::eval('Data_Function_Uncurried_runFn4')))(($GLOBALS['Foreign_Object__lookup'] ?? \PhpursThunks::eval('Foreign_Object__lookup'))))($v1_6))(function($v2_7) use ($f_0, $v1_6) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($f_0)($v1_6))($v2_7);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($k_5))($m2_2)))($s2_4);
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()))($s1_3))($m1_1);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($m2_2);
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Foreign_Object_semigroupObject'] = function() { $v = function($dictSemigroup_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_semigroupObject"), recVars=[];
  $__res = (object)["append" => (($GLOBALS['Foreign_Object_unionWith'] ?? \PhpursThunks::eval('Foreign_Object_unionWith')))(($dictSemigroup_0)->{'append'})];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_monoidObject'] = function() { $v = function($dictSemigroup_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_monoidObject"), recVars=[];
  $__res = (object)["mempty" => ($GLOBALS['Foreign_Object_empty'] ?? \PhpursThunks::eval('Foreign_Object_empty')), "Semigroup0" => function($dollar__unused_1) use ($dictSemigroup_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (object)["append" => (($GLOBALS['Foreign_Object_unionWith'] ?? \PhpursThunks::eval('Foreign_Object_unionWith')))(($dictSemigroup_0)->{'append'})];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_fold'] = function() { $v = (($GLOBALS['Foreign_Object__foldM'] ?? \PhpursThunks::eval('Foreign_Object__foldM')))(($GLOBALS['Data_Function_applyFlipped'] ?? \PhpursThunks::eval('Data_Function_applyFlipped'))); return $v; };
\PhpursThunks::$thunks['Foreign_Object_foldMap'] = function() { $v = function($dictMonoid_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_foldMap"), recVars=[];
  $mempty_1_0 = ($dictMonoid_0)->{'mempty'};
  $__res = function($f_2) use ($dictMonoid_0, $mempty_1_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Foreign_Object_fold'] ?? \PhpursThunks::eval('Foreign_Object_fold')))((function() use ($dictMonoid_0, $f_2) {
  $__fn = function($acc_3, $k_4 = null, $v_5 = null) use ($dictMonoid_0, $f_2, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((((($dictMonoid_0)->{'Semigroup0'})(($GLOBALS['Prim_undefined'] ?? \PhpursThunks::eval('Prim_undefined'))))->{'append'})($acc_3))((($f_2)($k_4))($v_5));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()))($mempty_1_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_foldableObject'] = function() { $v = (object)["foldl" => function($f_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Foreign_Object_fold'] ?? \PhpursThunks::eval('Foreign_Object_fold')))((function() use ($f_0) {
  $__fn = function($z_1, $v_2 = null) use ($f_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ($f_0)($z_1);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})());
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "foldr" => (function() {
  $__fn = function($f_0, $z_1 = null, $m_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Data_Foldable_foldrArray'] ?? \PhpursThunks::eval('Data_Foldable_foldrArray')))($f_0))($z_1))((($GLOBALS['Foreign_Object_values'] ?? \PhpursThunks::eval('Foreign_Object_values')))($m_2));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(), "foldMap" => function($dictMonoid_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $foldMap1_1_0 = (($GLOBALS['Foreign_Object_foldMap'] ?? \PhpursThunks::eval('Foreign_Object_foldMap')))($dictMonoid_0);
  $__res = function($f_2) use ($foldMap1_1_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ($foldMap1_1_0)(function($v_3) use ($f_2) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = $f_2;
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
}]; return $v; };
\PhpursThunks::$thunks['Foreign_Object_foldableWithIndexObject'] = function() { $v = (object)["foldlWithIndex" => function($f_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Foreign_Object_fold'] ?? \PhpursThunks::eval('Foreign_Object_fold')))((function() use ($f_0) {
  $__fn = function($b_1, $a_2 = null) use ($f_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($f_0)($a_2))($b_1);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})());
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "foldrWithIndex" => (function() {
  $__fn = function($f_0, $z_1 = null, $m_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Data_Foldable_foldrArray'] ?? \PhpursThunks::eval('Data_Foldable_foldrArray')))(function($v_3) use ($f_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($f_0)(($v_3)->{'value0'}))(($v_3)->{'value1'});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($z_1))(((($GLOBALS['Foreign_Object_toArrayWithKey'] ?? \PhpursThunks::eval('Foreign_Object_toArrayWithKey')))(($GLOBALS['Data_Tuple_Tuple'] ?? \PhpursThunks::eval('Data_Tuple_Tuple'))))($m_2));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(), "foldMapWithIndex" => function($dictMonoid_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (($GLOBALS['Foreign_Object_foldMap'] ?? \PhpursThunks::eval('Foreign_Object_foldMap')))($dictMonoid_0);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Foldable0" => function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ($GLOBALS['Foreign_Object_foldableObject'] ?? \PhpursThunks::eval('Foreign_Object_foldableObject'));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}]; return $v; };
\PhpursThunks::$thunks['Foreign_Object_traversableWithIndexObject'] = function() { $v = (object)["traverseWithIndex" => function($dictApplicative_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $Apply0_1_0 = (($dictApplicative_0)->{'Apply0'})(($GLOBALS['Prim_undefined'] ?? \PhpursThunks::eval('Prim_undefined')));
  $__res = (function() use ($Apply0_1_0, $dictApplicative_0) {
  $__fn = function($f_2, $ms_3 = null) use ($Apply0_1_0, $dictApplicative_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $__res = (((($GLOBALS['Foreign_Object_fold'] ?? \PhpursThunks::eval('Foreign_Object_fold')))((function() use ($Apply0_1_0, $f_2) {
  $__fn = function($acc_4, $k_5 = null, $v_6 = null) use ($Apply0_1_0, $f_2, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $__res = ((($Apply0_1_0)->{'apply'})(((((($Apply0_1_0)->{'Functor0'})(($GLOBALS['Prim_undefined'] ?? \PhpursThunks::eval('Prim_undefined'))))->{'map'})((function() use ($k_5) {
  $__fn = function($b_7, $a_8 = null) use ($k_5, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $__res = ((($GLOBALS['Foreign_Object_mutate'] ?? \PhpursThunks::eval('Foreign_Object_mutate')))(((($GLOBALS['Foreign_Object_ST_poke'] ?? \PhpursThunks::eval('Foreign_Object_ST_poke')))($k_5))($a_8)))($b_7);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()))($acc_4)))((($f_2)($k_5))($v_6));
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()))((($dictApplicative_0)->{'pure'})(($GLOBALS['Foreign_Object_empty'] ?? \PhpursThunks::eval('Foreign_Object_empty')))))($ms_3);
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})();
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "FunctorWithIndex0" => function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $__res = ($GLOBALS['Foreign_Object_functorWithIndexObject'] ?? \PhpursThunks::eval('Foreign_Object_functorWithIndexObject'));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "FoldableWithIndex1" => function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $__res = ($GLOBALS['Foreign_Object_foldableWithIndexObject'] ?? \PhpursThunks::eval('Foreign_Object_foldableWithIndexObject'));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Traversable2" => function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $__res = ($GLOBALS['Foreign_Object_traversableObject'] ?? \PhpursThunks::eval('Foreign_Object_traversableObject'));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}]; return $v; };
\PhpursThunks::$thunks['Foreign_Object_traversableObject'] = function() { $v = (object)["traverse" => function($dictApplicative_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $__res = ((($GLOBALS['Control_Semigroupoid_composeImpl'] ?? \PhpursThunks::eval('Control_Semigroupoid_composeImpl')))(((($GLOBALS['Foreign_Object_traversableWithIndexObject'] ?? \PhpursThunks::eval('Foreign_Object_traversableWithIndexObject')))->{'traverseWithIndex'})($dictApplicative_0)))(($GLOBALS['Data_Function_const'] ?? \PhpursThunks::eval('Data_Function_const')));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "sequence" => function($dictApplicative_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $__res = (((($GLOBALS['Foreign_Object_traversableObject'] ?? \PhpursThunks::eval('Foreign_Object_traversableObject')))->{'traverse'})($dictApplicative_0))((($GLOBALS['Control_Category_categoryFn'] ?? \PhpursThunks::eval('Control_Category_categoryFn')))->{'identity'});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Functor0" => function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $__res = ($GLOBALS['Foreign_Object_functorObject'] ?? \PhpursThunks::eval('Foreign_Object_functorObject'));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}, "Foldable1" => function($dollar__unused_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=["Foreign_Object_traversableWithIndexObject","Foreign_Object_traversableObject"];
  $__res = ($GLOBALS['Foreign_Object_foldableObject'] ?? \PhpursThunks::eval('Foreign_Object_foldableObject'));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}]; return $v; };
\PhpursThunks::$thunks['Foreign_Object_filterWithKey'] = function() { $v = (function() {
  $__fn = function($predicate_0, $m_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_filterWithKey"), recVars=[];
  $__res = (($GLOBALS['Foreign_Object_runST'] ?? \PhpursThunks::eval('Foreign_Object_runST')))(((($GLOBALS['Control_Monad_ST_Internal_bind_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_bind_')))(($GLOBALS['Foreign_Object_ST_new'] ?? \PhpursThunks::eval('Foreign_Object_ST_new'))))(function($m__prime___2) use ($m_1, $predicate_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = (((($GLOBALS['Foreign_Object_foldM1'] ?? \PhpursThunks::eval('Foreign_Object_foldM1')))((function() use ($predicate_0) {
  $__fn = function($acc_3, $k_4 = null, $v_5 = null) use ($predicate_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  if ((($predicate_0)($k_4))($v_5)) {
$__t0 = (((($GLOBALS['Foreign_Object_ST_poke'] ?? \PhpursThunks::eval('Foreign_Object_ST_poke')))($k_4))($v_5))($acc_3);
} else {
$__t0 = (($GLOBALS['Control_Monad_ST_Internal_pure_'] ?? \PhpursThunks::eval('Control_Monad_ST_Internal_pure_')))($acc_3);
};
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})()))($m__prime___2))($m_1);
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Foreign_Object_filterKeys'] = function() { $v = function($predicate_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_filterKeys"), recVars=[];
  $__res = (($GLOBALS['Foreign_Object_filterWithKey'] ?? \PhpursThunks::eval('Foreign_Object_filterWithKey')))(((($GLOBALS['Control_Semigroupoid_composeImpl'] ?? \PhpursThunks::eval('Control_Semigroupoid_composeImpl')))(($GLOBALS['Data_Function_const'] ?? \PhpursThunks::eval('Data_Function_const'))))($predicate_0));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_filter'] = function() { $v = function($predicate_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_filter"), recVars=[];
  $__res = (($GLOBALS['Foreign_Object_filterWithKey'] ?? \PhpursThunks::eval('Foreign_Object_filterWithKey')))(function($v_1) use ($predicate_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = $predicate_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
});
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_eqObject'] = function() { $v = function($dictEq_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_eqObject"), recVars=[];
  $__res = (object)["eq" => (function() use ($dictEq_0) {
  $__fn = function($m1_1, $m2_2 = null) use ($dictEq_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Data_HeytingAlgebra_boolConj'] ?? \PhpursThunks::eval('Data_HeytingAlgebra_boolConj')))((((($GLOBALS['Foreign_Object_isSubmap'] ?? \PhpursThunks::eval('Foreign_Object_isSubmap')))($dictEq_0))($m1_1))($m2_2)))((((($GLOBALS['Foreign_Object_isSubmap'] ?? \PhpursThunks::eval('Foreign_Object_isSubmap')))($dictEq_0))($m2_2))($m1_1));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})()];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_ordObject'] = function() { $v = function($dictOrd_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_ordObject"), recVars=[];
  $eqObject1_1_0 = (($GLOBALS['Foreign_Object_eqObject'] ?? \PhpursThunks::eval('Foreign_Object_eqObject')))((($dictOrd_0)->{'Eq0'})(($GLOBALS['Prim_undefined'] ?? \PhpursThunks::eval('Prim_undefined'))));
  $__res = (object)["compare" => (function() use ($dictOrd_0) {
  $__fn = function($m1_2, $m2_3 = null) use ($dictOrd_0, &$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((((($GLOBALS['Data_Ord_ordArray'] ?? \PhpursThunks::eval('Data_Ord_ordArray')))((($GLOBALS['Foreign_Object_ordTuple'] ?? \PhpursThunks::eval('Foreign_Object_ordTuple')))($dictOrd_0)))->{'compare'})((($GLOBALS['Foreign_Object_toAscArray'] ?? \PhpursThunks::eval('Foreign_Object_toAscArray')))($m1_2)))((($GLOBALS['Foreign_Object_toAscArray'] ?? \PhpursThunks::eval('Foreign_Object_toAscArray')))($m2_3));
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(), "Eq0" => function($dollar__unused_2) use ($eqObject1_1_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = $eqObject1_1_0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}];
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_eq1Object'] = function() { $v = (object)["eq1" => function($dictEq_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  $__res = ((($GLOBALS['Foreign_Object_eqObject'] ?? \PhpursThunks::eval('Foreign_Object_eqObject')))($dictEq_0))->{'eq'};
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}]; return $v; };
\PhpursThunks::$thunks['Foreign_Object_delete'] = function() { $v = function($k_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_delete"), recVars=[];
  $__res = (($GLOBALS['Foreign_Object_mutate'] ?? \PhpursThunks::eval('Foreign_Object_mutate')))((($GLOBALS['Foreign_Object_ST_delete'] ?? \PhpursThunks::eval('Foreign_Object_ST_delete')))($k_0));
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}; return $v; };
\PhpursThunks::$thunks['Foreign_Object_pop'] = function() { $v = (function() {
  $__fn = function($k_0, $m_1 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 2) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 2);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_pop"), recVars=[];
  $__local_var_2_0 = ((($GLOBALS['Foreign_Object_lookup'] ?? \PhpursThunks::eval('Foreign_Object_lookup')))($k_0))($m_1);
  if ((is_object($__local_var_2_0) && (($__local_var_2_0)->{'tag'} === "Just"))) {
$__t1 = new Phpurs_Data1("Just", new Phpurs_Data2("Tuple", ($__local_var_2_0)->{'value0'}, ((($GLOBALS['Foreign_Object_mutate'] ?? \PhpursThunks::eval('Foreign_Object_mutate')))((($GLOBALS['Foreign_Object_ST_delete'] ?? \PhpursThunks::eval('Foreign_Object_ST_delete')))($k_0)))($m_1)));
} else {
$__t1 = new Phpurs_Data0("Nothing");
};
  $__res = $__t1;
  goto __end;;
  __end:
  return $__num > 2 ? $__res(...\array_slice(\func_get_args(), 2)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Foreign_Object_alter'] = function() { $v = (function() {
  $__fn = function($f_0, $k_1 = null, $m_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_alter"), recVars=[];
  $v_3_0 = ($f_0)(((($GLOBALS['Foreign_Object_lookup'] ?? \PhpursThunks::eval('Foreign_Object_lookup')))($k_1))($m_2));
  if ((is_object($v_3_0) && (($v_3_0)->{'tag'} === "Nothing"))) {
$__t1 = ((($GLOBALS['Foreign_Object_mutate'] ?? \PhpursThunks::eval('Foreign_Object_mutate')))((($GLOBALS['Foreign_Object_ST_delete'] ?? \PhpursThunks::eval('Foreign_Object_ST_delete')))($k_1)))($m_2);
} else {
if ((is_object($v_3_0) && (($v_3_0)->{'tag'} === "Just"))) {
$__t1 = ((($GLOBALS['Foreign_Object_mutate'] ?? \PhpursThunks::eval('Foreign_Object_mutate')))(((($GLOBALS['Foreign_Object_ST_poke'] ?? \PhpursThunks::eval('Foreign_Object_ST_poke')))($k_1))(($v_3_0)->{'value0'})))($m_2);
} else {
throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
$__t1 = null;
};
};
  $__res = $__t1;
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(); return $v; };
\PhpursThunks::$thunks['Foreign_Object_update'] = function() { $v = (function() {
  $__fn = function($f_0, $k_1 = null, $m_2 = null) use (&$__fn) {
  $__num = \func_num_args();
  if ($__num < 3) {
    return phpurs_curry_fallback($__fn, \func_get_args(), 3);
  }
  // DEBUG UncurriedAbs: currentBindingName=(Just "Foreign_Object_update"), recVars=[];
  $__res = (((($GLOBALS['Foreign_Object_alter'] ?? \PhpursThunks::eval('Foreign_Object_alter')))(function($v2_3) use ($f_0) {
  $__num = \func_num_args();
  // DEBUG UncurriedAbs: currentBindingName=Nothing, recVars=[];
  if ((is_object($v2_3) && (($v2_3)->{'tag'} === "Nothing"))) {
$__t0 = new Phpurs_Data0("Nothing");
} else {
if ((is_object($v2_3) && (($v2_3)->{'tag'} === "Just"))) {
$__t0 = ($f_0)(($v2_3)->{'value0'});
} else {
throw new \Exception("Failed pattern match at " . __FILE__ . ":" . __LINE__);
$__t0 = null;
};
};
  $__res = $__t0;
  goto __end;;
  __end:
  return $__num > 1 ? $__res(...\array_slice(\func_get_args(), 1)) : $__res;
}))($k_1))($m_2);
  goto __end;;
  __end:
  return $__num > 3 ? $__res(...\array_slice(\func_get_args(), 3)) : $__res;
  };
  return $__fn;
})(); return $v; };
$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };
$ffi_Foreign_Object = \call_user_func(function() {
  $exports = [];
$_copyST = function($m) use (&$_copyST) {
    return function() use ($m) {
        $r = new \stdClass();
        foreach ($m as $k => $v) {
            $r->$k = $v;
        }
        return $r;
    };
};

$empty = new \stdClass();

$runST = function($f) use (&$runST) {
    return $f();
};

$_fmapObject = function($m0, $f = null) use (&$_fmapObject) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_fmapObject) {

            return $_fmapObject(...\array_merge($__args, $more));
        };
    }
    $m = new \stdClass();
    foreach ($m0 as $k => $v) {
        $m->$k = $f($v);
    }
    return $m;
};

$_mapWithKey = function($m0, $f = null) use (&$_mapWithKey) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_mapWithKey) {

            return $_mapWithKey(...\array_merge($__args, $more));
        };
    }
    $m = new \stdClass();
    foreach ($m0 as $k => $v) {
        $m->$k = $f($k)($v);
    }
    return $m;
};

$_foldM = function($bind, $f = null, $mz = null, $m = null) use (&$_foldM) {
    if (\func_num_args() < 4) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_foldM) {
            return $_foldM(...\array_merge($__args, $more));
        };
    }
    $acc = $mz;
    foreach ($m as $k => $v) {
        $g = function($z) use ($f, $k, $v) {
            return $f($z)($k)($v);
        };
        $acc = $bind($acc)($g);
    }
    return $acc;
};

$_foldSCObject = function($m, $z = null, $f = null, $fromMaybe = null) use (&$_foldSCObject) {
    if (\func_num_args() < 4) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_foldSCObject) {

            return $_foldSCObject(...\array_merge($__args, $more));
        };
    }
    $acc = $z;
    foreach ($m as $k => $v) {
        $maybeR = $f($acc)($k)($v);
        $r = $fromMaybe(null)($maybeR);
        if ($r === null) return $acc;
        else $acc = $r;
    }
    return $acc;
};

$all = function($f, $m = null) use (&$all) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$all) {

            return $all(...\array_merge($__args, $more));
        };
    }
    foreach ($m as $k => $v) {
        if (!$f($k)($v)) return false;
    }
    return true;
};

$size = function($m) use (&$size) {
    $s = 0;
    foreach ($m as $k => $v) {
        $s++;
    }
    return $s;
};

$_lookup = function($no, $yes = null, $k = null, $m = null) use (&$_lookup) {
    if (\func_num_args() < 4) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_lookup) {

            return $_lookup(...\array_merge($__args, $more));
        };
    }
    return property_exists($m, $k) ? $yes($m->$k) : $no;
};

$_lookupST = function($no, $yes = null, $k = null, $m = null) use (&$_lookupST) {
    if (\func_num_args() < 4) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$_lookupST) {

            return $_lookupST(...\array_merge($__args, $more));
        };
    }
    return function() use ($no, $yes, $k, $m) {
        return property_exists($m, $k) ? $yes($m->$k) : $no;
    };
};

$toArrayWithKey = function($f, $m = null) use (&$toArrayWithKey) {
    if (\func_num_args() < 2) {
        $__args = \func_get_args();
        return function(...$more) use ($__args, &$toArrayWithKey) {

            return $toArrayWithKey(...\array_merge($__args, $more));
        };
    }
    $r = [];
    foreach ($m as $k => $v) {
        $r[] = $f($k)($v);
    }
    return $r;
};

$keys = function($m) use (&$keys) {
    $r = [];
    foreach ($m as $k => $v) {
        $r[] = (string)$k;
    }
    return $r;
};

$exports['_copyST'] = $_copyST;
$exports['empty'] = $empty;
$exports['runST'] = $runST;
$exports['_fmapObject'] = $_fmapObject;
$exports['_mapWithKey'] = $_mapWithKey;
$exports['_foldM'] = $_foldM;
$exports['_foldSCObject'] = $_foldSCObject;
$exports['all'] = $all;
$exports['size'] = $size;
$exports['_lookup'] = $_lookup;
$exports['_lookupST'] = $_lookupST;
$exports['toArrayWithKey'] = $toArrayWithKey;
$exports['keys'] = $keys;
return $exports;
  return $exports;
});
\PhpursThunks::$thunks['Foreign_Object__copyST'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['_copyST']; };
\PhpursThunks::$thunks['Foreign_Object__fmapObject'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['_fmapObject']; };
\PhpursThunks::$thunks['Foreign_Object__foldM'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['_foldM']; };
\PhpursThunks::$thunks['Foreign_Object__foldSCObject'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['_foldSCObject']; };
\PhpursThunks::$thunks['Foreign_Object__lookup'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['_lookup']; };
\PhpursThunks::$thunks['Foreign_Object__lookupST'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['_lookupST']; };
\PhpursThunks::$thunks['Foreign_Object__mapWithKey'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['_mapWithKey']; };
\PhpursThunks::$thunks['Foreign_Object_all'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['all']; };
\PhpursThunks::$thunks['Foreign_Object_empty'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['empty']; };
\PhpursThunks::$thunks['Foreign_Object_keys'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['keys']; };
\PhpursThunks::$thunks['Foreign_Object_runST'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['runST']; };
\PhpursThunks::$thunks['Foreign_Object_size'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['size']; };
\PhpursThunks::$thunks['Foreign_Object_toArrayWithKey'] = function() use (&$ffi_Foreign_Object) { return $ffi_Foreign_Object['toArrayWithKey']; };




















































