// Instrument scratch JavaScript only. Production timing uses the separately
// frozen, packaged CLI; this bundle counts actual optimizer passes and chunks.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { build } from 'esbuild';

const root = fileURLToPath(new URL('../../../', import.meta.url));
await build({
  stdin: { contents: 'import { main } from "./output/Main/index.js"; main();', resolveDir: root, loader: 'js' },
  outfile: process.argv[2], bundle: true, platform: 'node', format: 'esm', sourcemap: true,
  plugins: [{ name: 'rewrite-iterations', setup(builder) {
    builder.onLoad({ filter: /[/\\]output[/\\]PureScript\.Backend\.Optimizer\.Semantics[/\\]index\.js$/ }, args => {
      let contents = fs.readFileSync(args.path, 'utf8');
      for (const [before, after] of [
        ['return function (originalExpr) {', 'return function (originalExpr) {\nconst audit = globalThis.phpursAuditRewriteStart(v, initN, analysisOf(originalExpr).size);'],
        ['var v1 = goStep(n)(expr1);', 'var v1 = goStep(n)(expr1);\naudit.step(n, analysisOf(expr1).size, v1.value0);'],
        ['var v1 = analysisOf(expr);\n                                    var $2352 = v1.size <= 2000;',
          'var v1 = analysisOf(expr);\naudit.chunk(v1.size);\n                                    var $2352 = v1.size <= 2000;'],
      ]) {
        assert.equal(contents.split(before).length, 2, before);
        contents = contents.replace(before, after);
      }
      return { contents, loader: 'js', resolveDir: path.dirname(args.path) };
    });
  } }],
});
