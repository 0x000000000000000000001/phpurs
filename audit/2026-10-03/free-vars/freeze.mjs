// Diagnostic entry counters affect scratch bundles only, not timed production.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { build } from 'esbuild';

const [destination, mode] = process.argv.slice(2);
const entry = fileURLToPath(new URL('./library.mjs', import.meta.url));
const output = fileURLToPath(new URL('../../../output/', import.meta.url));
const plugins = mode === '--visits' ? [{ name: 'free-vars-visits', setup(builder) {
  builder.onLoad({ filter: /[/\\]output[/\\]PureScript\.Backend\.Optimizer\.FreeVars[/\\]index\.js$/ }, args => {
    let contents = fs.readFileSync(args.path, 'utf8');
    const header = contents.includes('var freeVarsWith =')
      ? 'var freeVarsWith = function (recur) {\n    return function (v) {'
      : 'var freeVars = function (v) {';
    assert.equal(contents.split(header).length, 2);
    contents = contents.replace(header, header + '\n    globalThis.phpursAuditFreeVars?.(v);');
    return { contents, loader: 'js', resolveDir: path.dirname(args.path) };
  });
} }] : [];
// Re-freezing the predecessor must expose the analysis actually selected by
// its CodeGen, even if an incremental output tree contains a stale Memo module.
if (!fs.readFileSync(path.join(output, 'Phpurs.CodeGen/index.js'), 'utf8').includes('Phpurs_FreeVars.freeVars')) {
  plugins.push({ name: 'baseline-free-vars', setup(builder) {
    builder.onResolve({ filter: /\/Phpurs\.FreeVars\/index\.js$/ }, () => ({
      path: path.join(output, 'PureScript.Backend.Optimizer.FreeVars/index.js'),
    }));
  } });
}
await build({ entryPoints: [entry], outfile: destination, bundle: true,
  platform: 'node', format: 'esm', sourcemap: true, plugins });
