# PHPurs backend architecture

PHPurs turns the typed CoreFn (TAST / `tcorefn`) of the PureScript fork,
optimized by PBO, into PHP, then publishes modules, entrypoints and bundles.
This page is the overview; detailed contracts live in `compiler.md`,
`ffi-boundaries.md` and the audit reports.

## Pipeline

```text
typed TAST / CoreFn (output/*/corefn.json)
  -> PBO, directives and FFI contracts
  -> optimized BackendModule
  -> CodeGen (TCO analysis, bindings, expressions)
  -> PhpFile / PhpDecl / PhpExpr
  -> Printer (expressions, declarations, runtime, module)
  -> module index.php / main.mod.php / main.bundle.php / bundle.php
  -> output manifest, cache and Composer
```

## Inputs and orchestration

- `Main` loads CoreFn, selects main modules and computes the import closure
  (`Main.Selection`).
- `Main.Options` normalizes flags and budgets.
- `Main.Prepare` resolves Spago/FFI roots, captures FFI sources, prepares
  callbacks and the cache.
- PBO (`buildModules`) optimizes the selected modules and returns
  `BackendModule` values.

## PHP generation

- `CodeGen` orders the passes and the proofs that authorize specializations.
- `CodeGen.Expr` translates expressions recursively; `CodeGen.Declarations`
  emits the module's constructors, functions and values.
- `CodeGen.Context` carries lexical state, loops and effect position.
- `CodeGen.Tco` owns labels, tail jumps and direct registers.
- `CodeGen.Effects` distinguishes a deferred action from an already executed
  value.
- `CodeGen.Bindings` analyzes recursive groups and initialization order.
- Local passes (`PartialBindings`, `ThunkFusion`, `StateFusion`,
  `CallbackSpecialization`, `ArrayCallbacks`, `EnumRegions`, `TailInline`,
  `CompactLoops`, `NullableConstructors`, `CopyCleanup`, `ArrayRefs`) transform
  the TAST or the PHP AST before printing.

Passes that rewrite the TAST share the `Phpurs.Pass` protocol: a pure
`BackendModule -> { module_, privateNames }` function with shared size guards,
binding traversal and scan budget. Each pass keeps its proofs, budgets and
fallback.

Safety valves (module size, scanned nodes, fuel, workers, copies, depth and
width) live in `Phpurs.Budget`; a pass does not hard-code one.

`Optimize.NeutralExpr`, `Optimize.Names` and `Optimize.ScalarWorker` share the
common constructions.

`ArrayRefs.CallSites` finds call sites, `ArrayRefs.Decision` checks per-site
safety, and `ArrayRefs.Fixpoint` solves the promotion sets; `Analysis` exposes
the aggregate entry points.
`Ownership` tracks aliases and field reads of an owner; `Rebuild` applies the
authorized rewrite. `Support` provides the shared traversals and accessors.

## Printing and runtime

- `Printer.Expr` renders expressions, captures, currying and PHP names.
- `Printer.Decl` renders declarations, exact bodies and the complete module.
- `Printer.Runtime` provides the single global definitions (curry, effect,
  references); canonical names live in `RuntimeNames`.
- Modules use `runtime.php`; bundles embed the runtime once inside a
  `namespace { ... }` block.

## Outputs, cache and manifest

- `ModuleState` publishes fresh or restored modules in the same order.
- `BuildCache` connects content keys to module state; incomplete or ambiguous
  entries disable the cache.
- `OutputManifest` owns the PHP/Composer files, preserves user modifications and
  removes obsolete outputs after finalization.
- `Main.Outputs` writes entrypoints and bundles, then finalizes the manifest.

## Entrypoints

- Modular mode: `main.mod.php` loads the reachable modules.
- Explicit bundle: `main.bundle.php` contains the CoreFn closure and the runtime.
- Automatic bundle: union of the exported mains' closures.
- No main: every loaded module is kept.

## Tests

Tests are classified in `tests/codegen/README.md`:

- direct generator/AST tests;
- CLI/cache/emission tests (`tests/codegen/cli`);
- FFI/runtime tests (`tests/codegen/ffi`).

`npm run test:codegen` is the single entry point. Fixture and PHP execution
helpers live in `tests/codegen/fixtures/cli.mjs`.

## Invariants

- The optimized TAST is not mutated: passes build new nodes.
- FFI boundaries and PHP errors are preserved.
- Extracting a module must not change the PHP produced for an unchanged input.
- The cache and the manifest stay coherent across cold, partial and restored
  builds.
