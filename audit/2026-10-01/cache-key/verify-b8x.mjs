// Compare planned keys with the already validated M0 PHP differences, without
// running the optimizer again. All variants represent one logical workspace.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { Right } from '../../../output/Data.Either/index.js';
import { Just, Nothing } from '../../../output/Data.Maybe/index.js';
import { fingerprintBytes, NoForeign, MissingForeign, ForeignSource, planKeys } from '../../../output/Phpurs.CacheKey/index.js';
import { resolvePackagePaths, findForeignFile } from '../../../output/Phpurs.PackagePaths/index.js';
import { defaultDirectives } from '../../../output/PureScript.Backend.Optimizer.Directives.Defaults/index.js';

const [snapshotArg, measurementsArg, artifactsArg] = process.argv.slice(2);
assert.ok(snapshotArg && measurementsArg && artifactsArg,
  'Usage: node verify-b8x.mjs SNAPSHOT M0_ARTIFACTS EMPTY_ARTIFACTS');
const snapshot = path.resolve(snapshotArg);
const measurements = path.resolve(measurementsArg);
const artifacts = path.resolve(artifactsArg);
fs.mkdirSync(artifacts, { recursive: true });
assert.deepEqual(fs.readdirSync(artifacts), [], 'artifact directory must be empty');
const repo = fileURLToPath(new URL('../../../', import.meta.url));
const raw = JSON.parse(fs.readFileSync(path.join(measurements, 'results.json'), 'utf8'));
const provenance = JSON.parse(fs.readFileSync(path.join(repo, 'audit/2026-10-01/build-scenarios/measurements.json'), 'utf8'));
const order = JSON.parse(fs.readFileSync(path.join(measurements, 'fresh-unchanged.modules.json'), 'utf8'));
const readInputs = output => order.map(name => {
  const bytes = fs.readFileSync(path.join(output, name, 'corefn.json'));
  const module = JSON.parse(bytes.toString('utf8'));
  assert.equal(module.moduleName.join('.'), name);
  return {
    name, coreFn: fingerprintBytes(bytes), modulePath: module.modulePath,
    hasForeign: module.foreign.length !== 0,
    dependencies: module.imports.map(imp => imp.moduleName.join('.')),
  };
});
const baseInputs = readInputs(path.join(measurements, 'fresh-unchanged/b8x/output'));
const backend = fingerprintBytes(fs.readFileSync(path.join(repo, 'bin/phpurs.js')));
assert.equal(backend, raw.backendSHA256, 'M0 and this assay must use the same backend');
const cwd = process.cwd();
try {
  process.chdir(path.join(snapshot, 'b8x'));
  const { ffiRoots } = resolvePackagePaths({
    ffiDir: Nothing.value,
    modulePaths: [...baseInputs].sort((a, b) => a.name < b.name ? -1 : a.name > b.name ? 1 : 0).map(m => m.modulePath),
  })();
  const context = {
    toolchain: {
      phpursVersion: JSON.parse(fs.readFileSync(path.join(repo, 'package.json'), 'utf8')).version,
      pboVersion: provenance.pboCommit, backend,
      nodeVersion: process.versions.node, v8Version: process.versions.v8, platform: process.platform, arch: process.arch,
    },
    options: {
      cwd: process.cwd(), outputDir: 'output', ffiRoots,
      emitModules: true, emitBundle: true, mainModule: new Just('Inter.Api.Main'),
      autoloadPath: Nothing.value, rewriteLimit: 10000,
    },
    directives: fingerprintBytes(Buffer.from(defaultDirectives)),
  };
  const prepare = inputs => inputs.map(input => {
    let foreignInput = NoForeign.value;
    if (input.hasForeign) {
      const found = findForeignFile(ffiRoots)(input.name)(input.modulePath)();
      foreignInput = found instanceof Just
        ? new ForeignSource(found.value0, fingerprintBytes(fs.readFileSync(found.value0))) : MissingForeign.value;
    }
    return { name: input.name, coreFn: input.coreFn, dependencies: input.dependencies, foreignInput };
  });
  const plan = inputs => {
    const result = planKeys(context)(inputs);
    assert.ok(result instanceof Right, result.value0);
    return result.value0;
  };
  const prepared = prepare(baseInputs);
  const baseline = plan(prepared);
  const byName = new Map(baseline.modules.map(m => [m.name, m.key]));
  const result = {
    backendSHA256: backend, inputModules: order.length, context: baseline.context,
    foreign: {
      none: prepared.filter(m => m.foreignInput instanceof NoForeign).length,
      missing: prepared.filter(m => m.foreignInput instanceof MissingForeign).length,
      source: prepared.filter(m => m.foreignInput instanceof ForeignSource).length,
    },
    cases: {},
  };
  fs.writeFileSync(path.join(artifacts, 'baseline-keys.json'), JSON.stringify(baseline, null, 2) + '\n');
  for (const name of ['unchanged', 'leaf', 'dependency']) {
    const inputs = prepare(readInputs(path.join(measurements, `fresh-${name}/b8x/output`)));
    const next = plan(inputs);
    assert.equal(next.context, baseline.context);
    const changed = next.modules.filter(m => m.key !== byName.get(m.name)).map(m => m.name);
    const changedPHPModules = raw.fresh[name].changedPHP.filter(file => file.endsWith('/index.php'))
      .map(file => file.slice(0, -'/index.php'.length));
    assert.ok(changedPHPModules.every(module => changed.includes(module)), 'every changed PHP module must miss');
    const mutation = raw.mutations[name];
    const index = mutation ? order.indexOf(mutation.module) : order.length;
    assert.ok(index >= 0);
    assert.deepEqual(changed, order.slice(index), 'v1 invalidates exactly the changed module and its suffix');
    if (!mutation) assert.deepEqual(next, baseline);
    result.cases[name] = {
      firstChangedIndex: mutation ? index : null,
      changedKeys: changed.length, identicalKeys: order.length - changed.length,
      changedPHPModules: changedPHPModules.length,
      changedPHPFiles: raw.fresh[name].changedPHP.length,
      allChangedPHPCovered: true,
    };
    fs.writeFileSync(path.join(artifacts, `${name}-changed-keys.json`), JSON.stringify(changed, null, 2) + '\n');
  }
  assert.deepEqual(plan(prepared), baseline);
  fs.writeFileSync(path.join(artifacts, 'comparison.json'), JSON.stringify(result, null, 2) + '\n');
  console.log(JSON.stringify(result, null, 2));
} finally {
  process.chdir(cwd);
}
