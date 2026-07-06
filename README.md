# phpurs

An experimental PureScript to PHP compiler backend, entirely written in PureScript.

`phpurs` leverages the intermediate `CoreFn` representation to compile your pure business logic into robust, modern PHP 7.4/8+ code. It seamlessly integrates into your existing PureScript workflow as a custom backend.

## Why PHP?
While the broader JS ecosystem has heavily leaned towards TypeScript, the PHP world (which still powers roughly **70% of the web**) has somewhat been left without a true, strictly-typed functional alternative. Furthermore, a massive portion of the web relies on legacy shared hosting or "containerless" VPS environments where escaping the PHP runtime is either impossible, financially unviable, or technically difficult. Just a few examples: cPanel/Plesk, GoDaddy, Bluehost, SiteGround, standard HostGator/OVH shared plans, basic LAMP stacks…

Recently, we've observed a genuine surge of interest for strict typing and functional concepts within the PHP community (with recent PHP versions and RFCs: short closures, match expressions, pipe operators and partial application), although the frustration that comes with its current limitations is just as real. `phpurs` aims to provide a "comfort tool" for any PHP developer looking to finally take the leap towards a much safer paradigm. It opens a door for those who want the elegance of a purely functional language like PureScript, completely bypassing the JS/TS ecosystem while keeping deployment as simple as an FTP upload of `.php` files, a massive win for legacy or small projects, which constitute the majority of the web today.

For the PureScript ecosystem, this represents a strategic opportunity to gain visibility and traction by tapping directly into the largest demographic of web developers in the world.

## Current Status & Optimizations

This project is evolving rapidly. To prove the viability of the concept, it successfully compiles and executes a universal multi-runtime pedagogical benchmark suite (`altbak.pub`), which runs identical PureScript code across Node.js, Arista ES, Chez Scheme, Erlang BEAM… and natively on PHP. The tested features include Tail-Call Optimization (TCO) via `while(true)` loops, purely recursive algorithms (Fibonacci, Ackermann, Red-Black Trees), deep record updates, heavy polymorphism (Type Class dictionary lookups), as well as State Monad operations and Lazy Evaluation.

While initially an unoptimized proof-of-concept, we have recently implemented several key performance optimizations tailored specifically for the PHP runtime:
- **Native ADT Representation:** PureScript Algebraic Data Types (ADTs) are now represented using lightweight, statically-defined native PHP classes (`Phpurs_DataN`) instead of generic structures, drastically reducing memory footprint and instantiation overhead.
- **IIFE Flattening & Native TCO:** To accommodate PHP's execution model and avoid call stack exhaustion, nested Immediately Invoked Function Expressions (IIFEs) are flattened into linear statements. This paves the way for our robust Tail-Call Optimization (TCO), implemented via native `while(true)` loops.
- **Inline Static Uncurrying:** To address the performance hit inherent to curried functions and partial application in PHP, we employ an inline static uncurrying strategy. By statically generating partial application closure wrappers (up to arity 4) directly inside the function body, we bypass expensive generic variadic fallbacks (`array_merge`/`array_slice`). This achieves a massive 40% speedup on partial applications, maintains near-zero overhead on fully applied functions, and ensures 100% backward compatibility with FFI closures.
- **Pattern Matching as Native Jump Tables:** PureScript `Case` expressions handling ADT destructuring are aggressively translated into PHP `switch` jump tables instead of sequential `if-elseif` cascades. This reduces pattern matching dispatch to an `O(1)` operation, heavily optimizing recursive algorithms and State Monads.
- **Static Type Class Resolution:** PureScript's heavy reliance on Type Class dictionary passing often incurs severe indirection overhead. We implemented an aggressive simplification pass (`simplify`) that intercepts dictionary lookups at compile-time, rewriting them as direct function calls whenever possible. This optimization single-handedly reduced the execution time of polymorphic algorithms by nearly 75%.
- **Constant Folding & Intrinsic Operator Inlining:** Mathematical, string, and boolean operators are aggressively folded at compile time whenever possible. When variables are involved, the compiler translates operations (like `Data_Semiring_intAdd` or `Data_Semigroup_concatString`) directly into native PHP operators (`+`, `.`, `&&`, etc.), completely bypassing functional closures and drastically accelerating hot loops.
- **Global Thunk Hoisting:** By analyzing variable dependencies during the compilation of tail-recursive functions, global thunk evaluations (`$GLOBALS['...'] ?? phpurs_eval_thunk(...)`) are hoisted strictly outside of the `while(true)` loop. This eliminates redundant dictionary lookups and closure evaluations on every iteration.
- **Zero-Cost Lazy Initialization:** Traditional JavaScript-inspired approaches in PHP involve allocating thousands of file-level closures (`function() use (...)`) during `require_once` to handle currying constraints and recursive bindings. This previously caused massive startup overhead (~5.2s). We completely re-architected the initialization phase to utilize a centralized, lazy-loading mechanism powered by PHP's highly optimized null-coalescing operator (`??`) and static `switch` statement thunks. This ensures variables are only evaluated when first accessed, bringing startup initialization time effectively down to near zero.
- **Zero-Cost ADT Singletons:** 0-arity constructors (such as `Nil` or `Nothing`) are statically compiled as global singletons utilizing PHP's null coalescing assignment operator (`??=`). This fundamentally eradicates garbage collection pressure and instantiation overhead when generating or traversing massive functional data structures (e.g. linked lists or trees).
- **Native Asynchronous Effects (`Aff`):** Leveraging PHP 8.1+ Fibers and the Revolt event loop, asynchronous operations (`Aff`) are executed concurrently without blocking the main OS thread. This allows the compiled PHP code to elegantly mirror the non-blocking I/O behavior traditionally seen in Node.js.

Currently supported AST nodes: `Abs` (Lambdas), `Var` (Variables), `App` (Function application), `Let` (Bindings), `Case` (Pattern Matching), `Constructor`, `Literal`, `Accessor` and `Update` (Records).

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
   Back in your project directory, Spago will automatically resolve the PHP core packages and compile your `Main.purs` into PHP.
   ```bash
   npm run build  # Compiles to output/
   npm start      # Compiles and executes output/run.php
   ```

### Manual Configuration

If you wish to configure an existing project manually, `phpurs` acts as a drop-in backend for the Spago build system.

1. **Override Core Libraries (`spago.yaml`):**
   Because standard PureScript libraries use JavaScript FFI, you must override them with their `phpurs-*` counterparts using Git dependencies in your `workspace.extraPackages`. See the [starter template's spago.yaml](https://github.com/0x000000000000000000001/phpurs-starter/blob/master/spago.yaml) for the full list of required overrides.

2. **Specify Backend (`spago.yaml`):**
   ```yaml
   workspace:
     backend:
       cmd: phpurs
   ```
   *Alternatively, you can pass it directly via CLI:*
   ```bash
   spago build --backend phpurs
   ```

3. **Check the output:**
   The compiler will parse all `corefn.json` files generated by `purs` and output native PHP files in the `output/` directory, mirroring the module hierarchy.

### Compiler Configuration Options

You can pass arguments to the `phpurs` compiler by appending them to the `spago build --backend-args` command:

```bash
spago build --backend phpurs --backend-args "--main App.Main --bundle"
```

| Option | Description |
|---|---|
| `--main <Module>` | Specifies the entry point. The compiler will perform Dead Code Elimination (DCE) keeping only the code reachable from this module, and generate an executable `output/main.php`. |
| `--bundle` | Concatenates all generated PHP code into a single file (`output/bundle.php`). Excellent for minimal deployments, but **not necessarily more performant**. Depending on your PHP OPcache configuration, relying on multiple lazy-loaded files (`require_once`) might actually yield better startup times. |

## PHP Dependencies

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

## Architecture

The compilation pipeline is functionally decoupled. First, `Phpurs.CoreFn` decodes `corefn.json` into a PureScript Algebraic Data Type. Then, `Phpurs.CodeGen` maps the PureScript CoreFn AST to the `PhpAst`, and `Phpurs.Printer` formats it into valid PHP syntax. Finally, `Main` orchestrates the CLI, reading the `output/` directory and writing the generated `.php` files to their respective module directories.

## Future Roadmap

Our future roadmap includes advanced optimizations such as aggressive inlining and mutual recursion TCO, and a comprehensive FFI implementation for the broader PHP ecosystem.
