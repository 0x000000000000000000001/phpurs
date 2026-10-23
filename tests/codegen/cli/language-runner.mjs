// Exercise runner control flow without compiling hundreds of language fixtures.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { temporaryRoot } from '../fixtures/cli.mjs';

function fixture(t) {
  const root = temporaryRoot(t, 'phpurs-language-runner-');
  const project = path.join(root, 'phpurs');
  for (const directory of ['bin', 'tests/passing', 'tests/runner']) {
    fs.mkdirSync(path.join(project, directory), { recursive: true });
  }
  fs.mkdirSync(path.join(root, 'phpurs-prelude'));
  fs.copyFileSync(new URL('../../../bin/test', import.meta.url), path.join(project, 'bin/test'));
  fs.writeFileSync(path.join(project, 'bin/pkg'), 'CORE_PACKAGES=(prelude)\n');
  for (const name of ['Alpha', 'Beta', '2136']) {
    fs.writeFileSync(path.join(project, `tests/passing/${name}.purs`), `module ${name} where\n`);
  }
  for (const command of ['spago', 'phpurs', 'php', 'npm']) {
    fs.writeFileSync(
      path.join(project, 'bin', command),
      `#!/usr/bin/env bash
printf '%s\\n' '${command}' >> "$RUNNER_CALLS"
if [[ "\${RUNNER_FAIL:-}" == '${command}' ]]; then
  printf '%s\\n' '${command} failure detail' >&2
  exit 37
fi
`,
      { mode: 0o755 },
    );
  }
  const calls = path.join(root, 'calls');
  function run(args, fail = '') {
    fs.writeFileSync(calls, '');
    const result = spawnSync('bash', [path.join(project, 'bin/test'), ...args], {
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
  return { project, run };
}

for (const args of [
  ['Missing'],
  ['Alpha', 'Missing'],
  ['--skip-before=Missing'],
  ['--skip-before='],
  ['--unknown'],
]) {
  test(`language runner: rejects invalid selection ${args.join(' ')} before running tools`, (t) => {
    const { run } = fixture(t);
    const result = run(args);
    assert.equal(result.status, 1, result.stderr);
    assert.match(result.stderr, /\[ERROR\]/);
    assert.deepEqual(result.calls, []);
  });
}

for (const [command, expected] of [
  ['spago', ['spago']],
  ['phpurs', ['spago', 'phpurs']],
  ['php', ['spago', 'phpurs', 'php']],
]) {
  test(`language runner: reports ${command} failure and stops before the next fixture`, (t) => {
    const { run } = fixture(t);
    const result = run(['Alpha', 'Beta'], command);
    assert.equal(result.status, 1, result.stderr);
    assert.match(result.stderr, new RegExp(`${command} failure detail`));
    assert.match(result.stdout, /Summary: 0 passed, 1 failed, 0 skipped/);
    assert.deepEqual(result.calls, expected);
  });
}

test('language runner: selects relative paths before changing working directory', (t) => {
  const { project, run } = fixture(t);
  const result = run(['phpurs/tests/passing/Beta.purs']);
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /Summary: 1 passed, 0 failed, 0 skipped/);
  assert.match(
    fs.readFileSync(path.join(project, 'tests/runner/src/Main.purs'), 'utf8'),
    /module Beta where/,
  );
  assert.deepEqual(result.calls, ['spago', 'phpurs', 'php']);
});

test('language runner: resume starts at the named fixture and keeps exclusions visible', (t) => {
  const { run } = fixture(t);
  const resumed = run(['--skip-before=Beta']);
  assert.equal(resumed.status, 0, resumed.stderr);
  assert.doesNotMatch(resumed.stdout, /Testing Alpha/);
  assert.match(resumed.stdout, /Summary: 1 passed, 0 failed, 0 skipped/);
  const all = run([]);
  assert.equal(all.status, 0, all.stderr);
  assert.match(all.stdout, /Skipping 2136.purs/);
  assert.match(all.stdout, /Summary: 2 passed, 0 failed, 1 skipped/);
});

test('language runner: clean removes the actual fixture output cache', (t) => {
  const { project, run } = fixture(t);
  const cache = path.join(project, 'tests/runner/output-test/stale');
  fs.mkdirSync(cache, { recursive: true });
  fs.writeFileSync(path.join(cache, 'corefn.json'), '{}');
  const result = run(['Alpha', '--clean']);
  assert.equal(result.status, 0, result.stderr);
  assert.equal(fs.existsSync(cache), false);
  assert.deepEqual(result.calls, ['npm', 'spago', 'phpurs', 'php']);
});
