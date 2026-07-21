# phpurs

<img height="160" alt="Screenshot 2026-07-21 at 17 19 55" src="https://github.com/user-attachments/assets/461687af-046a-4b23-a625-ec084710782b" />
<br />
<br />

A super-optimized **PureScript-to-PHP compiler**, entirely written in PureScript, leveraging PHP's **portability** and **omnipresence on the web**. It successfully passes the official PureScript test suite and is ready for production.

`phpurs` leverages the intermediate `CoreFn` representation to compile your pure business logic into robust, modern PHP 8.4+ code. It seamlessly integrates into your existing PureScript workflow as a custom backend.

## Why PHP?
While the broader JS ecosystem has heavily leaned towards TypeScript, the PHP world (which still powers roughly **70% of the web**) has somewhat been left without a true, strictly-typed functional alternative. Furthermore, a massive portion of the web relies on legacy shared hosting or "containerless" VPS environments where escaping the PHP runtime is either impossible, financially unviable, or technically difficult. Just a few examples: cPanel/Plesk, GoDaddy, Bluehost, SiteGround, standard HostGator/OVH shared plans, basic LAMP stacks…

Recently, we've observed a genuine surge of interest for strict typing and functional concepts within the PHP community (with recent PHP versions and RFCs: short closures, match expressions, pipe operators and partial application), although the frustration that comes with its current limitations is just as real. `phpurs` aims to provide a "comfort tool" for any PHP developer looking to finally take the leap towards a much safer paradigm. It opens a door for those who want the elegance of a purely functional language like PureScript, completely bypassing the JS/TS ecosystem while keeping deployment as simple as an FTP upload of `.php` files, a massive win for legacy or small projects, which constitute the majority of the web today.

## Production readiness & optimizations

`phpurs` has successfully graduated from its experimental phase. It passes **100% of the [official PureScript passing test suite](https://github.com/purescript/purescript/tree/master/tests/purs/passing)** (361/361 tests), proving that the AST transformation is sound and the runtime execution strictly matches the original language's specifications (scoping, TCO loops, strict evaluation, etc.).

It also successfully compiles and executes a universal multi runtime pedagogical benchmark suite ([altbak.pub](https://github.com/0x000000000000000000001/altbak.pub)), demonstrating how PureScript changes the game for the ideal of **"Write once, run everywhere"**. The identical PureScript code runs seamlessly across Node.js (V8), Arista ES, Chez Scheme, Erlang BEAM… and natively on PHP!

### Write once, run everywhere (benchmarks)

The initial unoptimized benchmark gave 1m30s. After dozens of compiler optimizations, the exact same tests are now successful after a run of **~1.7s**. This is a massive step forward. 

Standard benchmarks often show Node (V8) to be 2 to 5 times faster than PHP 8 for heavy computational tasks, and our results reflect this reality fairly accurately (PHP is about x3 slower than V8, and only x2 slower than `purerl`). The compiler has therefore likely reached its peak performance area.

In our specific context, this x3 difference is **not a problem**:
- We are targeting a segment of the web that, while certainly the majority, is focused on factors other than pure performance (presence, visibility, significantly lower hosting costs, zero-config deployments). For highly advanced projects requiring containerized stacks and full DevOps control, JS, Go, or Erlang will naturally remain better compiler backends.
- In the vast majority of real-world PHP applications, the main bottleneck remains I/O (Database, Network, Filesystem). As a result, the difference in raw execution performance is entirely unnoticeable in practice.

### Asynchronous operations (Aff)
Support for `Aff` has also been natively added, backed by the **Revolt event loop** and **PHP 8.1+ Fibers**. The behavior of the event loop in the compiled PHP code perfectly mirrors its Node.js counterpart: asynchronous operations are handled transparently without blocking the OS thread, and the main process will automatically wait for all pending `Aff` tasks to complete before exiting.

### Project structure & ecosystem

In parallel, the project structure has been fully reorganized to align with the standard conventions of other alternate backends (like `purerl`, `purescm`, or `purescript-go`). We now have:
- A dedicated **FFI ecosystem** based on standard forks (`prelude`, `effect`, `aff`, etc.).
- A standalone compiler repository (`phpurs`).
- A ready-to-use **[starter template](https://github.com/0x000000000000000000001/phpurs-starter)** to easily bootstrap new projects.

The compiler itself is now robust and performant. It has been battle-tested on real-world projects and is ready for production use. We highly welcome community contributions—whether it's PRs for the compiler itself or adding missing PHP FFIs to core and major PureScript libraries.



## How to use

The easiest way to bootstrap a new PureScript-to-PHP project is by using our official starter template. It comes pre-configured with the necessary core library overrides (FFI mapped to native PHP) via Git dependencies.

1. **Clone the Starter Template:**
   ```bash
   git clone git@github.com:0x000000000000000000001/phpurs-starter.git my-php-project
   cd my-php-project
   ```

2. **Install the `phpurs` backend compiler:**
   You can install the compiler directly from GitHub. NPM will automatically compile it in the background during installation.
   ```bash
   npm install --save-dev github:0x000000000000000000001/phpurs
   ```

3. **Build and Run:**
   Back in your project directory, Spago will automatically resolve the PHP core packages and compile your code into native PHP.
   ```bash
   spago build
   php output/Main/main.mod.php
   ```

### Manual configuration

If you wish to configure an existing project manually, `phpurs` acts as a drop-in backend for the Spago build system.

1. **Manage Core Library Overrides (`spago.yaml`):**
   Because standard PureScript libraries use JavaScript FFI, you must override them with their `phpurs-*` counterparts. There are two valid approaches to handle this in modern Spago, depending on your needs:

   **Approach A: Custom Registry (Turnkey but strict)**
   Point your workspace package set to a pre-configured remote registry that already includes all PHP overrides. This keeps your `spago.yaml` incredibly clean, but you are tied to the update cycle of this custom registry for any third-party package updates.
   ```yaml
   workspace:
     packageSet:
       url: "https://raw.githubusercontent.com/0x000000000000000000001/phpurs-registry/main/packages.json"
     backend:
       cmd: phpurs
       args: ["--autoload-path", "path/to/vendor/autoload.php"] # Set your vendor autoload path.
   ```

   **Approach B: Local Overrides (Verbose but flexible)**
   Keep using the official PureScript registry as your base, and manually define all PHP overrides using the `extraPackages` directive (see the [`phpurs-starter` `spago.yaml`](https://github.com/0x000000000000000000001/phpurs-starter/blob/master/spago.yaml) for a complete working example). While this makes your `spago.yaml` quite verbose, it grants you total freedom to bump the official registry version (`registry: XX.X.X`) independently, without waiting for the custom registry maintainer.
   ```yaml
   workspace:
     packageSet:
       registry: 77.7.0
     extraPackages:
       prelude:
         git: "https://github.com/0x000000000000000000001/phpurs-prelude.git"
         ref: "master"
         dependencies: []
       # ... all other phpurs-* packages (see the phpurs-starter link above for full list)
     backend:
       cmd: phpurs
       args: ["--autoload-path", "path/to/vendor/autoload.php"]
   ```
   *Alternatively, you can pass the backend directly via CLI:*
   ```bash
   spago build --backend phpurs --backend-args "--autoload-path path/to/vendor/autoload.php"
   ```

3. **Execute your application:**
   The compiler will parse all `corefn.json` files generated by `purs` and output native PHP files in the `output/` directory. 
   
   For every module that exports a `main` function, an executable entrypoint script will be automatically generated. You can run it directly:
   
   ```bash
   php output/App.Main/main.mod.php
   ```
   
   *Note*: If you passed the `--bundle` option to the compiler, it will generate a completely standalone `main.bundle.php` file instead, which concatenates all of its dependencies into a single script. In that case:
   ```bash
   php output/App.Main/main.bundle.php
   ```
   
   > **Real-world example:** For a practical demonstration of how to orchestrate execution across multiple entrypoints, check out the [b8x.pub](https://github.com/0x000000000000000000001/b8x.pub) repository. It utilizes a dedicated [`bin/run`](https://github.com/0x000000000000000000001/b8x.pub/blob/main/bin/run) shell script wrapper that gracefully abstracts the underlying `main.mod.php` execution.


### Compiler configuration options

The `phpurs` compiler is entirely **zero-config by default**. It will automatically scan your `corefn` ASTs to detect any module exporting a `main` function, and will instantly generate a ready-to-execute `main.mod.php` entrypoint in its respective output directory.

If you need advanced behavior, you can pass arguments to the `phpurs` compiler by appending them to the `spago build --backend-args` command:

```bash
spago build --backend phpurs --backend-args "--bundle --autoload-path vendor/autoload.php"
```

| Option | Description |
|---|---|
| `--main <Module>` | *Optional*. Explicitly restricts compilation and Dead Code Elimination (DCE) to the specified entrypoint. Without this flag, `phpurs` automatically generates entrypoints for all modules exporting `main`. |
| `--bundle` | Concatenates all generated PHP code into a single file (`main.bundle.php` for each entrypoint, and a global `bundle.php` fallback). Excellent for minimal deployments, but **not necessarily more performant**. Depending on your PHP OPcache configuration, relying on multiple lazy-loaded files (`require_once`) might actually yield better startup times. |
| `--autoload-path <Path>` | Instructs the generated entrypoints on where to find the Composer `autoload.php` script relative to your project root. Defaults to `vendor/autoload.php`. |

## PHP dependencies

You should manage your own PHP dependencies using the standard `composer.json` file at the root of your project:
```bash
composer require mpdf/mpdf
```

Some PureScript dependencies (such as `purescript-aff`) rely on underlying PHP libraries via FFI (like `revolt/event-loop`). When you build your project with `phpurs`, the compiler will automatically scan your `.spago` dependencies for any PHP requirements and generate an isolated, internal package definition containing them at **`output/composer.json`**.

To seamlessly merge these FFI dependencies into your project without any friction, you should add `output/` as a local Path Repository in your project's root `composer.json` file:

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

Once this is set up, you can run standard Composer commands normally without any special environment variables:

```bash
composer update
composer install --no-dev --optimize-autoloader
```
Your IDE, autocomplete, and lockfiles will work natively and flawlessly.

> **Note on Custom Directory Structures:** If you prefer to keep your PHP environment isolated in a subdirectory (e.g., `run/bak/php/composer.json`) rather than the root, simply adjust the `url` to be relative to the location of the `composer.json` file (e.g., `"../../../output"`).

## Asynchronous I/O and Concurrency (Aff)

`phpurs` provides native asynchronous effects (`Aff`) by leveraging PHP 8.1+ Fibers and the [Revolt](https://revolt.run/) event loop. 

To take full advantage of this concurrency model without blocking the event loop, **you must use asynchronous, non-blocking PHP libraries** (such as [Amphp](https://amphp.org/) components like `amphp/postgres` or `amphp/http-client`) when writing FFI bindings for I/O operations. 

A good rule of thumb is to **act exactly as if you were importing Node.js libraries**: you must strictly seek out PHP packages that manage asynchronous I/O natively. If you use standard synchronous PHP functions (like `PDO` or `file_get_contents`) inside an `Aff` block, you will freeze the entire event loop, preventing all other concurrent fibers from executing. Alternatively, you can manage the `Fiber` and `Revolt` calls yourself to manually wrap blocking operations.

**Why native Fibers instead of Swoole?**
While `phpurs` could have been built around powerful daemonized extensions like [Swoole](https://swoole.co.uk/) or FrankenPHP, doing so would have compromised the core promise of the project: the ability to compile and deploy highly-concurrent PureScript into cheap, standard "containerless" environments (like a basic VPS or shared hosting). By relying exclusively on native PHP 8.1+ Fibers, `phpurs` ensures that your asynchronous PureScript code can run natively anywhere PHP runs, without requiring custom C extensions or complex server-side configurations.

## Architecture

`phpurs` is built on top of [Arista's purescript-backend-optimizer](https://github.com/aristanetworks/purescript-backend-optimizer) to avoid reinventing the optimization wheel. The compilation pipeline is functionally decoupled:

1. **Optimization**: The optimizer reads the `corefn.json` generated by `purs`, performs aggressive Dead Code Elimination (DCE), typeclass dictionary resolution, inlining, and constant folding at the AST level, and outputs an optimized `BackendModule`.
2. **Code Generation**: `Phpurs.CodeGen` maps this heavily optimized PureScript AST to our native `PhpAst`.
3. **Printing**: `Phpurs.Printer` formats the PHP AST into valid, modern PHP 8.4+ syntax.
4. **Caching & CLI**: `Main` orchestrates the CLI, writing the generated `.php` files to their respective module directories. 

To support lightning-fast **incremental compilation**, the `phpurs` compiler itself is compiled using `purs-backend-es`. This architectural choice translates PureScript's in-memory data structures into plain JavaScript objects (instead of ES6 classes). Because of this, the compiler's internal AST state becomes perfectly isomorphic to native JSON, allowing `phpurs` to seamlessly dump and reload its optimization cache (`.phpurs-cache.json`) to disk without writing any complex parsing logic.

## License

MIT License. See [LICENSE](LICENSE) for details.
