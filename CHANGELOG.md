# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow
[Semantic Versioning](https://semver.org/). `package.json` is at 0.1.0; this
section records work that is not published yet.

## [Unreleased]

### Added

- `Phpurs.Pass`: shared pass guard, binding traversal, scan budget and private
  names; `PartialBindings`, `ThunkFusion`, `StateFusion`,
  `CallbackSpecialization`, `ArrayCallbacks` and `EnumRegions` migrated.
- `Phpurs.Budget`: every pass safety valve in one named module; the convention
  check rejects numeric thresholds elsewhere.
- `PhpUnaryOp`, `PhpNamedCall`, `PhpGlobalSlot` and `PhpUnset` AST forms; the
  empty `PhpRaw` sentinel is gone.
- Golden PHP corpus (`tests/golden/`), exhaustive AST traversal test,
  conventions checker, docs/shell/source-map checks and commit-message lint.
- Per-pass contract cards, ADR-001…007, `docs/conventions.md` and
  `CONTRIBUTING.md`.
- CI workflow covering format, conventions, docs, shell, build, golden and
  tests, with the pinned PBO revision.

### Fixed

- Unary operators no longer rely on an empty binary operand.
- Module headers, numeric budgets and documentation links are checked instead
  of maintained by hand.

## [0.1.0] - 2026-10-31

Initial tracked version of the compiler. Not published to npm yet.
