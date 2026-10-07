# Module-cache keys and state (B1)

[`Phpurs.CacheKey`](../src/Phpurs/CacheKey.purs) defines content-based eligibility.
[`Phpurs.ModuleCache`](../src/Phpurs/ModuleCache.purs) persists versioned module
states, and [`Phpurs.ModuleState`](../src/Phpurs/ModuleState.purs) publishes both
fresh and restored states. The packaged CLI calls `Main.mainWithToolchain` and
enables caching by default. `Phpurs.BuildInputs` captures input bytes and
`Phpurs.BuildCache` connects the key plan to the store. `--no-cache` bypasses
both cache reads and writes, while retaining the same compilation/emission path.

Entries live in `<outputDir>/.phpurs-cache/v1`. An invocation reports
`[phpurs] cache: N hits, M misses, S stores` on stderr after successful
finalization. These count restored modules, normal compilations and successful
state writes, respectively. The directory is disposable and can be removed to
start cold; content-addressed entries from older inputs/options remain available.

## API and input capture

`planKeys :: BuildContext -> Array ModuleInput -> Either String KeyPlan` accepts
modules in the **same dependency order as PBO's builder**. It returns a context
fingerprint, one key per module and a final plan fingerprint. `Fingerprint` is an
opaque SHA-256 value; `fingerprintBytes` hashes immutable buffers and
`fingerprintString` exposes the lowercase hexadecimal representation.

| Input | Required identity |
| --- | --- |
| `toolchain.phpursVersion`, `pboVersion` | Versions/build revisions of PHPurs and PBO. |
| `toolchain.backend` | Digest of the actual executable compiler, including PBO, PHP passes, printer/runtime, FFI adapter code and host dependencies. |
| `nodeVersion`, `v8Version`, `platform`, `arch` | Host/serialization compatibility of the cached state. |
| `options` | Effective working/output paths, ordered FFI roots, emission booleans, main selection, optional autoloader path and rewrite limit. |
| `directives` | Digest of all initial directive input used by this invocation. |
| `ModuleInput.coreFn` | Digest of the complete raw typed CoreFn bytes, including types, usage metadata, paths, foreign signatures and module comments/directives. |
| `ModuleInput.foreignInput` | No foreign declarations, unresolved foreign source, or the selected source path plus its raw-byte digest. |
| `ModuleInput.dependencies` | Imported module names; self imports are ignored and other edges form a sorted set. |
| Module array order | Complete loaded graph membership and actual builder order. |

[`tools/bundle.mjs`](../tools/bundle.mjs) uses esbuild to package all compiler and
host-library JavaScript in a CommonJS source string inside the single executable
`bin/phpurs.js`. Only Node built-ins may remain external; the build rejects other
external imports. The startup function hashes an explicitly framed tuple of this
**in-memory source**, the PHPurs/PBO build identities and its own source, then
evaluates exactly that compiler source with `Function`. It passes the digest and
Node/V8/platform/architecture to `mainWithToolchain`. The on-disk executable is
never reread for its identity: replacing it during a build cannot relabel the
code already loaded. The PBO build label also includes a digest of its compiled
module inputs. Package versions or Git revisions alone would miss local edits.

The development `Main.main` entrypoint retains disabled hooks because separate
module imports do not supply an immutable executable identity. Explicit
`mainWithCache` hooks remain available for state-layer tests. Custom callers of
`mainWithToolchain` must provide an identity for the code actually loaded.

`BuildInputs.loadInputs` reads each CoreFn once and derives both its fingerprint
and decoded module from the **same captured bytes**. It keeps PBO's bounded
`GOPURS_JOBS` loading policy and uses PBO's sorter, preserving the module order.
It detects duplicate names before that sorter's module index could collapse them.
Incomplete/ambiguous input and unsupported key plans disable cache reuse for the
whole invocation, with a diagnostic and ordinary compilation.

Before lookup, `captureForeign` resolves every module with foreign declarations
using `PackagePaths`. The selected file is read once; both the raw fingerprint
and the UTF-8 source passed to `genForeignModule` derive from that buffer. This
also runs on hits and previous misses, so newly created higher-priority files
are considered. `NoForeign` retains the empty-foreign shortcut with no lookup.
PBO currently preserves the CoreFn foreign map in its backend module. Replacing
an input after its read affects the following invocation; it cannot associate a
new key with the previous bytes' compilation.

Effective options come from the driver's interpretation of the CLI.
`rewriteLimit` is the validated `--rewrite-limit` value, defaulting to 10,000;
the same value is passed to PBO. Changing it partitions the whole key plan.
An omitted flag, `--rewrite-limit 10000` and `--rewrite-limit=010000` share the
same effective value. See the [option contract](optimizer-diagnostics.md#rewrite-limit).
`emitBundle` combines the bundle flags, and `emitModules` is disabled by
`--bundle-only`. Equivalent spellings of raw flags need not be included when
they produce the same effective values. In contrast, paths are
retained as supplied by the driver/resolver: their spelling can affect lookup or
emitted PHP. The working directory must be absolute; the planner performs no
implicit filesystem or working-directory discovery.

Keep both `mainModule` and `autoloadPath` optional. In particular, no autoloader
option and an explicit `vendor/autoload.php` generate different fallback code in
`EntryPoint`. The key preserves that distinction and does not normalize those
paths. Ordered FFI roots are also preserved.

With today's `Main.loadDirectives`, the initial directive input is the UTF-8
`defaultDirectives` string. If a future caller combines directive sources, their
ordered, unambiguously framed contents must all participate. Module-local
directives are already part of each raw CoreFn fingerprint. Compiler-provided
foreign semantics and callbacks belong to the executable compiler identity.

## Versioned encoding

The JavaScript FFI performs SHA-256 and encodes explicit tuples; it does not
serialize arbitrary record property order, functions or optimizer objects.

```text
H(tag, ...fields) = SHA256(UTF8(JSON.stringify(
  ["phpurs/module-cache-key", 1, tag, ...fields]
)))

T = [phpursVersion, pboVersion, backend,
     nodeVersion, v8Version, platform, arch]
O = [cwd, outputDir, ffiRoots, emitModules, emitBundle,
     mainModule-or-null, autoloadPath-or-null, rewriteLimit]
C = H("context", T, O, initialDirectivesDigest, orderedModuleNames)
P₀ = H("prefix-start", C)

Iᵢ = H("input", moduleName, coreFnDigest, foreignDescriptor)
Dᵢ = sorted [dependencyName, earlierDependencyKey-or-null] pairs
Kᵢ = H("module", C, Iᵢ, Pᵢ, Dᵢ)
Pᵢ₊₁ = H("prefix-step", Pᵢ, moduleName, Kᵢ)

planKey = H("plan", C, Pₙ)
```

Foreign descriptors are `['none', null, null]`, `['missing', null, null]` or
`['source', selectedPath, contentDigest]`. An empty file therefore differs from
an absent file, and changing the winning path differs even when its bytes match.
Absent imported modules, including the compiler-provided `Prim.*` modules, use
the explicit null dependency marker. Their later appearance changes the graph
context; built-in semantics are covered by the toolchain fingerprint.

The key schema is **v1**. Change its version when changing the encoding or the
cache-state compatibility contract. Exact bytes determine input identity;
file length and timestamps never substitute for their digest.

## Why keys include the preceding module stream

The serial PBO builder accumulates directives and private-global information
from every preceding module. PHPurs similarly accumulates arities before printing
the next module. Imported dependency keys alone would omit these shared inputs.

The v1 prefix chain conservatively covers that entire preceding stream. Within
the same graph/context, changing a module's CoreFn or FFI invalidates that module
and every successor. Earlier keys stay equal. Adding/removing/reordering modules
changes the graph context and invalidates all module keys.

Duplicate/empty module names and dependencies on later loaded modules return
`Left` before any plan is returned. This includes cycles. `BuildCache` uses the
ordinary build for an unsupported plan. The current b8x corpus is
accepted in PBO's actual order, including its self imports and absent built-ins.

The [b8x assay](../audit/2026-10-01/cache-key/report.md) finds 2,684 equal keys
without changes. Its leaf edit changes 833 keys for one changed module PHP;
its shared-dependency edit changes 1,850 keys for 30 changed module PHP files.
All observed changed outputs are covered. These counts describe conservative
key invalidation, not cache hits or avoided compilation work.

## Persisted state

`ModuleState` stores the complete `BackendModule`, a per-module arity map, and
optional modular/bundle PHP strings. Its responsibilities are:

| Field | Used after restoration |
| --- | --- |
| `backend.name`, `imports`, `implementations` | Rebuild `Main`'s module map for reachability and modular entrypoints. PBO republishes the implementations as current-build `.purmeta` for newly optimized consumers. |
| `backend.directives` | PBO folds them into the directive environment before the next module, including exported never-inline/arity directives. |
| Other `BackendModule` fields | Preserve the full typed PBO contract: comments, bindings, data/class declarations, data types, exports/re-exports and foreign signatures. |
| `arities` | Restore this module's contribution to `globalAritiesRef`, including the `foreignValueArity` marker (`-1`) for demand-checked foreign values. Foreign entries take precedence over generated ones; the current module takes precedence over earlier modules. |
| `modularPhp :: Maybe String` | Emit the already printed module through byte-exact content comparison, repairing absent or damaged output. |
| `bundlePhp :: Maybe String` | Append the already printed contribution, with the same separator and order as a fresh build. |

`renderModuleState` translates and prints only the selected emission forms. The
arity map stored in the entry contains the **local contribution**, not the whole
preceding environment. The v1 key identifies that preceding environment, which
was used when printing the cached strings.

`publishModuleState` is shared by `onCodegenModule` and `onSkipModule`. Call it
**once per module, in builder order**. It checks the requested emission forms
before touching refs or output, then restores `globalAritiesRef`, the reachability
map when emitting modules, and `bundleContentRef` when emitting a bundle. The
initial bundle header comes from `newBuildRefs`, not from each cached contribution.
Output read/write errors propagate normally and must not become cache misses.

On a hit, PBO skips `onCodegenModule` but still writes `.purmeta`, accumulates the
cached directives and derives private globals from the current decoded CoreFn.
CoreFn loading and entrypoint/Composer finalization therefore still run. The
cached state does not replace PBO's current-build purmeta lifecycle.

The underlying `.purmeta` files have no PBO file-protocol version, input key or
integrity envelope. Process startup and every builder invocation begin with
empty publication membership; residual files stay unreadable until rewritten
in that scope. See the [storage and invalidation contract](purmeta-storage.md)
for the exact path, codec and boundaries of this scratch storage.

For that underlying PBO cache, `--profile-purmeta` reports RAM outcomes, `.purmeta`
I/O, serialization timings and RSS independently of the PHPurs state-cache
counters. Combine it with `--no-cache` to observe full optimization. See the
[PBO profiling contract](purmeta-profile.md).

`--purmeta-cache-mib N` independently configures PBO's decoded-module LRU, using
serialized sizes in integer MiB (default 64; `0` trims all entries at module
boundaries). It does not partition the persistent module-state keys: restored
implementations are republished into the current PBO build and remain readable
from disk after a RAM eviction. See the [budget contract](purmeta-profile.md#configuring-the-budget).

## Storage protocol v1

The caller supplies the cache directory and a `Fingerprint` from its key plan:

```purescript
loadModuleState :: CacheRequest -> Effect (Maybe ModuleState)
saveModuleState :: String -> Fingerprint -> ModuleState -> Effect Boolean
```

`CacheRequest` contains `directory`, `key`, `moduleName` and `emission`. Files are
stored as `<directory>/v1/<64-lowercase-hex-key>.bin`; module names are never used
as storage paths. Each entry contains:

1. The ASCII magic/version line `PHPURS-MODULE-STATE 1\n`.
2. One JSON header line containing the key, module name and payload SHA-256.
3. A V8-serialized, flat graph table for the module state.

The graph codec restores PureScript constructor prototypes via an explicit
registry of PBO and collection modules. JSON alone, or plain `v8.serialize` on
the original objects, loses the prototypes required by pattern matches. The
codec preserves shared objects and numeric values, traverses deep expressions
iteratively, and never annotates/mutates the live optimizer nodes. Function and
symbol values, and unregistered object prototypes, cannot be silently persisted.
The private PBO `.purmeta` codec is a different format and is not imported or
modified by this layer. Node/V8/platform/architecture and the compiler code
identity are already part of the key's compatibility contract. The payload
encoding itself need not be byte-canonical; only the key protocol is canonical.

Loading checks magic/version, key and module identity, payload checksum, graph
references/tags, top-level state shape and requested emission forms. Missing,
unreadable, incompatible, truncated or corrupt optional data returns `Nothing`.
Saving writes a uniquely named sibling temporary file and renames it over the
entry only after the full write and close. Failed writes return `false`, clean
up the temporary file and retain any previously published complete entry.

`Main.mainWithCache` accepts `{ load, store }` hooks. Its caller is responsible
for correct input capture/key planning; supplying hooks is not an automatic
invalidation policy. The state checks also reject the wrong module or an
incomplete emission before publishing. Fresh states are stored after successful
publication. These explicit hooks are separate from the CLI's automatically
planned `BuildCache` session.

## Output lifecycle and main selection

[`Phpurs.OutputManifest`](../src/Phpurs/OutputManifest.purs) maintains
`<outputDir>/.phpurs-outputs.json` independently of the optional module-state
cache. It runs for fresh modules, restored modules, development entrypoints and
`--no-cache`. `writeOutput` waits for byte-exact conditional emission, then records
the relative PHP path, output family (`modules` or `bundles`) and raw-byte SHA-256.
An unchanged file is recorded as well, so a full-hit build has complete ownership.

After entrypoints and Composer have finalized, the tracker compares the current
paths with the previous **version 1** manifest:

- For an active family, a previously recorded path absent from the current build
  is obsolete. It is removed only if it is still a regular file with identical
  bytes. Replaced file/directory symlinks and user-edited files are preserved and
  relinquished from ownership. Untracked files are preserved too.
- Disabled families retain both their files and their ownership records. Thus a
  `--bundle-only` build preserves modular outputs, and a modular-only build
  preserves bundles. Re-enabling a family allows its obsolete files to be retired.
- Incomplete or duplicate CoreFn inputs defer deletion and retain previous records
  alongside successfully emitted outputs. A failed optimization/emission never
  reaches cleanup. An empty, complete graph can retire all previous module and
  executable-entrypoint files; bundle mode still emits the empty global bundle.
- Missing, malformed, incompatible or structurally invalid ownership data supplies
  no deletion candidates. Only fixed compiler PHP path shapes are accepted;
  arbitrary paths, CoreFn and foreign sources cannot be cleanup targets. Current
  outputs establish new records, while unknown historical files stay untracked.
- Sorted records make unchanged manifests byte-stable. Publication uses a unique
  sibling temporary file and atomic rename. Other manifest/cleanup I/O errors
  fail the build and retain the previous manifest for retry. This atomicity covers
  the manifest, not the entire output tree: finalization may already have emitted
  or retired individual files before an error.

Module membership is defined by loaded CoreFn inputs. Removing only a `.purs`
source while leaving its CoreFn cannot signal deletion to this backend. Module
directories and their other files are retained. Content-addressed cache entries
remain reusable when returning to a previous graph/options; PBO's existing
current-build membership prevents reading stale `.purmeta` implementations.

`Main` validates an explicit `--main` during preparation: the module must be
loaded and export `main`. Invalid selections fail before module emission. Without
the flag, all current exported mains are selected. Changing that set retires old
entrypoints in active families; selecting an explicit main also retires a tracked
global `bundle.php`. Finalization rebuilds reachability and Composer requirements
from the current graph, including on full cache hits.

## Validation

`tests/codegen/module-cache.mjs` checks constructor/sharing round trips, a
20,000-level expression, arity precedence, emission completeness, damaged entries
and atomic publication failure. Fresh Node processes run the actual driver with
disabled, cold, full-hit and mixed hooks in all three emission modes. A cached
dependency supplies implementations, exported directives and PHP/FFI arities to
fresh consumers; the modular and bundled programs both print `43`. The checks
also remove `.purmeta`, preserve identical PHP mtimes, repair missing/damaged
outputs and verify that output errors still fail the build.

The [b8x state assay](../audit/2026-10-01/module-state/report.md) round-trips all
2,684 modules, then verifies full restoration, a mixed cached/fresh build and
repair of missing/damaged outputs against 5,371 uncached files. It uses explicit
hooks on frozen inputs.

`tests/codegen/cache-cli.mjs` additionally exercises the packaged executable with
default caching and `--no-cache`: all emission modes, grouped arguments, bounded
parallel reads, exact-byte/dependency/directive/FFI invalidation, effective
options, compiler and host identities, missing/corrupt entries, unavailable
storage and unsupported graphs. It replaces CoreFn, FFI and the executable on
disk during a read callback to verify the capture contract. Differential builds
check file sets and bytes; executable fixtures verify the changed PHP results.

Its lifecycle cases delete CoreFn while retaining old generated files, switch
between explicit and automatically discovered mains, remove a main export and
reach an empty graph. Fresh `--no-cache` controls start with only current CoreFn
at the same paths; file sets, PHP, Composer and ownership bytes agree, and modular
and bundled entrypoints execute with the expected results. Further cases cover
disabled-family preservation/resumption, custom output paths/grouped arguments,
invalid main selection, incomplete/duplicate input, user edits, symlinks, damaged
metadata, cleanup errors and atomic publication failure.

The [CLI activation assay](../audit/2026-10-02/cache-activation/report.md) verifies
2,684 actual hits on unchanged b8x input and the predicted 833/1,850 misses for
the M0 leaf/dependency changes. Every state matches its uncached control's 5,371
files. It retains single-run timing, memory and I/O samples alongside the exact
module lists; those samples are not a repeated performance benchmark.

The [lifecycle assay](../audit/2026-10-02/cache-lifecycle/report.md) extends this to
b8x main selection and module deletion, with fresh uncached controls and a return
to the original cached graph/options.

Composer manifests are collected independently during finalization. This key
identifies module/compiler state; it does not authorize reuse of a generated
Composer manifest or validate installed runtime dependencies.
