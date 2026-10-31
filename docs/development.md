# Development

## Build

The canonical path is:

```sh
npm run build
```

It runs `spago build` and then `tools/bundle.mjs`, which produces
`bin/phpurs.js` with the toolchain identity and the module cache enabled.
`bin/phpurs.js` is ignored by Git and rebuilt on demand.

The benchmark runner `altbak.pub-phpurs/bin/php/run -c` uses the same
`tools/bundle.mjs` after its `spago build` pinned to the host compiler.

## PBO toolchain

`spago.yaml` resolves the optimizer from
`../../purescript-backend-optimizer-phpurs`. The expected revision is pinned in
`tools/toolchain.json`; `npm run check:toolchain` verifies the checkout and the
path declared in `spago.yaml`. `./bin/setup` clones the repository at that
revision when missing.

## CI

`.github/workflows/check.yml` replays on every PR: formatting, conventions,
source map, docs, shell, build, golden and tests. The workflow recreates the
local layout (`phpurs/phpurs` and `purescript-backend-optimizer-phpurs` side by
side under the workspace), installs Node 22 and PHP 8.4, installs shellcheck,
checks the PBO revision and the PR title, then runs `npm run check`. npm and
Spago caches are shared between runs.

## Formatting and organization

```sh
npm run format        # apply purs-tidy and Prettier
npm run format:check  # check without modifying
```

Versions are pinned in `package.json`. `.editorconfig` fixes line endings and
indentation; `.tidyrc.json` and `.prettierrc.json` carry the formatter options,
including a 100-column target width.

The scope is the code maintained here: `src/**/*.purs`, `src/**/*.js`,
`tests/**/*.mjs`, `tools/*.mjs`, the root JSON files and `spago.yaml`. Keep the
fixtures imported from `tests/passing` and the historical audits in their
original form.

- Put each PureScript module at its name (`Phpurs.X` in `src/Phpurs/X.purs`) and
  its JavaScript FFI next to it.
- Name functions after their role and extract steps with a distinct
  responsibility, without adding a facade or an unused abstraction.
- Keep public contracts and invariants near the code that applies them; update
  documentation links when moving a module.

## Checked conventions

`npm run check:conventions` checks the rules the compiler and the formatters do
not cover: one `-- |` header per module, maximum module size (400 lines) and
declaration size (60 lines), no `PhpRaw ""`, and the allowed dependency
direction between layers (`PhpAst`, `Optimize`, `ArrayRefs`, `Printer`,
`CodeGen`, `Main`).

Accepted debt lives in `tools/conventions-baseline.json`: an entry allows the
current size, and any growth fails. After reducing debt, run
`npm run conventions:update` and review the diff.

The detailed rules (sizes, names, `PhpRaw`, budgets, tests, commits) are in
[conventions.md](conventions.md); structural decisions are in
[decisions/](decisions/README.md).

## Tests

Full verification, with a fresh build:

```sh
npm run check  # format:check, conventions, source map, docs, shell, build, golden, tests
```

After an up-to-date build, `npm run test:codegen` reruns only the tests.

The golden corpus freezes the printed PHP of representative cases:

```sh
npm run test:golden    # compare byte for byte
npm run golden:update  # rewrite expected/ after an intentional change
```

An intentional output change updates `tests/golden/expected/` in the same PR;
the diff is reviewed. How it works and how to add a case are described in
[`tests/golden/README.md`](../tests/golden/README.md).

Targeted test:

```sh
node --test tests/codegen/direct/<file>.mjs
node --test tests/codegen/cli/<file>.mjs
node --test tests/codegen/ffi/<file>.mjs
```

Families and conventions are described in
[`tests/codegen/README.md`](../tests/codegen/README.md). Shared helpers live in
`tests/codegen/fixtures/cli.mjs` (`temporaryRoot`, `runPhp`, `snapshotFiles`,
`snapshotText`).

CLI tests use `bin/phpurs.js` by default and can replace it through
`PHPURS_TEST_CLI`. PHP execution tests disable the CLI OPcache.

Language fixtures and PHP packages have their own runners:

```sh
./bin/test FunctionFFIBoundary     # targeted fixture, existing bundle
./bin/test --skip-before=TCO      # suite starting at TCO
./bin/test -c                    # rebuild and run with cleared caches
./bin/modtest                    # sibling package suites
```

`bin/test` validates selections before preparing the project and distinguishes
PureScript compilation, PHP generation and execution failures. It prints the
first failure's diagnostic and stops. Isolated tests of its control flow live in
`tests/codegen/cli/language-runner.mjs`; they simulate executables without
compiling fixtures. `tests/runner` is scratch space: do not keep personal
sources there.

`bin/modtest` counts executable suites in the sibling packages, reports their
absence and stops at the first failure. Its isolated tests live in
`tests/codegen/cli/sibling-runner.mjs`.

Library wrappers share `bin/test-package`, covered by
`tests/codegen/cli/package-runner.mjs`. The
[library guide](libraries.md) describes configuration selection/restoration,
FFI tests and the `format:packages` and `format:packages:check` commands.

## Benchmarks

The runner and performance fixtures live in `altbak.pub-phpurs`; they are not
part of `npm run test:codegen`.

```sh
cd ../../altbak.pub-phpurs
bin/php/run -c          # rebuild + 14 benchmarks
bin/php/run --run-only  # benchmarks on the existing output
```

Compare every measurement with the official baselines in altbak.pub's
`README.md`, using the same workloads and PHP options. For a refactor, also
check behaviour and PHP output for frozen inputs.

## Cleaning

`npm run clean` removes local outputs and caches (`output`, `.spago`,
`.purmeta`, `bin/phpurs.js`, the `tests/runner` scratch space and `.tmp-*`).
`npm run clean -- --dry-run` lists the targets without deleting anything. No
target outside the repository is accepted.

## Generated outputs

- `output/`, `.spago/`, `.purmeta/`, caches and `bin/phpurs.js` are ignored.
- The npm package is filtered by `.npmignore`; do not reintroduce audits, tests
  or caches there.
- Historical audits stay in `audit/` and are indexed by `audit/README.md`.

## Refactor rules

- An extracted module keeps the public API and the PHP output.
- Build and run targeted tests after each extraction.
- Check identical PHP output for `--main App --no-cache` when touching the
  generator.
- Do not mix a code move with an algorithm change.
