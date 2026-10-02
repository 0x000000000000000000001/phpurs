# PBO `.purmeta`: directory, format and invalidation

PHPurs uses PBO's JavaScript implementation cache during optimization. Its
`.purmeta` files are **scratch data for the current publication scope**. PHPurs's
validated cross-build reuse is provided by [module-state cache v1](cache.md).

## Storage paths

| Data | Path | Lifetime/identity |
| --- | --- | --- |
| PBO implementations | `<cwd>/.purmeta/<ModuleName>.purmeta` | Republished in each build; names retain dots, e.g. `Data.Map.purmeta` |
| PHPurs module states | `<outputDir>/.phpurs-cache/v1/<key>.bin` | Versioned, checksummed, keyed by captured inputs and compilation context |
| PHP output ownership | `<outputDir>/.phpurs-outputs.json` | Tracks generated output paths/families and byte fingerprints |

`--output`, `--main`, emission mode and `--purmeta-cache-mib` do not relocate
`.purmeta`. For example, a build from `/project` with `--output generated` uses
`/project/.purmeta/Data.Map.purmeta` and `/project/generated/Data.Map/index.php`.
The scratch directory is created lazily, overwritten module by module, and is
not swept at build start. Old files for removed modules can remain unread.

PBO's cache is process-global and looks up paths relative to the working
directory at each operation. Keep that directory stable for a build; independent
builds require separate working directories or serialized execution. After a
project/directory change in the same process, start a new builder invocation.

## Exact current format/version status

Each `.purmeta` contains a raw `v8.serialize` payload of the module's
`BackendImplementations` map. A recursive walk tags recognized PureScript
constructors with `__ps: "<prefix>$<constructor>"`; decoding reconstructs their
prototypes from the loaded PBO/library registry.

The current file format is **unversioned at the PBO level**. It has no PBO
magic/version envelope, module/compiler identity, input key or checksum. V8's
wire version provides no proof of optimizer compatibility. The profiling JSON's
`schema: 1` and PHPurs's `PHPURS-MODULE-STATE 1` header are separate protocols.

The codec is recursive, does not fully validate constructor fields or unknown
tags, and does not guarantee sharing of reconstructed constructor objects.
V8 bytes can also differ for equal values. Consequently neither successful
decoding, byte equality nor matching timestamps establishes cross-build validity.
The low-level implementation contract is documented in
`purescript-backend-optimizer-phpurs/docs/purmeta-cache.md`.

## Invalidation and restoration

1. A new process starts with empty publication membership. Reads of residual
   files are rejected even before the first builder invocation.
2. Both PBO builders call `beginPurmetaBuild` on every execution. It resets RAM
   and publication membership; it leaves files on disk.
3. A module becomes readable only after its implementations have been written
   successfully in this scope. Unpublished, absent and deleted modules are
   rejected before RAM lookup or filesystem probing.
4. RAM clear/eviction preserves membership, allowing current-build disk fallback.
   A new build invalidates membership again, even when inputs or filenames match.

PHPurs uses the sequential builder: fresh implementations are published after
`onCodegenModule`. A state-cache hit restores the complete validated module state
through `onSkipModule`; PBO then **rewrites** that module's `.purmeta` and publishes
it in the current build. The cached directives and the current CoreFn-derived
private globals also enter the builder context. This is why restored dependencies
remain usable by freshly optimized consumers after RAM eviction.

`--no-cache` bypasses PHPurs's state store while still creating `.purmeta` for
the current compilation. `--purmeta-cache-mib 0` evicts RAM at module boundaries
while retaining that disk fallback. Removing `.purmeta` between builds is valid;
it is regenerated from fresh or validated restored module states.

Disk writes are direct and synchronous. Write/serialization failures abort the
build; they do not authorize a new module's publication. A failed overwrite can
damage a scratch file. Published-file reads that are missing return `Nothing`;
read/decoding exceptions also return `Nothing` with a diagnostic. A decodable
file is assumed to be the current producer's data, without a separate integrity
or complete shape check.

## Conditions for any future cross-build `.purmeta` reuse

A future persistent protocol must define a versioned target/toolchain namespace,
module identity, an integrity-checked envelope, validated typed decoding and
atomic publication with independent-writer isolation. Invalidation must account
for compiler/PBO/Node/V8 compatibility, exact captured inputs, effective optimizer
options and directives/foreign semantics, dependencies, and preceding build
state that affects private/specialization names.

The [B1 key contract](cache.md#versioned-encoding) and complete module-state restoration
already address this context for PHPurs. A map of implementations alone cannot
replace the complete state required to skip a module. Any accepted persistent
entry must still be republished through the current builder path; the presence
of an old file must never itself add membership.

See the [B2 contract validation](../audit/2026-10-02/purmeta-contract/report.md)
for the startup regression and the checks covering fresh/restored builds.
