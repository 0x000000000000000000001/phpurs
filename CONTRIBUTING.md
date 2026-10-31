# Contributing

This repository builds the PHP backend for the TAST-capable PureScript
compiler. The conventions are enforced by `npm run check`; the rationale lives in
[`docs/conventions.md`](docs/conventions.md) and
[`docs/decisions/`](docs/decisions/README.md).

## Setup

Requirements: Node 20 or newer, PHP 8.4 or newer, and Composer for application
tests. Spago and `purs` come from the npm dependencies for building the backend;
compiling an application additionally needs the TAST-capable `purs` fork on
`PATH`.

```sh
git clone git@github.com:0x000000000000000000001/phpurs.git
cd phpurs
./bin/setup          # clones the sibling libraries and the pinned PBO checkout
npm ci
npm run check        # format, conventions, source map, docs, shell, build, golden, tests
```

`spago.yaml` resolves the optimizer from
`../../purescript-backend-optimizer-phpurs`, at the revision pinned in
`tools/toolchain.json`. `npm run check:toolchain` verifies it; `./bin/setup`
clones it when missing.

## Layout

- `src/Phpurs/` — passes, PHP AST, printer, cache, emission.
- `src/Main*.purs` — CLI orchestration.
- `tests/codegen/{direct,cli,ffi}/` — AST/pass, driver, and FFI tests.
- `tests/golden/` — byte-for-byte printed PHP.
- `docs/` — contributor documentation; `docs/decisions/` holds ADRs.
- `tools/` — checks, bundle and formatting helpers.

## Recipes

**Add a pass.** Create a module exporting `optimize :: BackendModule -> Result`
(use `Phpurs.Pass` for the guard, `scanGroups`, `privateName`, and
`Phpurs.Budget` for every threshold). Add its card to
`docs/compiler/passes.md`, its row in the pass pipeline, a direct test, and a
golden case when the printed output changes.

**Change a budget.** Edit `src/Phpurs/Budget.purs` only; moving a value must not
change it. Changing one needs a boundary test and an ADR when output changes.

**Add a PHP AST form.** Add the constructor, both traversal matches, the printer
case, a sample in `tests/codegen/direct/ast-traversal.mjs`, and update
`docs/compiler.md` when a raw form is replaced.

**Change FFI.** Keep the fragment self-contained, document the `$exports` shape
and calling convention, and update `docs/ffi-boundaries.md` plus the task in
`todo.md`. Build-time diagnostics belong to the FFI tasks (M7).

**Change printed output.** Run `npm run golden:update`, review the diff in
`tests/golden/expected/`, and keep the update in the same PR as the code.

**Move or rename a module.** Run `npm run docs:source-map`; `check:source-map`
fails while the page is stale. Update the nearest architecture pointer and
`docs/compiler/passes.md` if a pass moved.

## Pull requests

- Reference the `Mx-yy` task and tick it in `todo.md`.
- Keep one reviewable change per PR; do not mix a move with an algorithm change.
- PR titles use Conventional Commits (`feat(passes): …`), enforced in CI.
- `npm run check` must be green; a deliberate output change is a reviewed golden
  diff, never a silent one.
- CI runs `check`, the shell static analysis and the PR-title check. Branch
  protection is a repository setting, not a file in this tree.
