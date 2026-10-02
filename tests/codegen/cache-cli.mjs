import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { corefn, write, writeFixture } from './fixtures/cache-inputs.mjs';

const cli = fileURLToPath(new URL('../../bin/phpurs.js', import.meta.url));
const probe = fileURLToPath(new URL('./fixtures/cache-cli-probe.mjs', import.meta.url));
const names = ['Library', 'Consumer', 'Main'];
const old = new Date('2001-01-01T00:00:00Z');
function fixture(t) {
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-cache-cli-')));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  writeFixture(root);
  return root;
}
function build(root, args = ['--bundle'], options = {}) {
  write(root, 'probe-config.json', JSON.stringify({ stats: path.join(root, 'probe.json'), ...options }));
  const result = spawnSync(process.execPath, ['--import', probe, options.cli || cli, ...args], {
    cwd: root, encoding: 'utf8', timeout: 30000, maxBuffer: 2000000,
    env: { ...process.env, GOPURS_JOBS: options.jobs || '1', PHPURS_CLI_PROBE: path.join(root, 'probe-config.json') },
  });
  assert.equal(result.status, 0, (result.error || '') + result.stdout + result.stderr);
  if (!options.allowDecodeError) assert.doesNotMatch(result.stderr, /Failed to decode|Failed to read purmeta/);
  const stats = result.stderr.match(/cache: (\d+) hits, (\d+) misses, (\d+) stores/);
  return { ...JSON.parse(fs.readFileSync(path.join(root, 'probe.json'), 'utf8')),
    generated: [...result.stdout.matchAll(/Generating PHP code for (\S+)/g)].map(m => m[1]),
    counts: stats ? stats.slice(1).map(Number) : null, stderr: result.stderr };
}
function output(root) {
  return Object.fromEntries(fs.readdirSync(path.join(root, 'output'), { recursive: true }).filter(file => /\.php$|composer\.json$/.test(file)).sort()
    .map(file => [file, fs.readFileSync(path.join(root, 'output', file))]));
}
function matchesUncached(root, args = ['--bundle']) {
  const cached = output(root);
  const flags = args.flatMap(arg => arg.split(' '));
  const bundleOnly = flags.includes('--bundle-only');
  const bundle = bundleOnly || flags.includes('--bundle');
  for (const file of Object.keys(cached)) {
    if (file === 'bundle.php' && flags.includes('--main')) continue;
    if (file === 'composer.json' || (!bundleOnly && /index\.php$|main\.mod\.php$/.test(file)) || (bundle && /bundle\.php$/.test(file))) {
      fs.unlinkSync(path.join(root, 'output', file));
    }
  }
  const uncached = build(root, [...args, '--no-cache']);
  assert.equal(uncached.counts, null);
  const actual = output(root);
  assert.deepEqual(Object.keys(actual), Object.keys(cached));
  for (const file of Object.keys(cached)) assert.ok(actual[file].equals(cached[file]), `different uncached bytes: ${file}`);
}
function execute(root, expected = '43\n') {
  for (const file of ['Main/main.mod.php', 'Main/main.bundle.php']) {
    if (!fs.existsSync(path.join(root, 'output', file))) continue;
    const result = spawnSync('php', ['-d', 'opcache.enable_cli=0', path.join('output', file)], { cwd: root, encoding: 'utf8', timeout: 10000 });
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert.equal(result.stderr, '');
    assert.equal(result.stdout, expected);
  }
}
function entries(root) {
  const directory = path.join(root, 'output/.phpurs-cache/v1');
  return fs.readdirSync(directory).map(file => {
    const bytes = fs.readFileSync(path.join(directory, file));
    const header = JSON.parse(bytes.subarray(bytes.indexOf(10) + 1, bytes.indexOf(10, bytes.indexOf(10) + 1)).toString());
    return { ...header, file: path.join(directory, file) };
  });
}

for (const args of [[], ['--bundle'], ['--bundle-only'], ['--bundle --bundle-only --main Main --output ./output']]) {
  test(`CLI cache: cold/hot/uncached equivalence and output repair ${args.join(' ') || 'modular'}`, t => {
    const root = fixture(t);
    const cold = build(root, args);
    assert.deepEqual(cold.generated, names);
    assert.deepEqual(cold.counts, [0, 3, 3]);
    const expected = output(root);
    for (const file of Object.keys(expected).filter(f => f.endsWith('.php'))) fs.utimesSync(path.join(root, 'output', file), old, old);
    const hot = build(root, args, { jobs: '4' });
    assert.deepEqual(hot.counts, [3, 0, 0]);
    assert.deepEqual(hot.generated, []);
    assert.deepEqual(hot.writes.filter(f => f.endsWith('.php')), []);
    assert.deepEqual(hot.reads.filter(f => f.endsWith('corefn.json')).sort(), names.map(n => `${args.length === 1 && args[0].includes('--output') ? './output' : 'output'}/${n}/corefn.json`).sort());
    assert.deepEqual(hot.reads.filter(f => f.startsWith('src/')).sort(), ['src/Library.php', 'src/Main.php']);
    for (const file of Object.keys(expected).filter(f => f.endsWith('.php'))) assert.equal(fs.statSync(path.join(root, 'output', file)).mtimeMs, old.getTime());
    const php = Object.keys(expected).filter(f => f.endsWith('.php'));
    fs.unlinkSync(path.join(root, 'output', php[0]));
    write(root, 'output/' + php.at(-1), '<?php // damaged');
    assert.deepEqual(build(root, args).counts, [3, 0, 0]);
    assert.deepEqual(output(root), expected);
    execute(root);
    matchesUncached(root, args);
  });
}

test('CLI cache: CoreFn bytes, dependency constants and module directives invalidate the suffix', t => {
  const root = fixture(t);
  build(root);
  const consumerBefore = fs.readFileSync(path.join(root, 'output/Consumer/index.php'));
  const file = path.join(root, 'output/Library/corefn.json');
  const bytes = fs.readFileSync(file);
  const changed = bytes.toString().replace('"value":42', '"value":43');
  assert.notEqual(changed, bytes.toString());
  assert.equal(Buffer.byteLength(changed), bytes.length);
  fs.utimesSync(file, old, old);
  fs.writeFileSync(file, changed);
  fs.utimesSync(file, old, old);
  assert.deepEqual(build(root).generated, names);
  assert.notDeepEqual(fs.readFileSync(path.join(root, 'output/Consumer/index.php')), consumerBefore);
  execute(root, '44\n');
  matchesUncached(root);
  // Whitespace affects raw identity even when decoding produces the same AST.
  fs.appendFileSync(path.join(root, 'output/Main/corefn.json'), '\n');
  assert.deepEqual(build(root).generated, ['Main']);
  const mod = JSON.parse(fs.readFileSync(file, 'utf8'));
  mod.comments = [{ LineComment: '@inline export keep always' }];
  fs.writeFileSync(file, JSON.stringify(mod));
  assert.deepEqual(build(root).generated, names);
  matchesUncached(root);
  execute(root, '44\n');
});

test('CLI cache: FFI edits, UTF-8 bytes, missing/empty sources and winning paths are recaptured', t => {
  const root = fixture(t);
  build(root);
  const file = path.join(root, 'src/Main.php');
  fs.appendFileSync(file, '// \uFFFD\n');
  assert.deepEqual(build(root).generated, ['Main']);
  const before = fs.readFileSync(file);
  const i = before.indexOf(Buffer.from('\uFFFD'));
  fs.writeFileSync(file, Buffer.concat([before.subarray(0, i), Buffer.from([255]), before.subarray(i + 3)]));
  fs.utimesSync(file, old, old);
  const rawChange = build(root);
  assert.deepEqual(rawChange.generated, ['Main']);
  assert.deepEqual(rawChange.writes.filter(f => f.endsWith('.php')), [], 'equal decoded PHP still has distinct raw FFI keys');
  matchesUncached(root);
  execute(root);

  write(root, 'output/Z.Missing/corefn.json', JSON.stringify(corefn('Z.Missing', [], [], ['add'])));
  build(root);
  write(root, 'Z.Missing.php', '');
  assert.deepEqual(build(root).generated, ['Z.Missing']);
  write(root, 'Z.Missing.php', '<?php $exports["add"] = function($a, $b) { return $a + $b; };');
  assert.deepEqual(build(root).generated, ['Z.Missing']);
  write(root, 'src/Z.Missing.php', fs.readFileSync(path.join(root, 'Z.Missing.php')));
  assert.deepEqual(build(root).generated, ['Z.Missing'], 'same content at a new winning path misses');
  fs.unlinkSync(path.join(root, 'src/Z.Missing.php'));
  // Returning to an earlier identical path/content may reuse its retained key.
  assert.deepEqual(build(root).counts, [4, 0, 0]);
  fs.unlinkSync(path.join(root, 'Z.Missing.php'));
  assert.deepEqual(build(root).counts, [4, 0, 0]);
  matchesUncached(root);
});

test('CLI cache: each effective option, compiler identity and host compatibility partitions reuse', t => {
  const root = fixture(t);
  build(root);
  for (const args of [
    ['--bundle', '--main', 'Main'],
    ['--bundle', '--autoload-path', 'vendor/autoload.php'],
    ['--bundle', '--autoload-path', 'vendor/other.php'],
    ['--bundle', '--output', './output'],
    ['--bundle', '--ffi', 'alternate-ffi'],
    ['--bundle-only'], [],
  ]) {
    assert.deepEqual(build(root, args).counts, [0, 3, 3], args.join(' '));
    assert.deepEqual(build(root, args).counts, [3, 0, 0]);
    matchesUncached(root, args);
  }
  assert.deepEqual(build(root, ['--bundle', '--ffi', '.']).counts, [3, 0, 0], 'equivalent effective roots reuse keys');
  assert.deepEqual(build(root, ['--bundle'], { host: { arch: 'fixture-arch' } }).counts, [0, 3, 3]);
  const alternate = path.join(root, 'compiler.mjs');
  const original = fs.readFileSync(cli, 'utf8');
  const sourceChange = original.replace(/^const source = (.+);$/m, (_, value) => `const source = ${JSON.stringify(JSON.parse(value) + '\n// different compiler bytes')};`);
  assert.notEqual(sourceChange, original);
  fs.writeFileSync(alternate, sourceChange);
  assert.deepEqual(build(root, ['--bundle'], { cli: alternate }).counts, [0, 3, 3]);
  for (const field of ['phpursVersion', 'pboVersion']) {
    fs.writeFileSync(alternate, original.replace(/^const versions = (.+);$/m, (_, value) => `const versions = ${JSON.stringify({ ...JSON.parse(value), [field]: 'changed' })};`));
    assert.deepEqual(build(root, ['--bundle'], { cli: alternate }).counts, [0, 3, 3]);
  }
});

test('CLI cache: capture survives CoreFn, FFI and executable replacement after the read', t => {
  const root = fixture(t);
  const core = fs.readFileSync(path.join(root, 'output/Library/corefn.json'));
  const modified = core.toString().replace('"value":42', '"value":51');
  const first = build(root, ['--bundle'], { mutation: { afterRead: 'output/Library/corefn.json', file: 'output/Library/corefn.json', bytes: Buffer.from(modified).toString('base64') } });
  assert.equal(first.mutated, true);
  execute(root, '43\n');
  assert.deepEqual(build(root).generated, names);
  execute(root, '52\n');
  const ffi = fs.readFileSync(path.join(root, 'src/Main.php'), 'utf8');
  const replacement = ffi.replace('echo $value', 'echo 99');
  for (const entry of entries(root).filter(entry => entry.moduleName === 'Main')) fs.unlinkSync(entry.file);
  const second = build(root, ['--bundle'], { mutation: { afterRead: 'src/Main.php', file: 'src/Main.php', bytes: Buffer.from(replacement).toString('base64') } });
  assert.deepEqual(second.counts, [2, 1, 1], 'fresh codegen must use the captured FFI, too');
  execute(root, '52\n');
  assert.deepEqual(build(root).generated, ['Main']);
  execute(root, '99\n');

  const executable = path.join(root, 'compiler.mjs');
  const original = fs.readFileSync(cli, 'utf8');
  fs.writeFileSync(executable, original);
  const other = original.replace(/^const versions = (.+);$/m, (_, value) => `const versions = ${JSON.stringify({ ...JSON.parse(value), phpursVersion: 'replaced-on-disk' })};`);
  const replaced = build(root, ['--bundle'], { cli: executable, mutation: { afterRead: 'output/Library/corefn.json', file: executable, bytes: Buffer.from(other).toString('base64') } });
  assert.deepEqual(replaced.counts, [3, 0, 0], 'loaded compiler identity stays fixed');
  assert.deepEqual(build(root, ['--bundle'], { cli: executable }).counts, [0, 3, 3]);
});

test('CLI cache: missing/corrupt entries and unavailable storage use normal codegen', t => {
  const root = fixture(t);
  build(root);
  const byModule = new Map(entries(root).map(entry => [entry.moduleName, entry.file]));
  fs.unlinkSync(byModule.get('Library'));
  fs.writeFileSync(byModule.get('Main'), 'damaged');
  const repaired = build(root);
  assert.deepEqual(repaired.generated, ['Library', 'Main']);
  assert.deepEqual(repaired.counts, [1, 2, 2]);
  matchesUncached(root);
  const directory = path.join(root, 'output/.phpurs-cache');
  fs.rmSync(directory, { recursive: true });
  fs.writeFileSync(directory, 'not a directory');
  assert.deepEqual(build(root).counts, [0, 3, 0]);
  execute(root);
});

test('CLI cache: graph changes invalidate keys; duplicate names and cycles disable the whole plan', t => {
  const root = fixture(t);
  build(root);
  write(root, 'output/Extra/corefn.json', JSON.stringify(corefn('Extra')));
  assert.equal(build(root).generated.length, 4);
  fs.rmSync(path.join(root, 'output/Extra'), { recursive: true });
  assert.deepEqual(build(root).counts, [3, 0, 0], 'returning to the original graph can reuse its complete plan');
  write(root, 'output/Duplicate/corefn.json', fs.readFileSync(path.join(root, 'output/Library/corefn.json')));
  const duplicate = build(root);
  assert.match(duplicate.stderr, /cache disabled: Incomplete or duplicate/);
  assert.deepEqual(duplicate.generated, names);
  fs.rmSync(path.join(root, 'output/Duplicate'), { recursive: true });
  write(root, 'output/CycleA/corefn.json', JSON.stringify(corefn('CycleA', ['CycleB'])));
  write(root, 'output/CycleB/corefn.json', JSON.stringify(corefn('CycleB', ['CycleA'])));
  const cycle = build(root);
  assert.match(cycle.stderr, /cache disabled: Cache dependency/);
  assert.equal(cycle.generated.length, 5);
});
