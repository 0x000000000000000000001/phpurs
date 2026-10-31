# ADR-003 — The PHP runtime ABI is versioned

- Status: accepted
- Date: 2026-10-31
- Context: hand-written PHP reads representations directly: `->type`/`->value`
  for variants, `stdClass` for records, callbacks for `Maybe`/`Either`, a
  zero-argument closure for `Effect`, and a two-callback closure returning a
  canceler for `EffectFnAff`. These shapes were never written down, so a
  generator change can break FFI silently.
- Decision: the runtime ABI is documented and numbered. Stable accessors
  (`phpurs_variant_tag/value`, `phpurs_record_get`) are the supported entry
  points; direct field access remains allowed but is part of the same versioned
  contract. A breaking ABI change bumps the version and the module-cache key.
- Alternatives: freezing only the printed PHP output was rejected because it
  does not describe what a hand-written FFI may rely on; exposing the TAST
  directly was rejected because it would couple applications to PBO internals.
- Consequences: `docs/ffi-contract.md` must list every representation and
  cancellation rule, and stability tests must run against generated PHP.
- References: `docs/ffi-boundaries.md`, tasks M7-06/M7-09.
