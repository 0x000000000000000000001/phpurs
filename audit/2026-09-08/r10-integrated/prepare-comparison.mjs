// Regenerate a control with only the new pass disabled; active files stay intact.
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { build } from 'esbuild';
import { spawnSync } from 'node:child_process';
const audit = path.dirname(fileURLToPath(import.meta.url));
const backend = path.resolve(audit, '../../..');
const worktree = path.resolve(backend, '../../altbak.pub-phpurs');
const source = path.join(worktree, 'run/bak/php/output');
const control = path.join(audit, 'raw/baseline');
const integrated = path.join(audit, 'raw/integrated');
for (const project of [control, integrated]) {
  await fs.mkdir(project, { recursive: true });
  await fs.cp(source, path.join(project, 'output'), { recursive: true, dereference: true });
  for (const name of ['src', '.spago', 'bak', 'run', 'backend-directives.txt']) {
    try {
      await fs.stat(path.join(worktree, name));
      await fs.symlink(path.join(worktree, name), path.join(project, name));
    } catch (e) { if (!['ENOENT', 'EEXIST'].includes(e.code)) throw e; }
  }
  try { await fs.symlink(path.resolve(source, '../vendor'), path.join(project, 'vendor')); }
  catch (e) { if (e.code !== 'EEXIST') throw e; }
}
// CoreFn source paths are relative to the real worktree, including sibling FFI.
// Relocate this metadata so both compilations resolve the same source files.
for (const project of [control, integrated]) for (const module of await fs.readdir(path.join(project, 'output'))) {
  const filename = path.join(project, 'output', module, 'corefn.json');
  try {
    const data = JSON.parse(await fs.readFile(filename, 'utf8'));
    data.modulePath = path.resolve(worktree, data.modulePath);
    await fs.writeFile(filename, JSON.stringify(data));
  } catch (e) { if (!['ENOENT', 'ENOTDIR'].includes(e.code)) throw e; }
}
const bundle = path.join(audit, 'raw/backend-baseline.mjs');
await build({
  stdin: { contents: `import {main} from './output/Main/index.js'; main();`, resolveDir: backend },
  outfile: bundle, bundle: true, platform: 'node', format: 'esm',
  banner: { js: "import {createRequire} from 'node:module'; const require = createRequire(import.meta.url);" },
  plugins: [{ name: 'disable-enum-regions', setup(b) {
    b.onLoad({ filter: /Phpurs\.EnumRegions\/index\.js$/ }, () => ({ contents: "import {empty} from '../Data.Set/index.js'; export const optimize = module_ => ({module_, privateNames: empty, privateConstructors: empty});", loader: 'js', resolveDir: path.join(backend, 'output/Phpurs.EnumRegions') }));
  }}],
});
const result = spawnSync('node', ['--expose-gc', bundle, '--output', 'output', '--main', 'App'], { cwd: control, encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 });
await fs.writeFile(path.join(audit, 'baseline-codegen.log'), result.stdout + result.stderr);
if (result.status !== 0) throw new Error(result.stderr || result.stdout);
const active = spawnSync(path.join(backend, 'bin/phpurs'), ['--output', 'output', '--main', 'App'], { cwd: integrated, encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 });
await fs.writeFile(path.join(audit, 'integrated-codegen.log'), active.stdout + active.stderr);
if (active.status !== 0) throw new Error(active.stderr || active.stdout);
console.log('Control and integrated outputs regenerated from identical TAST input.');
