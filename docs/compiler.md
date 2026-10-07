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
| Traversal callback specialization | [`CallbackSpecialization.purs`](../src/Phpurs/CallbackSpecialization.purs) | Prove generic list folds and select private copies at known native callbacks. |
| Foreign callback contracts | [`NativeCallbacks.purs`](../src/Phpurs/NativeCallbacks.purs) | Tie native callback evidence to captured FFI source bytes and signatures. |
| FFI traversal specialization | [`ArrayCallbacks.purs`](../src/Phpurs/ArrayCallbacks.purs) | Select bounded private Array folds/filters with proven callbacks. |
| FFI traversal contracts | [`ForeignTraversals.purs`](../src/Phpurs/ForeignTraversals.purs) | Check exact source bytes, calling convention and polymorphic signatures for Array traversal templates. |
| Free-variable reuse | [`FreeVars.purs`](../src/Phpurs/FreeVars.purs) | Memoize PBO's scope-aware free-variable sets by immutable TCO-node identity. |
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
| PBO cache diagnostics | [`PurmetaProfile.purs`](../src/Phpurs/PurmetaProfile.purs) | Scope opt-in RAM, I/O, serialization and RSS diagnostics to optimization/emission. |
| Optimized AST metrics | [`AstMetrics.purs`](../src/Phpurs/AstMetrics.purs) | Count every expression occurrence via PBO's syntax traversal and a stack-safe worklist. |
| Rewrite-limit option | [`RewriteLimit.purs`](../src/Phpurs/RewriteLimit.purs) | Validate the positive iteration guard before loading inputs; share it between PBO and cache keys. |
| Package and FFI paths | [`PackagePaths.purs`](../src/Phpurs/PackagePaths.purs) | Prepare shared package roots once per build and resolve PHP files within ordered, explicit roots. |
| Composer integration | [`ComposerMerge.js`](../src/ComposerMerge.js) | Collect package requirements for the generated application. |

## Pass order

`CodeGen.translateWithContracts` is the place to read the pipeline. The order matters because
later passes consume both the rewritten code and the proofs attached to private
workers and constructors.

1. **`PartialBindings`** reuses partial applications inside proven private regions.
2. **`ThunkFusion`** creates scalar workers for eligible immediately forced chains.
3. **`StateFusion`** proves immediately executed State chains and their scalar projection.
4. **`CallbackSpecialization`** copies proven list folds for native arithmetic callbacks.
5. **`ArrayCallbacks`** selects contracted FFI traversal calls and prepares private PHP templates.
6. **`EnumRegions`** proves closed regions and prepares private representations.
7. **`analyzeBindings`** runs PBO's tail-call analysis. Within recursive groups,
   safe initializations precede expressions that can call into the group.
8. **`translateBindingGroup` / `translateExpr`** construct PHP declarations and
   bodies. `CompactLoops` runs while emitting eligible top-level recursive functions.
9. **`TailInline`** simplifies terminal control flow and inlines eligible leaves.
10. Private workers become **`PhpPrivateFunction`** declarations.
11. **`NullableConstructors`** lowers proven private empty constructors to `null`.
12. **`CopyCleanup`** removes redundant copies using the private-region metadata.
13. **`ArrayRefs`** promotes owned arguments and rewrites eligible node rebuilds.
14. Contracted **Array traversal templates** are appended as private declarations,
    after the PHP AST optimizations; their arities are included before printing.

The private scalar signatures are cleared before terminal inlining, where the
typed-region proof already establishes the argument types. Public signatures
and constructor representations are still generated at their boundaries.
State guards retain the original constructor's PHP `Int` check on depth; their
state and result remain untyped to preserve the original arithmetic at overflow.
Callback-specialized copies retain the original fold's generic signature.

`ThunkFusion` accepts a `Unit -> Int` seed whose body is either an integer
literal or a typed read of an already evaluated local. The scanner tracks
parameters and strict `Let`/`EffectBind` results by name and level; recursive
locals and the seed's own parameter are excluded. A dynamic depth uses a private
guard with the original builder as its negative fallback. That fallback keeps
construction and force as **nested applications**: the fusion scanner recognizes
only flattened consumers, so repeated scans cannot fuse their own fallback.
The PHP emitter may flatten these calls after this pass. Any extension of the
candidate application shapes must preserve that idempotence contract. See the
[captured-seed audit](../audit/2026-10-05/scalar-seeds/report.md) and
[`thunk-fusion.mjs`](../tests/codegen/thunk-fusion.mjs).

`StateFusion` derives the two-field `Unit`/`Int` layout from the constructor's
flattened signature, while requiring its actual runtime arity to be one. The
zero case must return the input state with a canonical Unit value. The recursive
case must prepare a proven same-module polymorphic modifier, with an optional
proven getter, and run that transition before recurring at `depth - 1`. The
callback must be a closed unary `Int` arithmetic operation (`+`, `-` or `*` by a
literal); every operation survives in the TCO worker. Helper signatures and the
effective native annotations are checked so no scalar coercion is silently lost.

Only a flattened, fully applied constructor immediately projected on the state
field is rewritten. Inputs must be typed integer literals or already evaluated
lexical locals. Record results, value-field projections, retained States, unknown
callbacks and foreign helpers retain their original paths. Both record labels
and all function/module names come from the proof. Budgets limit bodies/helpers
to 1,024 nodes, callers to 8,192 nodes, depth to 96, width to 64, scans to 32,768
nodes and generated workers to 32 plus at most one guard per worker.

Dynamic depth uses a private guard with the original constructor and projection
as its negative fallback. As in `ThunkFusion`, this fallback uses nested
applications, preserving idempotence on rescans. Public functions, closures and
records are unchanged. See [`state-fusion.mjs`](../tests/codegen/state-fusion.mjs),
the executable `ImmediateStateFusion` fixture and the
[integration measurements](../audit/2026-10-06/state-fusion/report.md).

`CallbackSpecialization` recognizes a same-module generic left fold from its
body, polymorphic signature and two-constructor recursive layout. The empty
branch returns the accumulator; the nonempty branch calls the callback on the
accumulator and head, then recurs with the same callback and the tail. Constructor
names and field positions come from declarations. Both the actual lambda chain
and the effective flat native annotation must agree with the emitted arity.

A selected consumer supplies a known binary native `Int` callback and a typed
integer literal accumulator. Same-module callback bodies are restricted to closed
addition, subtraction or multiplication of their two parameters. Foreign evidence
currently covers `Data.Semiring.intAdd` and `intMul`, with exact source SHA-256 and
binary `Int` signatures. `Main` derives this evidence from the same captures used
for FFI emission and cache identity on every invocation, including full/mixed
cache hits. The evidence is not inferred from a callback's functional type alone.
`translate` and `translateModuleState` remain wrappers with empty foreign evidence;
their `WithCallbacks` variants accept this invocation's proven set.

One private copy per fold/callback pair replaces the two curried callback entries
with one saturated call to the **existing native wrapper**. Its return `Int` check
therefore still fails at overflow. The literal seed and checked callback results
ensure the accumulator's first-argument check cannot fail before reading the next
head. Traversal branches, read order and input evaluation are preserved. The
invalid-constructor path delegates to the original fold, retaining its failure
message and generated source location. Public folds/callbacks, partial applications
and unknown callbacks retain their calling conventions.

Proof bodies are limited to 1,024 nodes, callers to 8,192, depth to 96, width to
64, the scan to 32,768 nodes and copies to 32 per invocation. Module selection
also caps binding groups at 256, bindings per group at 64 and declarations at 128.
Nested consumers are visited within the same scan; the generated copy's direct
callback call prevents it from matching the generic fold proof on a rescan.
FFI Array traversals are handled separately by the contracted pass below.
See [`callback-specialization.mjs`](../tests/codegen/callback-specialization.mjs),
the executable `NativeFoldCallbacks` fixture and the
[integration audit](../audit/2026-10-06/callback-specialization/report.md).

`ForeignTraversals` derives per-invocation evidence from the same source captures
and type annotations as FFI emission/cache identity. Exact SHA-256 contracts cover
`Data.Foldable.foldlArray` (generic native arity three) and `Data.Array.filterImpl`
(generic raw `Fn2`). Each contract states traversal order, callback convention,
result construction, input immutability and absence of callback retention. Changed
bytes or incompatible signatures disable that contract, including on mixed cache
hits. `translateWithContracts` / `translateModuleStateWithContracts` accept these
contracts alongside native callback evidence; the earlier APIs supply no traversal
evidence by default.

`ArrayCallbacks` specializes fully applied folds with a known native binary Int
callback and a typed literal Int seed. The private loop takes `count` once and
visits increasing numeric indices, calling the existing native callback wrapper.
Its return check preserves the Int accumulator invariant and overflow behavior.
Non-arrays and non-list PHP arrays delegate to the original FFI, preserving
Countable/ArrayAccess behavior and sparse-index diagnostics.

Filters require an immediate unary lambda without a functional annotation or
captures. The bounded predicate grammar permits Boolean literals, Int comparisons,
logical operations and a parameter/literal scalar or modulo by a nonzero integer
literal. The private `foreach` keeps insertion order and appends into a fresh dense
array. Int elements use the proven expression; every other element invokes the
original closure. Non-array inputs delegate to the original FFI, including iterator
objects. Callback expressions and array inputs are still evaluated once, and the
filter's result is materialized before a following fold.

Predicate proofs are capped at 128 nodes; callers at 8,192; depth at 96; width at
64; scans at 32,768; private copies at 32 per module. Selection also caps groups at
256, bindings per group at 64 and declarations at 128. Copies are shared by native
callback or normalized predicate, with fresh case-insensitive names and no public
exports. The fixed `foreach` template contains only printer-rendered closed scalar
code and is appended after ownership/inlining passes, which must not interpret it
as a partially modeled loop. See [`array-callbacks.mjs`](../tests/codegen/array-callbacks.mjs),
`NativeArrayCallbacks` and the [Array integration audit](../audit/2026-10-06/array-callbacks/report.md).

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

`translateValues` builds its statement and expression arrays inside one local
`ST` region. Each operand is translated left-to-right with the preceding
operand's `nextId`; only its new statements and result expression are appended.
Both arrays are freshly allocated and frozen after the last operand. No mutable
array escapes during construction, and previously returned arrays are never
reused. Accumulation is linear in the number of operands plus emitted statements,
in addition to the cost of translating the operands themselves. Statements are
appended individually, so a large result does not become a spread-argument call.
`operand-accumulator.mjs` checks copied-prefix bounds, wide arrays, IDs, statement
order and escaping captures. The [B4 measurement](../audit/2026-10-03/linear-accumulator/report.md)
records the observed effect on a real Unicode-data module and the full corpus.

`Phpurs.FreeVars.freeVars` shares PBO's `freeVarsWith` analysis step and memoizes
each recursively visited `TcoExpr`. The step retains PBO's binding rules: lambda
parameters are removed from their body, a `Let`/`EffectBind` binder only scopes
over its body, and a recursive group scopes over all its bindings and its body.
`Typed` and `TypeApp` delegate to their expression child. TCO usage metadata
includes bound locals too, so it cannot directly supply these free-variable sets.

The memo key is the immutable node object; the value is an immutable set of
**original local-ID strings**. Renaming through `boundVars` and reference capture
through `recursiveVars` happen on every use in the current context. A shared node
can therefore be translated in different lexical and effect contexts safely.
Any TCO rewrite must construct new nodes rather than mutate an analyzed object.

The JavaScript memo table uses weak keys. Its values contain no references to the
TCO graph, allowing the graph and its cached sets to be collected when translation
releases them. `translate` constructs fresh TCO nodes for each invocation. The
table is process-local and independent of serialized module state and the PBO
implementation-cache budget. V8 can retain the weak table's allocated capacity
after collecting its keys. A node's syntax is analyzed once while it remains
live; set operations still cost according to their cardinalities, and live cached
sets can require more than linear space in pathological trees.
`free-vars.mjs` checks lexical scopes, shared subtrees, context-specific captures,
recursive PHP closures, lazy effects and garbage collection. The
[B4 free-variable measurement](../audit/2026-10-03/free-vars/report.md) records
recomputation counts, module timings, live-cache heap cost and full-build RSS.

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
  once for both the wrapper and its arity marker. The internal wrapper record names
  the global key, native function name, optional export table, escaped export key
  and missing-export diagnostic;
  global-key escaping and native-function escaping are distinct operations.
- `printModularEntryPoint` and `printBundleEntryPoint` share
  `{ mainModule, autoloadPath, arities }` options and the same startup sequence: load
  Composer, install the exception handler, call main, then run Revolt. Modular
  entrypoints receive the reachable dependencies in topological order and require
  them before the main call. Both use the printer's global-read convention for
  `main`, including a missing raw foreign action.

Foreign arities take precedence over current-module arities, which take precedence
over previously emitted modules. Keep that merge before printing either form of
the module so direct calls and public wrappers agree.

### Missing foreign exports

A missing PHP file, an empty source and an absent key in its runtime `$exports`
table produce the same demand-time `RuntimeException`:
`Missing PHP FFI export: Module.Name.export`. Export lookup uses `array_key_exists`,
not `isset`: an explicitly exported `null` is a value, not an absent binding.

- A foreign `Func` retains its native wrapper, argument/result checks and curry
  fallback. Taking that function or retaining an unsaturated application does not
  demand the missing implementation. A saturated call diagnoses the missing export
  before attempting to call it. PHP argument checks still run at wrapper entry.
- Other foreign bindings, including raw `FnN`, `Effect` and unannotated values,
  retain their raw PHP representation in `$GLOBALS` when present. Missing values
  have no global slot. Generated reads use `($GLOBALS[key] ?? qualifiedGetter())`;
  the getter distinguishes a legitimate `null` from an absent slot and throws only
  for absence. It does not execute a returned action, wrap a callable or introduce
  a scalar result check. Reading a value is strict even when its consumer ignores it.
- The reserved `PhpAst.foreignValueArity` marker (`-1`) travels in the existing
  per-module arity table, including cache hits. Ordinary nonnegative arities retain
  their meaning; arity-zero PureScript globals do not acquire these getters.
  The packaged compiler's source identity invalidates older cached output.

Compilation and declaration loading alone do not reject missing foreign exports.
Unused declarations and untaken branches are allowed. Modular entrypoints load
their reachable modules; bundles still include all loaded modules and execute
their initializers. An initializer that actually reads a missing value counts as
a demand, including in a bundle. This does not suppress errors or effects in supplied
FFI initialization code. Handwritten PHP that directly accesses `$GLOBALS` bypasses
generated value-read checks; present-value interoperability is unchanged.
See [`missing-ffi.mjs`](../tests/codegen/missing-ffi.mjs) and the
[missing-FFI validation](../audit/2026-10-06/missing-ffi/report.md).

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

`--profile-purmeta` brackets optimization/emission with PBO's optional JavaScript
cache counters and prints one JSON report to stderr, including on failure. Use
`--no-cache` for full-optimization measurements: complete PHPurs state-cache hits
still publish `.purmeta` but perform no PBO implementation lookups. PBO retains
decoded implementations with a default 64 MiB serialized-size LRU budget, trimmed
at module boundaries. `--purmeta-cache-mib N` overrides that budget for the PHPurs
builder scope; `0` empties RAM at each boundary while allowing within-module reuse.
`Phpurs.PurmetaBudget` restores the previous budget on success, failure or
cancellation, then trims to it. The profile is captured before this restoration.
The budget does not partition module-state cache keys. See the
[profiling and budget contract](purmeta-profile.md) for units, scope and validation.

PBO's `.purmeta` data lives under the working directory and uses an unversioned
tagged-V8 scratch format. Reads require publication in the current scope,
including before the first builder call; every builder invocation resets that
membership. See the [storage/invalidation contract](purmeta-storage.md) for its
relationship to PHPurs's versioned module-state store.

## Printing contracts

`printExpr arities expr` uses the arity table to select saturated native calls.
Global references carry their module qualification in the PHP AST. Their global
keys and native names go through the same identifier construction before their
respective escaping rules are applied. The foreign-value marker selects the
demand-time read above, including callable values in ordinary and direct AST calls.

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

`build-profile.mjs` validates the optional detailed phase collector, strict pure
timing boundaries, asynchronous completion/cancellation, failure propagation,
independent nested scopes, and the `--verbose` diagnostic gate. It compares PHP
bytes and execution across flags, emission modes and fresh/restored module paths.
The [profiling contract](build-profile.md) specifies the sequential optimizer
hook boundary and the distinction between output comparisons and actual writes.

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
