// Test package discovery and failure propagation without running package suites.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { temporaryRoot } from '../fixtures/cli.mjs';

function fixture(t) {
  const root = temporaryRoot(t, 'phpurs-sibling-runner-');
  const project = path.join(root, 'compiler checkout');
  fs.mkdirSync(path.join(project, 'bin'), { recursive: true });
  fs.copyFileSync(
    new URL('../../../bin/modtest', import.meta.url),
    path.join(project, 'bin/modtest'),
  );
  const calls = path.join(root, 'calls');

  function executable(file, name, mode = 0o755) {
    fs.mkdirSync(path.dirname(file), { recursive: true });
    fs.writeFileSync(
      file,
      `#!/usr/bin/env bash
printf '%s:%s\\n' '${name}' "$PWD" >> "$RUNNER_CALLS"
if [[ "\${RUNNER_FAIL:-}" == '${name}' ]]; then
  printf '%s\\n' '${name} failure detail' >&2
  exit 37
fi
`,
      { mode },
    );
  }
  executable(path.join(project, 'bin/npm'), 'build');

  function addSuite(name, mode = 0o755) {
    executable(path.join(root, name, 'bin/test'), name, mode);
  }

  function run(args = [], fail = '') {
    fs.writeFileSync(calls, '');
    const result = spawnSync('bash', [path.join(project, 'bin/modtest'), ...args], {
      cwd: root,
      encoding: 'utf8',
      timeout: 10000,
      env: {
        ...process.env,
        PATH: path.join(project, 'bin') + path.delimiter + process.env.PATH,
        RUNNER_CALLS: calls,
        RUNNER_FAIL: fail,
      },
    });
    assert.equal(result.error, undefined);
    return { ...result, calls: fs.readFileSync(calls, 'utf8').trim().split('\n').filter(Boolean) };
  }
  return { root, project, addSuite, run };
}

test('sibling runner: rejects unknown arguments before building or running suites', (t) => {
  const { addSuite, run } = fixture(t);
  addSuite('phpurs-alpha');
  const result = run(['--clean', '--unknown']);
  assert.equal(result.status, 1, result.stderr);
  assert.match(result.stderr, /Unknown argument: --unknown/);
  assert.deepEqual(result.calls, []);
});

test('sibling runner: reports no executable suites instead of success', (t) => {
  const { addSuite, run } = fixture(t);
  addSuite('phpurs-disabled', 0o644);
  const result = run(['--clean']);
  assert.equal(result.status, 1, result.stderr);
  assert.match(result.stderr, /No executable sibling suites/);
  assert.deepEqual(result.calls, []);
});

test('sibling runner: selects executable phpurs siblings and uses each working directory', (t) => {
  const { root, addSuite, run } = fixture(t);
  addSuite('phpurs-alpha');
  addSuite('phpurs-beta');
  addSuite('phpurs-disabled', 0o644);
  addSuite('unrelated-package');
  const result = run();
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /Summary: 2 package suites passed/);
  assert.deepEqual(
    result.calls,
    ['phpurs-alpha', 'phpurs-beta'].map((name) => `${name}:${path.join(root, name)}`),
  );
});

test('sibling runner: stops after the first failing package and retains its diagnostic', (t) => {
  const { root, addSuite, run } = fixture(t);
  addSuite('phpurs-alpha');
  addSuite('phpurs-beta');
  const result = run([], 'phpurs-alpha');
  assert.equal(result.status, 1, result.stderr);
  assert.match(result.stderr, /phpurs-alpha failure detail/);
  assert.match(result.stderr, /Tests failed in phpurs-alpha/);
  assert.deepEqual(result.calls, [`phpurs-alpha:${path.join(root, 'phpurs-alpha')}`]);
});

test('sibling runner: a failed clean rebuild stops before package execution', (t) => {
  const { project, addSuite, run } = fixture(t);
  addSuite('phpurs-alpha');
  const result = run(['-c'], 'build');
  assert.equal(result.status, 1, result.stderr);
  assert.match(result.stderr, /build failure detail/);
  assert.match(result.stderr, /Compiler build failed/);
  assert.deepEqual(result.calls, [`build:${project}`]);
});

test('sibling runner: clean rebuild precedes package execution', (t) => {
  const { root, project, addSuite, run } = fixture(t);
  addSuite('phpurs-alpha');
  const result = run(['--clean']);
  assert.equal(result.status, 0, result.stderr);
  assert.deepEqual(result.calls, [
    `build:${project}`,
    `phpurs-alpha:${path.join(root, 'phpurs-alpha')}`,
  ]);
});

test('sibling runner: help succeeds without installed package suites', (t) => {
  const { run } = fixture(t);
  const result = run(['--help']);
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /Usage: bin\/modtest/);
  assert.deepEqual(result.calls, []);
});
