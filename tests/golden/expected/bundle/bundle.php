<?php

namespace {
if (!defined('PHPURS_ROOT')) { define('PHPURS_ROOT', __DIR__); }
if (!defined('PHPURS_ABI_VERSION')) { define('PHPURS_ABI_VERSION', 1); }
if (!function_exists('phpurs_root')) {
  function phpurs_root(): string { return PHPURS_ROOT; }
}
if (!function_exists('phpurs_abi_version')) {
  function phpurs_abi_version(): int { return PHPURS_ABI_VERSION; }
}
if (!function_exists('phpurs_variant_tag')) {
  function phpurs_variant_tag($value) { return $value->tag; }
}
if (!function_exists('phpurs_variant_value')) {
  function phpurs_variant_value($value, int $index) { $field = 'value' . $index; return $value->$field; }
}
if (!function_exists('phpurs_record_get')) {
  function phpurs_record_get($record, string $key) { return $record->$key; }
}
if (!function_exists('phpurs_aff_from_operation')) {
  function phpurs_aff_from_operation($operation) {
    return new \PhpursThenable(function($resolve, $reject) use ($operation) {
      return $operation($resolve, $reject);
    });
  }
}
if (!function_exists('phpurs_non_cancellable')) {
  function phpurs_non_cancellable() { return function() {}; }
}
if (!function_exists('phpurs_apply')) {
  function phpurs_apply($fn, array $args) {
    if (!\is_callable($fn)) { throw new \TypeError('phpurs_apply: expected a callable'); }
    $closure = \Closure::fromCallable($fn);
    $ref = new \ReflectionFunction($closure);
    $expected = $ref->getNumberOfParameters();
    if (!$ref->isVariadic() && $expected !== \count($args)) {
      throw new \TypeError('phpurs_apply: expected ' . $expected . ' arguments, got ' . \count($args));
    }
    return $closure(...$args);
  }
}

if (!\function_exists('phpurs_curry_fallback')) {
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
if (!\function_exists('phpurs_execute_effect')) {
  function phpurs_execute_effect($val) {
    if (\is_callable($val)) {
      return $val($GLOBALS['Data_Unit_unit']);
    }
    return $val;
  }
}
if (!\function_exists('phpurs_ref_new')) {
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
if (!\function_exists('phpurs_value')) {
  function phpurs_value($value) {
    if (\is_object($value) || !\is_array($value)) {
      return $value;
    }
    if (\array_is_list($value)) {
      $result = [];
      foreach ($value as $item) {
        $result[] = phpurs_value($item);
      }
      return $result;
    }
    $result = new \stdClass();
    foreach ($value as $key => $item) {
      $result->{$key} = phpurs_value($item);
    }
    return $result;
  }
  function phpurs_record($value) {
    if (\is_object($value) || !\is_array($value)) {
      return $value;
    }
    $result = new \stdClass();
    foreach ($value as $key => $item) {
      $result->{$key} = phpurs_value($item);
    }
    return $result;
  }
  function phpurs_promise_map($promise, $map) {
    if (\class_exists('PhpursPromise', false) && $promise instanceof \PhpursPromise) {
      $mapped = new \PhpursPromise();
      $promise->then(function($value) use ($mapped, $map) {
        $mapped->resolve($map($value));
      }, [$mapped, 'reject']);
      return $mapped;
    }
    return new \PhpursThenable(function($resolve, $reject) use ($promise, $map) {
      $promise->then(function($value) use ($resolve, $map) {
        return $resolve($map($value));
      }, $reject);
    });
  }
  function phpurs_effect_map($effect, $map) {
    return function(...$args) use ($effect, $map) {
      $value = \is_callable($effect) ? $effect(...$args) : $effect;
      return $map($value);
    };
  }
}
if (!\class_exists('PhpursThenable')) {
  class PhpursThenable extends \stdClass {
    public $executor;
    public function __construct($executor) {
      $this->executor = $executor;
    }
    public function then($resolve, $reject) {
      return ($this->executor)($resolve, $reject);
    }
  }
}
if (!\function_exists('phpurs_promise')) {
  function phpurs_promise($executor) {
    return new \PhpursThenable($executor);
  }
}
}

namespace Demo {
// ALL IMPORTS: 
// TO REQUIRE: 



$GLOBALS['Prim_undefined'] = function() { throw new \Exception("undefined"); };




// Demo_main
function majDemo_main(): int|\Closure {
  $__num = \func_num_args();
  $__fn = __NAMESPACE__ . '\\' . 'majDemo_main';
  if ($__num < 0) {
    return \phpurs_curry_fallback($__fn, \func_get_args(), 0);
  }
  $__res = (($GLOBALS['Demo_add'])(1))(2);
  goto __end;;
  __end:
  return 0 < $__num ? $__res(...\array_slice(\func_get_args(), 0)) : $__res;
}
$GLOBALS['Demo_main'] = __NAMESPACE__ . '\\majDemo_main';

}
