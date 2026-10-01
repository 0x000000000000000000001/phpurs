// Exercise the real build driver with a small typed input tree. In particular,
// dependency order differs from module-name order, which governs Composer merges.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';

const mainURL = new URL('../../output/Main/index.js', import.meta.url).href;

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

for (const mode of ['default', 'relative', 'absolute']) {
  test(`file emission: ${mode} output, bundles and Composer dependencies`, () => {
    const root = fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-emission-'));
    try {
      const custom = mode !== 'default';
      const output = custom ? 'generated/php' : 'output';
      writeJSON(root, `${output}/A.Main/corefn.json`,
        inputModule('A.Main', 'packages/a/src/A/Main.purs', ['main'], ['Z.Library']));
      writeJSON(root, `${output}/Z.Library/corefn.json`,
        inputModule('Z.Library', 'packages/z/src/Z/Library.purs', ['value']));
      write(root, custom ? 'ffi/src/A/Main.php' : 'packages/a/src/A/Main.php',
        '<?php\n$exports["main"] = function() { echo ($GLOBALS["Z_Library_value"] + 1) . "\\n"; };\n');
      write(root, 'packages/z/src/Z/Library.php', '<?php\n$exports["value"] = 41;\n');

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

      const args = ['--bundle'];
      if (custom) {
        args.push('--main', 'A.Main', '--ffi', 'ffi', '--output',
          mode === 'absolute' ? path.join(root, output) : output);
      }
      const build = spawnSync(process.execPath, [
        '--input-type=module', '--eval', `import { main } from ${JSON.stringify(mainURL)}; main();`,
        '--', ...args,
      ], { cwd: root, encoding: 'utf8', timeout: 30_000 });
      assert.equal(build.status, 0, build.stdout + build.stderr);
      assert.doesNotMatch(build.stderr, /Failed to decode/);

      const emitted = snapshot(path.join(root, output));
      assert.deepEqual(Object.keys(emitted).filter(file => file.endsWith('.php')).sort(), [
        'A.Main/index.php', 'A.Main/main.bundle.php', 'A.Main/main.mod.php',
        'Z.Library/index.php', ...(!custom ? ['bundle.php'] : []),
      ].sort());
      for (const entry of ['main.mod.php', 'main.bundle.php']) {
        const run = spawnSync('php', ['-d', 'opcache.enable_cli=0', path.join(output, 'A.Main', entry)],
          { cwd: root, encoding: 'utf8', timeout: 10_000 });
        assert.equal(run.status, 0, run.stdout + run.stderr);
        assert.equal(run.stderr, '');
        assert.equal(run.stdout, '42\n');
      }
      assert.deepEqual(JSON.parse(emitted['composer.json']), {
        name: 'phpurs/lib-deps', description: 'Auto-generated by phpurs',
        require: {
          'fixture/spago': '^1', 'fixture/shared': '^2',
          'fixture/a': '^1', 'fixture/z': '^1',
          ...(custom ? { 'fixture/ffi': '^1' } : {}),
        },
        'require-dev': { 'fixture/dev': '^2' },
      });
      if (custom) assert.deepEqual(snapshot(path.join(root, 'output')), oldOutput);
    } finally {
      fs.rmSync(root, { recursive: true, force: true });
    }
  });
}
