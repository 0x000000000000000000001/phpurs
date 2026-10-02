# Profiling and configuring the PBO module cache (B2)

Use `--profile-purmeta` to collect diagnostics for the JavaScript PBO cache during
PHP optimization/emission:

```sh
phpurs --main Main --bundle --no-cache --profile-purmeta
```

After that phase, stderr contains one `[phpurs] purmeta: {...}` JSON line. It is
also emitted when optimization/emission fails, before propagating the original
error. Grouped Spago backend arguments support the flag. Profiling is observational
and does not partition PHPurs module-cache keys.

`--no-cache` forces ordinary optimization for every module and is useful for
measuring PBO lookups. A build restored entirely by PHPurs's persistent state cache
can have zero PBO lookup requests while still serializing and writing every
module's `.purmeta`. The `[phpurs] cache: N hits, M misses, S stores` line describes
that separate persistent state cache; see [its contract](cache.md).

## Current policy and scope

PBO's JavaScript implementation retains decoded module implementations in an LRU.
It defaults to **64 MiB of serialized payload sizes**, which is a proxy for retained
data rather than a bound on JavaScript heap or process RSS. Reads refresh recency;
writes replace the module's previous contribution. `trimPurmetaCache` evicts the
oldest entries at module boundaries. The cache can exceed the budget while a
module is being processed, and an oversized entry is removed at the next trim.

`beginPurmetaBuild` empties RAM and starts a new current-build membership set.
Only modules already published in that build can be read. Files live under
`.purmeta/<Module>.purmeta` relative to the working directory, including when the
PHP output directory is customized. An explicit `clearPurmetaCache` empties RAM
while preserving that membership and therefore access to current-build disk data.

Membership is empty at process startup as well, so a direct lookup before the
first builder invocation cannot consume residual files. The
[storage and invalidation contract](purmeta-storage.md) describes the unversioned
tagged-V8 payload, publication failures and the requirements for persistent reuse.

Requests count calls reaching `readPurmetaSync`, after the builder's local
implementation-lookup memoization. They do not count every reference in the AST.
Diagnostics cover one JavaScript process and the operations routed through this
PBO cache implementation.

## Configuring the budget

Use `--purmeta-cache-mib N` to override the LRU budget for one PHPurs invocation:

```sh
phpurs --main Main --bundle --no-cache --profile-purmeta --purmeta-cache-mib 16
```

- `N` is a non-negative decimal integer in MiB (1 MiB = 1,048,576 bytes). The
  resulting byte count must be an exact JavaScript safe integer. Missing,
  negative, fractional and out-of-range values fail before loading inputs or
  emitting files. The maximum accepted MiB value is `8589934591`.
- `--purmeta-cache-mib=16` and grouped Spago arguments are also accepted. Like
  the shared CLI parser, the first occurrence wins.
- `0` removes all retained entries at each module boundary. Reads/writes can
  still populate RAM within a module; subsequent reads in that module reuse it.
  Current-build `.purmeta` files provide the disk fallback after eviction.
- Omitting the option leaves PBO's current policy in place: **64 MiB** in a fresh
  process. `--purmeta-cache-mib 64` explicitly selects this default policy.
- The option works independently of profiling and PHPurs's persistent state
  cache. It does not change state-cache keys. `--no-cache` remains useful when
  evaluating the budget during full optimization.

The limit is still applied only at module boundaries. Oversized entries are
available within the module and evicted at its next trim; the limit is a
serialized-size accounting budget, not a heap/RSS cap.

[`Phpurs.PurmetaBudget`](../src/Phpurs/PurmetaBudget.purs) brackets optimization/
emission with the override, restoring the previous value on success, failure or
Aff cancellation. Scope exit also trims to the restored limit so a larger
override cannot leave excess retained data for the next caller. The optional
profile is emitted **before** restoration and describes only the configured
build, including its active budget and ordinary module-boundary trims.

PBO exposes `setPurmetaCacheBudgetBytes :: Number -> Effect Number`: it validates
a non-negative safe integer byte count and returns the previous budget. Setting
it does not evict immediately. `beginPurmetaBuild` resets contents and membership
while retaining this setting. Like the rest of the cache, the setting is
process-global and the scope assumes serialized build invocations. Other
JavaScript backends retain the existing default unless they opt in.

## Report schema 1

The JSON report has `schema: 1` and
`policy: { kind: "lru-module-boundary", maxSerializedBytes: 67108864 }` by default;
`maxSerializedBytes` reports the configured limit when overridden.
Counters and byte quantities use JavaScript numbers, avoiding 32-bit integer
truncation. Milliseconds are monotonic floating-point durations.

| Field | Meaning |
| --- | --- |
| `reads.requests` | Calls reaching the module cache. |
| `reads.blocked` | Requests rejected by current-build membership before RAM/disk lookup. This includes unpublished or absent modules, not just deleted ones. |
| `reads.ramHits`, `reads.ramMisses` | RAM outcomes for requests accepted by membership. |
| `reads.diskHits` | Successful disk read and reconstruction after a RAM miss. |
| `reads.diskMissing` | RAM misses whose disk file does not exist at the existence probe. |
| `reads.errors` | Failed disk reads or reconstruction, retaining the existing diagnostic/fallback behavior. |
| `reads.ioAttempts`, `reads.files`, `reads.bytes` | File-read attempts, completed reads and bytes returned. Bytes from a payload that subsequently fails decoding are included. |
| `reads.ioMs` | Time within synchronous file reads, including failed calls. |
| `reads.deserializations`, `reads.deserializeMs` | Reconstruction attempts and time, including V8 decoding and restoration of PureScript constructors. |
| `writes.attempts`, `writes.errors` | Publication attempts and failures; serialization or file errors still propagate. |
| `writes.files`, `writes.bytes` | Completed writes and their serialized buffer sizes. |
| `writes.ioMs` | Time within synchronous file writes, including failed calls. |
| `writes.serializations`, `writes.serializeMs` | Serialization attempts and time, including constructor graph traversal/cleanup and V8 encoding. |
| `ram.entries`, `ram.serializedBytes` | Retained entries and their serialized-size sum at the snapshot. |
| `ram.peakEntries`, `ram.peakSerializedBytes` | High-water marks after insertions/replacements, including within-module overshoot. |
| `ram.boundaryPeakEntries`, `ram.boundaryPeakSerializedBytes` | High-water marks immediately after trimming at module boundaries. |
| `ram.trimCalls`, `ram.evictions`, `ram.evictedBytes` | Trim calls, entries evicted by the budget and their serialized-size sum. |
| `ram.clearCalls`, `ram.clearedEntries`, `ram.clearedBytes` | Explicit RAM clears and their removed contents; a new build resets counters instead. |
| `memory.rssBytesAtStart`, `memory.rssBytesAtSnapshot` | RSS sampled at profiler/build initialization and snapshot. |
| `memory.processPeakRSSKiB` | OS high-water RSS for the process up to the snapshot, including phases preceding optimization. |

For completed ordinary requests:

```text
requests  = blocked + ramHits + ramMisses
ramMisses = diskHits + diskMissing + errors
```

The RAM hit rate uses `ramHits / (ramHits + ramMisses)`; membership rejections are
reported separately. I/O counts describe application buffers, not physical disk
transfers. I/O timings exclude directory/existence probes. The audit also samples
RSS on process exit to include finalization, which runs after the profile snapshot.

## Integration

PBO exposes `setPurmetaStatsEnabled :: Boolean -> Effect Unit` and
`readPurmetaStatsJson :: Effect String` in `PureScript.Backend.Optimizer.Cache`.
Enabling starts new counters without touching cache contents; each subsequent
`beginPurmetaBuild` resets them. Reading returns a detached snapshot, or JSON
`null` when disabled. Disabling drops the counters while retaining the cache.
Per-operation clocks are read only while profiling is enabled; RSS is sampled at
initialization and snapshot rather than on cache hits.

[`Phpurs.PurmetaProfile`](../src/Phpurs/PurmetaProfile.purs) brackets the builder
action, reports the snapshot, and disables profiling on success or failure.
Other JavaScript callers retain disabled diagnostics unless they opt in.

## Comparing budgets

The b8x audit can compare explicit limits with rotating order and repeated
samples. Freeze the standalone executable along with the inputs so concurrent
local builds cannot change the compiler being measured:

```sh
python3 audit/2026-10-02/purmeta-budget/verify.py \
  --snapshot /path/to/frozen-inputs \
  --reference-output /path/to/reference-output \
  --backend /path/to/validated-backend.mjs \
  --artifacts /path/to/new-directory \
  --budgets 16 64 128 --repetitions 3
```

One initial build prepares output files; subsequent samples all start with
identical output bytes and fixed PHP mtimes. Each uses a fresh Node process and
`--no-cache`. The comparison records min/median/max backend time, serialization,
deserialization, I/O time and whole-process peak RSS, plus deterministic PBO
counters for each budget. It checks those counters against independent I/O
probes, requires byte-identical outputs and preserves the input snapshot.

Choose a budget using the observed build cost and process RSS as well as the
read count. A larger retained serialized-size budget can save disk reads while
retaining more decoded objects; a smaller budget can increase allocation work
through repeated deserialization. The serialized-size limit alone therefore
does not predict the process's peak RSS.

The [three-budget b8x campaign](../audit/2026-10-02/purmeta-budget-comparison/report.md)
selects **128 MiB as an explicit option for full compilation of that corpus**.
Across three repetitions per budget, 128 versus 64 MiB reduces reads from 717 to
99 (80.13 to 10.34 MiB). Median backend time is 47.040 versus 52.191 s, with median
process peak RSS of 3,495.5 versus 3,361.8 MiB. The observed time difference is
specific to this short shared-machine campaign. The startup default remains
64 MiB; use `--purmeta-cache-mib 128` to opt in, and profile your own corpus when
choosing its budget.

## Validation

`tests/codegen/purmeta-profile.mjs` checks full compilation, complete state-cache
hits, grouped arguments and failure reporting against an independent filesystem
probe. It compares generated bytes and executes modular and bundled PHP (`43`).
PBO's `test/purmeta-stats.mjs` checks exact outcomes/bytes, scope resets, transparent
enable/disable, boundary peaks/evictions, replacement accounting and error paths.
The existing LRU, current-build membership and implementation-lookup tests cover
the policy exercised by those counters.

`tests/codegen/purmeta-budget.mjs` checks parsing, grouped/equal-sign arguments,
scope restoration after success/error/cancellation, and byte-identical executable
PHP at 0, 1, 16, 64 and 128 MiB. It also covers persistent state hits across budget
changes and mixed restored/fresh builds using the disk fallback. PBO's
`test/purmeta-budget.mjs` checks custom-limit LRU recency, zero-budget boundaries,
validation, exact large byte counts and current-build membership. The existing
LRU tests additionally cover oversized entries and replacement accounting.

The [b8x assay](../audit/2026-10-02/purmeta-profile/report.md) retains repeatable
commands, independent I/O counts, logical counter consistency, timing/RSS samples
and output comparisons against the previous B1 reference.
It also checks fresh/restored `.purmeta` payloads as decoded graphs: V8 can encode
equal values with different bytes, so equal byte totals across those two paths
are not an invariant. Each path's I/O counters must still match its actual files.

The [budget configuration assay](../audit/2026-10-02/purmeta-budget/report.md)
checks default, zero and explicit 64 MiB full compilations against the B1 b8x
reference: 5,372 identical output files, 2,686 preserved PHP mtimes, independent
I/O counts and identical logical counters for default versus explicit 64 MiB.
