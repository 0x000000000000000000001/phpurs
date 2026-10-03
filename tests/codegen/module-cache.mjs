import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { Just, Nothing } from '../../output/Data.Maybe/index.js';
import { Right } from '../../output/Data.Either/index.js';
import { empty as emptyMap, singleton, lookup } from '../../output/Data.Map/index.js';
import { ordString } from '../../output/Data.Ord/index.js';
import { Tuple } from '../../output/Data.Tuple/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import { fingerprintBytes, NoForeign, ForeignSource, planKeys } from '../../output/Phpurs.CacheKey/index.js';
import { loadModuleState, saveModuleState } from '../../output/Phpurs.ModuleCache/index.js';
import { newBuildRefs, publishModuleState } from '../../output/Phpurs.ModuleState/index.js';
import { runAff } from '../../output/Effect.Aff/index.js';
import { defaultDirectives } from '../../output/PureScript.Backend.Optimizer.Directives.Defaults/index.js';
import { write, writeFixture } from './fixtures/cache-inputs.mjs';

const fingerprint = text => fingerprintBytes(Buffer.from(text));
const both = { emitModules: true, emitBundle: true };
const onlyModules = { emitModules: true, emitBundle: false };
const onlyBundle = { emitModules: false, emitBundle: true };
const driver = fileURLToPath(new URL('./fixtures/module-cache-driver.mjs', import.meta.url));
const temporary = label => fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-cache-' + label + '-')));
const run = aff => new Promise((resolve, reject) => runAff(result => () => result instanceof Right ? resolve(result.value0) : reject(result.value0))(aff)());
const arity = (name, n) => singleton(name)(n);

function emptyState(name = 'Library') {
  return {
    backend: {
      name, comments: [], bindings: [], dataDecls: [], classDecls: [],
      imports: emptyMap, dataTypes: emptyMap, exports: emptyMap, reExports: emptyMap,
      foreign: emptyMap, implementations: emptyMap, directives: emptyMap,
    },
    arities: emptyMap, modularPhp: new Just('<?php // café \uFFFD\n'), bundlePhp: new Just('// bundle\n'),
  };
}

test('module cache: restores constructor graphs, sharing, deep expressions and arity precedence', async () => {
  const root = temporary('state');
  try {
    const state = emptyState();
    const shared = new S.Lit(new T.LitNumber(-0));
    let deep = shared;
    for (let i = 0; i < 20000; i++) deep = new S.App(deep, [shared]);
    state.backend.bindings = [{ recursive: false, bindings: [new Tuple('deep', deep), new Tuple('shared', shared)] }];
    state.arities = arity('Library_apply', 2);
    const key = fingerprint('deep graph');
    assert.equal(saveModuleState(root)(key)(state)(), true);
    const restored = loadModuleState({ directory: root, key, moduleName: 'Library', emission: both })();
    assert.ok(restored instanceof Just);
    let node = restored.value0.backend.bindings[0].bindings[0].value1;
    const leaf = restored.value0.backend.bindings[0].bindings[1].value1;
    assert.ok(leaf instanceof S.Lit && leaf.value0 instanceof T.LitNumber);
    assert.ok(Object.is(leaf.value0.value0, -0));
    for (let i = 0; i < 20000; i++) {
      assert.ok(node instanceof S.App);
      assert.equal(node.value1[0], leaf, 'shared expressions retain identity');
      node = node.value0;
    }
    assert.equal(node, leaf);
    assert.deepEqual(Reflect.ownKeys(shared), ['value0'], 'serialization must not mark live PBO nodes');

    const refs = newBuildRefs();
    const previous = emptyState('Before');
    previous.arities = arity('Library_apply', 99);
    await run(publishModuleState(both)(root)(refs)(previous));
    await run(publishModuleState(both)(root)(refs)(restored.value0));
    assert.equal(lookup(ordString)('Library_apply')(refs.globalAritiesRef.value).value0, 2);
    assert.equal(refs.bundleContentRef.value, '<?php\n\n// bundle\n\n// bundle\n\n');
    assert.equal(fs.readFileSync(path.join(root, 'Library/index.php'), 'utf8'), state.modularPhp.value0);
    assert.ok(lookup(ordString)('Library')(refs.backendModulesRef.value) instanceof Just);
    const incomplete = { ...restored.value0, bundlePhp: Nothing.value };
    const untouched = newBuildRefs();
    await assert.rejects(run(publishModuleState(both)(root)(untouched)(incomplete)), /Incomplete PHP module state/);
    assert.equal(untouched.globalAritiesRef.value, emptyMap);
  } finally {
    fs.rmSync(root, { recursive: true, force: true });
  }
});

test('module cache: absent, incompatible, corrupt or incomplete entries miss; failed replacement is atomic', () => {
  const root = temporary('invalid');
  try {
    const key = fingerprint('entry');
    const state = emptyState();
    const request = { directory: root, key, moduleName: 'Library', emission: both };
    const load = overrides => loadModuleState({ ...request, ...overrides })();
    assert.ok(load() instanceof Nothing);
    assert.equal(saveModuleState(root)(key)(state)(), true);
    const file = path.join(root, 'v1', key + '.bin');
    const good = fs.readFileSync(file);
    assert.ok(load({ key: fingerprint('different key') }) instanceof Nothing);
    assert.ok(load({ moduleName: 'Another' }) instanceof Nothing);
    for (const damaged of [
      Buffer.alloc(0), good.subarray(0, good.length - 1),
      Buffer.from(good.toString('latin1').replace('STATE 1', 'STATE 2'), 'latin1'),
      Buffer.concat([good.subarray(0, -1), Buffer.from([good.at(-1) ^ 1])]),
      Buffer.from('PHPURS-MODULE-STATE 1\n{malformed header}\n'),
    ]) {
      fs.writeFileSync(file, damaged);
      assert.ok(load() instanceof Nothing);
    }
    fs.writeFileSync(file, good);
    const noBundle = { ...state, bundlePhp: Nothing.value };
    assert.equal(saveModuleState(root)(key)(noBundle)(), true);
    assert.ok(load() instanceof Nothing);
    assert.ok(load({ emission: onlyModules }) instanceof Just);
    assert.equal(saveModuleState(root)(key)(state)(), true);
    const beforeReplacement = fs.readFileSync(file);
    const rename = fs.renameSync;
    try {
      fs.renameSync = () => { throw new Error('fixture interrupted publication'); };
      assert.equal(saveModuleState(root)(key)({ ...state, modularPhp: new Just('new PHP') })(), false);
    } finally { fs.renameSync = rename; }
    assert.deepEqual(fs.readFileSync(file), beforeReplacement, 'failed publication preserves the old complete entry');
    assert.deepEqual(fs.readdirSync(path.join(root, 'v1')), [key + '.bin'], 'temporary files are removed');
    const unsupported = { ...state, backend: { ...state.backend, comments: [() => null] } };
    assert.equal(saveModuleState(root)(key)(unsupported)(), false, 'functions cannot silently disappear');
    assert.deepEqual(fs.readFileSync(file), beforeReplacement);
    assert.equal(saveModuleState(file)(key)(state)(), false, 'unwritable optional cache is non-fatal');
    assert.ok(load({ directory: file }) instanceof Nothing);
  } finally {
    fs.rmSync(root, { recursive: true, force: true });
  }
});

function fixture(root, emission) {
  const { inputs, foreign } = writeFixture(root);
  const context = {
    toolchain: {
      phpursVersion: 'fixture', pboVersion: 'fixture', backend: fingerprint('fixed test driver'),
      nodeVersion: process.versions.node, v8Version: process.versions.v8, platform: process.platform, arch: process.arch,
    },
    options: { cwd: root, outputDir: 'output', ffiRoots: ['.'], ...emission, mainModule: Nothing.value, autoloadPath: Nothing.value, rewriteLimit: 10000 },
    directives: fingerprint(defaultDirectives),
  };
  const plan = planKeys(context)(inputs.map(input => {
    const name = input.moduleName.join('.');
    return { name, coreFn: fingerprint(JSON.stringify(input)), dependencies: input.imports.map(i => i.moduleName.join('.')),
      foreignInput: foreign[name] ? new ForeignSource(`src/${name}.php`, fingerprint(foreign[name])) : NoForeign.value };
  }));
  assert.ok(plan instanceof Right, plan.value0);
  return { directory: path.join(root, 'cache'), keys: plan.value0.modules, emission, stats: path.join(root, 'stats.json') };
}

function invoke(root, config, extra = {}) {
  write(root, 'config.json', JSON.stringify({ ...config, ...extra }));
  const flags = config.emission.emitBundle ? [config.emission.emitModules ? '--bundle' : '--bundle-only'] : [];
  const result = spawnSync(process.execPath, [driver, '--verbose', ...flags], {
    cwd: root, encoding: 'utf8', timeout: 30000,
    env: { ...process.env, PHPURS_TEST_CACHE_CONFIG: path.join(root, 'config.json') },
  });
  if (extra.failure) return result;
  assert.equal(result.status, 0, result.stdout + result.stderr);
  assert.doesNotMatch(result.stderr, /Failed to decode|Failed to read purmeta/);
  return { ...JSON.parse(fs.readFileSync(config.stats, 'utf8')), generated: [...result.stdout.matchAll(/Generating PHP code for (\S+)/g)].map(m => m[1]) };
}

function outputs(root) {
  return Object.fromEntries(fs.readdirSync(path.join(root, 'output'), { recursive: true })
    .filter(file => /\.php$|composer\.json$/.test(file))
    .sort().map(file => [file, fs.readFileSync(path.join(root, 'output', file))]));
}

for (const [label, emission] of [['modules', onlyModules], ['both', both], ['bundle-only', onlyBundle]]) {
  test(`module cache: ${label} cold, full-hit, mixed and repaired builds match uncached PHP execution`, () => {
    const root = temporary(label);
    try {
      const config = fixture(root, emission);
      const names = config.keys.map(k => k.name);
      assert.deepEqual(invoke(root, config, { disabled: true }).generated, names);
      const expected = outputs(root);
      const cold = invoke(root, config);
      assert.deepEqual(cold.generated, names);
      assert.deepEqual(cold.roundTrips, names);
      assert.deepEqual(cold.hits, []);
      assert.deepEqual(outputs(root), expected);
      const old = new Date('2001-01-01T00:00:00Z');
      for (const file of Object.keys(expected).filter(f => f.endsWith('.php'))) fs.utimesSync(path.join(root, 'output', file), old, old);
      fs.rmSync(path.join(root, '.purmeta'), { recursive: true });
      const hot = invoke(root, config);
      assert.deepEqual(hot.hits, names);
      assert.deepEqual(hot.generated, []);
      assert.deepEqual(hot.writes, []);
      assert.deepEqual(fs.readdirSync(path.join(root, '.purmeta')).sort(), names.map(n => n + '.purmeta').sort());
      assert.deepEqual(outputs(root), expected);
      for (const file of Object.keys(expected).filter(f => f.endsWith('.php'))) assert.equal(fs.statSync(path.join(root, 'output', file)).mtimeMs, old.getTime());

      // A cached dependency must provide implementations, its never-inline
      // directive and PHP/FFI arities to freshly optimized consumers.
      for (const forcedMisses of [['Consumer', 'Main'], ['Library']]) {
        fs.rmSync(path.join(root, '.purmeta'), { recursive: true });
        const mixed = invoke(root, config, { forcedMisses, readOnly: true });
        assert.deepEqual(mixed.generated, forcedMisses);
        assert.deepEqual(outputs(root), expected);
      }
      if (emission.emitModules) {
        const consumer = expected['Consumer/index.php'].toString();
        assert.match(consumer, /Library_keep\(42\)\)\(0\)/, 'never-inline directive and imported implementation are both exercised');
        assert.match(consumer, /Library_add/, 'partial FFI call exercises restored arities');
      }

      // Restore a wholly absent module PHP, corrupted entrypoints/bundles and
      // missing purmeta in a new process, using only complete cached states.
      const repaired = [];
      fs.rmSync(path.join(root, '.purmeta'), { recursive: true });
      for (const file of Object.keys(expected)) {
        if (file.endsWith('.php')) {
          repaired.push(path.join('output', file));
          if (file === 'Library/index.php') fs.unlinkSync(path.join(root, 'output', file));
          else fs.writeFileSync(path.join(root, 'output', file), '<?php // damaged\n');
        }
      }
      const repair = invoke(root, config);
      assert.deepEqual(repair.generated, []);
      assert.deepEqual(repair.writes.sort(), repaired.sort());
      assert.deepEqual(outputs(root), expected);
      for (const file of ['Main/main.mod.php', 'Main/main.bundle.php'].filter(f => f in expected)) {
        const result = spawnSync('php', ['-d', 'opcache.enable_cli=0', path.join('output', file)], { cwd: root, encoding: 'utf8', timeout: 10000 });
        assert.equal(result.status, 0, result.stdout + result.stderr);
        assert.equal(result.stderr, '');
        assert.equal(result.stdout, '43\n');
      }

      // Bad optional data is recomputed; generated-file errors still abort.
      const bad = config.keys.find(k => k.name === 'Library');
      fs.writeFileSync(path.join(config.directory, 'v1', bad.key + '.bin'), 'truncated');
      const fallback = invoke(root, config);
      assert.deepEqual(fallback.generated, ['Library']);
      assert.deepEqual(outputs(root), expected);
      if (emission.emitModules) {
        for (const operation of ['readFile', 'writeFile']) {
          fs.writeFileSync(path.join(root, 'output/Library/index.php'), 'damaged');
          const result = invoke(root, config, { failure: { operation, file: 'output/Library/index.php' } });
          assert.equal(result.status, 1, result.stdout + result.stderr);
          assert.match(result.stderr, /fixture output failure/);
          assert.doesNotMatch(result.stdout, /Generating PHP code/, 'output errors must not be downgraded to misses');
        }
      }
    } finally {
      fs.rmSync(root, { recursive: true, force: true });
    }
  });
}
