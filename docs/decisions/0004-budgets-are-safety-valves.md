# ADR-004 — Budgets are safety valves

- Status: accepted
- Date: 2026-10-31
- Context: each pass bounded its scan with local numeric literals (1,024,
  8,192, 32,768, 131,072, 32, 64, 128, 96…). The same-looking number could mean
  different things, and a pass could scan eight times its advertised proof
  budget.
- Decision: every pass budget is named, justified and defined once in
  `src/Phpurs/Budget.purs`. A pass must not hard-code a threshold. Changing a
  value requires a boundary test and a new ADR when the change alters output;
  moving a value must not change its meaning.
- Alternatives: per-pass constants were rejected because they drift and because
  tests import them from different modules; a single global cap was rejected
  because node counts, depth, width, fuel, workers and copies measure different
  risks.
- Consequences: `check:conventions` rejects large numeric thresholds outside
  `Budget.purs`, and `docs/compiler/passes.md` lists the valves used by each
  pass.
- References: `src/Phpurs/Budget.purs`, `tools/check-conventions.mjs`,
  `tests/codegen/direct/pass-protocol.mjs`.
