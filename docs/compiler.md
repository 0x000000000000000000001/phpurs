# Working on the compiler

## Two compiler roles

The backend is a PureScript program compiled to JavaScript and run by Node.js.
The programs it translates are compiled to enriched CoreFn by the TAST fork.

- **Build phpurs:** `npm run build` uses the upstream `purescript@0.15.16` and
  Spago development dependencies in this checkout. It compiles `src` into
  `output`; `tools/bundle.mjs` packages `Main` into the standalone `bin/phpurs.js`.
- **Build a PHP program:** put the TAST-capable `purs` on `PATH`, then run the
  application's Spago build. Its `corefn.json` must include `dataDecls`,
  `classDecls` and `typeTable`.

Keep npm's local compiler selection scoped to the host build. Exporting this
checkout's `node_modules/.bin` into the application environment selects the
wrong `purs` for PHP generation.

The local optimizer dependency and sibling library layout are described in
the [README](../README.md#build-the-backend).

## Source map

| Area | Entry point | Responsibility |
| --- | --- | --- |
| Build orchestration | [`Main.purs`](../src/Main.purs) | Load input, invoke PBO, discover FFI, write modules and entrypoints. |
| Module and expression lowering | [`CodeGen.purs`](../src/Phpurs/CodeGen.purs) | Coordinate local passes, analyze TCO, lower bindings and expressions to PHP AST. |
| Primitive operators | [`CodeGen/Operators.purs`](../src/Phpurs/CodeGen/Operators.purs) | Choose PHP operators and runtime calls such as `intdiv`. |
| Signatures and arity | [`CodeGen/Types.purs`](../src/Phpurs/CodeGen/Types.purs) | Extract annotated function types, select scalar PHP types, calculate remaining application arity. |
| PHP representation | [`PhpAst.purs`](../src/Phpurs/PhpAst.purs) | Expression, statement, declaration and file types. |
| PHP AST traversal | [`PhpAst/Traversal.purs`](../src/Phpurs/PhpAst/Traversal.purs) | Enumerate children with explicit scope boundaries and map expression children or statement blocks. |
| PHP printing | [`Printer.purs`](../src/Phpurs/Printer.purs) | Render the AST, calling conventions and module layout. |
| Embedded PHP runtime | [`Printer/Runtime.purs`](../src/Phpurs/Printer/Runtime.purs) | Assemble namespace-local data classes, curry fallback, effect execution and reference helpers. |
| FFI assembly | [`GenNativeForeign.purs`](../src/GenNativeForeign.purs) | Prepare foreign export tables, arities and public calling wrappers from FFI source. |
| Executable entrypoints | [`EntryPoint.purs`](../src/Phpurs/EntryPoint.purs) | Render shared startup, module loading, the main call and Revolt execution for modular files and bundles. |
| PHP file writes | [`FileEmission.purs`](../src/Phpurs/FileEmission.purs) | Compare generated UTF-8 bytes with existing output and write only missing or different files. |
| Output lifecycle | [`OutputManifest.purs`](../src/Phpurs/OutputManifest.purs) | Record generated PHP ownership and retire unchanged obsolete outputs after successful finalization. |
| Module-cache identity | [`CacheKey.purs`](../src/Phpurs/CacheKey.purs) | Plan versioned input keys, dependency keys and conservative preceding-module state identity. |
| Captured build inputs | [`BuildInputs.purs`](../src/Phpurs/BuildInputs.purs) | Decode/hash each CoreFn from one captured buffer and capture selected FFI bytes before lookup. |
| Active build cache | [`BuildCache.purs`](../src/Phpurs/BuildCache.purs) | Connect a complete key plan to optional state I/O and report hits/misses/stores. |
| Module-state publication | [`ModuleState.purs`](../src/Phpurs/ModuleState.purs) | Render selected PHP forms and publish fresh/restored arities, reachability and ordered bundle contributions. |
| Module-state persistence | [`ModuleCache.purs`](../src/Phpurs/ModuleCache.purs) | Store and load versioned, checksummed state with restored PBO constructor prototypes. |
| Package and FFI paths | [`PackagePaths.purs`](../src/Phpurs/PackagePaths.purs) | Prepare shared package roots once per build and resolve PHP files within ordered, explicit roots. |
| Composer integration | [`ComposerMerge.js`](../src/ComposerMerge.js) | Collect package requirements for the generated application. |

## Pass order

`CodeGen.translate` is the place to read the pipeline. The order matters because
later passes consume both the rewritten code and the proofs attached to private
workers and constructors.

1. **`PartialBindings`** reuses partial applications inside proven private regions.
2. **`ThunkFusion`** creates scalar workers for eligible immediately forced chains.
3. **`EnumRegions`** proves closed regions and prepares private representations.
4. **`analyzeBindings`** runs PBO's tail-call analysis. Within recursive groups,
   safe initializations precede expressions that can call into the group.
5. **`translateBindingGroup` / `translateExpr`** construct PHP declarations and
   bodies. `CompactLoops` runs while emitting eligible top-level recursive functions.
6. **`TailInline`** simplifies terminal control flow and inlines eligible leaves.
7. Private workers become **`PhpPrivateFunction`** declarations.
8. **`NullableConstructors`** lowers proven private empty constructors to `null`.
9. **`CopyCleanup`** removes redundant copies using the private-region metadata.
10. **`ArrayRefs`** promotes owned arguments and rewrites eligible node rebuilds.

The private scalar signatures are cleared before terminal inlining, where the
typed-region proof already establishes the argument types. Public signatures
and constructor representations are still generated at their boundaries.

## Expression translation contracts

`translateExpr context nextId expr` returns a `TranslationResult`:

- `stmts` must execute before `expr` is consumed.
- `nextId` is the next available temporary/label ID. Thread it through sibling
  translations; independently restarting the counter can alias live locals.
- `TranslationContext.boundVars` maps optimizer local IDs to renamed PHP locals.
- `recursiveVars` identifies captures that need PHP references so a closure can
  observe its own eventual initialization.
- `loops` and `isTail` identify legal tail jumps. Arguments are evaluated into
  temporaries before loop parameters are reassigned, preserving simultaneous updates.
- `inEffectBlock` says the expression is already inside an effect-executing body.
  Otherwise an effect node is wrapped in a zero-argument closure.

`translateValue` resets the tail/effect position for operands while retaining
their lexical scope. Binding bodies inherit the surrounding position; a new
function body starts its own tail-call scope. `translateValues` shares operand
lowering and temporary allocation across arrays, constructors and calls.

Tail-loop construction has two shared helpers:

- `translateTailJump` saves all arguments into temporaries, updates the loop
  slots, then jumps. Curried and uncurried calls use the same protocol.
- `wrapLoopBody` initializes those slots once, places the loop label, then binds
  the parameters on each iteration. Local closures and top-level functions use
  the same body layout. Local loop lowering accepts a single recursive function;
  other recursive groups use the ordinary reference-capturing closures.

Key invariants to preserve in a refactor:

- **Short-circuiting:** the right operand of `&&` or `||` may produce statements.
  Those statements belong inside a deferred operand, not before the operator.
- **Remaining arity:** an application can keep an annotation containing arguments
  already supplied, including dictionaries. `remainingArity` is shared by global
  forwarding wrappers and the printer's arity table. The flattened function type
  used for signatures serves a different purpose.
- **Callable effect results:** `EffectFn` and `EffectBind` execute opaque actions
  once. An explicit effect body has already produced its result, which may itself
  be an action or canceler. `Typed` and `TypeApp` preserve this distinction.
  `effect-results.mjs` checks execution order and repeated use of returned actions.

For ownership rewrites, read the contract at the top of `ArrayRefs.purs` together
with `array-refs-ownership.mjs`: a last use at one call site is insufficient when
another binding retains an alias or a constructor shares a child.

## PHP AST traversal contracts

The shared traversal module provides structural operations with explicit scope:

- `children` includes nested function bodies and global initializers;
  `localChildren` treats those declarations as opaque.
- `mapChildren` maps every direct expression child, including function bodies.
  Its caller controls recursion and any scope-sensitive rewriting.
- `mapBranches` rewrites the statement lists of `if` and `switch`; `mapBlocks`
  also handles `while`. Both retain conditions, case labels and nested functions.

Passes own their eligibility rules and budgets. In particular,
`CopyCleanup.supportedChildren` is an allowlist that can reject a worker, whereas
the shared child enumeration only describes structure. Enumeration order is not
PHP evaluation order: the shared match traversal lists the fallback before the
arms; `ArrayRefs` explicitly retains its arms-before-fallback scan order.

When extending `PhpExpr`, update both exhaustive matches in `PhpAst.Traversal`
and review each optimization's admissible forms and scope assumptions.

## File emission contracts

`Main` discovers and reads foreign files only when `backendMod.foreign` is
nonempty, selects reachable modules and writes the generated files. Modules with
no foreign bindings pass an empty source to FFI assembly, even if an adjacent or
fallback PHP file exists. All loaded modules still contribute their package roots
to Composer discovery. Pure rendering is delegated through two interfaces:

- `genForeignModule { moduleName, bindings, source }` accepts the original FFI
  source and returns `{ code, arities }`. Each foreign signature is flattened
  once for both the wrapper and its arity. The internal wrapper record names
  the global key, native function name, optional export table and value expression;
  global-key escaping and native-function escaping are distinct operations.
- `printModularEntryPoint` and `printBundleEntryPoint` share
  `{ mainModule, autoloadPath }` options and the same startup sequence: load
  Composer, install the exception handler, call main, then run Revolt. Modular
  entrypoints receive the reachable dependencies in topological order and require
  them before the main call.

Foreign arities take precedence over current-module arities, which take precedence
over previously emitted modules. Keep that merge before printing either form of
the module so direct calls and public wrappers agree.

During preparation, `Main` calls `resolvePackagePaths { ffiDir, modulePaths }`
once. `PackagePaths` returns separate `ffiRoots` and `composerRoots` arrays from
one discovery of the Spago package directories. These arrays belong to that build;
subsequent preparations observe their own working directory and FFI option.

`findForeignFile` tries the adjacent PHP source first, then the supplied FFI roots:
Spago packages, the optional FFI directory, and the project directory. Within each
root it tries `src/Foo/Bar.php`, `src/Foo.Bar.php`, then `Foo.Bar.php`. Candidate
paths are normalized lexically and deduplicated per lookup, retaining the first
occurrence. Lookup does not enumerate directories or recursively search other roots.

Composer roots use the same Spago and optional FFI roots, followed by roots inferred
from the already loaded module paths. `Main` sorts those paths by **module name**,
matching the old output directory scan rather than the PHP dependency order. Roots
are deduplicated at their first occurrence; later manifests overwrite earlier entries
in both `require` and `require-dev`. Module roots contribute Composer requirements;
their direct FFI paths are already handled by the adjacent-file lookup.

`mergeComposers { outputDir, packageRoots }` receives the resulting Composer roots
and only reads their manifests. All generated files, including per-entrypoint
bundles and `composer.json`, use `outputDir`.

`Main` handles the PHP-specific `--bundle-only` flag alongside the shared CLI
options, including grouped Spago arguments. It enables `emitBundle` and disables
`emitModules`. Modular emission owns the reachability graph, `printPhpFile false`,
the `index.php` writes and `main.mod.php` entrypoints. Bundle emission uses
`printPhpFile true` and the same ordered module stream and arity table.

With an explicit main, bundle-only emits its `main.bundle.php`. Without one, it
emits a bundle entrypoint for every exported main plus a global `bundle.php` that
does not invoke main. An explicit main must be loaded and export `main`; `Main`
checks this during preparation, before module emission. Composer collection runs
in every mode. The mode selects the current writes and preserves the existing
files of disabled output families.

All four PHP write sites use `OutputManifest.writeOutput`: module
`index.php`, `main.mod.php`, `main.bundle.php` and the global `bundle.php`.
It awaits `FileEmission.writeTextFileIfChanged` before recording ownership.
That helper encodes the generated string as UTF-8 and compares it with the existing
file's raw bytes. Decoding the old file would conceal some invalid UTF-8 sequences
behind replacement characters. An equal file keeps its contents and modification
time; a different or missing file is written asynchronously, and the build waits
for completion. Only `ENOENT` is treated as missing output. Other read errors and
all write errors propagate to the phase and total failure reporting.

After entrypoint/Composer finalization, `OutputManifest.finalizeOutputs` compares
this build's recorded PHP paths with `<outputDir>/.phpurs-outputs.json`. With
complete, unambiguous inputs, it removes obsolete files in active output families
only when their raw SHA-256 still matches the previous record. Modified/untracked
files and replaced symlinks are preserved; disabled families retain their records
for a later build. Missing or damaged ownership data authorizes no deletion.
The new manifest is sorted, conditionally written and atomically replaced. Output
and manifest I/O errors propagate. This lifecycle runs on hits, misses and builds
with `--no-cache`; see the [detailed contract](cache.md#output-lifecycle-and-main-selection).

The packaged CLI enables cache reuse by default; `--no-cache` compiles every
module without reading or storing cached states. `tools/bundle.mjs` embeds all
compiler JavaScript and hashes the same in-memory source that it evaluates,
passing its identity to `Main.mainWithToolchain`. Direct development imports
through `Main.main` use disabled hooks; `Main.mainWithCache` accepts explicit
hooks for state-layer tests. Both branches use `ModuleState.publishModuleState`
to update arities, reachability and bundle content in the same order and perform
conditional PHP writes. Entry points and Composer are finalized normally.

`Phpurs.CacheKey` supplies the pure v1 key planner. Its [cache contract](cache.md)
specifies byte capture, toolchain/options/directives, FFI selection and dependency
fingerprints. A prefix chain covers the directives, private globals and PHP
arities accumulated from preceding modules. `ModuleCache` now persists the full
typed backend module and its local arities/printed PHP in a versioned, checksummed
entry with atomic replacement. `BuildInputs` captures CoreFn and FFI bytes once,
then `BuildCache` plans keys in the actual PBO order. Incomplete/duplicate input
or an unsupported plan disables the whole cache session and uses normal codegen.
The store lives under `<outputDir>/.phpurs-cache/v1`; entrypoints and Composer
are rebuilt from current inputs and restored/fresh module contributions.

## Printing contracts

`printExpr arities expr` uses the arity table to select saturated native calls.
Global references carry their module qualification in the PHP AST. Their global
keys and native names go through the same identifier construction before their
respective escaping rules are applied.

`genCurry arities params returnType captures body` renders a closure. Capture
clauses and return signatures have shared renderers. `printCurryStatements`
rewrites returns to a common exit before applying extra arguments to the result;
that rewrite stays within the current function's scope.

`Printer.Runtime.preamble` is shared by modular files and bundles. The generic
data classes (arities 0–12) and fixed-parameter curry fallbacks (one to four missing
arguments) are generated from common templates. Larger partial applications use
the variadic fallback. Effect and reference helpers are kept as readable PHP
blocks in the same module.

## Validation commands

From this repository:

```bash
npm run build
npm run test:codegen

# With the TAST fork and application Spago on PATH:
./bin/test
./bin/modtest
```

The codegen command runs the existing `.mjs` suites serially with Node's test
runner. They import freshly compiled modules from `output`; several also run
the generated PHP and check captures, effects, persistent values and boundaries.
`branch-slots.mjs` uses `initialContext` and `translateExpr` directly when PBO
would otherwise erase the shape under test.
`file-emission.mjs` runs the compiled `Main` on small typed module fixtures in
fresh directories. It checks default, relative and absolute output paths, executes
modular and bundled entrypoints, and verifies FFI dependency discovery, Composer
merge precedence and independence from stale output/cache files.
It also tracks PHP file probes: modules without foreign declarations must avoid
both adjacent and fallback FFI lookup, while their Composer requirements are kept.
Equivalent candidates are probed once, package discovery is shared with Composer,
and the absolute-output case also uses an absolute FFI directory.
The emission cases cover default modular output, `--bundle`, standalone and
combined `--bundle-only`, grouped CLI arguments, multiple discovered mains and
preservation of pre-existing modular files in a bundle-only rebuild.
Each mode also repeats the build and checks zero PHP writes, identical bytes and
preserved modification times. Further cases change FFI while preserving its mtime,
change the autoloader option, remove outputs, and damage same-size or UTF-8 contents.
Only the affected files are rewritten; the changed executable fixture produces its
new result in both modular and bundled form. Injected read/write failures verify
error propagation and preservation of the previous output on those failures.
`package-paths.mjs` verifies competing-file precedence, bounded lookup and successive
preparations with different working directories and FFI options in one process.

`module-cache.mjs` exercises the versioned state store and the real driver's
explicit cache hooks. Cold, full-hit and mixed builds must match the uncached
output and PHP execution in modular, bundle and bundle-only modes. It also checks
constructor identity/sharing, deep expressions, arity precedence, corrupt entries,
atomic replacement failure, current-build purmeta republication, output repair
and output-error propagation. `cache-cli.mjs` runs the packaged executable with
automatic reuse and `--no-cache`, checks invalidation and identical outputs, and
replaces CoreFn, FFI and executable files after reads to check input capture.

`bin/test` accepts fixture names for targeted work. Its `tests/runner/src` and
output directories are scratch space. `bin/modtest` exercises executable sibling
package suites; asynchronous suites must reach their completion marker as well
as exit successfully.

For routine refactors, use b8x's generated PHP as the main differential check:

1. Freeze one TAST input tree (`corefn.json`), its PHP FFI dependencies and the
   backend options. Preserve relative module paths used to resolve FFI files.
2. Build the baseline backend, generate PHP from that snapshot and save it.
3. Build the refactored backend and generate again into a fresh copy of the same
   input tree. Compare file contents byte for byte **and** the relative file set,
   including entrypoints, so added or missing files cannot be hidden by stale output.
4. Run `npm run test:codegen` for the focused compiler checks.

Behavior changes also need the relevant executable regression or fixture. Run
the complete fixture/library suites at broader milestones.

The [B1 rebuild measurement](../audit/2026-10-01/build-scenarios/report.md) runs
three controlled states on the frozen b8x corpus: unchanged input, a changed
application leaf and a changed shared dependency. Its driver records codegen
module lists, PHP and purmeta writes, phase timings and peak RSS. Each state is
checked against fresh generation, and unchanged PHP must retain its mtime.
The mutations replace one typed CoreFn literal, so these measurements cover the
backend after the upstream PureScript compilation. Use this protocol when
evaluating future build-cache hits and dependency invalidation.

The benchmark checkout provides the usual `./bin/php/run -c` workflow (`runp`);
it rebuilds the host backend and then uses the TAST compiler for the workloads.
Published reference measurements live in `altbak.pub/README.md`.
