# Expression lowering and AST traversal

## Expression translation contracts

`translateExpr context nextId expr` returns a `TranslationResult`:

- `stmts` must execute before `expr` is consumed.
- `nextId` is the next available temporary/label ID. Thread it through sibling
  translations; independently restarting the counter can alias live locals.
- `TranslationContext.boundVars` maps optimizer local IDs to renamed PHP locals.
- `recursiveVars` identifies captures that need PHP references so a closure can
  observe its own eventual initialization.
- `loops` and `isTail` identify legal tail jumps. Arguments are evaluated into
  temporaries before loop parameters are reassigned, preserving simultaneous
  updates. Direct parameter registers are used only outside private-constructor
  modules and when no nested TCO loop can capture them; other loops keep the
  buffered `__tco_var_*` slots.
- `inEffectBlock` says the expression is already inside an effect-executing body.
  Otherwise an effect node is wrapped in a zero-argument closure.

`translateValue` resets the tail/effect position for operands while retaining
their lexical scope. Binding bodies inherit the surrounding position; a new
function body starts its own tail-call scope. `translateValues` shares operand
lowering and temporary allocation across arrays, constructors and calls.

`translateValues` builds its statement and expression arrays inside one local
`ST` region. Each operand is translated left-to-right with the preceding
operand's `nextId`; only its new statements and result expression are appended.
Both arrays are freshly allocated and frozen after the last operand. No mutable
array escapes during construction, and previously returned arrays are never
reused. Accumulation is linear in the number of operands plus emitted statements,
in addition to the cost of translating the operands themselves. Statements are
appended individually, so a large result does not become a spread-argument call.
`operand-accumulator.mjs` checks copied-prefix bounds, wide arrays, IDs, statement
order and escaping captures. The [B4 measurement](../../audit/2026-10-03/linear-accumulator/report.md)
records the observed effect on a real Unicode-data module and the full corpus.

`Phpurs.FreeVars.freeVars` shares PBO's `freeVarsWith` analysis step and memoizes
each recursively visited `TcoExpr`. The step retains PBO's binding rules: lambda
parameters are removed from their body, a `Let`/`EffectBind` binder only scopes
over its body, and a recursive group scopes over all its bindings and its body.
`Typed` and `TypeApp` delegate to their expression child. TCO usage metadata
includes bound locals too, so it cannot directly supply these free-variable sets.

The memo key is the immutable node object; the value is an immutable set of
**original local-ID strings**. Renaming through `boundVars` and reference capture
through `recursiveVars` happen on every use in the current context. A shared node
can therefore be translated in different lexical and effect contexts safely.
Any TCO rewrite must construct new nodes rather than mutate an analyzed object.

The JavaScript memo table uses weak keys. Its values contain no references to the
TCO graph, allowing the graph and its cached sets to be collected when translation
releases them. `translate` constructs fresh TCO nodes for each invocation. The
table is process-local and independent of serialized module state and the PBO
implementation-cache budget. V8 can retain the weak table's allocated capacity
after collecting its keys. A node's syntax is analyzed once while it remains
live; set operations still cost according to their cardinalities, and live cached
sets can require more than linear space in pathological trees.
`free-vars.mjs` checks lexical scopes, shared subtrees, context-specific captures,
recursive PHP closures, lazy effects and garbage collection. The
[B4 free-variable measurement](../../audit/2026-10-03/free-vars/report.md) records
recomputation counts, module timings, live-cache heap cost and full-build RSS.

Tail-loop construction has two shared helpers:

- `translateTailJump` saves all arguments into temporaries, updates the loop
  slots, then jumps. Curried and uncurried calls use the same protocol.
- `wrapLoopBody` initializes those slots once, places the loop label, then binds
  the parameters on each iteration. Local closures and top-level functions use
  the same body layout. Local loop lowering accepts a single recursive function;
  other recursive groups use the ordinary reference-capturing closures.

Key invariants to preserve in a refactor:

- **Short-circuiting:** the right operand of `&&` or `||` may produce statements.
  Those statements belong inside a deferred operand, not before the operator.
- **Remaining arity:** an application can keep an annotation containing arguments
  already supplied, including dictionaries. `remainingArity` is shared by global
  forwarding wrappers and the printer's arity table. The flattened function type
  used for signatures serves a different purpose.
- **Callable effect results:** `EffectFn` and `EffectBind` execute opaque actions
  once. An explicit effect body has already produced its result, which may itself
  be an action or canceler. `Typed` and `TypeApp` preserve this distinction.
  `effect-results.mjs` checks execution order and repeated use of returned actions.

For ownership rewrites, read the contract at the top of `ArrayRefs.purs` together
with `array-refs-ownership.mjs`: a last use at one call site is insufficient when
another binding retains an alias or a constructor shares a child.

## PHP AST traversal contracts

The shared traversal module provides structural operations with explicit scope:

- `children` includes nested function bodies and global initializers;
  `localChildren` treats those declarations as opaque.
- `mapChildren` maps every direct expression child, including function bodies.
  Its caller controls recursion and any scope-sensitive rewriting.
- `mapBranches` rewrites the statement lists of `if` and `switch`; `mapBlocks`
  also handles `while`. Both retain conditions, case labels and nested functions.

Passes own their eligibility rules and budgets. In particular,
`CopyCleanup.supportedChildren` is an allowlist that can reject a worker, whereas
the shared child enumeration only describes structure. Enumeration order is not
PHP evaluation order: the shared match traversal lists the fallback before the
arms; `ArrayRefs` explicitly retains its arms-before-fallback scan order.

When extending `PhpExpr`, update both exhaustive matches in `PhpAst.Traversal`
and review each optimization's admissible forms and scope assumptions. The
`ast-traversal.mjs` test enumerates the constructors exported by the module, so
a new form without a sample (and therefore without a traversal decision) fails
there.

Three forms replaced raw text and must not be reintroduced as `PhpRaw`:

- `PhpDirectCall` resolves a generated module global through `$GLOBALS`;
- `PhpNamedCall` prints a literal PHP function name (builtins and runtime
  helpers such as `count`, `\intdiv`, `\phpurs_execute_effect`);
- `PhpGlobalSlot` and `PhpUnset` model `$GLOBALS['...']` and `unset($x)`.

The remaining `PhpRaw` uses are documented exceptions: the Array traversal
guards and `foreach` template, and the generated pattern-match error message.
