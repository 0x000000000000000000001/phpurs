---
name: FFI contract
about: A hand-written PHP file hits an undocumented boundary or a missing helper
labels: ffi
---

## Boundary

<!-- Inline fragment, `$exports` shape, `__DIR__`, records/variants, Effect,
     EffectFnAff, cancellation, replay, Composer/test helpers. -->

## Application code

<!-- Minimal `.php` and the `foreign import` it implements. -->

## Expected contract

<!-- What phpurs should document, validate at build, or provide at runtime. -->

## Checks

- [ ] The limitation is not already described in `docs/ffi-boundaries.md` or
      `docs/decisions/0002-ffi-is-an-inline-fragment.md`.
- [ ] The report says whether the failure happens at build, module
      initialization, or call time.
