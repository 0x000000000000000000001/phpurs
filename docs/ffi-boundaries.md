# JavaScript / PureScript FFI boundaries

This page describes the JavaScript modules called by the compiler. Boundaries
stay thin: data conversion, system access and operations that cannot reasonably
be expressed in PureScript.

## Principles

- PureScript values cross as curried functions; callbacks must not be stored
  past the call without an explicit contract.
- Validation errors are returned as `Either` when the signature allows it.
  Caches and Composer manifests ignore some errors according to the contracts
  below; other I/O errors propagate to the caller.
- Buffers and serialized structures are treated as bytes, never as shared
  mutable objects.
- No internal TAST/PBO type is exposed as a PHP API; the cache serializes only a
  validated data graph.

## Generated output root

The emitted tree is not `src/`: `runtime.php` sits at the output root, modules
live in `<Module.Name>/index.php`, entrypoints in `bin/`, and the optional
bundle at the root. `runtime.php` defines `PHPURS_ROOT` and
`phpurs_root()`; both resolve to the directory that contains `runtime.php`
(`__DIR__` at definition time). A file may call `phpurs_root()` only after it
has required `runtime.php`. Entrypoints run before that first require, so they
keep `__DIR__`-relative paths. Tests assert both the constant and the helper.

The stable runtime contract is in [ffi-contract.md](ffi-contract.md).

## Modules

| JS module | Wrapper | Inputs / outputs | Errors |
| --- | --- | --- | --- |
| `ComposerMerge.js` | `Phpurs.ComposerMerge` | Output directory and package roots; writes the merged manifest | Missing, unreadable or malformed manifests are ignored; write errors propagate |
| `BuildInputs.js` | `Phpurs.BuildInputs` | Paths, concurrency, missing-file test | No business validation |
| `BuildProfile.js` | `Phpurs.BuildProfile` | Profile, spans, phases, JSON | A failing action ends the span as failed, then rethrows |
| `CacheKey.js` | `Phpurs.CacheKey` | Context, inputs, dependencies, keys | `Either String` for invalid cwd/input/dependency |
| `FileEmission.js` | `Phpurs.FileEmission` | Path, missing-file test | File-system errors propagate |
| `FreeVars.js` | `Phpurs.FreeVars` | TAST graph memoization | Callback errors propagate |
| `Metrics.js` | `Phpurs.Metrics` | Monotonic clock | None |
| `ModuleCache.js` | `Phpurs.ModuleCache` | Module states, V8 serialization, checksums | `Nothing` on read or `false` on write for invalid data and file-system errors |
| `OutputManifest.js` | `Phpurs.OutputManifest` | Output tracking, manifest, cleanup | Invalid entries are ignored; file-system errors propagate |
| `PackagePaths.js` | `Phpurs.PackagePaths` | Spago/FFI directories, candidate paths | File-system errors propagate |
| `Printer/Expr.js` | `Phpurs.Printer.Expr` | PHP names, escaping, 32-bit integers | Pure, no errors |
| `PurmetaBudget.js` | `Phpurs.PurmetaBudget` | PBO memory budget | `Either String` for a missing/invalid value |
| `RewriteLimit.js` | `Phpurs.RewriteLimit` | Rewrite limit | `Either String` for a missing/invalid value |

## Error coverage

Error paths are mainly covered by:

- `tests/codegen/cli/cache-key.mjs` and `tests/codegen/cli/cache-cli.mjs` for
  keys, manifests, corrupted caches and cleanup;
- `tests/codegen/cli/build-profile.mjs` for spans, phases and failures;
- `tests/codegen/cli/purmeta-budget.mjs` and
  `tests/codegen/cli/rewrite-limit.mjs` for options;
- `tests/codegen/ffi/missing-ffi.mjs` for PHP export errors;
- `tests/codegen/cli/composer-merge.mjs` for malformed or missing Composer
  manifests;
- `tests/codegen/ffi/runtime-preamble.mjs` and the `tests/codegen/direct/` tests
  for pure helpers.

Every new FFI export must state its input, output, data owner and error
behaviour here.
