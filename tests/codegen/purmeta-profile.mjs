import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { writeFixture } from './fixtures/cache-inputs.mjs';

const cli = fileURLToPath(new URL('../../bin/phpurs.js', import.meta.url));
const probe = fileURLToPath(new URL('./fixtures/purmeta-profile-probe.mjs', import.meta.url));

function build(root, args, status = 0) {
  const result = spawnSync(process.execPath, ['--import', probe, cli, ...args], {
    cwd: root, encoding: 'utf8', timeout: 30000,
    env: { ...process.env, PHPURS_PURMETA_PROBE: path.join(root, 'probe.json') },
  });
  assert.equal(result.status, status, result.stdout + result.stderr);
  const profiles = [...result.stderr.matchAll(/^\[phpurs\] purmeta: (.+)$/gm)].map(match => JSON.parse(match[1]));
  assert.equal(profiles.length, args.join(' ').includes('--profile-purmeta') ? 1 : 0);
  const profile = profiles[0];
  if (profile) {
    const io = JSON.parse(fs.readFileSync(path.join(root, 'probe.json')));
    assert.deepEqual({ attempts: profile.reads.ioAttempts, files: profile.reads.files, bytes: profile.reads.bytes }, io.reads);
    assert.deepEqual({ attempts: profile.writes.attempts, files: profile.writes.files, bytes: profile.writes.bytes }, io.writes);
    assert.equal(profile.reads.requests, profile.reads.blocked + profile.reads.ramHits + profile.reads.ramMisses);
    assert.equal(profile.reads.ramMisses, profile.reads.diskHits + profile.reads.diskMissing + profile.reads.errors);
    assert.ok(profile.memory.processPeakRSSKiB <= io.processPeakRSSKiB);
  }
  return { ...result, profile };
}

function output(root) {
  return Object.fromEntries(fs.readdirSync(path.join(root, 'output'), { recursive: true })
    .filter(file => /\.php$|composer\.json$|\.phpurs-outputs\.json$/.test(file)).sort()
    .map(file => [file, fs.readFileSync(path.join(root, 'output', file))]));
}

test('purmeta profile: full compilation and restored modules expose independent PBO counters with identical PHP', t => {
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-purmeta-profile-')));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  writeFixture(root);
  build(root, ['--bundle', '--no-cache']);
  const expected = output(root);
  const full = build(root, ['--bundle', '--no-cache', '--profile-purmeta']).profile;
  assert.ok(full.reads.ramHits > 0);
  assert.equal(full.reads.ioAttempts, 0, 'the fixture fits in the existing RAM budget');
  assert.equal(full.writes.files, 3);
  assert.equal(full.writes.serializations, 3);
  assert.equal(full.ram.trimCalls, 3);
  assert.deepEqual(output(root), expected);
  build(root, ['--bundle']);
  const hot = build(root, ['--bundle --profile-purmeta']);
  assert.match(hot.stderr, /cache: 3 hits, 0 misses, 0 stores/);
  assert.doesNotMatch(hot.stdout, /Generating PHP code/);
  assert.equal(hot.profile.reads.requests, 0);
  assert.equal(hot.profile.writes.files, 3);
  assert.equal(hot.profile.writes.serializations, 3);
  assert.deepEqual(output(root), expected);
  for (const entry of ['main.mod.php', 'main.bundle.php']) {
    const run = spawnSync('php', ['-d', 'opcache.enable_cli=0', 'output/Main/' + entry], { cwd: root, encoding: 'utf8' });
    assert.equal(run.status, 0, run.stdout + run.stderr);
    assert.equal(run.stdout, '43\n');
    assert.equal(run.stderr, '');
  }
  fs.unlinkSync(path.join(root, 'output/Main/index.php'));
  fs.mkdirSync(path.join(root, 'output/Main/index.php'));
  const failed = build(root, ['--bundle', '--profile-purmeta'], 1);
  assert.match(failed.stderr, /optimize \+ emit: .*\(failed\)/);
  assert.match(failed.stderr, /EISDIR/);
  assert.equal(failed.profile.writes.files, 2, 'failure still reports the completed prefix');
});
