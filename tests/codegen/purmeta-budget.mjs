import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { Left, Right } from '../../output/Data.Either/index.js';
import { Just, Nothing } from '../../output/Data.Maybe/index.js';
import { bindAff, killFiber, monadEffectAff, never, runAff } from '../../output/Effect.Aff/index.js';
import * as Cache from '../../output/PureScript.Backend.Optimizer.Cache/index.js';
import { parseBudget, withBudget } from '../../output/Phpurs.PurmetaBudget/index.js';
import { writeFixture } from './fixtures/cache-inputs.mjs';

const cli = fileURLToPath(new URL('../../bin/phpurs.js', import.meta.url));
const probe = fileURLToPath(new URL('./fixtures/purmeta-profile-probe.mjs', import.meta.url));
const flag = '--purmeta-cache-mib';
const mib = 1024 * 1024;
const stats = () => JSON.parse(Cache.readPurmetaStatsJson());
const run = action => new Promise((resolve, reject) => runAff(result => () => result instanceof Right ? resolve(result.value0) : reject(result.value0))(action)());
const effect = monadEffectAff.liftEffect;

function fixture(t) {
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-purmeta-budget-')));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  writeFixture(root);
  return root;
}

function build(root, args, { status = 0, profiled = true } = {}) {
  const result = spawnSync(process.execPath, ['--import', probe, cli, ...args], {
    cwd: root, encoding: 'utf8', timeout: 30000,
    env: { ...process.env, PHPURS_PURMETA_PROBE: path.join(root, 'probe.json') },
  });
  assert.equal(result.status, status, result.stdout + result.stderr);
  const profiles = [...result.stderr.matchAll(/^\[phpurs\] purmeta: (.+)$/gm)].map(match => JSON.parse(match[1]));
  assert.equal(profiles.length, profiled ? 1 : 0);
  const profile = profiles[0];
  const io = JSON.parse(fs.readFileSync(path.join(root, 'probe.json')));
  if (profile) {
    assert.deepEqual({ attempts: profile.reads.ioAttempts, files: profile.reads.files, bytes: profile.reads.bytes }, io.reads);
    assert.deepEqual({ attempts: profile.writes.attempts, files: profile.writes.files, bytes: profile.writes.bytes }, io.writes);
    assert.ok(profile.ram.boundaryPeakSerializedBytes <= profile.policy.maxSerializedBytes);
  }
  return { ...result, profile, io };
}

function output(root) {
  return Object.fromEntries(fs.readdirSync(path.join(root, 'output'), { recursive: true })
    .filter(file => /\.php$|composer\.json$|\.phpurs-outputs\.json$/.test(file)).sort()
    .map(file => [file, fs.readFileSync(path.join(root, 'output', file))]));
}

function execute(root, expected = '43\n') {
  for (const entry of ['main.mod.php', 'main.bundle.php']) {
    const result = spawnSync('php', ['-d', 'opcache.enable_cli=0', 'output/Main/' + entry], { cwd: root, encoding: 'utf8' });
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert.equal(result.stdout, expected);
    assert.equal(result.stderr, '');
  }
}

test('purmeta budget: strict MiB parsing, exact byte range and shared first-occurrence precedence', () => {
  assert.ok(parseBudget([]).value0 instanceof Nothing);
  for (const [args, bytes] of [
    [[flag, '0'], 0], [[flag + '=16'], 16 * mib], [[flag, '0016'], 16 * mib],
    [[flag, '4096'], 4294967296], [[flag, '8589934591'], 8589934591 * mib],
    [[flag, '16', flag, '64'], 16 * mib], [[flag + '=8', flag, '16'], 8 * mib],
  ]) {
    const result = parseBudget(args);
    assert.ok(result instanceof Right && result.value0 instanceof Just);
    assert.equal(result.value0.value0, bytes);
  }
  for (const value of ['', '-1', '+1', '1.5', '1e2', 'Infinity', 'NaN', '0x10', '16MiB', '8589934592', '9'.repeat(400)]) {
    assert.ok(parseBudget([flag, value]) instanceof Left, value);
  }
  assert.match(parseBudget([flag]).value0, /Missing value/);
});

test('purmeta budget: same-process restoration, cleanup and cancellation preserve the previous caller policy', async t => {
  const root = fixture(t);
  const cwd = process.cwd();
  process.chdir(root);
  const previous = Cache.setPurmetaCacheBudgetBytes(1024)();
  Cache.beginPurmetaBuild();
  Cache.setPurmetaStatsEnabled(true)();
  t.after(() => {
    Cache.setPurmetaCacheBudgetBytes(previous)();
    Cache.beginPurmetaBuild();
    Cache.setPurmetaStatsEnabled(false)();
    process.chdir(cwd);
  });
  await run(withBudget(Nothing.value)(effect(() => assert.equal(stats().policy.maxSerializedBytes, 1024))));
  assert.equal(await run(withBudget(new Just(8192))(effect(() => {
    assert.equal(stats().policy.maxSerializedBytes, 8192);
    Cache.beginPurmetaBuild();
    Cache.writePurmetaSync('Large')({ contents: 'x'.repeat(2048) })();
    Cache.trimPurmetaCache();
    assert.ok(stats().ram.serializedBytes > 1024);
    return 42;
  }))), 42);
  assert.equal(stats().policy.maxSerializedBytes, 1024);
  assert.ok(stats().ram.serializedBytes <= 1024, 'restoring a smaller policy trims excess at scope exit');
  await assert.rejects(run(withBudget(new Just(0))(effect(() => {
    assert.equal(stats().policy.maxSerializedBytes, 0);
    throw new Error('budget scope failure');
  }))), /budget scope failure/);
  assert.equal(stats().policy.maxSerializedBytes, 1024);
  let entered;
  const ready = new Promise(resolve => { entered = resolve; });
  const fiber = runAff(_ => () => {})(withBudget(new Just(0))(
    bindAff.bind(effect(() => entered()))(_ => never)))();
  await ready;
  assert.equal(stats().policy.maxSerializedBytes, 0);
  await run(killFiber(new Error('cancel budget scope'))(fiber));
  assert.equal(stats().policy.maxSerializedBytes, 1024);
});

test('purmeta budget CLI: default, zero, small and larger limits generate and execute identical PHP', t => {
  const root = fixture(t);
  const args = ['--bundle', '--no-cache', '--profile-purmeta'];
  const first = build(root, args);
  assert.equal(first.profile.policy.maxSerializedBytes, 64 * mib);
  const expected = output(root);
  const old = new Date('2001-01-01T00:00:00Z');
  for (const file of Object.keys(expected).filter(file => file.endsWith('.php'))) fs.utimesSync(path.join(root, 'output', file), old, old);
  for (const [options, budget] of [
    [[flag, '0'], 0], [[flag + '=1'], 1], [[flag, '16'], 16], [[flag, '128'], 128], [[flag, '64'], 64],
  ]) {
    const result = build(root, [[...args, ...options].join(' ')]);
    assert.equal(result.profile.policy.maxSerializedBytes, budget * mib);
    assert.equal(result.profile.ram.trimCalls, 3, 'the reported profile covers the configured build');
    assert.deepEqual(output(root), expected);
    if (budget === 0) {
      assert.ok(result.profile.reads.files > 0, 'zero exercises the disk path');
      assert.equal(result.profile.ram.entries, 0);
      assert.equal(result.profile.ram.boundaryPeakSerializedBytes, 0);
    }
    for (const file of Object.keys(expected).filter(file => file.endsWith('.php'))) assert.equal(fs.statSync(path.join(root, 'output', file)).mtimeMs, old.getTime());
    execute(root);
  }
  build(root, ['--bundle', flag, '0'], { profiled: false });
  assert.deepEqual(output(root), expected, 'the budget works independently of diagnostic logging');
});

test('purmeta budget CLI: persistent hits and mixed builds are independent of the RAM storage budget', t => {
  const root = fixture(t);
  const args = ['--bundle', '--profile-purmeta'];
  build(root, args);
  const expected = output(root);
  const hot = build(root, [...args, flag, '0']);
  assert.match(hot.stderr, /cache: 3 hits, 0 misses, 0 stores/);
  assert.equal(hot.profile.policy.maxSerializedBytes, 0);
  assert.equal(hot.profile.ram.entries, 0);
  assert.deepEqual(output(root), expected);
  const ffi = path.join(root, 'src/Main.php');
  fs.writeFileSync(ffi, fs.readFileSync(ffi, 'utf8').replace('echo $value', 'echo ($value + 1)'));
  const mixed = build(root, [...args, flag, '0']);
  assert.match(mixed.stderr, /cache: 2 hits, 1 misses, 1 stores/);
  assert.ok(mixed.profile.reads.diskHits > 0, 'fresh consumers can read restored then evicted dependencies');
  const changed = output(root);
  execute(root, '44\n');
  build(root, [...args, '--no-cache']);
  assert.deepEqual(output(root), changed);
  execute(root, '44\n');
});

test('purmeta budget CLI: malformed options fail before input loading or emission', t => {
  const root = fixture(t);
  const before = output(root);
  for (const args of [[flag], [flag + '='], [flag, '-1'], [flag, '1.5'], [flag, '8589934592'], [flag, '--main', 'Main']]) {
    const result = build(root, ['--profile-purmeta', ...args], { status: 1, profiled: false });
    assert.match(result.stderr, /(Missing|Invalid) value for --purmeta-cache-mib/);
    assert.doesNotMatch(result.stderr, /load TAST \+ sort:/);
    assert.equal(result.stdout, '');
    assert.equal(result.io.writes.attempts, 0);
    assert.deepEqual(output(root), before);
    assert.equal(fs.existsSync(path.join(root, '.purmeta')), false);
    assert.equal(fs.existsSync(path.join(root, 'output/.phpurs-cache')), false);
  }
});
