import { createHash } from 'node:crypto';
import { isAbsolute } from 'node:path';

export const fingerprintBytesImpl = bytes => createHash('sha256').update(bytes).digest('hex');

// Explicit tuples give every field a position; tags separate the key domains.
// Bump the schema when this encoding or the cache-state contract changes.
const hash = (tag, ...fields) => fingerprintBytesImpl(
  Buffer.from(JSON.stringify(['phpurs/module-cache-key', 1, tag, ...fields]), 'utf8'));

export const planKeysImpl = left => right => ({ toolchain: t, options: o, directives }) => modules => {
  if (!isAbsolute(o.cwd)) return left('Cache keys require an absolute working directory');
  const names = new Map();
  for (const [index, input] of modules.entries()) {
    if (input.name === '' || names.has(input.name)) return left('Empty or duplicate cache module: ' + input.name);
    names.set(input.name, index);
  }
  const dependencies = modules.map(input => [...new Set(input.dependencies)].filter(name => name !== input.name).sort());
  for (const [index, deps] of dependencies.entries()) {
    for (const name of deps) {
      if (names.has(name) && names.get(name) >= index) {
        return left('Cache dependency ' + name + ' must precede ' + modules[index].name);
      }
    }
  }

  const context = hash('context',
    [t.phpursVersion, t.pboVersion, t.backend, t.nodeVersion, t.v8Version, t.platform, t.arch],
    [o.cwd, o.outputDir, o.ffiRoots, o.emitModules, o.emitBundle, o.mainModule, o.autoloadPath, o.rewriteLimit],
    directives, modules.map(input => input.name));
  let prefix = hash('prefix-start', context);
  const completed = new Map();
  const keys = modules.map((input, index) => {
    const ffi = input.foreignInput;
    const local = hash('input', input.name, input.coreFn, [ffi.kind, ffi.path, ffi.content]);
    // Absent modules (including Prim.*) have an explicit null marker. Adding
    // them later changes the ordered graph in the context key.
    const deps = dependencies[index].map(name => [name, completed.get(name) ?? null]);
    const key = hash('module', context, local, prefix, deps);
    completed.set(input.name, key);
    // PBO directives/private globals and PHP arities depend on the entire
    // preceding stream, including modules absent from the declared imports.
    prefix = hash('prefix-step', prefix, input.name, key);
    return { name: input.name, key };
  });
  return right({ context, modules: keys, key: hash('plan', context, prefix) });
};
