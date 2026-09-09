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
// Keep the original modulePath metadata and the original working directory.
// Only output files are redirected, so FFI resolution and optimizer caches use
// the same project context as bin/php/run -c.
const bundle = path.join(audit, 'raw/backend-baseline.mjs');
await build({
  stdin: { contents: `import {main} from './output/Main/index.js'; main();`, resolveDir: backend },
  outfile: bundle, bundle: true, platform: 'node', format: 'esm',
  banner: { js: "import {createRequire} from 'node:module'; const require = createRequire(import.meta.url);" },
  plugins: [{ name: 'disable-partial-bindings', setup(b) {
    b.onLoad({ filter: /Phpurs\.PartialBindings\/index\.js$/ }, () => ({ contents: "import {empty} from '../Data.Set/index.js'; export const optimize = module_ => ({module_, privateNames: empty});", loader: 'js', resolveDir: path.join(backend, 'output/Phpurs.PartialBindings') }));
  }}],
});
const result = spawnSync('node', ['--expose-gc', bundle, '--output', path.join(control, 'output'), '--main', 'App'], { cwd: worktree, encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 });
await fs.writeFile(path.join(audit, 'baseline-codegen.log'), result.stdout + result.stderr);
if (result.status !== 0) throw new Error(result.stderr || result.stdout);
const integratedBundle = path.join(audit, 'raw/backend-integrated.mjs');
await build({ stdin: { contents: `import {main} from './output/Main/index.js'; main();`, resolveDir: backend }, outfile: integratedBundle, bundle: true, platform: 'node', format: 'esm', banner: { js: "import {createRequire} from 'node:module'; const require = createRequire(import.meta.url);" } });
const active = spawnSync('node', ['--expose-gc', integratedBundle, '--output', path.join(integrated, 'output'), '--main', 'App'], { cwd: worktree, encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 });
await fs.writeFile(path.join(audit, 'integrated-codegen.log'), active.stdout + active.stderr);
if (active.status !== 0) throw new Error(active.stderr || active.stdout);
console.log('Control and integrated outputs regenerated from identical TAST input.');
