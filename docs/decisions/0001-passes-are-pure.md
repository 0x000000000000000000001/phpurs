# ADR-001 — Passes are pure module rewrites

- Status: accepted
- Date: 2026-10-31
- Context: local passes rewrote `BackendModule` values while also planning PHP
  declarations, and each pass repeated its own traversal and size guards.
- Decision: a pass that rewrites the TAST has the shape
  `BackendModule -> { module_, privateNames }`, performs no IO, never mutates its
  input, and reports the private names it introduced. `Phpurs.Pass` owns the
  shared guard, binding traversal, scan budget and private-name convention;
  `Phpurs.Budget` owns the numeric valves.
- Alternatives: a pass monad with logging and IO was rejected because it makes
  ordering and cache identity harder to reason about; mutating the module in
  place was rejected because cache restoration reuses the same values.
- Consequences: a pass is testable from a plain AST fixture, composition is a
  table, and private names are explicit. Passes that also emit declarations
  (`ArrayCallbacks`, `EnumRegions`) keep extended result records.
- References: `src/Phpurs/Pass.purs`, `docs/compiler/passes.md`,
  `tests/codegen/direct/pass-protocol.mjs`.
