// A single-file executable whose in-memory compiler source is both hashed and
// evaluated. Reading the executable back from disk after loading would race a
// rebuild/replacement and could attribute old code to a new compiler identity.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { createHash } from 'node:crypto';
import { isBuiltin } from 'node:module';
import { build } from 'esbuild';

const result = await build({
  entryPoints: ['output/Main/index.js'], bundle: true, platform: 'node', format: 'cjs',
  write: false, metafile: true, footer: { js: '//# sourceURL=phpurs-backend.cjs' },
});
for (const output of Object.values(result.metafile.outputs)) {
  for (const dependency of output.imports) {
    assert.ok(dependency.external && isBuiltin(dependency.path), `Unfingerprinted host dependency: ${dependency.path}`);
  }
}
const source = result.outputFiles[0].text;
const pboInputs = Object.keys(result.metafile.inputs).filter(file => file.startsWith('output/PureScript.Backend.Optimizer.')).sort();
const versions = {
  phpursVersion: JSON.parse(fs.readFileSync('package.json', 'utf8')).version,
  pboVersion: 'compiled-' + createHash('sha256').update(JSON.stringify(pboInputs.map(file => [file, fs.readFileSync(file, 'utf8')]))).digest('hex'),
};
// This function is emitted verbatim. Its source participates in the identity as
// well as the complete bundled code (including PBO and all host-library JS).
function start(source, versions) {
  const backend = createHash('sha256').update(JSON.stringify(['phpurs-executable', 1, source, versions, start.toString()])).digest('hex');
  const compiled = { exports: {} };
  const require = createRequire(import.meta.url);
  new Function('require', 'module', 'exports', source)(require, compiled, compiled.exports);
  compiled.exports.mainWithToolchain({ ...versions, backend,
    nodeVersion: process.versions.node, v8Version: process.versions.v8, platform: process.platform, arch: process.arch })();
}
const executable = '#!/usr/bin/env node\n'
  + "import { createHash } from 'node:crypto';\nimport { createRequire } from 'node:module';\n"
  + `const source = ${JSON.stringify(source)};\nconst versions = ${JSON.stringify(versions)};\n`
  + start.toString() + '\nstart(source, versions);\n';
fs.mkdirSync('bin', { recursive: true });
fs.writeFileSync('bin/phpurs.js.tmp', executable, { mode: 0o755 });
fs.renameSync('bin/phpurs.js.tmp', 'bin/phpurs.js');
console.log(`Bundled bin/phpurs.js (${Buffer.byteLength(executable)} bytes)`);
