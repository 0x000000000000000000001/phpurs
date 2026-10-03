// Freeze the saved PHP inputs; compare uncached, round-trip and restored builds.
// This is a state-layer assay with explicit keys, not production activation.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { Just, Nothing } from '../../../output/Data.Maybe/index.js';
import { Right } from '../../../output/Data.Either/index.js';
import { fingerprintBytes, NoForeign, MissingForeign, ForeignSource, planKeys } from '../../../output/Phpurs.CacheKey/index.js';
import { resolvePackagePaths, findForeignFile } from '../../../output/Phpurs.PackagePaths/index.js';
import { defaultDirectives } from '../../../output/PureScript.Backend.Optimizer.Directives.Defaults/index.js';

const [projectArg, phpOutputArg, artifactsArg] = process.argv.slice(2);
assert.ok(projectArg && phpOutputArg && artifactsArg, 'Usage: node verify-b8x.mjs B8X_PROJECT SAVED_PHP_OUTPUT EMPTY_ARTIFACTS');
const project = fs.realpathSync(projectArg);
const phpOutput = fs.realpathSync(phpOutputArg);
const artifacts = path.resolve(artifactsArg);
const repo = fileURLToPath(new URL('../../../', import.meta.url));
fs.mkdirSync(artifacts, { recursive: true });
assert.deepEqual(fs.readdirSync(artifacts), []);
console.log('Artifacts:', artifacts);
const saveJSON = (file, data) => fs.writeFileSync(path.join(artifacts, file), JSON.stringify(data, null, 2) + '\n');
function files(root) {
  if (!fs.existsSync(root)) return [];
  return fs.readdirSync(root, { recursive: true }).filter(file => fs.statSync(path.join(root, file)).isFile()).sort();
}
const manifest = root => Object.fromEntries(files(root).map(file => [file, fingerprintBytes(fs.readFileSync(path.join(root, file)))]));
function equalFiles(left, right) {
  const names = files(left);
  assert.deepEqual(files(right), names);
  for (const file of names) assert.ok(fs.readFileSync(path.join(left, file)).equals(fs.readFileSync(path.join(right, file))), file);
  return names.length;
}

// Keep relative CoreFn paths, including ../phpurs and the PHP .spago tree.
const snapshot = path.join(artifacts, 'snapshot');
const snapshotProject = path.join(snapshot, 'b8x');
const inputs = fs.readdirSync(phpOutput).sort().filter(name => fs.existsSync(path.join(phpOutput, name, 'corefn.json'))).map(name => {
  const bytes = fs.readFileSync(path.join(phpOutput, name, 'corefn.json'));
  const json = JSON.parse(bytes.toString('utf8'));
  assert.equal(json.moduleName.join('.'), name);
  return { name, bytes, json };
});
function copyInput(relative, source) {
  const target = path.resolve(snapshotProject, relative);
  assert.ok(target.startsWith(snapshot + path.sep), relative);
  fs.mkdirSync(path.dirname(target), { recursive: true });
  fs.copyFileSync(source, target);
}
for (const input of inputs) copyInput(`output/${input.name}/corefn.json`, path.join(phpOutput, input.name, 'corefn.json'));
const roots = new Set(['', ...inputs.map(({ json }) => json.modulePath.match(/^(.*?)\/(?:src|test)\//)?.[1]).filter(Boolean)]);
const sourcePath = relative => relative.startsWith('.spago/')
  ? path.resolve(phpOutput, '..', relative) : path.resolve(project, relative);
for (const relative of roots) {
  const composer = path.join(relative, 'composer.json');
  if (fs.existsSync(sourcePath(composer))) copyInput(composer, sourcePath(composer));
  for (const directory of ['src', 'test']) {
    const sourceRoot = sourcePath(path.join(relative, directory));
    for (const file of files(sourceRoot).filter(file => file.endsWith('.php'))) {
      copyInput(path.join(relative, directory, file), path.join(sourceRoot, file));
    }
  }
}
const inputManifest = manifest(snapshot);
saveJSON('inputs.json', inputManifest);
fs.cpSync(snapshot, path.join(artifacts, 'work'), { recursive: true });
const work = path.join(artifacts, 'work/b8x');
const output = path.join(work, 'output');
const control = path.join(artifacts, 'control-output');
const emission = { emitModules: true, emitBundle: true };
let config = { directory: path.join(artifacts, 'cache'), emission, keys: [] };
const driver = path.join(repo, 'tests/codegen/fixtures/module-cache-driver.mjs');
const results = {
  node: process.versions.node, v8: process.versions.v8, platform: process.platform, arch: process.arch,
  bundledBackendSHA256: fingerprintBytes(fs.readFileSync(path.join(repo, 'bin/phpurs.js'))),
  inputFiles: Object.keys(inputManifest).length, inputModules: inputs.length,
  inputManifestSHA256: fingerprintBytes(Buffer.from(JSON.stringify(inputManifest))), trials: {},
};
function build(label, overrides) {
  const statsPath = path.join(artifacts, label + '.stats.json');
  saveJSON(label + '.config.json', { ...config, stats: statsPath, ...overrides });
  const log = fs.openSync(path.join(artifacts, label + '.log'), 'w');
  let processResult;
  try {
    processResult = spawnSync(process.execPath, [driver, '--main', 'Inter.Api.Main', '--bundle', '--verbose'], {
      cwd: work, stdio: ['ignore', log, log], timeout: 300000,
      env: { ...process.env, GOPURS_JOBS: '1', PHPURS_TEST_CACHE_CONFIG: path.join(artifacts, label + '.config.json') },
    });
  } finally { fs.closeSync(log); }
  assert.equal(processResult.status, 0, `${label}: see ${label}.log (${processResult.error || ''})`);
  const text = fs.readFileSync(path.join(artifacts, label + '.log'), 'utf8');
  assert.doesNotMatch(text, /Failed to decode|Failed to read purmeta|DIRECTIVE PARSE ERRORS|\(failed\)/);
  const generated = [...text.matchAll(/Generating PHP code for (\S+)/g)].map(m => m[1]);
  const stats = JSON.parse(fs.readFileSync(statsPath, 'utf8'));
  assert.equal(stats.purmetaWrites.length, inputs.length);
  assert.equal(new Set(stats.purmetaWrites).size, inputs.length);
  results.trials[label] = Object.fromEntries(Object.entries({ ...stats, generated }).map(([k, values]) => [k, values.length]));
  saveJSON(label + '.generated.json', generated);
  saveJSON('results.json', results);
  console.log(label, JSON.stringify(results.trials[label]));
  return { ...stats, generated };
}

const baseline = build('uncached', { disabled: true });
assert.equal(baseline.generated.length, inputs.length);
fs.cpSync(output, control, { recursive: true });
const byName = new Map(inputs.map(input => [input.name, input]));
// All executable JS modules and the driver are frozen by this manifest for the
// duration of the assay. No compiler, CoreFn or FFI edit occurs between reads.
const codeManifest = Object.fromEntries(files(path.join(repo, 'output')).filter(file => file.endsWith('.js'))
  .map(file => [file, fingerprintBytes(fs.readFileSync(path.join(repo, 'output', file)))]));
codeManifest['test-driver'] = fingerprintBytes(fs.readFileSync(driver));
saveJSON('code.json', codeManifest);
const backend = fingerprintBytes(Buffer.from(JSON.stringify(codeManifest)));
const previousCwd = process.cwd();
try {
  process.chdir(work);
  const { ffiRoots } = resolvePackagePaths({ ffiDir: Nothing.value, modulePaths: inputs.map(i => i.json.modulePath) })();
  const prepared = baseline.generated.map(name => {
    const input = byName.get(name);
    let foreignInput = NoForeign.value;
    if (input.json.foreign.length) {
      const selected = findForeignFile(ffiRoots)(name)(input.json.modulePath)();
      foreignInput = selected instanceof Just ? new ForeignSource(selected.value0, fingerprintBytes(fs.readFileSync(selected.value0))) : MissingForeign.value;
    }
    return { name, coreFn: fingerprintBytes(input.bytes), foreignInput, dependencies: input.json.imports.map(i => i.moduleName.join('.')) };
  });
  results.foreign = { none: prepared.filter(i => i.foreignInput instanceof NoForeign).length,
    missing: prepared.filter(i => i.foreignInput instanceof MissingForeign).length, source: prepared.filter(i => i.foreignInput instanceof ForeignSource).length };
  const plan = planKeys({
    toolchain: { phpursVersion: 'state-assay', pboVersion: 'compiled-manifest', backend,
      nodeVersion: process.versions.node, v8Version: process.versions.v8, platform: process.platform, arch: process.arch },
    options: { cwd: work, outputDir: 'output', ffiRoots, ...emission, mainModule: new Just('Inter.Api.Main'), autoloadPath: Nothing.value, rewriteLimit: 10000 },
    directives: fingerprintBytes(Buffer.from(defaultDirectives)),
  })(prepared);
  assert.ok(plan instanceof Right, plan.value0);
  config = { ...config, keys: plan.value0.modules };
  saveJSON('plan.json', plan.value0);
} finally { process.chdir(previousCwd); }

const cold = build('store-roundtrip', {});
assert.deepEqual(cold.roundTrips, baseline.generated);
assert.deepEqual(cold.generated, baseline.generated);
assert.deepEqual(cold.writes, []);
results.trials['store-roundtrip'].identicalFiles = equalFiles(control, output);
const old = new Date('2001-01-01T00:00:00Z');
const phpFiles = files(output).filter(file => file.endsWith('.php'));
for (const file of phpFiles) fs.utimesSync(path.join(output, file), old, old);
fs.rmSync(path.join(work, '.purmeta'), { recursive: true });
const hot = build('restore-all', {});
assert.deepEqual(hot.hits, baseline.generated);
assert.deepEqual(hot.generated, []);
assert.deepEqual(hot.writes, []);
results.trials['restore-all'].identicalFiles = equalFiles(control, output);
for (const file of phpFiles) assert.equal(fs.statSync(path.join(output, file)).mtimeMs, old.getTime());
results.trials['restore-all'].preservedPHPMtimes = phpFiles.length;

// Force recomputation of consumers of the M0 shared dependency, with its prefix
// restored. This is a controlled state test, without mutating the input graph.
const index = baseline.generated.indexOf('Core.Message.Command.Command');
assert.ok(index >= 0);
const forcedMisses = baseline.generated.slice(index + 1);
fs.rmSync(path.join(work, '.purmeta'), { recursive: true });
const mixed = build('restore-prefix', { forcedMisses, readOnly: true });
assert.deepEqual(mixed.generated, forcedMisses);
assert.deepEqual(mixed.writes, []);
results.trials['restore-prefix'].identicalFiles = equalFiles(control, output);

const damaged = ['Core.Message.Command.Command/index.php', 'Inter.Api.Main/main.mod.php', 'Inter.Api.Main/main.bundle.php'];
fs.unlinkSync(path.join(output, damaged[0]));
for (const file of damaged.slice(1)) fs.writeFileSync(path.join(output, file), '<?php // damaged\n');
fs.rmSync(path.join(work, '.purmeta'), { recursive: true });
const repaired = build('restore-outputs', {});
assert.deepEqual(repaired.generated, []);
assert.deepEqual(repaired.writes.sort(), damaged.map(file => 'output/' + file).sort());
results.trials['restore-outputs'].identicalFiles = equalFiles(control, output);
results.cacheFiles = files(config.directory).length;
results.cacheBytes = files(config.directory).reduce((n, file) => n + fs.statSync(path.join(config.directory, file)).size, 0);
assert.equal(results.cacheFiles, inputs.length);
assert.deepEqual(manifest(snapshot), inputManifest);
for (const [file, hash] of Object.entries(codeManifest)) assert.equal(fingerprintBytes(fs.readFileSync(file === 'test-driver' ? driver : path.join(repo, 'output', file))), hash);
saveJSON('results.json', results);
console.log(JSON.stringify(results, null, 2));
