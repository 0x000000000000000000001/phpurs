# Printing contracts

## Printing contracts

`printExpr arities expr` uses the arity table to select saturated native calls.
Global references carry their module qualification in the PHP AST. Their global
keys and native names go through the same identifier construction before their
respective escaping rules are applied. The foreign-value marker selects the
demand-time read above, including callable values in ordinary and direct AST calls.

`genCurry arities params returnType captures body` renders a closure. Capture
clauses and return signatures have shared renderers. `printCurryStatements`
rewrites returns to a common exit before applying extra arguments to the result;
that rewrite stays within the current function's scope.

For scalar monomorphic native declarations, `printPhpFile` also emits a private
`name__phpurs_exact` body. Eligibility requires every argument and the return to
map to `int`, `float`, `string` or `bool`, the emitted argument count to equal
the runtime arity, no suffix collision, and a non-compact-loop declaration. Only
calls with exactly that arity target the body; partial, overapplied, polymorphic,
ADT, reference and FFI paths keep the public wrapper. The exact body keeps the
wrapper's first-parameter check and required arguments. Its return is precise
when the body has no nested closure; otherwise it keeps `T|\Closure`, because an
alias can return a callable that the scalar annotation does not describe.
Internal exact calls omit the public wrapper frame, so stack inspection and
return-check diagnostics can differ from a public call.

`Printer.Runtime.sharedRuntime` is emitted once per application. `sharedRuntimeFile`
provides the module-side `runtime.php`; `bundleHeader` embeds the same helpers in a
global namespace block at the start of each bundle. Generated calls are fully
qualified as `\phpurs_*`. Modular files emit a guarded `require_once` for
`runtime.php` only when they reference a helper; the runtime file is owned by the
modular output manifest. It emits
only the pieces referenced by the assembled module body: the generic data-class
block (arities 0–12) when a `Phpurs_DataN` name occurs, the curry fallback when
a wrapper uses it, and the effect/reference helpers when their functions are
referenced. The fixed-parameter curry fallbacks (one to four missing arguments)
and variadic fallback remain common templates. Effect and reference helpers are
kept as readable PHP blocks in the same module.
