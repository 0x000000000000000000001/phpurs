# Documentation

Entry points for building, changing and operating the PHP backend. `README.md`
is the project overview; `todo.md` is the maintainer's working backlog (French).

## Getting started

- [README](../README.md) — features, prerequisites and application builds.
- [Development](development.md) — build, tests, CI, cleaning and refactor rules.
- [Contributing](../CONTRIBUTING.md) — setup and recipes.
- [Conventions](conventions.md) — sizes, names, budgets, tests, docs and commits.
- [Libraries](libraries.md) — building and testing the sibling `phpurs-*` packages.

## Architecture

- [Backend architecture](architecture.md) — pipeline, outputs, cache and invariants.
- [Compiler](compiler.md) — hub for the compiler contracts.
- [Pipeline and passes](compiler/pipeline.md) — roles, architecture, pass order.
- [Pass cards](compiler/passes.md) — proof, budgets, fallback and test per pass.
- [Lowering](compiler/lowering.md) — expression translation and AST traversal.
- [Printing](compiler/printing.md) — expression, declaration and module printing.
- [Emission](compiler/emission.md) — entrypoints, owned outputs and aliases.
- [Diagnostics](compiler/diagnostics.md) — missing foreign exports and build errors.
- [Source map](compiler/source-map.md) — generated module-to-responsibility map.

## Operations

- [Module cache](cache.md) — keys, persisted state and output lifecycle.
- [Build profile](build-profile.md) — phase spans and JSON output.
- [Purmeta profile](purmeta-profile.md) — PBO cache diagnostics.
- [Purmeta storage](purmeta-storage.md) — `.purmeta` format and invalidation.

## FFI and decisions

- [FFI boundaries](ffi-boundaries.md) — JavaScript modules called by the compiler.
- [Decision records](decisions/README.md) — ADR-001 to ADR-007.

## Tests and history

- [Codegen tests](../tests/codegen/README.md) — families, helpers and pass contracts.
- [Golden corpus](../tests/golden/README.md) — byte-for-byte PHP output.
- [Audit index](../audit/README.md) — historical measurements.
- [Changelog](../CHANGELOG.md) — unreleased changes.
