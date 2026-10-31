# ADR-006 — Cache keys include the preceding module stream

- Status: accepted
- Date: 2026-10-31
- Context: the serial PBO builder accumulates directives and private-global
  information from preceding modules, and phpurs accumulates arities before
  printing the next module. A key derived only from a module's own CoreFn and
  its imported dependencies would omit those shared inputs.
- Decision: each module key is chained over the preceding module stream. Within
  one graph and context, changing a module's CoreFn or FFI invalidates that
  module and every successor; earlier keys stay equal. Adding, removing or
  reordering modules changes the graph context and invalidates all keys.
  Duplicate or empty module names, forward dependencies and cycles return a
  conservative "unsupported plan" before any key is produced.
- Alternatives: per-module keys without the prefix were rejected because they
  would restore modules against a different accumulation state; hashing the
  whole graph for every module was rejected because it would invalidate
  unrelated modules on every edit.
- Consequences: cache misses are conservative and prefix-sensitive; the tests
  compare cached and uncached bytes for the same output.
- References: `docs/cache.md`, `src/Phpurs/CacheKey.purs`,
  `tests/codegen/cli/cache-key.mjs`.
