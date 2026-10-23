// Exercise package isolation, config restoration and failure gates without a build.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { temporaryRoot } from '../fixtures/cli.mjs';

const completionMarker = 'Package tests completed';

function fixture(t, configuration = 'regular') {
  const root = temporaryRoot(t, 'phpurs-package-runner-');
  const compiler = path.join(root, 'compiler checkout');
  const library = path.join(root, 'phpurs-example');
  const commands = path.join(root, 'commands');
  const temporary = path.join(root, 'temporary');
  const calls = path.join(root, 'calls');
  for (const directory of [
    path.join(compiler, 'bin'),
    path.join(compiler, 'output'),
    path.join(library, 'output'),
    path.join(library, '.spago'),
    path.join(root, 'phpurs-neighbor/output'),
    commands,
    temporary,
  ]) {
    fs.mkdirSync(directory, { recursive: true });
  }
  fs.copyFileSync(
    new URL('../../../bin/test-package', import.meta.url),
    path.join(compiler, 'bin/test-package'),
  );
  const originalConfig = 'package:\n  name: original-config\n';
  fs.writeFileSync(path.join(library, 'spago.php.yaml'), 'package:\n  name: php-config\n');
  if (configuration === 'regular') {
    fs.writeFileSync(path.join(library, 'spago.yaml'), originalConfig);
  } else if (configuration === 'symlink') {
    fs.writeFileSync(path.join(library, 'original.yaml'), originalConfig);
    fs.symlinkSync('original.yaml', path.join(library, 'spago.yaml'));
  } else if (configuration === 'dangling symlink') {
    fs.symlinkSync('missing-original.yaml', path.join(library, 'spago.yaml'));
  }
  const preservedFiles = [
    path.join(library, 'spago.lock'),
    path.join(library, '.spago/package-cache'),
    path.join(compiler, 'output/compiler-cache'),
    path.join(root, 'phpurs-neighbor/output/neighbor-cache'),
  ];
  for (const file of preservedFiles) fs.writeFileSync(file, `preserve ${file}\n`);
  const staleOutput = path.join(library, 'output/stale-cache');
  fs.writeFileSync(staleOutput, 'outdated TAST input\n');

  function executable(file, stage, body = '') {
    fs.writeFileSync(
      file,
      `#!/usr/bin/env bash
set -euo pipefail
stage=${stage}
printf '%s\t%s\t%s\n' "$stage" "$PWD" "$*" >> "$RUNNER_CALLS"
if [[ "\${RUNNER_FAIL:-}" == "$stage" ]]; then
  printf '%s failure detail\n' "$stage" >&2
  exit 37
fi
${body}
`,
      { mode: 0o755 },
    );
  }
  executable(path.join(commands, 'npm'), 'npm');
  executable(
    path.join(commands, 'spago'),
    'spago',
    `[[ -L spago.yaml && $(readlink spago.yaml) == spago.php.yaml ]] || exit 41
[[ ! -e output/stale-cache ]] || exit 42
mkdir -p output/Test.Main
printf '%s\n' "$RUNNER_CORE_JSON" > output/Test.Main/corefn.json`,
  );
  executable(
    path.join(compiler, 'bin/phpurs'),
    'phpurs',
    `if [[ "\${RUNNER_MISSING_MAIN:-}" != 1 ]]; then
  printf '%s\n' '<?php' > output/Test.Main/main.mod.php
fi`,
  );
  executable(
    path.join(commands, 'php'),
    '"php:$(basename "\${!#}")"',
    `if [[ "\${!#}" == output/Test.Main/main.mod.php && "\${RUNNER_OMIT_MARKER:-}" != 1 ]]; then
  printf '%s\n' "$PHPURS_TEST_COMPLETION_MARKER"
fi`,
  );

  function configState() {
    const file = path.join(library, 'spago.yaml');
    const stat = fs.lstatSync(file, { throwIfNoEntry: false });
    if (!stat) return { kind: 'absent' };
    return stat.isSymbolicLink()
      ? { kind: 'symlink', target: fs.readlinkSync(file) }
      : { kind: 'regular', content: fs.readFileSync(file, 'utf8') };
  }
  const initialConfig = configState();
  const preservedContents = preservedFiles.map((file) => fs.readFileSync(file, 'utf8'));

  function assertRestored() {
    assert.deepEqual(configState(), initialConfig);
    assert.deepEqual(
      preservedFiles.map((file) => fs.readFileSync(file, 'utf8')),
      preservedContents,
    );
    assert.deepEqual(fs.readdirSync(temporary), []);
    if (configuration === 'symlink') {
      assert.equal(fs.readFileSync(path.join(library, 'original.yaml'), 'utf8'), originalConfig);
    }
  }

  function run(args = [], environment = {}) {
    fs.writeFileSync(calls, '');
    const result = spawnSync('bash', [path.join(compiler, 'bin/test-package'), library, ...args], {
      cwd: root,
      encoding: 'utf8',
      timeout: 10000,
      env: {
        ...process.env,
        PATH: commands + path.delimiter + process.env.PATH,
        TMPDIR: temporary,
        RUNNER_CALLS: calls,
        RUNNER_FAIL: '',
        RUNNER_CORE_JSON: JSON.stringify({ dataDecls: [], classDecls: [] }),
        RUNNER_MISSING_MAIN: '',
        RUNNER_OMIT_MARKER: '',
        PHPURS_TEST_COMPLETION_MARKER: completionMarker,
        ...environment,
      },
    });
    assert.equal(result.error, undefined);
    const recordedCalls = fs
      .readFileSync(calls, 'utf8')
      .trim()
      .split('\n')
      .filter(Boolean)
      .map((line) => {
        const [stage, cwd, args] = line.split('\t');
        return { stage, cwd, args };
      });
    return { ...result, calls: recordedCalls, stages: recordedCalls.map((call) => call.stage) };
  }

  function addFfiTest() {
    fs.mkdirSync(path.join(library, 'test'));
    fs.writeFileSync(path.join(library, 'test/ffi.php'), '<?php\n');
  }

  return { compiler, library, staleOutput, run, addFfiTest, assertRestored };
}

for (const configuration of ['regular', 'symlink', 'dangling symlink', 'absent']) {
  for (const failure of ['', 'spago']) {
    test(`package runner: restores ${configuration} configuration after ${failure || 'success'}`, (t) => {
      const { run, assertRestored } = fixture(t, configuration);
      const result = run([], { RUNNER_FAIL: failure });
      assert.equal(result.status, failure ? 1 : 0, result.stderr);
      assert.deepEqual(
        result.stages,
        failure ? ['spago'] : ['spago', 'phpurs', 'php:main.mod.php'],
      );
      assertRestored();
    });
  }
}

test('package runner: builds with --pure and clears only its package output', (t) => {
  const { library, staleOutput, run, assertRestored } = fixture(t);
  const result = run();
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /Tests passed: phpurs-example/);
  assert.deepEqual(result.calls, [
    { stage: 'spago', cwd: library, args: 'build --pure' },
    { stage: 'phpurs', cwd: library, args: '--main Test.Main' },
    {
      stage: 'php:main.mod.php',
      cwd: library,
      args: '-d memory_limit=512M -d opcache.enable_cli=0 output/Test.Main/main.mod.php',
    },
  ]);
  assert.equal(fs.existsSync(staleOutput), false);
  assertRestored();
});

for (const [stage, expected] of [
  ['npm', ['npm']],
  ['spago', ['npm', 'spago']],
  ['phpurs', ['npm', 'spago', 'phpurs']],
  ['php:ffi.php', ['npm', 'spago', 'phpurs', 'php:ffi.php']],
  ['php:main.mod.php', ['npm', 'spago', 'phpurs', 'php:ffi.php', 'php:main.mod.php']],
]) {
  test(`package runner: stops after ${stage} failure and restores configuration`, (t) => {
    const { run, addFfiTest, assertRestored } = fixture(t);
    addFfiTest();
    const result = run(['--clean'], { RUNNER_FAIL: stage });
    assert.equal(result.status, 1, result.stderr);
    assert.ok(result.stderr.includes(`${stage} failure detail`), result.stderr);
    assert.deepEqual(result.stages, expected);
    assert.doesNotMatch(result.stdout, /Tests passed:/);
    assertRestored();
  });
}

test('package runner: clean rebuilds the compiler before package tests', (t) => {
  const { compiler, run, assertRestored } = fixture(t);
  const result = run(['-c']);
  assert.equal(result.status, 0, result.stderr);
  assert.deepEqual(result.stages, ['npm', 'spago', 'phpurs', 'php:main.mod.php']);
  assert.deepEqual(result.calls[0], { stage: 'npm', cwd: compiler, args: 'run build' });
  assertRestored();
});

for (const input of [{}, { dataDecls: [] }, { dataDecls: {}, classDecls: [] }]) {
  test(`package runner: rejects CoreFn without TAST declarations ${JSON.stringify(input)}`, (t) => {
    const { run, assertRestored } = fixture(t);
    const result = run([], { RUNNER_CORE_JSON: JSON.stringify(input) });
    assert.equal(result.status, 1, result.stderr);
    assert.match(result.stderr, /Test inputs lack TAST declarations/);
    assert.match(result.stderr, /Expected typed CoreFn for Test.Main/);
    assert.deepEqual(result.stages, ['spago']);
    assertRestored();
  });
}

test('package runner: refuses a missing generated main before running PHP', (t) => {
  const { run, assertRestored } = fixture(t);
  const result = run([], { RUNNER_MISSING_MAIN: '1' });
  assert.equal(result.status, 1, result.stderr);
  assert.match(result.stderr, /Missing generated Test.Main entrypoint/);
  assert.deepEqual(result.stages, ['spago', 'phpurs']);
  assertRestored();
});

test('package runner: rejects successful PHP exit without the required completion marker', (t) => {
  const { run, assertRestored } = fixture(t);
  const result = run([], { RUNNER_OMIT_MARKER: '1' });
  assert.equal(result.status, 1, result.stderr);
  assert.match(result.stderr, /PHP tests exited before completion marker: Package tests completed/);
  assert.deepEqual(result.stages, ['spago', 'phpurs', 'php:main.mod.php']);
  assert.doesNotMatch(result.stdout, /Tests passed:/);
  assertRestored();
});

test('package runner: executes optional FFI checks before main with Composer autoloading', (t) => {
  const { library, run, addFfiTest, assertRestored } = fixture(t);
  addFfiTest();
  fs.mkdirSync(path.join(library, 'vendor'));
  fs.writeFileSync(path.join(library, 'vendor/autoload.php'), '<?php\n');
  const result = run();
  assert.equal(result.status, 0, result.stderr);
  assert.deepEqual(result.stages, ['spago', 'phpurs', 'php:ffi.php', 'php:main.mod.php']);
  for (const call of result.calls.slice(2)) {
    assert.match(call.args, /-d auto_prepend_file=vendor\/autoload.php/);
  }
  assertRestored();
});

for (const args of [['--unknown'], ['--clean', '--unknown'], ['--help'], ['--clean', '--help']]) {
  test(`package runner: handles ${args.join(' ')} before mutations or tool execution`, (t) => {
    const { staleOutput, run, assertRestored } = fixture(t);
    const result = run(args);
    const help = args.includes('--help');
    assert.equal(result.status, help ? 0 : 1, result.stderr);
    if (help) assert.match(result.stdout, /Usage: bin\/test/);
    else assert.match(result.stderr, /Unknown argument: --unknown/);
    assert.deepEqual(result.stages, []);
    assert.equal(fs.readFileSync(staleOutput, 'utf8'), 'outdated TAST input\n');
    assertRestored();
  });
}
