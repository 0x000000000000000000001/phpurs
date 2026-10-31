---
name: Pass bug
about: A local optimization proves too much, refuses a valid case, or changes output
labels: bug, passes
---

## Pass

<!-- PartialBindings, ThunkFusion, StateFusion, CallbackSpecialization,
     ArrayCallbacks, EnumRegions, TailInline, NullableConstructors,
     CopyCleanup, ArrayRefs or CompactLoops. -->

## Input

<!-- Minimal TAST or PHP AST fixture, or the application module/function. -->

## Expected vs actual

<!-- The generated PHP (or the refusal) and what should happen. -->

## Checks

- [ ] Reproduced from a fixture in `tests/codegen/direct/` (or can be reduced to one).
- [ ] The pass card in `docs/compiler/passes.md` matches the observed behavior.
- [ ] Budgets were not changed to hide the symptom.
