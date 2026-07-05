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

Currently supported AST nodes: `Abs` (Lambdas), `Var` (Variables), `App` (Function application), `Let` (Bindings), `Case` (Pattern Matching), `Constructor`, `Literal`, `Accessor` and `Update` (Records).

## How to use

`phpurs` acts as a drop-in backend for the Spago build system.

1. **Install the backend (e.g. link it locally):**
   ```bash
   npm link
   ```

2. **Configure your project to use the backend:**
   In your target PureScript project (`spago.yaml`), specify `phpurs` as the backend:
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

## Architecture

The compilation pipeline is functionally decoupled. First, `Phpurs.CoreFn` decodes `corefn.json` into a PureScript Algebraic Data Type. Then, `Phpurs.CodeGen` maps the PureScript CoreFn AST to the `PhpAst`, and `Phpurs.Printer` formats it into valid PHP syntax. Finally, `Main` orchestrates the CLI, reading the `output/` directory and writing the generated `.php` files to their respective module directories.

## Future Roadmap

Our future roadmap includes advanced optimizations such as aggressive inlining and mutual recursion TCO, a comprehensive FFI implementation for the broader ecosystem, and `Aff` translation utilizing PHP 8.1 Fibers (Revolt).
