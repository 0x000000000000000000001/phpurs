// Resolve real competing files to verify lookup priority and per-build roots.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { Just, Nothing } from '../../output/Data.Maybe/index.js';
import { resolvePackagePaths, findForeignFile } from '../../output/Phpurs.PackagePaths/index.js';

function withProject(run) {
  const cwd = process.cwd();
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-paths-')));
  try {
    process.chdir(root);
    run(root);
  } finally {
    process.chdir(cwd);
    fs.rmSync(root, { recursive: true, force: true });
  }
}

function write(file) {
  fs.mkdirSync(path.dirname(file), { recursive: true });
  fs.writeFileSync(file, '<?php\n');
}

function found(roots, moduleName, modulePath) {
  const result = findForeignFile(roots)(moduleName)(modulePath)();
  assert.ok(result instanceof Just);
  return path.resolve(result.value0);
}

test('package paths: adjacent, root and filename precedence with bounded lookup', () => withProject(root => {
  const candidates = [
    'local/src/Data/Example.php',
    '.spago/example/v1.0.0/src/Data/Example.php',
    '.spago/example/v1.0.0/src/Data.Example.php',
    '.spago/example/v1.0.0/Data.Example.php',
    'spago.d/example/src/Data/Example.php',
    'bak/spago.d/php/p/example/src/Data/Example.php',
    'ffi/src/Data/Example.php',
    'src/Data/Example.php',
  ];
  candidates.forEach(write);
  write('node_modules/ignored/src/Data/Example.php');
  const { ffiRoots } = resolvePackagePaths({ ffiDir: new Just('ffi'), modulePaths: [] })();
  const readdirSync = fs.readdirSync;
  try {
    fs.readdirSync = () => { throw new Error('lookup must use its prepared roots'); };
    for (const candidate of candidates) {
      assert.equal(found(ffiRoots, 'Data.Example', 'local/src/Data/Example.purs'), path.join(root, candidate));
      fs.unlinkSync(candidate);
    }
    assert.equal(findForeignFile(ffiRoots)('Data.Example')('local/src/Data/Example.purs')(), Nothing.value);
  } finally {
    fs.readdirSync = readdirSync;
  }
}));

test('package paths: roots follow each build and FFI option in the same process', () => withProject(root => {
  write('one/ffi/Client.php');
  write('one/other/Client.php');
  write('two/ffi/Client.php');

  process.chdir(path.join(root, 'one'));
  const first = resolvePackagePaths({ ffiDir: new Just('ffi'), modulePaths: [] })();
  assert.equal(found(first.ffiRoots, 'Client', 'src/Client.purs'), path.join(root, 'one/ffi/Client.php'));
  const other = resolvePackagePaths({ ffiDir: new Just('other'), modulePaths: [] })();
  assert.equal(found(other.ffiRoots, 'Client', 'src/Client.purs'), path.join(root, 'one/other/Client.php'));

  process.chdir(path.join(root, 'two'));
  const second = resolvePackagePaths({ ffiDir: new Just(path.join(root, 'two/ffi')), modulePaths: [] })();
  assert.equal(found(second.ffiRoots, 'Client', 'src/Client.purs'), path.join(root, 'two/ffi/Client.php'));
}));
