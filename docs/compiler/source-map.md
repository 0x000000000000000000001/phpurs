# Source map

Generated from module headers by `npm run docs:source-map`. Do not edit by
hand; `npm run check:source-map` fails when this page is stale.

| Module | Responsibility |
| --- | --- |
| [`Main`](../../src/Main.purs) | Load typed modules, invoke the optimizer and coordinate PHP file emission. |
| [`Main.Options`](../../src/Main/Options.purs) | Parsed build options shared by the Main orchestration. |
| [`Main.Outputs`](../../src/Main/Outputs.purs) | Final PHP output emission: entrypoints, bundles, runtime, Composer and bounded cleanup of obsolete owned files. |
| [`Main.Prepare`](../../src/Main/Prepare.purs) | Capture FFI inputs, resolve package roots, build callback evidence and prepare the persistent cache for one build. |
| [`Main.Selection`](../../src/Main/Selection.purs) | Pure main selection and CoreFn import-closure helpers. |
| [`Phpurs.ArrayCallbacks`](../../src/Phpurs/ArrayCallbacks.purs) | Specialize only explicitly contracted FFI traversals. |
| [`Phpurs.ArrayRefs`](../../src/Phpurs/ArrayRefs.purs) | Promote private worker parameters to PHP references when every call site |
| [`Phpurs.ArrayRefs.Analysis`](../../src/Phpurs/ArrayRefs/Analysis.purs) | Aggregate entry points for call-site discovery, decisions and the fixpoint. |
| [`Phpurs.ArrayRefs.CallSites`](../../src/Phpurs/ArrayRefs/CallSites.purs) | Call-site discovery for array-reference promotion. |
| [`Phpurs.ArrayRefs.Decision`](../../src/Phpurs/ArrayRefs/Decision.purs) | Per-site and per-owner safety decisions. |
| [`Phpurs.ArrayRefs.Fixpoint`](../../src/Phpurs/ArrayRefs/Fixpoint.purs) | Private-parameter promotion fixpoint. |
| [`Phpurs.ArrayRefs.Ownership`](../../src/Phpurs/ArrayRefs/Ownership.purs) | Track one candidate owner's aliases and field reads through a function. |
| [`Phpurs.ArrayRefs.Rebuild`](../../src/Phpurs/ArrayRefs/Rebuild.purs) | Rewrite owned-node rebuilds into in-place field writes. |
| [`Phpurs.ArrayRefs.Support`](../../src/Phpurs/ArrayRefs/Support.purs) | Small PHP-AST helpers shared by ownership analysis and rebuild rewriting. |
| [`Phpurs.AstMetrics`](../../src/Phpurs/AstMetrics.purs) | Count occurrences in the optimized expression tree, including Typed and TypeApp wrappers, but excluding type metadata and binding/group wrappers. |
| [`Phpurs.Budget`](../../src/Phpurs/Budget.purs) | Safety valves shared by the local passes. |
| [`Phpurs.BuildCache`](../../src/Phpurs/BuildCache.purs) | Connect a complete captured key plan to the optional module-state store. |
| [`Phpurs.BuildInputs`](../../src/Phpurs/BuildInputs.purs) | Capture each input once: the key and decoder/codegen consume the same bytes. |
| [`Phpurs.BuildProfile`](../../src/Phpurs/BuildProfile.purs) | Invocation-local, observational timings. |
| [`Phpurs.CacheKey`](../../src/Phpurs/CacheKey.purs) | Content keys for module-state reuse, in the builder's module order. |
| [`Phpurs.CallbackSpecialization`](../../src/Phpurs/CallbackSpecialization.purs) | Specialize a proven list fold at a known native binary Int callback. |
| [`Phpurs.CodeGen`](../../src/Phpurs/CodeGen.purs) | Module compilation pipeline. |
| [`Phpurs.CodeGen.Bindings`](../../src/Phpurs/CodeGen/Bindings.purs) | Binding-group analysis: TCO environments, safe recursive initialization order and lambda flattening. |
| [`Phpurs.CodeGen.Context`](../../src/Phpurs/CodeGen/Context.purs) | Shared state of expression translation: loop scope, lexical names and evaluation position. |
| [`Phpurs.CodeGen.Declarations`](../../src/Phpurs/CodeGen/Declarations.purs) | Emit module-level constructor classes, native functions and initialized values after binding-group analysis. |
| [`Phpurs.CodeGen.Effects`](../../src/Phpurs/CodeGen/Effects.purs) | Recognize explicit effect syntax and decide whether an expression must be executed through the runtime effect helper. |
| [`Phpurs.CodeGen.Expr`](../../src/Phpurs/CodeGen/Expr.purs) | Translate one typed optimizer expression while threading lexical scope, tail/effect position and temporary names through its children. |
| [`Phpurs.CodeGen.Expr.Prims`](../../src/Phpurs/CodeGen/Expr/Prims.purs) | Constructor, primitive-operator and effect rules for expression translation. |
| [`Phpurs.CodeGen.Expr.Statements`](../../src/Phpurs/CodeGen/Expr/Statements.purs) | Binding, effect and branch rules for expression translation. |
| [`Phpurs.CodeGen.Expr.Support`](../../src/Phpurs/CodeGen/Expr/Support.purs) | Pure helpers and the shared operand walker for expression translation. |
| [`Phpurs.CodeGen.Expr.Terms`](../../src/Phpurs/CodeGen/Expr/Terms.purs) | Expression-shaped translation rules: literals, variables, calls and closures. |
| [`Phpurs.CodeGen.Expr.Update`](../../src/Phpurs/CodeGen/Expr/Update.purs) | Record-update fusion and the typed re-entry that selects it. |
| [`Phpurs.CodeGen.Operators`](../../src/Phpurs/CodeGen/Operators.purs) | Lower primitive operators to PHP expressions. |
| [`Phpurs.CodeGen.Pipeline`](../../src/Phpurs/CodeGen/Pipeline.purs) | Ordered module-pass table and per-module statistics. |
| [`Phpurs.CodeGen.Tco`](../../src/Phpurs/CodeGen/Tco.purs) | TCO loop helpers: detect nested loops, select a tail target, save jump arguments and emit the loop entry protocol. |
| [`Phpurs.CodeGen.Types`](../../src/Phpurs/CodeGen/Types.purs) | PHP signatures and runtime arity derived from typed optimizer expressions. |
| [`Phpurs.CompactLoops`](../../src/Phpurs/CompactLoops.purs) | Compact captured functions only in a proven local flow through one TCO loop. |
| [`Phpurs.ComposerMerge`](../../src/Phpurs/ComposerMerge.purs) | Collect Composer requirements from resolved, ordered package roots. |
| [`Phpurs.CopyCleanup`](../../src/Phpurs/CopyCleanup.purs) | Local copy elimination after terminal inlining. |
| [`Phpurs.EntryPoint`](../../src/Phpurs/EntryPoint.purs) | Pure rendering of executable entrypoints. |
| [`Phpurs.EnumRegions`](../../src/Phpurs/EnumRegions.purs) | Closed scalar computations can use private ADT layouts. |
| [`Phpurs.EnumRegions.Proof`](../../src/Phpurs/EnumRegions/Proof.purs) | Proof that a closed scalar region can use private layouts. |
| [`Phpurs.EnumRegions.Rewrite`](../../src/Phpurs/EnumRegions/Rewrite.purs) | Apply a proven enum region: private layouts, constructor copies and call sites. |
| [`Phpurs.EnumRegions.Types`](../../src/Phpurs/EnumRegions/Types.purs) | Shared state for the enum-region proof and rewrite. |
| [`Phpurs.FileEmission`](../../src/Phpurs/FileEmission.purs) | Preserve generated PHP files whose UTF-8 bytes are already up to date. |
| [`Phpurs.ForeignTraversals`](../../src/Phpurs/ForeignTraversals.purs) | Exact contracts for the PHP traversal bodies consumed by ArrayCallbacks. |
| [`Phpurs.FreeVars`](../../src/Phpurs/FreeVars.purs) | Reuse exact scope-aware results for immutable TcoExpr nodes. |
| [`Phpurs.GenNativeForeign`](../../src/Phpurs/GenNativeForeign.purs) | Prepare a module's foreign exports and their public PHP calling wrappers. |
| [`Phpurs.Metrics`](../../src/Phpurs/Metrics.purs) | Measure named build phases and return the measured result. |
| [`Phpurs.ModuleCache`](../../src/Phpurs/ModuleCache.purs) | Optional, versioned storage. |
| [`Phpurs.ModuleState`](../../src/Phpurs/ModuleState.purs) | Per-module output and one publication path for fresh and restored modules. |
| [`Phpurs.NativeCallbacks`](../../src/Phpurs/NativeCallbacks.purs) | Evidence about native callback wrappers, separate from flattened type arity. |
| [`Phpurs.NullableConstructors`](../../src/Phpurs/NullableConstructors.purs) | Layout selection is consumed by the typed region proof. |
| [`Phpurs.Optimize.Names`](../../src/Phpurs/Optimize/Names.purs) | Collision-free prefixes for private workers and constructors. |
| [`Phpurs.Optimize.NeutralExpr`](../../src/Phpurs/Optimize/NeutralExpr.purs) | Shared inspection and construction of the optimizer's typed expressions. |
| [`Phpurs.Optimize.ScalarWorker`](../../src/Phpurs/Optimize/ScalarWorker.purs) | Shared scalar loop used after a thunk or State chain has been proven safe. |
| [`Phpurs.OutputManifest`](../../src/Phpurs/OutputManifest.purs) | Track successfully emitted PHP, including restored modules, for bounded removal of obsolete compiler-owned outputs after successful finalization. |
| [`Phpurs.PackagePaths`](../../src/Phpurs/PackagePaths.purs) | Resolve package roots once per build for PHP FFI lookup and Composer. |
| [`Phpurs.PartialBindings`](../../src/Phpurs/PartialBindings.purs) | Reuse a closure binding inside a closed, immediately consumed chain. |
| [`Phpurs.Pass`](../../src/Phpurs/Pass.purs) | Shared protocol for local optimization passes: a pure `BackendModule` rewrite that also reports the private names it introduced. |
| [`Phpurs.PhpAst`](../../src/Phpurs/PhpAst.purs) | Defines the Abstract Syntax Tree (AST) representing PHP code. |
| [`Phpurs.PhpAst.Traversal`](../../src/Phpurs/PhpAst/Traversal.purs) | Structural PHP AST traversal. |
| [`Phpurs.Printer`](../../src/Phpurs/Printer.purs) | Public PHP printer facade. |
| [`Phpurs.Printer.Context`](../../src/Phpurs/Printer/Context.purs) | Printer context shared by expression rendering and declaration emission. |
| [`Phpurs.Printer.Decl`](../../src/Phpurs/Printer/Decl.purs) | Render declarations, exact wrappers and complete PHP modules. |
| [`Phpurs.Printer.Expr`](../../src/Phpurs/Printer/Expr.purs) | Render PHP expressions, calls and currying forms. |
| [`Phpurs.Printer.Names`](../../src/Phpurs/Printer/Names.purs) | PHP names and global reads shared by the printer and declaration emitter. |
| [`Phpurs.Printer.Runtime`](../../src/Phpurs/Printer/Runtime.purs) | PHP runtime assembled once per output. |
| [`Phpurs.Printer.Support`](../../src/Phpurs/Printer/Support.purs) | Pure printer helpers: return annotations, captures, curry bodies and switch recognition. |
| [`Phpurs.PurmetaBudget`](../../src/Phpurs/PurmetaBudget.purs) | Parse and scope PHPurs's optional PBO RAM-cache budget. |
| [`Phpurs.PurmetaProfile`](../../src/Phpurs/PurmetaProfile.purs) | Scope optional PBO diagnostics to one PHP optimization/emission phase. |
| [`Phpurs.RewriteLimit`](../../src/Phpurs/RewriteLimit.purs) | Validate the effective per-binding optimizer iteration guard once, before loading inputs, then share it between the builder and persistent keys. |
| [`Phpurs.RuntimeNames`](../../src/Phpurs/RuntimeNames.purs) | Canonical names of the generated PHP runtime helpers. |
| [`Phpurs.StateFusion`](../../src/Phpurs/StateFusion.purs) | Immediately execute a proven State chain in a private scalar loop. |
| [`Phpurs.StateFusion.Prove`](../../src/Phpurs/StateFusion/Prove.purs) | Proof for an immediately executed State chain. |
| [`Phpurs.StateFusion.Rewrite`](../../src/Phpurs/StateFusion/Rewrite.purs) | Apply a proven State chain: private scalar workers, guards and call-site rewrites. |
| [`Phpurs.StateFusion.Types`](../../src/Phpurs/StateFusion/Types.purs) | Shared types for the State-fusion proof and its rewrite. |
| [`Phpurs.TailInline`](../../src/Phpurs/TailInline.purs) | A single, bounded pass over native functions. |
| [`Phpurs.TailInline.Proof`](../../src/Phpurs/TailInline/Proof.purs) | Pure predicates and name scans used by tail inlining. |
| [`Phpurs.TailInline.Rewrite`](../../src/Phpurs/TailInline/Rewrite.purs) | Return simplification and local renaming for tail inlining. |
| [`Phpurs.ThunkFusion`](../../src/Phpurs/ThunkFusion.purs) | Fuse an immediately forced, closed chain into a scalar TCO worker. |
