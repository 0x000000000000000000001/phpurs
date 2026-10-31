# Pass contract cards

Each local pass is a pure module rewrite. Cards below record what the pass
proves, which safety valves bound it, what it leaves untouched when the proof
fails, and the regression that owns the contract. Budget values live in
`src/Phpurs/Budget.purs`; `src/Phpurs/Pass.purs` owns the shared protocol.

| Pass | Stage | Responsibility | Test |
| --- | --- | --- | --- |
| `PartialBindings` | TAST | Reuse partial applications inside closed, immediately consumed chains | `partial-bindings.mjs` |
| `ThunkFusion` | TAST | Fuse an immediately forced chain into a scalar TCO worker | `thunk-fusion.mjs` |
| `StateFusion` | TAST | Execute a proven State chain in a private scalar loop | `state-fusion.mjs` |
| `CallbackSpecialization` | TAST | Copy a proven list fold at a native Int callback | `callback-specialization.mjs` |
| `ArrayCallbacks` | TAST | Specialize contracted Array traversals | `array-callbacks.mjs` |
| `EnumRegions` | TAST | Prove closed regions and private scalar/nullable layouts | `enum-regions.mjs`, golden `adt` |
| `TailInline` | PHP AST | Simplify terminal control flow and inline eligible leaves | `tail-inline.mjs` |
| `NullableConstructors` | PHP AST | Lower proven private empty constructors to `null` | `nullable-constructors.mjs` |
| `CopyCleanup` | PHP AST | Remove redundant local copies after inlining | `copy-cleanup.mjs` |
| `ArrayRefs` | PHP AST | Promote owned parameters to references; rewrite rebuilds | `array-refs.mjs`, `array-refs-ownership.mjs` |
| `CompactLoops` | PHP AST | Compact eligible recursive functions into loops | `compact-loops.mjs` |

All paths are under `tests/codegen/direct/`. The golden corpus (`tests/golden/`)
freezes the printed output of representative cases.

## PartialBindings

- **Proves**: a producer grammar (neutral, successor, composition), a scalar
  unary callback over `Int`, distinct levels, and a bounded original body.
- **Rewrites**: adds private chain copies and rewrites saturated consumers.
- **Budgets**: `inputNodes`, `successorNodes`, `compositionNodes`, `scanNodes`,
  `stateFuel`, `workerLimit`.
- **Fallback**: unresolved producers and consumers keep the original code;
  a rescan is idempotent.

## ThunkFusion

- **Proves**: a closed `Unit -> Int` builder forced immediately, with a literal
  or already evaluated local seed; recursive locals and the seed parameter are
  excluded.
- **Rewrites**: a private scalar TCO worker; dynamic depths get a private guard
  whose negative fallback is the original builder.
- **Budgets**: `proofNodes`, `scanNodes`, `stateFuel`, `workerLimit`,
  `boundedDepth`, `boundedWidth`.
- **Fallback**: the original builder stays as nested applications so repeated
  scans cannot fuse their own fallback.

## StateFusion

- **Proves**: the two-field `Unit`/`Int` layout, a zero case returning the input
  state, a same-module recursive modifier, and a closed unary `Int` callback.
- **Rewrites**: a private scalar loop plus a guard for dynamic depths.
- **Budgets**: `proofNodes`, `scanNodes`, `stateFuel`, `workerLimit`,
  `boundedDepth`, `boundedWidth`.
- **Fallback**: record results, value projections, retained states, unknown
  callbacks and foreign helpers keep the original path.

## CallbackSpecialization

- **Proves**: a same-module generic left fold (empty returns the accumulator,
  nonempty applies the callback then recurs), with constructor field positions
  from declarations and a native binary `Int` callback tied to exact source
  SHA-256 and signatures.
- **Rewrites**: one private copy per fold/callback pair, calling the existing
  native wrapper; the return check still catches overflow.
- **Budgets**: `proofNodes`, `scanNodes`, `stateFuel`, `workerLimit`, `dataDecls`.
- **Fallback**: invalid constructors delegate to the original fold, preserving
  its failure message and source location.

## ArrayCallbacks

- **Proves**: exact FFI source SHA-256 and signatures for `Data.Foldable.foldlArray`
  and `Data.Array.filterImpl`, a native binary `Int` callback, a literal `Int`
  seed, and the bounded predicate grammar for filters.
- **Rewrites**: private PHP templates for increasing-index folds and
  insertion-order `foreach` filters; the filter result is materialized before a
  following fold.
- **Budgets**: `inputNodes` (predicate), `scanNodes`, `stateFuel`, `workerLimit`,
  `dataDecls`.
- **Fallback**: non-arrays and non-list PHP arrays delegate to the original FFI,
  including iterator objects and sparse-index diagnostics.

## EnumRegions

- **Proves**: closed, monomorphic local layouts with scalar or ADT fields, and
  every use inside the region; workers, layouts and attempts are bounded.
- **Rewrites**: private scalar/nullable layouts, constructor copies and call
  sites; the public layout is untouched.
- **Budgets**: `regionNodes`, `regionDataDecls`, `constructorWidth`,
  `inferDepth`, `attempts`, `workerLimit`, `stateFuel`.
- **Fallback**: public constructors, higher-order uses, open layouts, records
  passed externally and any failed inference keep the original representation.

## TailInline

- **Proves**: a native function body made of leaf nodes, with typed PHP
  parameters/returns excluded from inlining.
- **Rewrites**: terminal control flow (`if`/`switch` returns and joins) and
  eligible leaf bodies at bounded call sites.
- **Budgets**: `inlineBody`, `inlineCaller`.
- **Fallback**: public entry points and non-leaf bodies are unchanged.

## NullableConstructors

- **Proves**: a binary empty/payload layout for a **private** constructor whose
  complete uses were checked by `EnumRegions`.
- **Rewrites**: the empty constructor to `null`; the payload stays boxed.
- **Budgets**: `identityNodes`, `identityDepth`, `identityWidth`.
- **Fallback**: public layouts and arbitrary PHP values are never tagged.

## CopyCleanup

- **Proves**: a `pureResult` allowlist (variables, literals, field reads,
  binops, unary ops and generated constructors) and supported children, never
  across a scope boundary.
- **Rewrites**: removes local copies and replaces their reads.
- **Budgets**: `copyNodes`, `copyDepth`, `copyWidth`.
- **Fallback**: opaque PHP, calls and nested closures keep the copy.

## ArrayRefs

- **Proves**: ownership at every call site (last use, owned field or consumed
  TCO register) and safe field observations before a rebuild, through a
  conservative fixpoint.
- **Rewrites**: owned worker parameters become PHP references; a rebuild from
  the node's own fields becomes an in-place update.
- **Budgets**: call-site and fixpoint bounds are local to the analysis; see
  `ArrayRefs/Analysis.purs` and `ArrayRefs/Ownership.purs`.
- **Fallback**: another alias, an observation after mutation, a shared
  constructor child or an unproven call site keeps the original code.

## CompactLoops

- **Proves**: a local flow through exactly one TCO loop, with captured names
  tracked until a fixed point and a bounded body.
- **Rewrites**: an eligible recursive function becomes a `PhpCompactLoop`.
- **Budgets**: `compactBodyNodes`, `compactCaptures`.
- **Fallback**: raw PHP, tainted values, nested closures and non-converging
  captures keep the original function.
