# 🐘 phpurs

<img height="160" alt="phpurs project illustration" src="https://github.com/user-attachments/assets/461687af-046a-4b23-a625-ec084710782b" />

_Experimental PureScript-to-PHP backend under active development. Follow the [development log](https://discourse.purescript.org/t/leveraging-70-of-the-web-a-php-backend-for-purescript/5340)._

`phpurs` compiles PureScript to **PHP 8.4+**, combining PureScript's type system and pure business logic with PHP's hosting options and library ecosystem. The compiler is written in PureScript and uses a TAST-aware fork of [Arista's purescript-backend-optimizer](https://github.com/aristanetworks/purescript-backend-optimizer).

It consumes the enriched **Typed CoreFn (TAST / `tcorefn`)** produced by the [custom PureScript compiler](https://github.com/0x000000000000000000001/purescript). In the current toolchain, this typed payload is stored in `output/<Module>/corefn.json`. The filename does **not** mean ordinary upstream CoreFn: expression types, ADT layouts (`dataDecls`), class declarations (`classDecls`) and type applications remain available to the backend.

_Thanks to PHP Weekly for [featuring the project](https://www.phpweekly.com/archive/2026-07-24.html)!_

## Features

PHP is useful when deployment already revolves around `.php` files, Composer packages and an existing web server: shared hosting, a small VPS, or an established PHP application. `phpurs` lets PureScript business logic join that environment while retaining PureScript's static checking, algebraic data types and functional abstractions.

Generated applications run on PHP; Node.js and the PureScript toolchain are needed to build them. Native PHP libraries can be called through `.php` FFI, and `Aff` uses PHP Fibers with the Revolt event loop for concurrent I/O. Deployment still needs the PHP version, extensions and Composer dependencies used by the application.

- **Optimization before PHP generation.** The shared optimizer handles inlining, uncurrying, constant folding and specialization. PHP lowering adds tail-call transformations and passes for partial bindings, thunk fusion, compact loops, copy cleanup and terminal inlining.
- **Representations informed by types.** TAST declaration layouts drive ADT constructor classes, and known scalar types provide PHP signatures where supported. Eligible internal ADT regions can use scalar or nullable representations; public constructors and general higher-order code retain the representations they need.
- **PHP FFI with generated wrappers.** Define imports through a module's `$exports` array. The backend derives calling wrappers from their PureScript types, including support for partial application. The FFI author supplies the PHP implementation and respects the effect and callback conventions.
- **Application integration.** Generated entrypoints load reachable modules in dependency order. Optional bundles combine modules, and Composer dependency collection helps connect PHP library requirements to an application's Composer project.
- **Cooperative I/O through Aff.** The sibling `phpurs-aff` library implements Fibers using Revolt. Non-blocking PHP APIs can suspend and resume computations within one process; synchronous I/O and CPU work still occupy the executing thread.

The compiler is written in PureScript with JavaScript FFI for host operations and runs on Node.js. Its optimization passes are covered by [focused regression fixtures](tests/codegen); performance depends on the program and runtime configuration.

## Benchmarks

The reference results live in [altbak.pub's PHP table](https://github.com/0x000000000000000000001/altbak.pub#php), alongside native PHP implementations and the other PureScript backends. See also the [extended I/O, mutability and asynchronous benchmarks](https://github.com/0x000000000000000000001/altbak.pub#extended-benchmark-results-io-mutability-async).

Use those documented baselines for performance comparisons, preserving the workload, PHP version, JIT/OPcache settings and measurement method. Older development timings are historical observations, not a claim about every program or the current compiler. The repository also retains [a detailed optimization audit](audit/2026-09-08/r10-integrated/report.md) with generated-code checks and controlled measurements.

## Getting started

### Prerequisites

For development and application compilation, provide:

- Node.js and npm to build and execute the backend's JavaScript bundle. The bundled esbuild dependency requires Node.js 18 or newer; your Spago version may require a newer Node.js.
- Spago on `PATH`; the compiler's [spago.yaml](spago.yaml) uses registry package set `77.10.1`. Spago is not installed by this repository's `package.json`.
- The TAST-capable `purs` fork for programs targeting PHP. Rebuild their output with that compiler when switching from upstream PureScript.
- PHP **8.4+** as the target documented here, and Composer for FFI packages with PHP dependencies. Fibers themselves were introduced in PHP 8.1.
- The matching optimizer checkout and PHP library forks described below.

The compiler itself runs on Node.js and uses ordinary JavaScript FFI libraries. The applications it emits use `phpurs-*` overrides. Keep those two dependency configurations separate.

### Build the backend

The current source checkout is configured for local development: `spago.yaml` resolves `backend-optimizer` from `../../purescript-backend-optimizer-phpurs`. Arrange a compatible checkout of the [optimizer fork](https://github.com/0x000000000000000000001/purescript-backend-optimizer) at that path, or deliberately update the dependency to the matching location before building.

```text
workspace/
├── purescript-backend-optimizer-phpurs/
└── phpurs/
    ├── phpurs/                 # this compiler repository
    ├── phpurs-prelude/
    ├── phpurs-effect/
    ├── phpurs-console/
    └── ...
```

From the compiler repository:

```bash
npm install
# npm's prepare hook runs npm run build.

# Rebuild after editing the compiler:
npm run build
```

The build runs `spago build` and bundles `Main` into `bin/phpurs.js`. The `bin/phpurs` shell launcher executes it with Node.js. Add this repository's `bin` directory to `PATH`, or configure Spago with its explicit path.

The npm package declares a `phpurs` executable and a build-on-install hook. With the current local optimizer dependency, installing directly from GitHub is not a self-contained setup: the optimizer path must also resolve in the installation directory. The source layout above makes that dependency explicit.

Each backend invocation reports monotonic elapsed times to stderr, in milliseconds, for `load TAST + sort`, `prepare`, `optimize + emit`, `finalize`, and `backend total`. The total includes these phases; it excludes the earlier `purs` compilation and target-language compilation or execution. Each phase waits for its asynchronous callbacks and file writes to finish. Failed phases and the total are marked `(failed)`, and the original error is rethrown.

### Compile and run an application

The [starter's library overrides](https://github.com/0x000000000000000000001/phpurs-starter/blob/master/spago.yaml) provide a larger dependency example. Use the compiler setup and entrypoint commands in this README: the starter's npm scripts still refer to the older `output/main.php` layout. Existing projects can retain their registry package set and replace libraries that contain JavaScript FFI with their PHP equivalents, including transitive dependencies.

For a minimal console application, create this `spago.yaml` (or merge its settings into an existing project):

```yaml
package:
  name: hello-php
  dependencies: [prelude, effect, console]

workspace:
  packageSet:
    registry: 77.10.1
  extraPackages:
    prelude:
      git: "https://github.com/0x000000000000000000001/phpurs-prelude.git"
      ref: "master"
      dependencies: []
    effect:
      git: "https://github.com/0x000000000000000000001/phpurs-effect.git"
      ref: "master"
      dependencies: [prelude]
    console:
      git: "https://github.com/0x000000000000000000001/phpurs-console.git"
      ref: "master"
      dependencies: [effect, prelude]
  backend:
    cmd: phpurs
    args: ["--main", "Main", "--autoload-path", "vendor/autoload.php"]
```

The example assumes the prepared `phpurs` launcher is on `PATH`. Add the PHP FFI overrides needed by any further dependencies, such as `aff`. Packages written entirely in PureScript can continue to come from the registry. Keep the application's `spago.lock` under version control to record the resolved Git revisions.

Create `src/Main.purs`:

```purescript
module Main where

import Prelude
import Effect (Effect)
import Effect.Console (log)

main :: Effect Unit
main = log "Hello, PHP from PureScript!"
```

#### Build and run

From the application root, with the TAST compiler on `PATH`:

```bash
export PATH="/absolute/path/to/tast-purs-directory:$PATH"
spago build
# Install any required Composer dependencies as described below.
php output/Main/main.mod.php
# Hello, PHP from PureScript!
```

Check that the generated `output/Main/corefn.json` includes `dataDecls` and
`classDecls`, plus `typeTable` with the current fork. If they are absent, select
the correct compiler and rebuild in a fresh output directory. A matching version
number alone does not prove TAST support.

The backend writes:

| Output | Purpose |
|---|---|
| `output/<Module>/index.php` | Generated module and its PHP FFI. Module directory names retain dots, e.g. `App.Main`. |
| `output/<Module>/main.mod.php` | Runnable entrypoint loading the modules reachable from that entrypoint. |
| `output/<Module>/main.bundle.php` | Additional entrypoint when `--bundle` is enabled. |
| `output/bundle.php` | Bundle of generated modules when `--bundle` is used without `--main`; it does not invoke an application entrypoint. |
| `output/composer.json` | Generated `phpurs/lib-deps` package collecting discovered PHP requirements. |

Without `--main`, an entrypoint is written for every module exporting `main`. To target `App.Main`, set `args: ["--main", "App.Main"]` and run `php output/App.Main/main.mod.php`.

### Compiler options

Set the executable in `workspace.backend.cmd`. Arguments can be stored in `workspace.backend.args` or replaced for one build with `--backend-args`:

```bash
spago build --backend-args "--main App.Main --bundle --autoload-path vendor/autoload.php"
php output/App.Main/main.bundle.php
```

| Option | Description |
|---|---|
| `--main <Module>` | Select the generated application entrypoint. With no flag, discover all exported `main` bindings. This does not prevent the compiler from generating other input modules. |
| `--output <Directory>` | Input directory containing the typed `corefn.json` files and destination for generated module files. Default: `output`. |
| `--ffi <Directory>` | Add a directory to FFI discovery. An adjacent `.php` file at the original `.purs` source path takes precedence. |
| `--bundle` | Also concatenate generated PHP modules into bundle files. Composer dependencies remain external. |
| `--autoload-path <Path>` | Composer autoloader path, normally relative to the application root. Default: `vendor/autoload.php`. |

Use the default `output` layout for bundling and Composer integration: per-entrypoint bundle paths and Composer discovery/output currently still refer to `output`, even when `--output` changes the module directory. The shared argument parser recognizes `--rewrite-limit`, but this backend currently uses a fixed limit of 10,000. Paths containing spaces are not supported by the current argument splitting.

## Foreign function interface

Place `Example.php` beside `Example.purs`. Populate `$exports` with keys matching the `foreign import` names. The compiler embeds that file inside a module-local closure and generates the calling wrappers from the TAST types.

```purescript
module Example where

import Prelude (Unit)
import Effect (Effect)

foreign import add :: Int -> Int -> Int
foreign import printLine :: String -> Effect Unit
```

```php
<?php
$exports['add'] = function(int $a, int $b): int {
    return $a + $b;
};

$exports['printLine'] = function(string $message) {
    return function() use ($message) {
        echo $message . "\n";
    };
};
```

`add` accepts both arguments directly. The wrapper handles PureScript partial application. `printLine` returns a zero-argument closure so its side effect occurs when the `Effect` runs, not when its argument is supplied. Preserve that delay when writing effectful imports; callback and ADT representations should follow the existing `phpurs-*` implementations.

Useful examples include the sibling `phpurs-console/src/Effect/Console.php` and the local [function/FFI boundary fixture](tests/passing/FunctionFFIBoundary.php). The latter covers captured closures, callbacks stored in records and effectful retention. Records use PHP object properties, and generated public ADT constructors expose a tag and constructor fields.

Check every imported symbol: the current compiler can generate placeholder callables for missing FFI files or exports instead of issuing a hard error. Successful code generation alone does not validate an FFI port.

### Composer dependencies

Manage application dependencies in your root `composer.json`. FFI packages may have their own Composer requirements; for example, `phpurs-aff` requires `revolt/event-loop`.

After code generation, inspect `output/composer.json`. The collector scans Spago package directories and package roots inferred from module paths, then writes a package named `phpurs/lib-deps`. Add that generated package as a Composer path repository in your application's configuration:

```json
{
  "require": {
    "phpurs/lib-deps": "@dev"
  },
  "repositories": [
    {
      "type": "path",
      "url": "output"
    }
  ]
}
```

Merge these entries into an existing configuration rather than replacing its requirements. Build first so `output/composer.json` exists, then resolve dependencies:

```bash
composer update
```

Commit the application's Composer lockfile. Deployment can install its locked requirements with:

```bash
composer install --no-dev --optimize-autoloader
```

If the root Composer project is in a subdirectory, adjust the path repository's `url` relative to that `composer.json`, and set `--autoload-path` accordingly. Dependency discovery does not install Composer packages or guarantee that every custom FFI directory is scanned; declare missing requirements in the application explicitly.

### Asynchronous I/O and concurrency (Aff)

The `phpurs-aff` implementation uses native PHP Fibers and the Revolt event loop. Fibers suspend while waiting and are resumed through the runtime's callbacks. This provides cooperative concurrency within a PHP process; CPU-bound code does not automatically run on multiple cores.

Use asynchronous PHP APIs for I/O. A synchronous database query or `file_get_contents` still blocks the executing thread, even when called inside a Fiber. Merely wrapping it in `Fiber` or scheduling it with Revolt does not make the operation non-blocking.

Install the Aff package's Composer dependencies and load the autoloader before running the application. Generated entrypoints contain a Revolt event-loop hook; Aff launchers also manage runtime progress. Existing sibling package implementations provide the contracts for cancellation, callbacks and supervision.

## Development and testing

### Library checkouts

The [bin/pkg](bin/pkg) list defines the core packages required by the compiler's passing-test runner. Clone them beside the compiler:

```bash
./bin/setup
```

This script skips directories that already exist. It prepares PHP library forks, not the optimizer dependency or the `purs` toolchain. Resolve missing repositories reported by setup before attempting the full runner.

### Compiler and generated-code tests

```bash
# Run the vendored passing fixtures with the existing backend bundle:
./bin/test

# Rebuild the backend, clear runner caches and run one fixture:
./bin/test FunctionFFIBoundary -c

# Run a code-generation regression after npm run build:
node tests/codegen/enum-regions.mjs
```

`bin/test` accepts fixture names or paths, several fixtures, and `--skip-before=<Name>`. `-c` / `--clean` rebuilds the compiler and clears runner caches. The runner replaces files under `tests/runner/src` and generates output there; it is a scratch project, not a place to keep application sources.

The [tests/codegen](tests/codegen) scripts exercise individual optimization passes and representation boundaries. They import the compiler modules from `output`, and some execute PHP, so rebuild those modules after changing compiler code. To run the whole group:

```bash
for test_file in tests/codegen/*.mjs; do
  node "$test_file" || exit 1
done
```

Package-level tests live in sibling repositories. `./bin/modtest` runs the siblings that provide an executable `bin/test`; `./bin/modtest -c` rebuilds the backend first. Install each package's declared runtime dependencies before those tests, especially Composer dependencies for Aff.

### Nix environment

A [Nix flake](flake.nix) supplies a development shell:

```bash
nix develop
```

It currently selects upstream PureScript `0.15.15`. That can serve as a host compiler, but application generation still needs the TAST fork, and the local optimizer dependency must resolve. Treat the flake as development tooling; the file alone does not establish a portable, fully pinned build of this local checkout.

## Architecture

1. **Typed input:** the optimizer fork's `App` loader reads and sorts the enriched `corefn.json` modules. TAST preserves structural types, declaration layouts and polymorphic instantiations.
2. **Optimization:** `buildModules` applies the shared optimizer's analysis, directives and rewrites to produce `BackendModule` values.
3. **PHP lowering:** [Phpurs.CodeGen](src/Phpurs/CodeGen.purs) applies PHP-specific transformations and TCO analysis, then constructs [PhpAst](src/Phpurs/PhpAst.purs). Specialized representations are used where proven; unsupported shapes retain the general representation.
4. **FFI and printing:** [GenNativeForeign](src/GenNativeForeign.purs) generates typed calling wrappers; [Phpurs.Printer](src/Phpurs/Printer.purs) emits PHP source.
5. **Application integration:** [Main](src/Main.purs) writes module files, entrypoints and optional bundles. [ComposerMerge](src/ComposerMerge.js) collects PHP package requirements.

Spago provides incremental compilation of the backend itself. The current `Main` does not call the optimizer cache helpers, so generated PHP is rebuilt on each backend invocation. Rebuild the compiler bundle after changing its source.

## Current status and limitations

Implemented capabilities and remaining validation work:

- [x] PureScript compiler integrated with the TAST-aware optimizer fork.
- [x] Typed scalar signatures and ADT constructor layouts in generated PHP.
- [x] Native FFI wrappers, automatic entrypoints, optional bundling and Composer dependency collection.
- [x] Fiber/Revolt Aff implementation in the sibling FFI ecosystem.
- [x] Vendored language fixtures and focused regression tests for compiler optimizations and FFI boundaries.
- [x] [Recorded application validation](https://discourse.purescript.org/t/leveraging-70-of-the-web-a-php-backend-for-purescript/5340/9) involving Postgres, S3, RabbitMQ and HTTP in July 2026.
- [ ] Complete and maintain package-by-package validation of the `phpurs-*` ecosystem.
- [ ] Make installation independent of local optimizer paths and finish cleanup.

Earlier development reports recorded a passing language suite and successful real-application tests. Those results are historical, not a fresh certification of this checkout. The current [test runner](bin/test) explicitly skips several newer language features, the 32-bit integer-overflow fixture (`2136`) and the cyclic-initialization fixture (`4179`). PHP integers follow the platform's integer width, typically 64 bits. Check the runner's exclusions when reporting coverage rather than claiming unrestricted upstream test compatibility.

## License

MIT License. See [LICENSE](LICENSE) for details.
