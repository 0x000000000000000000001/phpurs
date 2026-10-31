// Option parsing is pure and precedes every build; invalid values must fail
// before any output is touched.
import assert from 'node:assert/strict';
import test from 'node:test';
import { isLeft, isRight } from '../../../output/Data.Either/index.js';
import { parseOptions } from '../../../output/Main.Options/index.js';

const parse = (args) => parseOptions(args);

test('options: defaults', () => {
  const result = parse([]);
  assert.ok(isRight(result), result.value0);
  const options = result.value0;
  assert.equal(options.outputDir, 'output');
  assert.equal(options.verbose, false);
  assert.equal(options.noCache, false);
  assert.equal(options.profilePurmeta, false);
  assert.deepEqual(options.emission, { emitModules: true, emitBundle: false });
});

test('options: bundle modes and explicit output', () => {
  const bundle = parse(['--bundle', '--main', 'App.Main', '--output', 'dist']).value0;
  assert.deepEqual(bundle.emission, { emitModules: true, emitBundle: true });
  assert.equal(bundle.outputDir, 'dist');

  const only = parse(['--bundle-only']).value0;
  assert.deepEqual(only.emission, { emitModules: false, emitBundle: true });
});

test('options: first output occurrence wins and switches are booleans', () => {
  const options = parse([
    '--output',
    'first',
    '--output',
    'second',
    '--verbose',
    '--no-cache',
    '--profile-purmeta',
  ]).value0;
  assert.equal(options.outputDir, 'first');
  assert.equal(options.verbose, true);
  assert.equal(options.noCache, true);
  assert.equal(options.profilePurmeta, true);
});

test('options: invalid budget and rewrite limit fail', () => {
  assert.ok(isLeft(parse(['--purmeta-cache-mib', 'not-a-number'])));
  assert.ok(isLeft(parse(['--rewrite-limit', '0'])));
});
