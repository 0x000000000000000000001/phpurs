# ADR-002 — FFI stays an inline PHP fragment

- Status: accepted
- Date: 2026-10-31
- Context: a module's `.php` file is inlined into a module-local closure.
  `declare(strict_types=1)`, `namespace`, named classes and named functions are
  therefore impossible, and `__DIR__` resolves to the generated output directory.
  The arrangement is a contract applications already depend on.
- Decision: keep the fragment form for now. Document the wrapper, the `$exports`
  shape (functions and the existing raw-value exceptions), the `__DIR__`
  semantics, and the uncurried callback convention. Validate missing export keys
  and arities at build time instead of at module initialization. A future
  `phpurs_ffi_mode: file` may load a real PHP file and recover `$exports`, but it
  is a separate, versioned change.
- Alternatives: switching to real PHP files immediately would enable PSR-12,
  `strict_types` and PHPStan, but breaks every existing FFI file and the
  self-contained module guarantee.
- Consequences: diagnostics must reject `declare`/`namespace` fragments with a
  clear message, and generated layouts cannot be discovered through `__DIR__`
  alone; a `phpurs_root()` helper or documented constant is needed.
- References: `docs/ffi-boundaries.md`, `docs/libraries.md`, tasks M7-01…M7-04.
