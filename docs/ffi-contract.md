# PHP runtime ABI (version 1)

This page is the stable contract between generated PHP and PHP code written
outside the compiler. `PHPURS_ABI_VERSION` and `phpurs_abi_version()` return
`1`; a change to a representation or helper name below bumps both and the
runtime source, which is part of the compiler backend fingerprint and therefore
invalidates module-cache keys.

## Generated output root

`runtime.php` lives at the output root. It defines `PHPURS_ROOT` and
`phpurs_root()` from its own `__DIR__`; module files live under
`<Module.Name>/index.php` and entrypoints under `bin/`. A module may call
`phpurs_root()` only after requiring `runtime.php`. Entrypoints run before that
require and keep `__DIR__`-relative paths.

## Variants and records

Constructors use `Phpurs_DataN` classes with a public `$tag` and `$value0`…
`$valueN` properties. Read them through the stable accessors, not directly:

```php
$tag = \phpurs_variant_tag($value);
$field = \phpurs_variant_value($value, 0);
$x = \phpurs_record_get($record, 'x');
```

`Maybe` is `Phpurs_Data1` with tag `Just` (field 0) or `Phpurs_Data0` with tag
`Nothing`. `Either` is `Phpurs_Data1` with tag `Left` or `Right` (field 0).
Records are `stdClass` values; `phpurs_record_get` is the stable field reader.
`Effect a` is a zero-argument PHP closure returning `a`; execute it with
`\phpurs_execute_effect($effect)`. `EffectFnAff` is a thenable built by
`\phpurs_promise` and consumed through `then`, as before.

## Callbacks

A PureScript callback received by PHP FFI is **uncurried**: its PHP callable
takes all arguments at once. Call it through `phpurs_apply` so an arity mistake
fails explicitly instead of silently producing a partial closure:

```php
$result = \phpurs_apply($callback, [$a, $b]);
```

`phpurs_apply` accepts a callable and an array, checks the declared parameter
count (variadic functions bypass the check), and throws `TypeError` with the
expected and actual counts. A curried function called with the wrong number of
arguments therefore raises instead of returning a closure.

## Aff operations

`EffectFnAff` values are replayable: every `then` starts a new operation with
fresh callbacks. Build one with `phpurs_aff_from_operation`:

```php
$aff = \phpurs_aff_from_operation(function($resolve, $reject) {
  // start the operation, call exactly one of $resolve/$reject
  return \phpurs_non_cancellable(); // or a canceler closure
});
```

The returned canceler is cooperative and idempotent; calling it after
completion is safe. `phpurs_non_cancellable()` returns a no-op canceler for
operations that cannot be interrupted. A failing operation calls `$reject`
with the error value. Synchronous PHP work still occupies the thread: these
helpers do not make blocking I/O asynchronous.

## `$exports`

An inline FFI fragment returns an array from `$exports`. Each `foreign import`
must have a key with the same name; values are callables or constants allowed
by the foreign type. The compiler validates the fragment before emission: a callable literal with
more parameters than the flattened foreign signature and
`declare(strict_types=1)`/`namespace` are rejected. Keys are checked on a
best-effort basis only: loop-generated tables, aliases and partial fragments
keep runtime demand-driven diagnostics, and extra helper exports are allowed.
A missing key therefore still reports `Missing PHP FFI export:
<Module>.<name>` when the wrapper is first demanded.
