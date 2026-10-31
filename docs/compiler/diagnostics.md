# Diagnostics

## Missing foreign exports

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
Unused declarations and untaken branches are allowed. Compilation first selects
the union of CoreFn import closures rooted at the selected main module, or every
exported main when none is explicit. Modular entrypoints and bundles both use
that selected set; only a build with no discovered main keeps all loaded modules.
An initializer that actually reads a missing value counts as a demand whenever
its module is selected. This does not suppress errors or effects in supplied FFI
initialization code. Handwritten PHP that directly accesses `$GLOBALS` bypasses
generated value-read checks; present-value interoperability is unchanged.
See [`missing-ffi.mjs`](../../tests/codegen/ffi/missing-ffi.mjs) and the
[missing-FFI validation](../../audit/2026-10-06/missing-ffi/report.md).

After loading all CoreFn inputs, `Main` selects the union of CoreFn import
closures rooted at the target mains before `buildModules`. An explicit main is
validated first; automatic builds use every exported main. If no main is found,
all loaded modules are retained. Only selected modules are captured for FFI,
keyed in the persistent cache, optimized, translated, printed and written.
Composer and FFI package roots still describe the full loaded workspace, so a
reviewed Composer lock remains valid when only the optimized subset changes.
Obsolete outputs from a previous wider build are retired by the output manifest
after successful finalization.

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

With an explicit main, bundle-only emits its `main.bundle.php`, filtered to the
same CoreFn closure as `main.mod.php`. Without one, it emits a bundle entrypoint
for every exported main plus a global `bundle.php` for the union of their
closures. If no exported main exists, all loaded modules are retained. An
explicit main must be loaded and export `main`; `Main` checks this during
preparation, before module emission. Composer collection runs in every mode. The
mode selects the current writes and preserves the existing files of disabled
output families.

All four PHP write sites use `OutputManifest.writeOutput`: module
`index.php`, `main.mod.php`, `main.bundle.php`, `runtime.php` and the global
`bundle.php`.
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
with `--no-cache`; see the [detailed contract](../cache.md#output-lifecycle-and-main-selection).

The packaged CLI enables cache reuse by default; `--no-cache` compiles every
module without reading or storing cached states. `tools/bundle.mjs` embeds all
compiler JavaScript and hashes the same in-memory source that it evaluates,
passing its identity to `Main.mainWithToolchain`. Direct development imports
through `Main.main` use disabled hooks; `Main.mainWithCache` accepts explicit
hooks for state-layer tests. Both branches use `ModuleState.publishModuleState`
to update arities, reachability and bundle content in the same order and perform
conditional PHP writes. Entry points and Composer are finalized normally.

`Phpurs.CacheKey` supplies the pure v1 key planner. Its [cache contract](../cache.md)
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
[profiling and budget contract](../purmeta-profile.md) for units, scope and validation.

PBO's `.purmeta` data lives under the working directory and uses an unversioned
tagged-V8 scratch format. Reads require publication in the current scope,
including before the first builder call; every builder invocation resets that
membership. See the [storage/invalidation contract](../purmeta-storage.md) for its
relationship to PHPurs's versioned module-state store.
