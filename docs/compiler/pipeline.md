# Pipeline, roles and passes

This page covers the two compiler roles, the pipeline overview and the pass
order. Per-pass contract cards live in [passes.md](passes.md); the generated
module map is in [source-map.md](source-map.md).

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
the [README](../../README.md#build-the-backend).

## Architecture

See [`architecture.md`](../architecture.md) for the pipeline overview, FFI
boundaries and output lifecycle, and [`development.md`](../development.md) for the
build, tests and runners.

## Source map

The module map is generated from module headers: [source-map.md](source-map.md).
Run `npm run docs:source-map` after moving a module; `check:source-map` fails
when the committed page is stale.

## Pass order

`CodeGen.translateWithContracts` is the place to read the pipeline. The order matters because
later passes consume both the rewritten code and the proofs attached to private
workers and constructors.

Passes that rewrite the TAST share the `Phpurs.Pass` protocol: a pure
`BackendModule -> { module_, privateNames }` function with shared module-size
guards, binding traversal, scan budget and private-name convention. Each pass
still owns its proofs, budgets and fallback. Safety valves (module size, scan
nodes, fuel, worker and copy limits, depth and width) live in `Phpurs.Budget`;
a pass must not hard-code one. Per-pass contract cards (proof, budgets,
fallback, test) live in [`compiler/passes.md`](passes.md).

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

A closed record update is reconstructed directly only when the `Typed` result
is `Record (Row fields Nothing)`, every row label is updated exactly once, and
each nested update targets the expected field of the same local root. Values are
translated in property order and the object is built in row order; the old
record is never mutated. Partial updates, open rows and mismatched paths keep
the existing clone-and-assign path. This is a bounded case of R5, not a general
alias proof; see the [record measurement](../../audit/2026-10-19/record-updates/report.md).

`ThunkFusion` accepts a `Unit -> Int` seed whose body is either an integer
literal or a typed read of an already evaluated local. The scanner tracks
parameters and strict `Let`/`EffectBind` results by name and level; recursive
locals and the seed's own parameter are excluded. A dynamic depth uses a private
guard with the original builder as its negative fallback. That fallback keeps
construction and force as **nested applications**: the fusion scanner recognizes
only flattened consumers, so repeated scans cannot fuse their own fallback.
The PHP emitter may flatten these calls after this pass. Any extension of the
candidate application shapes must preserve that idempotence contract. See the
[captured-seed audit](../../audit/2026-10-05/scalar-seeds/report.md) and
[`thunk-fusion.mjs`](../../tests/codegen/direct/thunk-fusion.mjs).

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
records are unchanged. See [`state-fusion.mjs`](../../tests/codegen/direct/state-fusion.mjs),
the executable `ImmediateStateFusion` fixture and the
[integration measurements](../../audit/2026-10-06/state-fusion/report.md).

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
See [`callback-specialization.mjs`](../../tests/codegen/direct/callback-specialization.mjs),
the executable `NativeFoldCallbacks` fixture and the
[integration audit](../../audit/2026-10-06/callback-specialization/report.md).

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
as a partially modeled loop. See [`array-callbacks.mjs`](../../tests/codegen/direct/array-callbacks.mjs),
`NativeArrayCallbacks` and the [Array integration audit](../../audit/2026-10-06/array-callbacks/report.md).
