// Cache eligibility depends on every input to the module state, not on mtimes
// or just the module's own CoreFn. The production skip callback is still off.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { Left, Right } from '../../output/Data.Either/index.js';
import { Just, Nothing } from '../../output/Data.Maybe/index.js';
import {
  fingerprintBytes, fingerprintString, NoForeign, MissingForeign, ForeignSource, planKeys,
} from '../../output/Phpurs.CacheKey/index.js';
import { findForeignFile } from '../../output/Phpurs.PackagePaths/index.js';
import { printBundleEntryPoint } from '../../output/Phpurs.EntryPoint/index.js';

const fingerprint = input => fingerprintBytes(Buffer.from(input));
const context = () => ({
  toolchain: {
    phpursVersion: 'fixture-phpurs', pboVersion: 'fixture-pbo', backend: fingerprint('backend bytes'),
    nodeVersion: process.versions.node, v8Version: process.versions.v8, platform: process.platform, arch: process.arch,
  },
  options: {
    cwd: path.resolve('fixture'), outputDir: 'output', ffiRoots: ['packages/ffi', '.'],
    emitModules: true, emitBundle: true, mainModule: new Just('App.Main'),
    autoloadPath: Nothing.value, rewriteLimit: 10000,
  },
  directives: fingerprint('Shared.value always'),
});
const input = (name, text, dependencies = []) => ({ name, coreFn: fingerprint(text), foreignInput: NoForeign.value, dependencies });
const modules = () => [
  input('Policy', 'module directives'),
  input('Shared', 'value = 50', ['Prim']),
  input('Consumer', 'value = Shared.value', ['Shared']),
  input('Unrelated', 'value = 1'),
  input('App.Main', 'main = Consumer.value', ['Consumer']),
];
function plan(inputs = modules(), config = context()) {
  const result = planKeys(config)(inputs);
  assert.ok(result instanceof Right, result.value0);
  return result.value0;
}
function changed(before, after) {
  const keys = new Map(before.modules.map(module => [module.name, module.key]));
  return after.modules.filter(module => keys.get(module.name) !== module.key).map(module => module.name);
}

test('cache keys: stable content, explicit encoding and process-local independence', () => {
  const before = plan();
  const config = context();
  config.toolchain = Object.fromEntries(Object.entries(config.toolchain).reverse());
  config.options = Object.fromEntries(Object.entries(config.options).reverse());
  assert.deepEqual(plan(modules(), config), before, 'record insertion order is irrelevant');
  assert.match(fingerprintString(before.key), /^[0-9a-f]{64}$/);
  assert.notEqual(plan([], config).key, before.key);
  assert.notEqual(plan([input('ab', 'c')]).key, plan([input('a', 'bc')]).key, 'field boundaries must be encoded');
  plan([input('Other', 'another build')], { ...config, directives: fingerprint('other directives') });
  assert.deepEqual(plan(), before, 'previous plans must not contribute state');
  const deps = modules();
  deps[2] = { ...deps[2], dependencies: ['Prim', 'Shared', 'Consumer', 'Shared'] };
  const reordered = [...deps];
  reordered[2] = { ...deps[2], dependencies: ['Shared', 'Prim'] };
  assert.deepEqual(plan(deps), plan(reordered), 'dependency edges are a set, excluding self imports');
});

test('cache keys: toolchain, host, initial directives and every effective option invalidate', () => {
  const before = plan();
  const toolchainChanges = {
    phpursVersion: 'new phpurs', pboVersion: 'new pbo', backend: fingerprint('dirty compiler with unchanged versions'),
    nodeVersion: 'new node', v8Version: 'new v8', platform: 'another platform', arch: 'another arch',
  };
  const optionChanges = {
    cwd: path.resolve('other-project'), outputDir: 'other-output', ffiRoots: ['.', 'packages/ffi'],
    emitModules: false, emitBundle: false, mainModule: Nothing.value,
    autoloadPath: new Just('vendor/autoload.php'), rewriteLimit: 9999,
  };
  for (const [group, changes] of [['toolchain', toolchainChanges], ['options', optionChanges]]) {
    for (const [key, value] of Object.entries(changes)) {
      const config = context();
      config[group] = { ...config[group], [key]: value };
      assert.deepEqual(changed(before, plan(modules(), config)), modules().map(m => m.name), `${group}.${key}`);
    }
  }
  assert.deepEqual(changed(before, plan(modules(), { ...context(), directives: fingerprint('Shared.value never') })),
    modules().map(m => m.name));
  // Nothing and an explicit default render different autoloader fallback code.
  assert.notEqual(printBundleEntryPoint({ mainModule: 'App.Main', autoloadPath: Nothing.value }),
    printBundleEntryPoint({ mainModule: 'App.Main', autoloadPath: new Just('vendor/autoload.php') }));
  const config = context();
  assert.notEqual(plan(modules(), { ...config, options: { ...config.options, autoloadPath: new Just('vendor/../vendor/autoload.php') } }).key,
    plan(modules(), { ...config, options: { ...config.options, autoloadPath: new Just('vendor/autoload.php') } }).key);
});

test('cache keys: dependency changes propagate through consumers and preceding shared state', () => {
  const before = plan();
  const changedDependency = modules();
  changedDependency[1] = { ...changedDependency[1], coreFn: fingerprint('value = 51') };
  assert.equal(changedDependency[2].coreFn, modules()[2].coreFn, 'consumer input is unchanged');
  assert.deepEqual(changed(before, plan(changedDependency)), ['Shared', 'Consumer', 'Unrelated', 'App.Main']);
  const changedPolicy = modules();
  changedPolicy[0] = { ...changedPolicy[0], coreFn: fingerprint('changed module directives') };
  assert.deepEqual(changed(before, plan(changedPolicy)), modules().map(m => m.name), 'unimported predecessor state is covered');
  const changedLeaf = modules();
  changedLeaf[4] = { ...changedLeaf[4], coreFn: fingerprint('changed main body') };
  assert.deepEqual(changed(before, plan(changedLeaf)), ['App.Main'], 'later input cannot affect earlier keys in the same graph');
  const changedEdge = modules();
  changedEdge[2] = { ...changedEdge[2], dependencies: ['Policy'] };
  assert.deepEqual(changed(before, plan(changedEdge)), ['Consumer', 'Unrelated', 'App.Main']);
});

test('cache keys: graph additions, removals, order and previously absent imports', () => {
  const before = plan();
  for (const next of [
    [input('Added', 'new'), ...modules()],
    modules().filter(m => m.name !== 'Unrelated'),
    [modules()[1], modules()[0], ...modules().slice(2)],
    [input('Prim', 'now a loaded module'), ...modules()],
  ]) {
    const after = plan(next);
    assert.notEqual(before.context, after.context);
    for (const entry of after.modules) {
      assert.notEqual(entry.key, before.modules.find(m => m.name === entry.name)?.key);
    }
  }
});

test('cache keys: ambiguous or unsupported module orders return no partial plan', () => {
  for (const inputs of [
    [input('Duplicate', 'one'), input('Duplicate', 'two')],
    [input('', 'unnamed')],
    [input('First', 'one', ['Later']), input('Later', 'two')],
    [input('First', 'one', ['Later']), input('Later', 'two', ['First'])],
  ]) assert.ok(planKeys(context())(inputs) instanceof Left);
  const config = context();
  config.options.cwd = 'relative-cwd';
  assert.ok(planKeys(config)(modules()) instanceof Left, 'working directory must be explicit');
});

test('cache keys: exact CoreFn and compiler bytes override size, mtime and UTF-8 decoding', () => {
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-key-bytes-')));
  try {
    const file = path.join(root, 'corefn.json');
    const original = Buffer.from('{"value":50,"annotation":{"type":1}}');
    const updated = Buffer.from('{"value":51,"annotation":{"type":1}}');
    fs.writeFileSync(file, original);
    const time = new Date('2001-01-01T00:00:00Z');
    fs.utimesSync(file, time, time);
    const readPlan = () => plan([{ ...input('Shared', ''), coreFn: fingerprintBytes(fs.readFileSync(file)) }]);
    const before = readPlan();
    fs.utimesSync(file, new Date(), new Date());
    assert.deepEqual(readPlan(), before, 'mtime alone must not invalidate content');
    fs.writeFileSync(file, updated);
    fs.utimesSync(file, time, time);
    assert.equal(original.length, updated.length);
    assert.notEqual(readPlan().key, before.key, 'same-size edit with the old mtime must invalidate');
    assert.notEqual(plan([input('Shared', '{"value":50,"annotation":{"type":2}}')]).key,
      plan([input('Shared', original)]).key, 'type annotations belong to the CoreFn identity');
    const bad = Buffer.from([0xff]);
    const replacement = Buffer.from('\uFFFD');
    assert.equal(bad.toString('utf8'), replacement.toString('utf8'));
    assert.notEqual(fingerprintBytes(bad), fingerprintBytes(replacement));
    const config = context();
    const compiler = path.join(root, 'backend.js');
    fs.writeFileSync(compiler, 'compiler A');
    config.toolchain.backend = fingerprintBytes(fs.readFileSync(compiler));
    const first = plan(modules(), config);
    fs.writeFileSync(compiler, 'compiler B');
    config.toolchain.backend = fingerprintBytes(fs.readFileSync(compiler));
    assert.notEqual(plan(modules(), config).key, first.key, 'same version strings do not hide compiler edits');
  } finally {
    fs.rmSync(root, { recursive: true, force: true });
  }
});

test('cache keys: FFI misses, bytes and winning paths are re-evaluated before reuse', () => {
  const cwd = process.cwd();
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-key-ffi-')));
  try {
    process.chdir(root);
    const config = context();
    config.options.cwd = root;
    config.options.ffiRoots = [path.join(root, 'ffi')];
    fs.mkdirSync('ffi/src', { recursive: true });
    fs.mkdirSync('src', { recursive: true });
    const select = () => {
      const found = findForeignFile(config.options.ffiRoots)('Shared')('src/Shared.purs')();
      return found instanceof Just ? new ForeignSource(found.value0, fingerprintBytes(fs.readFileSync(found.value0))) : MissingForeign.value;
    };
    const readPlan = () => {
      const inputs = modules();
      inputs[1] = { ...inputs[1], foreignInput: select() };
      return plan(inputs, config);
    };
    const missing = readPlan();
    assert.notEqual(missing.key, plan(modules(), config).key, 'no declaration differs from missing FFI');
    fs.writeFileSync('ffi/src/Shared.php', '');
    const empty = readPlan();
    assert.notEqual(empty.key, missing.key, 'an empty file differs from an absent file');
    fs.writeFileSync('ffi/src/Shared.php', '<?php $exports["value"] = 50;');
    const fallback = readPlan();
    assert.notEqual(fallback.key, empty.key);
    const stat = fs.statSync('ffi/src/Shared.php');
    fs.writeFileSync('ffi/src/Shared.php', '<?php $exports["value"] = 51;');
    fs.utimesSync('ffi/src/Shared.php', stat.atime, stat.mtime);
    const modified = readPlan();
    assert.deepEqual(changed(fallback, modified), ['Shared', 'Consumer', 'Unrelated', 'App.Main']);
    fs.copyFileSync('ffi/src/Shared.php', 'src/Shared.php');
    const adjacent = readPlan();
    assert.notEqual(adjacent.key, modified.key, 'a new higher-priority file changes the selected path, even with identical bytes');
    fs.unlinkSync('src/Shared.php');
    assert.deepEqual(readPlan(), modified);
    fs.unlinkSync('ffi/src/Shared.php');
    assert.deepEqual(readPlan(), missing);
  } finally {
    process.chdir(cwd);
    fs.rmSync(root, { recursive: true, force: true });
  }
});
