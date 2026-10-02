# Profiling the PBO module cache (B2)

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
It budgets **64 MiB of serialized payload sizes**, which is a proxy for retained
data rather than a bound on JavaScript heap or process RSS. Reads refresh recency;
writes replace the module's previous contribution. `trimPurmetaCache` evicts the
oldest entries at module boundaries. The cache can exceed the budget while a
module is being processed, and an oversized entry is removed at the next trim.

`beginPurmetaBuild` empties RAM and starts a new current-build membership set.
Only modules already published in that build can be read. Files live under
`.purmeta/<Module>.purmeta` relative to the working directory, including when the
PHP output directory is customized. An explicit `clearPurmetaCache` empties RAM
while preserving that membership and therefore access to current-build disk data.

Requests count calls reaching `readPurmetaSync`, after the builder's local
implementation-lookup memoization. They do not count every reference in the AST.
Diagnostics cover one JavaScript process and the operations routed through this
PBO cache implementation.

## Report schema 1

The JSON report has `schema: 1` and
`policy: { kind: "lru-module-boundary", maxSerializedBytes: 67108864 }`.
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

## Validation

`tests/codegen/purmeta-profile.mjs` checks full compilation, complete state-cache
hits, grouped arguments and failure reporting against an independent filesystem
probe. It compares generated bytes and executes modular and bundled PHP (`43`).
PBO's `test/purmeta-stats.mjs` checks exact outcomes/bytes, scope resets, transparent
enable/disable, boundary peaks/evictions, replacement accounting and error paths.
The existing LRU, current-build membership and implementation-lookup tests cover
the policy exercised by those counters.

The [b8x assay](../audit/2026-10-02/purmeta-profile/report.md) retains repeatable
commands, independent I/O counts, logical counter consistency, timing/RSS samples
and output comparisons against the previous B1 reference.
It also checks fresh/restored `.purmeta` payloads as decoded graphs: V8 can encode
equal values with different bytes, so equal byte totals across those two paths
are not an invariant. Each path's I/O counters must still match its actual files.
