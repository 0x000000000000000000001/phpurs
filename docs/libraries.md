# Working on the PHP libraries

The sibling `phpurs-*` repositories contain PureScript libraries and their PHP
foreign implementations. Their upstream API documentation remains useful; PHP
runtime behavior is defined by each module's `.php` file and its PHP tests.

## Checkout and toolchain

Keep the compiler and libraries beside each other:

```text
phpurs/
  phpurs/             # compiler, shared test runner and formatters
  phpurs-prelude/
  phpurs-aff/
  phpurs-arrays/
  ...
```

Build the compiler with `npm run build` in its checkout. Application and library
tests require the TAST-capable `purs` fork on `PATH`, Spago, and PHP 8.4 or newer.
The compiler's npm dependencies select its separate host compiler. See the
[compiler development guide](development.md).

Install the Composer dependencies declared by each package before its tests.
For example, Aff and the socket libraries need Revolt. PHP extensions are also
part of the runtime requirements: string libraries use `mbstring`, and BigInt
uses `gmp`. Package READMEs describe implementation-specific capabilities and
limits, especially the Node API ports.

## Running tests

From a library with an executable `bin/test`:

```sh
./bin/test         # fresh package output, existing compiler bundle
./bin/test --clean # also rebuild the compiler
```

Each wrapper calls the compiler's `bin/test-package`. It selects
`spago.php.yaml` for the build and restores the original `spago.yaml` file or
symlink on exit, including on failure. It uses `spago build --pure` to preserve
the package lock and clears only that package's generated `output` directory.
Keep application sources outside generated output directories.

The runner checks that `Test.Main` contains TAST declarations before invoking
PHPurs. If this check fails, select the fork on `PATH` and rerun; the fresh
output removes inputs produced by a different compiler. When dependencies
change deliberately, update `spago.lock` separately with a normal Spago build.

PHP runs with CLI OPcache disabled and a 512 MiB memory limit. An existing
`vendor/autoload.php` is loaded. A package's optional `test/ffi.php` runs before
the generated `Test.Main` entrypoint. Aff additionally requires its completion
marker, so a prematurely stopped event loop cannot appear successful. Errors
from compilation, generation, direct FFI tests and execution fail the runner.

From the compiler checkout, `./bin/modtest` runs every executable sibling
suite. Libraries without a standalone runner are exercised by their consumers;
add a focused test to a consuming suite when changing an uncovered boundary.
The shared runner itself is tested by `npm run test:codegen`.

## Source organization and formatting

- Keep PureScript module paths and adjacent PHP FFI paths aligned.
- Keep the `$exports` keys and calling convention aligned with the module's
  `foreign import` declarations. An `Effect` remains a delayed zero-argument
  callable, and callbacks retain their declared currying convention.
- Use named local helpers or class methods for distinct responsibilities.
  Keep scheduling, cancellation and callback ordering explicit in async code.
- PHPurs embeds a foreign file inside a module-local closure. A relative
  `require` of a new implementation file does not automatically ship that file
  with generated output. Keep a module's runtime self-contained unless the
  compiler explicitly supports the added dependency.
- Keep runtime sources, direct PHP tests and generated output separate. Preserve
  upstream JavaScript implementations and PureScript APIs when maintaining a
  PHP port.

The compiler checkout pins Prettier and its PHP plugin. From that checkout:

```sh
npm run format:packages -- phpurs-aff  # one sibling
npm run format:packages               # all sibling PHP sources and tests
npm run format:packages:check         # check without modifying
```

The formatter selects tracked and new non-ignored PHP files under `src`, `test`
and `tests`. It never traverses `vendor`, generated outputs
or scratch directories. PHP uses four-space indentation and a target width of
100 columns. PureScript retains each package's upstream formatting conventions.

Three sources have an additional compiler contract: `Data.Semiring.php`,
`Data.Array.php` and `Data.Foldable.php`. The optimizer recognizes reviewed exact
byte hashes in `Phpurs.NativeCallbacks` and `Phpurs.ForeignTraversals`; even
formatting changes their identity. When changing these files, verify the
semantic contract and update the reviewed hashes and regression coverage. The
compiler accepts both the original and the formatted versions from this cleanup.
Unrecognized sources conservatively use ordinary FFI calls.

For a refactor, run `php -l`, the relevant direct FFI regression, and the
package/consumer suite. Compare observable results and failures before and
after when restructuring stateful or asynchronous code. Performance claims
need the official baselines in `altbak.pub/README.md`.
