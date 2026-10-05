# Optimizer counts and rewrite limit

## AST-node counts

`--verbose` prints `Generating PHP code for <Module> (Total AST Nodes: <N>)`
for each freshly optimized module. `N` is the sum of expression occurrences in
all top-level binding groups of the optimized `BackendModule`, before PHP
translation. Both recursive and non-recursive groups contribute.

Every `BackendSyntax` node contributes one, including `Typed`, `TypeApp`,
`PrimUndefined`, `Fail` and constructor definitions. All expression children
contribute recursively: array/record elements, record update values, call heads
and arguments, local bindings and bodies, branch conditions/results/fallbacks,
primitive operator operands, reference-effect operands and constructor fields.

Types, identifiers, binder lists, field labels, binding-group containers and FFI
source text are metadata rather than expression nodes. Sharing an expression
object in two tree positions counts both occurrences. This is an optimized PBO
expression count, distinct from source CoreFn size, generated PHP size or PBO's
internal optimization-cost analysis.

`Phpurs.AstMetrics` uses PBO's complete `Foldable BackendSyntax` child enumeration
with a tail-recursive list worklist. Traversal avoids recursion proportional to
tree depth and avoids the composed-function fold that can overflow on wide
nodes. Regression inputs cover 100,000 nested wrapper pairs, array elements,
record fields, call arguments and branch pairs.

The traversal and progress lines are skipped without `--verbose`, and on
persistent module-state hits. With `--profile-build`, counting and logging are
included in the `diagnostics` interval. See [build profiling](build-profile.md).

## Rewrite limit

```sh
phpurs --main App.Main --bundle --rewrite-limit 10000
phpurs --main App.Main --bundle --no-cache --rewrite-limit=100
```

The default remains **10,000**. Both spellings accept decimal digits with a
numeric value from **1 to 2,147,483,647**, the positive PureScript `Int` range.
Leading zeroes are accepted. Missing values, zero, negative values, fractions,
exponents, hex and out-of-range integers fail before input loading or emission.
As with the other PHPurs numeric option, the first occurrence wins, including
when the two spellings are mixed. Spago's space-grouped backend arguments are
supported.

PHPurs passes this value to PBO's existing `rewriteLimit` builder option. PBO
initializes an iteration guard for each top-level binding; for the iterative
path, another pass consumes the remaining guard when analysis requests further
rewriting. Exhaustion throws `<Module>.<binding>: Possible infinite optimization
loop.` A low guard can therefore reject a terminating optimization. It does not
return a partially optimized binding as a successful result.

This option retains PBO's current semantics, including its separate chunked path
for large expressions. It is not an AST-node budget, total rewrite counter or
wall-clock timeout. The measured production choice remains 10,000, as explained
below.

The validated effective value is shared by the builder and the persistent
cache-key context. Changing it prevents reuse under another limit across the
loaded graph, even if the generated PHP would be identical. Equivalent numeric
spellings, including the explicit default, share keys. Previously stored keys
can be reused when returning to their limit. `--no-cache` uses the same guard
without reading or storing persistent states. See [cache keys](cache.md).

## Measured default

The [5 October 2026 comparison](../audit/2026-10-05/rewrite-tradeoff/report.md)
retains **10,000**. The frozen b8x graph (2,684 modules) needs at most **11 passes
per binding**, including the final pass that requests no further rewrite. The
benchmark graph (306 modules) needs at most **8**. Guards 10 and 7 respectively
reject terminating bindings in those graphs.

At sufficient guards, the optimizer performs the same work: normalized pass and
chunk traces match at 10,000 versus each observed minimum. Nine builds per corpus
compare 11, 100 and 10,000 in rotating order. All successful builds preserve PHP
bytes and sizes, and the fourteen benchmark values pass at every compared guard.
Timing ranges overlap and do not improve monotonically as the guard is lowered.
The guard therefore supplies convergence headroom, with no measured code-quality
or size tradeoff supporting a smaller default. The observed minima apply to those
specific loaded graphs; they are not a general upper bound for other programs.

## Validation

- `tests/codegen/ast-metrics.mjs`: every syntax form, nested literal/update/branch
  children, type-metadata exclusion, repeated occurrences, recursive module
  groups, and deep/wide worklists.
- `tests/codegen/build-profile.mjs`: exact optimized counts through the CLI in
  all three emission modes, with the existing optional-diagnostics checks.
- `tests/codegen/rewrite-limit.mjs`: numeric validation and precedence; grouped
  CLI arguments; cache partitioning and equivalent spellings; unchanged PHP
  bytes/mtimes and runtime; invalid values before I/O; a real multi-pass binding
  that fails at limit 1 after a warm build and succeeds at a sufficient limit.
- The [B4 audit](../audit/2026-10-03/ast-rewrite-limit/report.md) compares frozen
  executables on the large b8x corpus with complete diagnostics enabled.
