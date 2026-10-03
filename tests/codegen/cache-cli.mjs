import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { corefn, write, writeFixture } from './fixtures/cache-inputs.mjs';

const cli = process.env.PHPURS_TEST_CLI || fileURLToPath(new URL('../../bin/phpurs.js', import.meta.url));
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
  const result = spawnSync(process.execPath, ['--import', probe, options.cli || cli, '--verbose', ...args], {
    cwd: root, encoding: 'utf8', timeout: 30000, maxBuffer: 2000000,
    env: { ...process.env, GOPURS_JOBS: options.jobs || '1', PHPURS_CLI_PROBE: path.join(root, 'probe-config.json') },
  });
  assert.equal(result.status, options.status ?? 0, (result.error || '') + result.stdout + result.stderr);
  if (!options.allowDecodeError) assert.doesNotMatch(result.stderr, /Failed to decode|Failed to read purmeta/);
  const stats = result.stderr.match(/cache: (\d+) hits, (\d+) misses, (\d+) stores/);
  return { ...JSON.parse(fs.readFileSync(path.join(root, 'probe.json'), 'utf8')),
    generated: [...result.stdout.matchAll(/Generating PHP code for (\S+)/g)].map(m => m[1]),
    counts: stats ? stats.slice(1).map(Number) : null, stderr: result.stderr };
}
function output(root, directory = 'output') {
  const target = path.resolve(root, directory);
  return Object.fromEntries(fs.readdirSync(target, { recursive: true }).filter(file => /\.php$|composer\.json$|\.phpurs-outputs\.json$/.test(file)).sort()
    .map(file => [file, fs.readFileSync(path.join(target, file))]));
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
function execute(root, expected = '43\n', main = 'Main', directory = 'output') {
  let executed = 0;
  for (const entry of ['main.mod.php', 'main.bundle.php']) {
    const file = path.resolve(root, directory, main, entry);
    if (!fs.existsSync(file)) continue;
    const result = spawnSync('php', ['-d', 'opcache.enable_cli=0', file], { cwd: root, encoding: 'utf8', timeout: 10000 });
    assert.equal(result.status, 0, result.stdout + result.stderr);
    assert.equal(result.stderr, '');
    assert.equal(result.stdout, expected);
    executed++;
  }
  assert.ok(executed > 0, `no entrypoint for ${main}`);
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

function writeMain(root, name, message) {
  const mod = corefn(name, [], [], ['main']);
  mod.foreignAnnotations.main = { type: 4 };
  write(root, `output/${name}/corefn.json`, JSON.stringify(mod));
  write(root, `src/${name}.php`, `<?php\n$exports["main"] = function() { echo ${JSON.stringify(message)}; };\n`);
}

// Rebuild only the current inputs at the SAME paths, without prior generated
// output, ownership history or cache. Restore the cached tree even on failure.
function matchesFreshUncached(root, args, mains = { Main: '43\n' }, directory = 'output') {
  const cached = output(root, directory);
  for (const [main, expected] of Object.entries(mains)) execute(root, expected, main, directory);
  const target = path.resolve(root, directory);
  const saved = path.join(root, 'saved-output');
  fs.renameSync(target, saved);
  try {
    fs.mkdirSync(target, { recursive: true });
    for (const entry of fs.readdirSync(saved, { withFileTypes: true })) {
      if (entry.isDirectory() && fs.existsSync(path.join(saved, entry.name, 'corefn.json'))) {
        write(root, path.relative(root, path.join(target, entry.name, 'corefn.json')), fs.readFileSync(path.join(saved, entry.name, 'corefn.json')));
      }
    }
    assert.equal(build(root, [...args, '--no-cache']).counts, null);
    assert.deepEqual(output(root, directory), cached, 'fresh uncached file sets, PHP, Composer and ownership must agree');
    for (const [main, expected] of Object.entries(mains)) execute(root, expected, main, directory);
  } finally {
    fs.rmSync(target, { recursive: true, force: true });
    fs.renameSync(saved, target);
  }
}

test('CLI lifecycle: deleting only CoreFn removes owned PHP on a full hit and refreshes Composer', t => {
  const root = fixture(t);
  build(root);
  const original = output(root);
  const retired = corefn('Retired');
  retired.modulePath = 'packages/retired/src/Retired.purs';
  write(root, 'packages/retired/composer.json', '{"require":{"fixture/retired":"^1"}}');
  write(root, 'output/Retired/corefn.json', JSON.stringify(retired));
  assert.deepEqual(build(root).counts, [0, 4, 4]);
  assert.ok(fs.existsSync(path.join(root, 'output/Retired/index.php')));
  assert.equal(JSON.parse(output(root)['composer.json']).require['fixture/retired'], '^1');
  const cacheFiles = entries(root).map(entry => entry.file);
  fs.unlinkSync(path.join(root, 'output/Retired/corefn.json'));
  const removed = build(root);
  assert.deepEqual(removed.counts, [3, 0, 0]);
  assert.deepEqual(removed.generated, []);
  assert.equal(fs.existsSync(path.join(root, 'output/Retired/index.php')), false);
  assert.deepEqual(output(root), original);
  assert.deepEqual(entries(root).map(entry => entry.file), cacheFiles, 'content-addressed states remain reusable');
  matchesFreshUncached(root, ['--bundle']);
  write(root, 'output/Retired/corefn.json', JSON.stringify(retired));
  assert.deepEqual(build(root).counts, [4, 0, 0]);
  fs.unlinkSync(path.join(root, 'output/Retired/corefn.json'));
  const uncached = build(root, ['--bundle', '--no-cache']);
  assert.equal(uncached.counts, null);
  assert.deepEqual(uncached.generated, names);
  assert.deepEqual(output(root), original, 'uncached builds also retire obsolete owned PHP');
  execute(root);
  for (const name of names) fs.unlinkSync(path.join(root, `output/${name}/corefn.json`));
  assert.deepEqual(build(root).counts, [0, 0, 0]);
  assert.deepEqual(Object.keys(output(root)), ['.phpurs-outputs.json', 'bundle.php', 'composer.json']);
  matchesFreshUncached(root, ['--bundle'], {});
});

test('CLI lifecycle: main selection, automatic discovery, lost exports and deleted mains match fresh builds', t => {
  const root = fixture(t);
  writeMain(root, 'Alternate', 'alternate\n');
  for (const [main, counts] of [[null, [0, 4, 4]], ['Alternate', [0, 4, 4]], ['Main', [0, 4, 4]], [null, [4, 0, 0]]]) {
    const args = ['--bundle', ...(main ? ['--main', main] : [])];
    const result = build(root, args);
    assert.deepEqual(result.counts, counts);
    const expected = main ? { [main]: main === 'Main' ? '43\n' : 'alternate\n' } : { Main: '43\n', Alternate: 'alternate\n' };
    matchesFreshUncached(root, args, expected);
  }
  const mainFile = path.join(root, 'output/Main/corefn.json');
  const main = JSON.parse(fs.readFileSync(mainFile));
  main.exports = main.exports.filter(name => name !== 'main');
  fs.writeFileSync(mainFile, JSON.stringify(main));
  build(root);
  assert.equal(fs.existsSync(path.join(root, 'output/Main/main.mod.php')), false);
  assert.equal(fs.existsSync(path.join(root, 'output/Main/main.bundle.php')), false);
  assert.ok(fs.existsSync(path.join(root, 'output/Main/index.php')));
  matchesFreshUncached(root, ['--bundle'], { Alternate: 'alternate\n' });

  fs.unlinkSync(path.join(root, 'output/Alternate/corefn.json'));
  assert.deepEqual(build(root).counts, [0, 3, 3]);
  assert.equal(fs.existsSync(path.join(root, 'output/Alternate/index.php')), false);
  assert.equal(fs.existsSync(path.join(root, 'output/Alternate/main.mod.php')), false);
  assert.equal(fs.existsSync(path.join(root, 'output/Alternate/main.bundle.php')), false);
  assert.ok(fs.existsSync(path.join(root, 'output/bundle.php')));
  matchesFreshUncached(root, ['--bundle'], {});
  const library = spawnSync('php', ['output/bundle.php'], { cwd: root, encoding: 'utf8' });
  assert.equal(library.status, 0, library.stdout + library.stderr);
  assert.equal(library.stdout + library.stderr, '');
});

for (const bundleOnly of [false, true]) {
  test(`CLI lifecycle: disabled outputs survive deletion until their mode resumes (${bundleOnly ? 'bundle-only' : 'modular'})`, t => {
    const root = fixture(t);
    writeMain(root, 'Alternate', 'alternate\n');
    const directory = bundleOnly ? 'generated/php' : 'output';
    if (bundleOnly) {
      fs.mkdirSync(path.join(root, 'generated'));
      fs.renameSync(path.join(root, 'output'), path.join(root, directory));
    }
    const withOutput = args => [...args, '--output', path.resolve(root, directory)];
    build(root, withOutput(['--bundle']));
    const before = output(root, directory);
    const preserved = Object.keys(before).filter(file => bundleOnly ? /index\.php$|main\.mod\.php$/.test(file) : /bundle\.php$/.test(file));
    for (const file of preserved) fs.utimesSync(path.join(root, directory, file), old, old);
    fs.unlinkSync(path.join(root, directory, 'Main/corefn.json'));
    const nextArgs = withOutput(bundleOnly ? ['--bundle-only', '--main', 'Alternate'] : []);
    build(root, [nextArgs.join(' ')]);
    for (const file of preserved) {
      assert.deepEqual(fs.readFileSync(path.join(root, directory, file)), before[file], file);
      assert.equal(fs.statSync(path.join(root, directory, file)).mtimeMs, old.getTime(), file);
    }
    const obsolete = bundleOnly ? ['Main/main.bundle.php', 'bundle.php'] : ['Main/index.php', 'Main/main.mod.php'];
    for (const file of obsolete) assert.equal(fs.existsSync(path.join(root, directory, file)), false, file);
    assert.deepEqual(build(root, nextArgs).counts, [3, 0, 0]);
    const finalArgs = withOutput(['--bundle']);
    build(root, finalArgs);
    matchesFreshUncached(root, finalArgs, { Alternate: 'alternate\n' }, directory);
  });
}

test('CLI lifecycle: missing or non-exported explicit main fails before any emission, with and without cache', t => {
  const root = fixture(t);
  build(root);
  const before = output(root);
  for (const noCache of [[], ['--no-cache']]) {
    for (const [main, message] of [['Missing', /Main module Missing was not loaded/], ['Library', /Module Library does not export main/]]) {
      const result = build(root, ['--bundle', '--main', main, ...noCache], { status: 1 });
      assert.match(result.stderr, message);
      assert.deepEqual(result.generated, []);
      assert.deepEqual(result.writes, []);
      assert.deepEqual(output(root), before);
    }
  }
});

test('CLI lifecycle: incomplete or duplicate input defers cleanup; emission failures retain prior ownership', t => {
  const root = fixture(t);
  write(root, 'output/Retired/corefn.json', JSON.stringify(corefn('Retired')));
  build(root);
  fs.unlinkSync(path.join(root, 'output/Retired/corefn.json'));
  write(root, 'output/Broken/corefn.json', '{');
  const incomplete = build(root, ['--bundle'], { allowDecodeError: true });
  assert.match(incomplete.stderr, /cache disabled: Incomplete or duplicate/);
  assert.ok(fs.existsSync(path.join(root, 'output/Retired/index.php')));
  fs.rmSync(path.join(root, 'output/Broken'), { recursive: true });
  write(root, 'output/Duplicate/corefn.json', fs.readFileSync(path.join(root, 'output/Library/corefn.json')));
  assert.match(build(root).stderr, /cache disabled: Incomplete or duplicate/);
  assert.ok(fs.existsSync(path.join(root, 'output/Retired/index.php')));
  fs.rmSync(path.join(root, 'output/Duplicate'), { recursive: true });
  const manifest = fs.readFileSync(path.join(root, 'output/.phpurs-outputs.json'));
  fs.unlinkSync(path.join(root, 'output/Main/index.php'));
  fs.mkdirSync(path.join(root, 'output/Main/index.php'));
  assert.match(build(root, ['--bundle'], { status: 1 }).stderr, /optimize \+ emit: .*\(failed\)/);
  assert.deepEqual(fs.readFileSync(path.join(root, 'output/.phpurs-outputs.json')), manifest);
  assert.ok(fs.existsSync(path.join(root, 'output/Retired/index.php')));
  fs.rmdirSync(path.join(root, 'output/Main/index.php'));
  build(root);
  assert.equal(fs.existsSync(path.join(root, 'output/Retired/index.php')), false);
  matchesFreshUncached(root, ['--bundle']);
});

test('CLI lifecycle: obsolete user edits, untracked PHP and replaced symlinks are preserved', t => {
  const root = fixture(t);
  for (const name of ['Edited', 'Linked', 'Moved', 'Missing']) writeMain(root, name, name + '\n');
  build(root);
  const edited = path.join(root, 'output/Edited/index.php');
  const original = fs.readFileSync(edited);
  const modified = Buffer.from(original);
  modified[1] = 0xff;
  fs.writeFileSync(edited, modified);
  fs.utimesSync(edited, old, old);
  write(root, 'output/Edited/helper.php', '<?php // user file\n');
  fs.copyFileSync(path.join(root, 'output/Linked/index.php'), path.join(root, 'linked.php'));
  fs.unlinkSync(path.join(root, 'output/Linked/index.php'));
  fs.symlinkSync(path.join(root, 'linked.php'), path.join(root, 'output/Linked/index.php'));
  fs.unlinkSync(path.join(root, 'output/Missing/index.php'));
  for (const name of ['Edited', 'Linked', 'Moved', 'Missing']) fs.unlinkSync(path.join(root, `output/${name}/corefn.json`));
  fs.renameSync(path.join(root, 'output/Moved'), path.join(root, 'moved'));
  fs.symlinkSync(path.join(root, 'moved'), path.join(root, 'output/Moved'));
  const moved = fs.readFileSync(path.join(root, 'moved/index.php'));
  const result = build(root);
  assert.deepEqual(result.counts, [0, 3, 3]);
  assert.deepEqual(fs.readFileSync(edited), modified);
  assert.equal(fs.statSync(edited).mtimeMs, old.getTime());
  assert.equal(fs.readFileSync(path.join(root, 'output/Edited/helper.php'), 'utf8'), '<?php // user file\n');
  assert.ok(fs.lstatSync(path.join(root, 'output/Linked/index.php')).isSymbolicLink());
  assert.deepEqual(fs.readFileSync(path.join(root, 'moved/index.php')), moved);
  assert.equal(fs.existsSync(path.join(root, 'output/Edited/main.mod.php')), false);
  const owned = JSON.parse(fs.readFileSync(path.join(root, 'output/.phpurs-outputs.json'))).files;
  assert.equal(owned.some(entry => /^(Edited|Linked|Moved|Missing)\//.test(entry.path)), false);
  // Once a modified obsolete file is relinquished, restoring its old bytes
  // does not allow a later build to delete it.
  fs.writeFileSync(edited, original);
  assert.deepEqual(build(root).counts, [3, 0, 0]);
  assert.deepEqual(fs.readFileSync(edited), original);
  execute(root);
});

test('CLI lifecycle: missing or damaged ownership metadata cannot authorize deletion', t => {
  const root = fixture(t);
  const manifest = path.join(root, 'output/.phpurs-outputs.json');
  for (const damage of ['missing', 'json', 'version', 'path']) {
    writeMain(root, 'Retired', 'retired\n');
    build(root);
    fs.unlinkSync(path.join(root, 'output/Retired/corefn.json'));
    const before = fs.readFileSync(path.join(root, 'output/Retired/index.php'));
    const previous = JSON.parse(fs.readFileSync(manifest));
    if (damage === 'missing') fs.unlinkSync(manifest);
    else if (damage === 'json') fs.writeFileSync(manifest, '{');
    else if (damage === 'version') fs.writeFileSync(manifest, JSON.stringify({ ...previous, version: 99 }));
    else fs.writeFileSync(manifest, JSON.stringify({ ...previous, files: [...previous.files, { ...previous.files[0], path: '../index.php' }] }));
    build(root);
    assert.deepEqual(fs.readFileSync(path.join(root, 'output/Retired/index.php')), before, damage);
    assert.equal(JSON.parse(fs.readFileSync(manifest)).files.some(entry => entry.path.startsWith('Retired/')), false);
  }
});

test('CLI lifecycle: cleanup and atomic ownership publication errors fail and remain retryable', t => {
  const root = fixture(t);
  writeMain(root, 'Retired', 'retired\n');
  build(root);
  const manifest = path.join(root, 'output/.phpurs-outputs.json');
  const before = fs.readFileSync(manifest);
  fs.unlinkSync(path.join(root, 'output/Retired/corefn.json'));
  for (const failure of [
    { operation: 'readFileSync', file: manifest, code: 'EIO' },
    { operation: 'unlinkSync', file: path.join(root, 'output/Retired/index.php'), code: 'EACCES' },
    { operation: 'renameSync', file: manifest, code: 'ENOSPC' },
  ]) {
    const result = build(root, ['--bundle'], { status: 1, failure });
    assert.match(result.stderr, new RegExp('fixture I/O failure: ' + failure.code));
    assert.match(result.stderr, /finalize: .*\(failed\)/);
    assert.deepEqual(fs.readFileSync(manifest), before);
    assert.deepEqual(fs.readdirSync(path.dirname(manifest)).filter(file => file.endsWith('.tmp')), []);
  }
  assert.deepEqual(build(root).counts, [3, 0, 0]);
  assert.equal(fs.existsSync(path.join(root, 'output/Retired/index.php')), false);
  matchesFreshUncached(root, ['--bundle']);
  fs.utimesSync(manifest, old, old);
  build(root);
  assert.equal(fs.statSync(manifest).mtimeMs, old.getTime(), 'unchanged manifest is not rewritten');
});
