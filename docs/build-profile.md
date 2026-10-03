# Backend phase profiling

Use `--profile-build` to emit one versioned JSON report on stderr after a backend
invocation:

```sh
phpurs --main App.Main --bundle --no-cache --profile-build
phpurs --main App.Main --bundle --profile-build --profile-purmeta
```

The first command measures a full optimization. With the default persistent
cache, a restored module skips optimization, PHP translation and module printing.
The second command can therefore describe a very different workload. The
`modules[].source` field distinguishes `optimized` and `cache`; `null` means the
invocation stopped before deciding that module's path.

The new report starts with `[phpurs] build-profile: `, followed by:

- `version: 1`;
- `status`: `completed`, `failed` or `cancelled`, the outcome of the profiled Aff
  action;
- `totalMs`: monotonic elapsed time for that action, excluding JSON formatting
  and emission of the report itself;
- `phases`: aggregate metrics for the scopes below;
- `modules`: per-module metrics for the same scopes, where a module is known.

Every metric contains `calls`, `completed`, `failed`, `cancelled`, `ms` and
`maxMs`. `calls` is the sum of the three outcome counters. `ms` is the sum of
elapsed times, and `maxMs` is the largest individual interval. Missing module
metrics mean zero calls; the aggregate phase table includes zero-valued entries.
No AST, source text or compiler state is kept in the profile.

## Scope boundaries

| Phase | Work inside the clock interval |
| --- | --- |
| `corefn.read` | Directory listing, directory/file stat probes and asynchronous CoreFn reads. Expected absent-file probes are handled outcomes. |
| `corefn.decode` | UTF-8 conversion, JSON/TAST parsing and hashing the captured input bytes. One call per file read. |
| `corefn.sort` | Building and sorting the decoded module dependency list. |
| `ffi` | Per-module FFI selection, reading, decoding and fingerprinting; no-foreign modules take the existing short path. |
| `cache.plan` | Selecting hooks and preparing the persistent-state key plan. |
| `cache.load` | Calling the per-module state-load hook, including validation/deserialization on the ordinary cache path. Disabled hooks also have a call. |
| `optimize` | From a state miss returned to the **sequential** PBO builder to entry into `onCodegenModule`: optimizer setup, conversion/optimization and directive accumulation. Cache lookup and PHP codegen are outside this interval. |
| `translate` | TCO/region lowering, PHP AST passes, FFI wrapper generation and combining arities. |
| `print` | Constructing module PHP strings for the selected emission families, plus entrypoint strings. Two output families are printed in one module call. |
| `php.mkdir` | The existing per-module output-directory creation attempt. |
| `php.write` | UTF-8 encoding, comparison with existing PHP, conditional write, and output-ownership registration. A call does **not** imply a physical write. |
| `cache.store` | Calling the module-state storage hook, including serialization and atomic publication when enabled. |
| `entrypoint` | Computing the modular entrypoint's reachability and module list. |
| `composer` | Reading package manifests, merging requirements and writing generated `composer.json`; this is not a Composer install/update process. |
| `cleanup` | Final output-ownership reconciliation, obsolete-file checks/removal and manifest publication. |
| `diagnostics` | Optional per-module AST counting and progress logging under `--verbose`. |

The existing stderr summaries (`load TAST + sort`, `prepare`, `optimize + emit`,
`finalize`, `backend total`) remain available in ordinary builds. Those outer
intervals include the detailed scopes: adding both sets would double count work.
Detailed scopes also do not constitute a complete partition of `totalMs`:
package-root discovery, some bookkeeping, PBO publication of `.purmeta`, and
profiler overhead remain outside them. The separate
[`--profile-purmeta` report](purmeta-profile.md) measures PBO cache I/O and
serialization, including operations inside `optimize`; its durations are not
additive to the build profile either.

With multiple loader jobs, asynchronous `corefn.read` intervals can overlap and
their sum can exceed loading wall time. Use `GOPURS_JOBS=1` for an additive
single-loader diagnostic. These are elapsed times, including scheduler delays
and garbage collection, not CPU-only measurements. String materialization for
UTF-8 output belongs to `php.write`, rather than necessarily to `print`.

## Diagnostics, lifecycle and reuse

`--verbose` enables the previous `Generating PHP code for ... (Total AST Nodes:
...)` and entrypoint progress lines on stdout. The AST-count traversal is complete
and skipped without this flag. It counts expression occurrences, including typed
wrappers and all literal/branch/primitive children; see the
[counting and rewrite-limit contract](optimizer-diagnostics.md). Errors and the
ordinary coarse summaries remain visible independently of `--verbose`.

Both flags support Spago's grouped arguments. They are observational and do not
participate in persistent-state keys. Enabling a profile after an ordinary cold
build can therefore measure a full state hit. On such a hit, module
optimization/translation/printing metrics are zero, while entrypoint printing,
output comparisons and PBO republication still execute.

Profiler state is explicit and local to each invocation. Pure work is supplied
as a thunk so it runs after the first clock read. Aff scopes wait for completion,
and close on success, error or Aff cancellation. The report includes completed
prefixes and a partial failed/cancelled optimization if codegen was never
reached. Original exceptions propagate. Nested invocations have independent
collectors; disabled profiling creates no collector or detailed clock samples.

## Validation

`tests/codegen/build-profile.mjs` covers three emission modes, grouped flags,
verbose/default logging, combined PBO profiling, persistent and mixed hits,
read/write failures, original exception identity, pure-work and asynchronous
timing boundaries, nested scopes and cancellation. Generated bytes and PHP
results are compared across the observational options. Audit scripts that count
codegen calls through progress lines now request `--verbose` explicitly.

The [B4 audit](../audit/2026-10-03/build-profile/report.md) compares frozen
before/after executables on b8x and checks the phase counters against an external
filesystem probe.
