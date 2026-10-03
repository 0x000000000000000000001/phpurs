import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { Right } from '../../output/Data.Either/index.js';
import { bindAff, delay, killFiber, monadEffectAff, never, runAff } from '../../output/Effect.Aff/index.js';
import * as Profile from '../../output/Phpurs.BuildProfile/index.js';
import { writeFixture } from './fixtures/cache-inputs.mjs';

const cli = process.env.PHPURS_TEST_CLI || fileURLToPath(new URL('../../bin/phpurs.js', import.meta.url));
const names = ['Library', 'Consumer', 'Main'];
const run = action => new Promise((resolve, reject) => runAff(result => () => result instanceof Right ? resolve(result.value0) : reject(result.value0))(action)());
const effect = monadEffectAff.liftEffect;
const then = bindAff.bind;

function fixture(t) {
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-build-profile-')));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  writeFixture(root);
  return root;
}
function build(root, args, status = 0, jobs = '1') {
  const result = spawnSync(process.execPath, [cli, '--main', 'Main', ...args], {
    cwd: root, encoding: 'utf8', timeout: 30000, env: { ...process.env, GOPURS_JOBS: jobs },
  });
  assert.equal(result.status, status, result.stdout + result.stderr);
  const reports = [...result.stderr.matchAll(/^\[phpurs\] build-profile: (.+)$/gm)].map(m => JSON.parse(m[1]));
  assert.equal(reports.length, args.join(' ').includes('--profile-build') ? 1 : 0);
  const profile = reports[0];
  if (profile) {
    assert.equal(profile.version, 1);
    assert.equal(profile.status, status === 0 ? 'completed' : 'failed');
    assert.ok(Number.isFinite(profile.totalMs) && profile.totalMs >= 0);
    for (const metric of [...Object.values(profile.phases), ...profile.modules.flatMap(m => Object.values(m.phases))]) {
      assert.equal(metric.calls, metric.completed + metric.failed + metric.cancelled);
      assert.ok(Number.isFinite(metric.ms) && metric.ms >= metric.maxMs && metric.maxMs >= 0);
    }
  }
  return { ...result, profile };
}
function outputs(root) {
  return Object.fromEntries(fs.readdirSync(path.join(root, 'output'), { recursive: true })
    .filter(file => /\.php$|composer\.json$|\.phpurs-outputs\.json$/.test(file)).sort()
    .map(file => [file, fs.readFileSync(path.join(root, 'output', file))]));
}

for (const [mode, args, prints, writes] of [
  ['modules', [], 4, 4], ['both', ['--bundle'], 5, 5], ['bundle-only', ['--bundle-only'], 4, 1],
]) {
  test(`build profile: ${mode} reports separate phases, keeps outputs and gates detailed logs`, t => {
    const root = fixture(t);
    const plain = build(root, [...args, '--no-cache']);
    assert.equal(plain.stdout, '', 'module diagnostics should require --verbose');
    assert.match(plain.stderr, /\[phpurs\] backend total: \d+ ms/);
    const expected = outputs(root);
    const profiled = build(root, [...args, '--no-cache --profile-build --profile-purmeta'], 0, '4');
    assert.equal(profiled.stdout, '');
    assert.equal([...profiled.stderr.matchAll(/^\[phpurs\] purmeta:/gm)].length, 1);
    const { phases, modules } = profiled.profile;
    for (const phase of ['corefn.decode', 'optimize', 'translate']) assert.equal(phases[phase].completed, 3, phase);
    assert.equal(phases['corefn.sort'].completed, 1);
    assert.equal(phases.print.completed, prints);
    assert.equal(phases['php.write'].completed, writes);
    assert.equal(phases.diagnostics.calls, 0);
    assert.deepEqual(modules.filter(m => m.source === 'optimized').map(m => m.name).sort(), [...names].sort());
    assert.deepEqual(outputs(root), expected);
    const verbose = build(root, [...args, '--no-cache --verbose --profile-build']);
    assert.deepEqual([...verbose.stdout.matchAll(/Generating PHP code for (\S+)/g)].map(m => m[1]), names);
    // The optimized typed fixture has 8, 16 and 10 expression occurrences.
    // The old partial counter stopped at the root Typed wrappers (2, 2, 1).
    assert.deepEqual([...verbose.stdout.matchAll(/Total AST Nodes: (\d+)/g)].map(m => Number(m[1])), [8, 16, 10]);
    assert.equal(verbose.profile.phases.diagnostics.completed, 3);
    assert.deepEqual(outputs(root), expected);
    for (const entry of ['main.mod.php', 'main.bundle.php']) {
      const filename = path.join(root, 'output/Main', entry);
      if (!fs.existsSync(filename)) continue;
      const execution = spawnSync('php', ['-d', 'opcache.enable_cli=0', filename], { cwd: root, encoding: 'utf8' });
      assert.equal(execution.status, 0, execution.stdout + execution.stderr);
      assert.equal(execution.stdout, '43\n');
      assert.equal(execution.stderr, '');
    }
  });
}

test('build profile: flags do not partition persistent hits; mixed builds time only fresh codegen', t => {
  const root = fixture(t);
  build(root, ['--bundle']);
  const expected = outputs(root);
  const hot = build(root, ['--bundle --profile-build --verbose']);
  assert.match(hot.stderr, /cache: 3 hits, 0 misses, 0 stores/);
  for (const phase of ['optimize', 'translate', 'cache.store', 'diagnostics']) assert.equal(hot.profile.phases[phase].calls, 0, phase);
  assert.equal(hot.profile.phases.print.calls, 2, 'entrypoints are still printed on state hits');
  assert.equal(hot.profile.phases['php.write'].calls, 5, 'unchanged output comparison still runs');
  assert.ok(hot.profile.modules.every(m => m.source === 'cache'));
  assert.deepEqual(outputs(root), expected);
  fs.appendFileSync(path.join(root, 'src/Main.php'), '\n// invalidate only the final module\n');
  const mixed = build(root, ['--bundle --profile-build']);
  assert.match(mixed.stderr, /cache: 2 hits, 1 misses, 1 stores/);
  assert.equal(mixed.profile.phases.optimize.calls, 1);
  assert.equal(mixed.profile.phases.translate.calls, 1);
  assert.deepEqual(mixed.profile.modules.filter(m => m.source === 'optimized').map(m => m.name), ['Main']);
});

test('build profile: load and output failures retain partial measurements and original errors', t => {
  const root = fixture(t);
  const missing = build(root, ['--output', 'absent', '--profile-build'], 1);
  assert.match(missing.stderr, /ENOENT/);
  assert.equal(missing.profile.phases['corefn.read'].failed, 1);
  assert.equal(missing.profile.phases.optimize.calls, 0);
  build(root, ['--bundle']);
  const target = path.join(root, 'output/Main/index.php');
  fs.unlinkSync(target);
  fs.mkdirSync(target);
  const failed = build(root, ['--bundle --profile-build'], 1);
  assert.match(failed.stderr, /EISDIR/);
  assert.match(failed.stderr, /backend total: .*\(failed\)/);
  assert.equal(failed.profile.phases['php.write'].failed, 1);
  assert.equal(failed.profile.phases['php.write'].completed, 2);
  assert.equal(failed.profile.phases.composer.calls, 0);
});

test('build profile: pure work is delayed and measured; scopes retain exceptions and stay independent', async t => {
  const reports = [];
  const original = console.error;
  console.error = line => reports.push(JSON.parse(line.replace('[phpurs] build-profile: ', '')));
  t.after(() => { console.error = original; });
  const failure = new Error('original pure failure');
  await assert.rejects(run(Profile.withProfile(true)(profile => effect(() => Profile.measurePure(profile)('translate')('Outer')(() => {
    Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, 15);
    throw failure;
  })()))), error => error === failure);
  assert.equal(reports[0].phases.translate.failed, 1);
  assert.ok(reports[0].phases.translate.ms >= 10);
  await run(Profile.withProfile(true)(outer => then(
    Profile.withProfile(true)(inner => Profile.measureAff(inner)('php.write')('Inner')(() => delay(20)))
  )(() => effect(() => Profile.measurePure(outer)('print')('Outer')(() => 42)()))));
  assert.equal(reports[1].phases['php.write'].completed, 1);
  assert.ok(reports[1].phases['php.write'].ms >= 15, 'must await Aff completion');
  assert.equal(reports[2].phases['php.write'].calls, 0);
  assert.equal(reports[2].phases.print.completed, 1);
  await run(Profile.withProfile(false)(profile => effect(() => Profile.measurePure(profile)('print')('')(() => 42)())));
  assert.equal(reports.length, 3, 'disabled profiling must not report or retain state');
});

test('build profile: cancellation closes active scopes and unfinished optimizer work', async t => {
  const reports = [];
  const original = console.error;
  console.error = line => reports.push(JSON.parse(line.replace('[phpurs] build-profile: ', '')));
  t.after(() => { console.error = original; });
  let entered;
  const started = new Promise(resolve => { entered = resolve; });
  const fiber = runAff(() => () => {})(Profile.withProfile(true)(profile =>
    then(effect(() => { Profile.beginOptimization(profile)('Interrupted')(); entered(); }))(() => never)))();
  await started;
  await run(killFiber(new Error('cancelled by test'))(fiber));
  assert.equal(reports.length, 1);
  assert.equal(reports[0].status, 'cancelled');
  assert.equal(reports[0].phases.optimize.cancelled, 1);
  assert.equal(reports[0].modules[0].name, 'Interrupted');
});
