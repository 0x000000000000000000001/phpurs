# Working on the compiler

The backend is a PureScript program compiled to JavaScript and run by Node.js.
The programs it translates are compiled to enriched CoreFn by the TAST fork.
This page is the hub; the detailed contracts live in the pages below.

| Page | Contents |
| --- | --- |
| [pipeline.md](compiler/pipeline.md) | Two compiler roles, architecture, pass order and the generated source map |
| [passes.md](compiler/passes.md) | Contract cards for the eleven local passes |
| [lowering.md](compiler/lowering.md) | Expression translation and PHP AST traversal contracts |
| [printing.md](compiler/printing.md) | Expression, declaration and module printing |
| [emission.md](compiler/emission.md) | File emission, entrypoints, owned outputs and aliases |
| [diagnostics.md](compiler/diagnostics.md) | Missing foreign exports and build diagnostics |
| [source-map.md](compiler/source-map.md) | Generated module-to-responsibility map |

## Build and run

- **Build phpurs:** `npm run build` compiles `src` into `output` and bundles
  `Main` into `bin/phpurs.js` with the toolchain identity and module cache.
- **Build a PHP program:** put the TAST-capable `purs` on `PATH`, then run the
  application's Spago build. Its `corefn.json` must include `dataDecls`,
  `classDecls` and `typeTable`.

Keep npm's local compiler selection scoped to the host build. Exporting this
checkout's `node_modules/.bin` into the application environment selects the
wrong `purs` for PHP generation.

The local optimizer dependency and sibling layout are described in the
[README](../README.md#build-the-backend) and
[development.md](development.md).

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
