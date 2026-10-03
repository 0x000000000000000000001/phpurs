import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { Left, Right } from '../../output/Data.Either/index.js';
import { parseLimit } from '../../output/Phpurs.RewriteLimit/index.js';
import { corefn, write, writeFixture } from './fixtures/cache-inputs.mjs';

const cli = process.env.PHPURS_TEST_CLI || fileURLToPath(new URL('../../bin/phpurs.js', import.meta.url));
const probe = fileURLToPath(new URL('./fixtures/cache-cli-probe.mjs', import.meta.url));
function fixture(t) {
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-rewrite-limit-')));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  writeFixture(root);
  return root;
}
function build(root, flags = [], status = 0) {
  write(root, 'probe-config.json', JSON.stringify({ stats: path.join(root, 'probe.json') }));
  const result = spawnSync(process.execPath, ['--import', probe, cli, '--main', 'Main', '--bundle', '--verbose', ...flags], {
    cwd: root, encoding: 'utf8', timeout: 30000,
    env: { ...process.env, GOPURS_JOBS: '1', PHPURS_CLI_PROBE: path.join(root, 'probe-config.json') },
  });
  assert.equal(result.status, status, (result.error || '') + result.stdout + result.stderr);
  assert.doesNotMatch(result.stdout + result.stderr, /Failed to decode|Failed to read purmeta/);
  const cache = result.stderr.match(/cache: (\d+) hits, (\d+) misses, (\d+) stores/);
  return { ...result, cache: cache ? cache.slice(1).map(Number) : null,
    io: JSON.parse(fs.readFileSync(path.join(root, 'probe.json'), 'utf8')) };
}
function outputs(root) {
  return Object.fromEntries(fs.readdirSync(path.join(root, 'output'), { recursive: true })
    .filter(file => /\.php$|composer\.json$|\.phpurs-outputs\.json$/.test(file)).sort()
    .map(file => [file, fs.readFileSync(path.join(root, 'output', file))]));
}
function runtime(root) {
  for (const entry of ['main.mod.php', 'main.bundle.php']) {
    const run = spawnSync('php', ['-d', 'opcache.enable_cli=0', path.join(root, 'output/Main', entry)], { cwd: root, encoding: 'utf8' });
    assert.equal(run.status, 0, run.stdout + run.stderr);
    assert.equal(run.stdout, '43\n');
    assert.equal(run.stderr, '');
  }
}

test('rewrite limit: validates decimal Int bounds and takes the first occurrence', () => {
  for (const [args, expected] of [
    [[], 10000], [['--rewrite-limit', '1'], 1], [['--rewrite-limit=00042'], 42],
    [['--rewrite-limit', '2147483647'], 2147483647],
    [['--rewrite-limit=12', '--rewrite-limit', 'bad'], 12],
    [['--rewrite-limit', '13', '--rewrite-limit=14'], 13],
  ]) assert.deepEqual(parseLimit(args), new Right(expected), JSON.stringify(args));
  for (const value of ['', '0', '-1', '+1', '1.5', '1e3', '0x10', 'NaN', 'Infinity', '2147483648', '4294967297', '3x', '1\n', '\t1', '--bundle']) {
    for (const args of [['--rewrite-limit', value], ['--rewrite-limit=' + value]]) {
      const result = parseLimit(args);
      assert.ok(result instanceof Left, JSON.stringify(args));
      assert.match(result.value0, /Invalid value for --rewrite-limit/);
    }
  }
  assert.match(parseLimit(['--rewrite-limit']).value0, /Missing value for --rewrite-limit/);
});

test('rewrite limit CLI: effective values partition reuse; equivalent spellings share cache and PHP', t => {
  const root = fixture(t);
  assert.deepEqual(build(root).cache, [0, 3, 3]);
  const expected = outputs(root);
  const old = new Date('2001-01-01T00:00:00Z');
  for (const file of Object.keys(expected).filter(f => f.endsWith('.php'))) fs.utimesSync(path.join(root, 'output', file), old, old);
  for (const [flags, counts] of [
    [['--rewrite-limit', '10000'], [3, 0, 0]],
    [['--rewrite-limit=010000'], [3, 0, 0]],
    [['--rewrite-limit 10001'], [0, 3, 3]],
    [['--rewrite-limit=10001', '--rewrite-limit', '1'], [3, 0, 0]],
    [['--rewrite-limit=1'], [0, 3, 3]],
    [['--rewrite-limit', '1'], [3, 0, 0]],
    [[], [3, 0, 0]],
    [['--no-cache --rewrite-limit=10001'], null],
  ]) {
    const result = build(root, flags);
    assert.deepEqual(result.cache, counts, flags.join(' '));
    assert.deepEqual(result.io.writes.filter(f => f.endsWith('.php')), []);
    assert.deepEqual(outputs(root), expected);
    for (const file of Object.keys(expected).filter(f => f.endsWith('.php'))) assert.equal(fs.statSync(path.join(root, 'output', file)).mtimeMs, old.getTime());
    runtime(root);
  }
});

test('rewrite limit CLI: invalid values fail before input reads or output/cache mutation', t => {
  const root = fixture(t);
  build(root);
  const expected = outputs(root);
  const directory = path.join(root, 'output/.phpurs-cache/v1');
  const cache = fs.readdirSync(directory).sort().map(file => [file, fs.readFileSync(path.join(directory, file))]);
  for (const flags of [['--rewrite-limit'], ['--rewrite-limit', '0'], ['--rewrite-limit=-1'],
    ['--rewrite-limit 2147483648'], ['--rewrite-limit=oops'], ['--rewrite-limit=1\n'], ['--rewrite-limit', '--profile-build']]) {
    const result = build(root, flags, 1);
    assert.match(result.stderr, /(?:Missing|Invalid) value for --rewrite-limit/);
    assert.deepEqual(result.io.reads.filter(f => f.endsWith('corefn.json')), []);
    assert.deepEqual(result.io.writes, []);
    assert.deepEqual(outputs(root), expected);
    assert.deepEqual(fs.readdirSync(directory).sort().map(file => [file, fs.readFileSync(path.join(directory, file))]), cache);
  }
});

test('rewrite limit CLI: a real multi-pass binding fails at a low limit even after a warm build', t => {
  const root = fixture(t);
  // run b = (if b then (\x -> opaque x) else (\_ -> 2)) 42
  // Distributing the application through branches needs multiple PBO passes.
  const expr = (type, fields, ty = 0) => ({ type, annotation: { type: ty }, ...fields });
  const literal = value => expr('Literal', { value: { literalType: 'IntLiteral', value } });
  const variable = (identifier, ty = 0, moduleName) => expr('Var', { value: { identifier, ...(moduleName ? { moduleName: [moduleName] } : {}) } }, ty);
  const abs = (argument, body, ty = 1) => expr('Abs', { argument, body }, ty);
  const app = (abstraction, argument) => expr('App', { abstraction, argument });
  const select = expr('Case', { caseExpressions: [variable('b', 6)], caseAlternatives: [
    { binders: [{ binderType: 'LiteralBinder', annotation: { type: 6 }, literal: { literalType: 'BooleanLiteral', value: true } }],
      isGuarded: false, expression: abs('x', app(variable('opaque', 1, 'Limit'), variable('x'))) },
    { binders: [{ binderType: 'NullBinder', annotation: { type: 6 } }], isGuarded: false, expression: abs('x', literal(2)) },
  ] }, 1);
  const input = corefn('Limit', [], [{ bindType: 'NonRec', annotation: { type: 7 }, identifier: 'run',
    expression: abs('b', app(select, literal(42)), 7) }], ['opaque']);
  input.typeTable.push('Boolean', { type: 'Func', args: [6], ret: 0 });
  input.foreignAnnotations.opaque = { type: 1 };
  write(root, 'output/Limit/corefn.json', JSON.stringify(input));
  write(root, 'src/Limit.php', '<?php $exports["opaque"] = function($x) { return $x + 1; };');
  build(root);
  const expected = outputs(root);
  assert.deepEqual(build(root).cache, [4, 0, 0]);
  for (const flags of [['--rewrite-limit', '1'], ['--no-cache --rewrite-limit=1']]) {
    const result = build(root, flags, 1);
    assert.match(result.stderr, /Limit\.run: Possible infinite optimization loop/);
    assert.doesNotMatch(result.stdout, /Generating PHP code for Limit /);
    assert.deepEqual(outputs(root), expected);
  }
  assert.deepEqual(build(root, ['--rewrite-limit=100']).cache, [0, 4, 4]);
  assert.deepEqual(build(root, ['--rewrite-limit 100']).cache, [4, 0, 0]);
  assert.deepEqual(outputs(root), expected);
  runtime(root);
  const run = spawnSync('php', [], { cwd: root, encoding: 'utf8', input: '<?php require "output/Limit/index.php"; echo \\Limit\\majLimit_run(true), ",", \\Limit\\majLimit_run(false), "\\n";' });
  assert.equal(run.status, 0, run.stdout + run.stderr);
  assert.equal(run.stdout, '43,2\n');
  assert.equal(run.stderr, '');
});
