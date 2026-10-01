# Module-cache keys (B1)

[`Phpurs.CacheKey`](../src/Phpurs/CacheKey.purs) defines content-based eligibility
for a future cached module state. `planKeys` is a pure calculator used by the
invalidation checks and the frozen-input audit. `Main.onSkipModule` currently
returns `Nothing`; state persistence, restoration and activation are subsequent
B1 steps.

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

For the bundled backend, `toolchain.backend` identifies an immutable copy of
`bin/phpurs.js`. A development invocation through separate compiled modules must
fingerprint its entire loaded code/dependency manifest instead. Package versions
or Git revisions alone do not distinguish local source/FFI edits. A fingerprint
must identify the code actually loaded, rather than a file subsequently replaced
by another build.

The future caller must hash and decode the **same captured CoreFn bytes**. Reading
the file again after decoding could associate one version's key with another
version's module. The same rule applies to FFI: resolve with `PackagePaths` before
each lookup, capture the winning file once, and derive both the fingerprint and
the source passed to `genForeignModule` from those bytes. Existing misses must be
resolved again so a newly created higher-priority file can invalidate the entry.
`NoForeign` follows the existing empty-foreign shortcut and requires no lookup.
PBO currently preserves the CoreFn foreign map in its backend module.

Effective options come from the driver's interpretation of the CLI. Today,
`rewriteLimit` is 10,000, `emitBundle` combines the bundle flags, and `emitModules`
is disabled by `--bundle-only`. Equivalent spellings of raw flags need not be
included when they produce the same effective values. In contrast, paths are
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
`Left` before any plan is returned. This includes cycles. A future cache caller
must use the ordinary build for an unsupported plan. The current b8x corpus is
accepted in PBO's actual order, including its self imports and absent built-ins.

The [b8x assay](../audit/2026-10-01/cache-key/report.md) finds 2,684 equal keys
without changes. Its leaf edit changes 833 keys for one changed module PHP;
its shared-dependency edit changes 1,850 keys for 30 changed module PHP files.
All observed changed outputs are covered. These counts describe conservative
key invalidation, not cache hits or avoided compilation work.

## Cache-entry responsibilities

A matching key will only be usable together with a valid, versioned payload.
The next B1 step must restore optimizer implementations/directives and the
imports, arities and bundle contribution consumed by `Main`, preserving the
builder order on hits as well as misses. Generated PHP must still be emitted
through content comparison so missing or damaged outputs are restored.

Composer manifests are collected independently during finalization. This key
identifies module/compiler state; it does not authorize reuse of a generated
Composer manifest or validate installed runtime dependencies.
