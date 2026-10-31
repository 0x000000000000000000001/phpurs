# ADR-005 — English docs, French working backlog

- Status: accepted
- Date: 2026-10-31
- Context: `README.md` and most `docs/` pages are English, while
  `architecture.md`, `development.md` and `ffi-boundaries.md` were French. New
  contributors cannot tell which language to use and translations drift.
- Decision: `README.md` and `docs/` are English. `todo.md` stays French as the
  maintainer's working backlog and is not published. Long documents are split
  under `docs/compiler/` with a hub of at most 250 lines, and
  `docs/index.md` links every maintained page.
- Alternatives: French-only documentation was rejected because the repository,
  issues and upstream ecosystem are English; keeping both languages was rejected
  because it doubles the maintenance and the review surface.
- Consequences: the three French pages were translated; `check:docs` verifies
  links, anchors and the hub size; the source map is generated from module
  headers rather than edited by hand.
- References: `docs/development.md`, `tools/check-docs.mjs`, task M5-01.
