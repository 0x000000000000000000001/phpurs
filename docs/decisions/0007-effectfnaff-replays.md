# ADR-007 — EffectFnAff values replay

- Status: accepted
- Date: 2026-10-31
- Context: PHP FFI builds `EffectFnAff` as a closure taking success and error
  callbacks and returning a canceler. Applications rewrote the same `$runAsync`
  with divergent cancellation: some abandon the operation, others let it run.
  A replay test in b8x ("Hash could not replay") exposed an unwritten contract.
- Decision: an `EffectFnAff` value is replayable: running it again starts a new
  operation with fresh callbacks. Cancellation is cooperative and idempotent;
  the canceler must be safe to call after completion. Operations that cannot be
  cancelled use a standard non-cancellable canceler instead of each application
  inventing one. The runtime provides `phpurs_aff_from_operation` and
  `phpurs_non_cancellable`; the replay, cancellation and failure semantics are
  documented and tested in PHP.
- Alternatives: single-shot semantics were rejected because `Aff` may re-run a
  suspended computation; fire-and-forget cancellation was rejected because it
  silently leaks locks and connections.
- Consequences: synchronous PHP work still occupies the thread; the helper does
  not turn blocking I/O into non-blocking I/O. FFI authors get one documented
  bridge instead of copying `$runAsync`.
- References: `phpurs-aff/src/Effect/Aff/Compat.purs`, tasks M7-07/M7-09,
  `docs/ffi-contract.md`.
