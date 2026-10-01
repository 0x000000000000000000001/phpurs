// Exercise the real build driver with a small typed input tree. In particular,
// dependency order differs from module-name order, which governs Composer merges.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';

const mainURL = new URL('../../output/Main/index.js', import.meta.url).href;
const runMain = `
  import fs from 'node:fs';
  import { syncBuiltinESMExports } from 'node:module';
  import { main } from ${JSON.stringify(mainURL)};
  const probes = [];
  const scans = [];
  const writes = [];
  const failure = JSON.parse(process.env.PHPURS_TEST_IO_ERROR || 'null');
  const existsSync = fs.existsSync;
  const readdirSync = fs.readdirSync;
  fs.existsSync = file => {
    if (String(file).endsWith('.php')) probes.push(String(file));
    return existsSync(file);
  };
  fs.readdirSync = (directory, ...args) => {
    scans.push(String(directory));
    return readdirSync(directory, ...args);
  };
  for (const operation of ['readFile', 'writeFile', 'writeFileSync']) {
    const original = fs[operation];
    fs[operation] = (file, ...args) => {
      if (operation.startsWith('writeFile') && String(file).endsWith('.php')) writes.push(String(file));
      if (failure?.operation === operation && String(file) === 'output/Empty/index.php') {
        const error = Object.assign(new Error('fixture I/O failure: ' + failure.code), { code: failure.code });
        queueMicrotask(() => args.at(-1)(error));
        return;
      }
      return original(file, ...args);
    };
  }
  syncBuiltinESMExports();
  process.on('exit', () => fs.writeFileSync('ffi-probes.json', JSON.stringify({ probes, scans, writes })));
  main();
`;

function write(root, relative, contents) {
  const target = path.join(root, relative);
  fs.mkdirSync(path.dirname(target), { recursive: true });
  fs.writeFileSync(target, contents);
}

function writeJSON(root, relative, value) {
  write(root, relative, JSON.stringify(value));
}

function inputModule(name, modulePath, foreign, imports = []) {
  return {
    builtWith: '0.15.16',
    moduleName: name.split('.'), modulePath,
    sourceSpan: { start: [1, 1], end: [1, 1] },
    imports: imports.map(name => ({ moduleName: name.split('.'), annotation: {} })),
    exports: foreign, foreign, foreignAnnotations: {},
    decls: [], reExports: {}, comments: [],
    typeTable: [], dataDecls: [], classDecls: [],
  };
}

function snapshot(directory) {
  const files = {};
  function visit(relative) {
    for (const name of fs.readdirSync(path.join(directory, relative))) {
      const file = path.join(relative, name);
      if (fs.statSync(path.join(directory, file)).isDirectory()) visit(file);
      else files[file] = fs.readFileSync(path.join(directory, file), 'utf8');
    }
  }
  visit('');
  return files;
}

function invokeBuild(root, args, failure = null) {
  return spawnSync(process.execPath, [
    '--input-type=module', '--eval', runMain, '--', ...args,
  ], {
    cwd: root, encoding: 'utf8', timeout: 30_000,
    env: { ...process.env, PHPURS_TEST_IO_ERROR: JSON.stringify(failure) },
  });
}

function build(root, args) {
  const result = invokeBuild(root, args);
  assert.equal(result.status, 0, result.stdout + result.stderr);
  assert.doesNotMatch(result.stderr, /Failed to decode/);
}

function writtenPhp(root, output) {
  const { writes } = JSON.parse(fs.readFileSync(path.join(root, 'ffi-probes.json'), 'utf8'));
  return writes.map(file => path.relative(path.join(root, output), path.resolve(root, file))).sort();
}

function rememberPhp(directory) {
  const previous = {};
  // A fixed old timestamp avoids sleeps and filesystem clock-resolution races.
  const time = new Date('2001-01-01T00:00:00Z');
  for (const [file, contents] of Object.entries(snapshot(directory))) {
    if (!file.endsWith('.php')) continue;
    const target = path.join(directory, file);
    fs.utimesSync(target, time, time);
    previous[file] = { contents: Buffer.from(contents), mtime: fs.statSync(target, { bigint: true }).mtimeNs };
  }
  return previous;
}

function checkRebuild(root, output, args, previous, changed = []) {
  build(root, args);
  assert.deepEqual(writtenPhp(root, output), [...changed].sort(), 'only changed or missing PHP must be written');
  for (const [file, before] of Object.entries(previous)) {
    const target = path.join(root, output, file);
    const mtime = fs.statSync(target, { bigint: true }).mtimeNs;
    if (changed.includes(file)) {
      assert.notEqual(mtime, before.mtime, `updated mtime: ${file}`);
    } else {
      assert.deepEqual(fs.readFileSync(target), before.contents, `unchanged bytes: ${file}`);
      assert.equal(mtime, before.mtime, `unchanged mtime: ${file}`);
    }
  }
}

const cases = [
  { mode: 'default', emission: 'modules' },
  ...['default', 'relative', 'absolute'].flatMap(mode =>
    ['both', 'bundle-only'].map(emission => ({ mode, emission }))),
];

for (const { mode, emission } of cases) {
  test(`file emission: ${emission}, ${mode} output and Composer dependencies`, () => {
    const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-emission-')));
    try {
      const custom = mode !== 'default';
      const bundleOnly = emission === 'bundle-only';
      const emitBundle = emission !== 'modules';
      const output = custom ? 'generated/php' : 'output';
      writeJSON(root, `${output}/A.Main/corefn.json`,
        inputModule('A.Main', 'packages/a/src/A/Main.purs', ['main'], ['Z.Library', 'Solo', 'Pure.Local', 'Pure.Fallback']));
      writeJSON(root, `${output}/B.Main/corefn.json`,
        inputModule('B.Main', 'packages/a/src/B/Main.purs', ['main']));
      writeJSON(root, `${output}/Z.Library/corefn.json`,
        inputModule('Z.Library', 'packages/z/src/Z/Library.purs', ['value']));
      writeJSON(root, `${output}/Solo/corefn.json`, inputModule('Solo', 'src/Solo.purs', ['value']));
      for (const name of ['Local', 'Fallback']) {
        writeJSON(root, `${output}/Pure.${name}/corefn.json`,
          inputModule(`Pure.${name}`, `packages/pure/src/Pure/${name}.purs`, []));
      }
      write(root, custom ? 'ffi/src/A/Main.php' : 'packages/a/src/A/Main.php',
        '<?php\n$exports["main"] = function() { echo ($GLOBALS["Z_Library_value"] + $GLOBALS["Solo_value"]) . "\\n"; };\n');
      write(root, custom ? 'ffi/src/B/Main.php' : 'packages/a/src/B/Main.php',
        '<?php\n// Unicode: café \uFFFD\n$exports["main"] = function() { echo "second\\n"; };\n');
      write(root, 'packages/z/src/Z/Library.php', '<?php\n$exports["value"] = 41;\n');
      // For an unqualified name, src/Solo.php is both the adjacent candidate
      // and two fallback spellings. Each path should be probed only once.
      write(root, 'Solo.php', '<?php\n$exports["value"] = 1;\n');
      // A stale adjacent file and a fallback candidate must neither be probed
      // nor embedded when the module declares no foreign bindings.
      const unusedFFI = '<?php\nthrow new \\RuntimeException("undeclared FFI");\n';
      write(root, 'packages/pure/src/Pure/Local.php', unusedFFI);
      write(root, 'src/Pure/Fallback.php', unusedFFI);

      writeJSON(root, '.spago/example/v1.0.0/composer.json', {
        require: { 'fixture/spago': '^1', 'fixture/shared': '^0' },
      });
      writeJSON(root, 'packages/a/composer.json', {
        require: { 'fixture/a': '^1', 'fixture/shared': '^1' },
        'require-dev': { 'fixture/dev': '^1' },
      });
      writeJSON(root, 'packages/z/composer.json', {
        require: { 'fixture/z': '^1', 'fixture/shared': '^2' },
        'require-dev': { 'fixture/dev': '^2' },
      });
      writeJSON(root, 'packages/pure/composer.json', { require: { 'fixture/pure': '^1' } });

      let oldOutput;
      if (custom) {
        writeJSON(root, 'ffi/composer.json', { require: { 'fixture/ffi': '^1' } });
        // The default tree and obsolete cache must not supply dependencies or
        // receive artifacts when another output directory was selected.
        writeJSON(root, 'ignored/composer.json', { require: { 'fixture/ignored': '^1' } });
        writeJSON(root, 'output/Old/corefn.json',
          inputModule('Old', 'ignored/src/Old.purs', []));
        writeJSON(root, `${output}/.phpurs-cache.json`, {
          modules: { 'Z.Library': { modulePath: 'ignored/src/Old.purs' } },
        });
        write(root, 'output/composer.json', '{"sentinel":true}\n');
        oldOutput = snapshot(path.join(root, 'output'));
      }

      let args = bundleOnly ? ['--bundle-only'] : emitBundle ? ['--bundle'] : [];
      if (custom) {
        args.push('--main', 'A.Main', '--ffi', mode === 'absolute' ? path.join(root, 'ffi') : 'ffi', '--output',
          mode === 'absolute' ? path.join(root, output) : output);
      }
      if (bundleOnly && mode === 'absolute') {
        // Spago can pass backend options as one string. Bundle-only also wins
        // when the existing --bundle flag is present in the same invocation.
        args = [['--bundle', ...args].join(' ')];
      }
      build(root, args);
      const { probes, scans } = JSON.parse(fs.readFileSync(path.join(root, 'ffi-probes.json'), 'utf8'));
      assert.deepEqual(probes.filter(file => file.includes('Pure')), [],
        'modules without foreign bindings must skip FFI discovery');
      const normalizedProbes = probes.map(file => path.resolve(root, file));
      assert.equal(normalizedProbes.length, new Set(normalizedProbes).size,
        'equivalent FFI candidates must be probed once');
      assert.equal(scans.filter(dir => path.resolve(root, dir) === path.join(root, '.spago')).length, 1,
        'FFI and Composer must share one package-directory discovery');

      const emitted = snapshot(path.join(root, output));
      const modules = ['A.Main', 'B.Main', 'Pure.Fallback', 'Pure.Local', 'Solo', 'Z.Library'];
      const mains = custom ? ['A.Main'] : ['A.Main', 'B.Main'];
      const entries = [
        ...(!bundleOnly ? ['main.mod.php'] : []),
        ...(emitBundle ? ['main.bundle.php'] : []),
      ];
      const expectedFiles = [
        ...(!bundleOnly ? modules.map(name => `${name}/index.php`) : []),
        ...mains.flatMap(name => entries.map(entry => `${name}/${entry}`)),
        ...(emitBundle && !custom ? ['bundle.php'] : []),
      ];
      assert.deepEqual(Object.keys(emitted).filter(file => file.endsWith('.php')).sort(), expectedFiles.sort());
      assert.deepEqual(writtenPhp(root, output), expectedFiles, 'a fresh build must write every selected PHP file');
      for (const main of mains) {
        for (const entry of entries) {
          const run = spawnSync('php', ['-d', 'opcache.enable_cli=0', path.join(output, main, entry)],
            { cwd: root, encoding: 'utf8', timeout: 10_000 });
          assert.equal(run.status, 0, run.stdout + run.stderr);
          assert.equal(run.stderr, '');
          assert.equal(run.stdout, main === 'A.Main' ? '42\n' : 'second\n');
        }
      }
      if (emitBundle && !custom) {
        const run = spawnSync('php', ['-d', 'opcache.enable_cli=0', path.join(output, 'bundle.php')],
          { cwd: root, encoding: 'utf8', timeout: 10_000 });
        assert.equal(run.status, 0, run.stdout + run.stderr);
        assert.equal(run.stderr, '');
        assert.equal(run.stdout, '', 'the global bundle must not call either main');
      }
      assert.deepEqual(JSON.parse(emitted['composer.json']), {
        name: 'phpurs/lib-deps', description: 'Auto-generated by phpurs',
        require: {
          'fixture/spago': '^1', 'fixture/shared': '^2',
          'fixture/a': '^1', 'fixture/z': '^1', 'fixture/pure': '^1',
          ...(custom ? { 'fixture/ffi': '^1' } : {}),
        },
        'require-dev': { 'fixture/dev': '^2' },
      });
      if (custom) assert.deepEqual(snapshot(path.join(root, 'output')), oldOutput);
      checkRebuild(root, output, args, rememberPhp(path.join(root, output)));
      if (emission === 'both' && !custom) {
        let previous = rememberPhp(path.join(root, output));
        const ffi = path.join(root, 'packages/z/src/Z/Library.php');
        const ffiStat = fs.statSync(ffi);
        write(root, 'packages/z/src/Z/Library.php', '<?php\n$exports["value"] = 42;\n');
        fs.utimesSync(ffi, ffiStat.atime, ffiStat.mtime);
        checkRebuild(root, output, args, previous, [
          'Z.Library/index.php', 'A.Main/main.bundle.php', 'B.Main/main.bundle.php', 'bundle.php',
        ]);
        for (const entry of entries) {
          const run = spawnSync('php', ['-d', 'opcache.enable_cli=0', path.join(output, 'A.Main', entry)],
            { cwd: root, encoding: 'utf8', timeout: 10_000 });
          assert.equal(run.status, 0, run.stdout + run.stderr);
          assert.equal(run.stderr, '');
          assert.equal(run.stdout, '43\n');
        }

        previous = rememberPhp(path.join(root, output));
        args = [...args, '--autoload-path', 'vendor/other-autoload.php'];
        checkRebuild(root, output, args, previous,
          mains.flatMap(main => entries.map(entry => `${main}/${entry}`)));

        previous = rememberPhp(path.join(root, output));
        const unicodeFile = 'B.Main/index.php';
        const original = previous[unicodeFile].contents;
        const replacement = original.indexOf(Buffer.from('\uFFFD'));
        assert.ok(replacement >= 0);
        const damaged = Buffer.concat([original.subarray(0, replacement), Buffer.from([0xff]), original.subarray(replacement + 3)]);
        assert.equal(damaged.toString('utf8'), original.toString('utf8'), 'decoding would conceal this byte change');
        write(root, `${output}/${unicodeFile}`, damaged);
        // Same-size damage must also be repaired; neither size nor mtime is a key.
        const entry = 'A.Main/main.mod.php';
        write(root, `${output}/${entry}`, previous[entry].contents.toString('utf8').replace('<?php', '<?Php'));
        fs.unlinkSync(path.join(root, output, 'A.Main/main.bundle.php'));
        fs.unlinkSync(path.join(root, output, 'bundle.php'));
        checkRebuild(root, output, args, previous, [unicodeFile, entry, 'A.Main/main.bundle.php', 'bundle.php']);
        for (const [file, before] of Object.entries(previous)) {
          assert.deepEqual(fs.readFileSync(path.join(root, output, file)), before.contents, `restored bytes: ${file}`);
        }
        checkRebuild(root, output, args, rememberPhp(path.join(root, output)));
      }
      if (bundleOnly && !custom) {
        // Emission mode must neither overwrite nor remove pre-existing modules.
        const previous = {
          'A.Main/index.php': '<?php // existing module\n',
          'A.Main/main.mod.php': '<?php // existing entrypoint\n',
        };
        for (const [file, contents] of Object.entries(previous)) write(root, `${output}/${file}`, contents);
        build(root, args);
        for (const [file, contents] of Object.entries(previous)) {
          assert.equal(fs.readFileSync(path.join(root, output, file), 'utf8'), contents);
        }
        assert.equal(fs.readFileSync(path.join(root, output, 'A.Main/main.bundle.php'), 'utf8'), emitted['A.Main/main.bundle.php']);
      }
    } finally {
      fs.rmSync(root, { recursive: true, force: true });
    }
  });
}

test('file emission: read and write errors fail the build without masking the cause', () => {
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-emission-errors-')));
  try {
    writeJSON(root, 'output/Empty/corefn.json', inputModule('Empty', 'src/Empty.purs', []));
    const sentinel = '<?php // previous output\n';
    write(root, 'output/Empty/index.php', sentinel);
    for (const failure of [
      { operation: 'readFile', code: 'EACCES' },
      { operation: 'readFile', code: 'EIO' },
      { operation: 'writeFile', code: 'ENOSPC' },
    ]) {
      const result = invokeBuild(root, [], failure);
      assert.equal(result.status, 1, result.stdout + result.stderr);
      assert.match(result.stderr, new RegExp('fixture I/O failure: ' + failure.code));
      assert.match(result.stderr, /optimize \+ emit: .*\(failed\)/);
      assert.match(result.stderr, /backend total: .*\(failed\)/);
      assert.deepEqual(writtenPhp(root, 'output'), failure.operation === 'writeFile' ? ['Empty/index.php'] : []);
      assert.equal(fs.readFileSync(path.join(root, 'output/Empty/index.php'), 'utf8'), sentinel);
    }
  } finally {
    fs.rmSync(root, { recursive: true, force: true });
  }
});
