# File emission contracts

`Main` discovers and reads foreign files only when `backendMod.foreign` is
nonempty, selects reachable modules and writes the generated files. Modules with
no foreign bindings pass an empty source to FFI assembly, even if an adjacent or
fallback PHP file exists. All loaded modules still contribute their package roots
to Composer discovery. Pure rendering is delegated through two interfaces:

- `genForeignModule { moduleName, bindings, source }` accepts the original FFI
  source and returns `{ code, arities }`. Each foreign signature is flattened
  once for both the wrapper and its arity marker. The internal wrapper record names
  the global key, native function name, optional export table, escaped export key
  and missing-export diagnostic;
  global-key escaping and native-function escaping are distinct operations.
- `printModularEntryPoint` and `printBundleEntryPoint` share
  `{ mainModule, autoloadPath, arities }` options and the same startup sequence: load
  Composer, install the exception handler, call main, then run Revolt. Modular
  entrypoints receive the reachable dependencies in topological order and require
  them before the main call. Both use the printer's global-read convention for
  `main`, including a missing raw foreign action.

Missing exports are diagnosed according to [diagnostics.md](diagnostics.md).

Foreign arities take precedence over current-module arities, which take precedence
over previously emitted modules. Keep that merge before printing either form of
the module so direct calls and public wrappers agree.

