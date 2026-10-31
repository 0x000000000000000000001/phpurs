# Conventions

Rules that the compiler, the formatters and `npm run check` enforce. They apply
to `src/`, `tests/`, `tools/` and `docs/` in this repository.

## Commands

```sh
npm run check             # format, conventions, docs, shell, build, golden, tests
npm run check:conventions # module headers, sizes, imports, raw sentinels, budgets
npm run check:docs        # links, anchors, docs index and compiler hub size
npm run check:shell       # bash -n and shellcheck on bin/*
npm run test:golden       # byte-for-byte PHP output
npm run check:commit -- "feat(ast): describe the change"
```

`check` must fail on the first violation; scripts never mask an exit code (no
`| tail` in npm scripts, `set -euo pipefail` in shell entry points).

## Module shape

- One module per concern; the file name matches the module name.
- Every module starts with a `-- |` header stating its responsibility and, when
  it owns proofs or budgets, its invariant.
- Modules stay under 400 lines and top-level declarations under 60 lines.
  Accepted exceptions are recorded with their current size in
  `tools/conventions-baseline.json`; growing past that size fails, shrinking
  below a limit asks for the entry to be removed.
- Imports follow the layer direction: `PhpAst` → `Optimize` → `ArrayRefs` →
  `Printer` → `CodeGen` → `Main`. `Phpurs.Budget` is a leaf available to every
  layer.

## Accepted size exceptions

No module exceeds 400 lines since the `CodeGen/Expr` split (M3-01) on
2026-10-31. The remaining function exceptions are listed with their current
size in `tools/conventions-baseline.json`; the largest are
`Statements.translateLetRec` (72), `Statements.translateBranch` (69) and
`Update.tryFusedUpdate` (68).

## Names

- `module_` is the PBO field name; do not rename it locally.
- Private generated names use the `__phpurs_*` prefixes and go through
  `Phpurs.Pass.privateName` or `freshPrefixFor`.
- A PHP helper name is declared once in `Phpurs.RuntimeNames` and printed from
  there.

## PHP AST

- No `PhpRaw ""` sentinel. Unary operators use `PhpUnaryOp`.
- `PhpRaw` is an escape hatch for generated PHP only. Four uses are accepted:
  the two Array traversal guards, the Array `foreach` template and the generated
  pattern-match error message. A new use needs a comment and a test.
- `PhpDirectCall` resolves a generated module global through `$GLOBALS`;
  `PhpNamedCall` prints a literal function name (builtins and runtime helpers).
  Do not confuse them.
- Adding a constructor requires a sample in
  `tests/codegen/direct/ast-traversal.mjs` and a decision in both traversal
  matches.

## Budgets

- Numeric safety valves live in `src/Phpurs/Budget.purs`. A pass must not
  hard-code one; `check:conventions` rejects integers of 64 or more outside that
  file (except the `RewriteLimit` CLI default).
- Moving a budget must not change its value. Changing one requires a boundary
  test and, when output changes, an ADR update.

## Tests

- Families: `tests/codegen/direct/` (AST and passes), `cli/` (driver, cache,
  emission), `ffi/` (foreign adaptation and runtime), `tests/golden/` (printed
  PHP, byte-for-byte).
- A failure message names the fixture and the contract being checked.
- A golden change is intentional: update `tests/golden/expected/`, review the
  diff, and keep the change in the same PR as the code.
- Temporary directories go through `temporaryRoot` or `t.after`; no test depends
  on a personal path or on another application's output.

## Documentation

- `README.md` and `docs/` are English; `todo.md` is the French working backlog.
- Every maintained page is linked from `docs/index.md` once the M5-02 split
  lands. Links and anchors must resolve (`npm run check:docs`).
- Per-pass contracts live in `docs/compiler/passes.md`; decisions live in
  `docs/decisions/`.
- `docs/compiler.md` is a hub of at most 250 lines; details move under
  `docs/compiler/`.

## Line endings

Text files use LF (`.editorconfig`); binary assets are the only exceptions and
can be checked with `git ls-files --eol`. Generated `output/`, `.purmeta/`,
`bin/phpurs.js` and local scratch directories are never committed.

## Commits and pull requests

- Titles and commits follow Conventional Commits:
  `type(scope): summary`, with `type` in
  `feat|fix|refactor|docs|test|build|perf|chore` and a lowercase scope.
- A pull request references its `Mx-yy` task, updates the checkbox in `todo.md`,
  and includes tests and documentation.
- Squash merge uses the PR title, so the title is the commit message.
