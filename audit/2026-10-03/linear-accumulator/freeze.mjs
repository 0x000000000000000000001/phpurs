// Produce immutable probe bundles. --copies instruments only scratch JavaScript,
// never compiler sources or normal timing runs.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { build } from 'esbuild';

const [destination, mode] = process.argv.slice(2);
const root = fileURLToPath(new URL('../../../', import.meta.url));
const plugins = mode === '--copies' ? [{
  name: 'count-accumulator-copies',
  setup(builder) {
    builder.onLoad({ filter: /[/\\]output[/\\]Phpurs\.CodeGen[/\\]index\.js$/ }, args => {
      let contents = fs.readFileSync(args.path, 'utf8');
      for (const [before, after] of [
        ['Data_Array.snoc(acc.exprs)(result.expr)', '(globalThis.phpursAuditCopy("values.exprs", acc.exprs.length), Data_Array.snoc(acc.exprs)(result.expr))'],
        ['stmts: append1(acc.stmts)(result.stmts)', 'stmts: (globalThis.phpursAuditCopy("values.stmts", result.stmts.length === 0 ? 0 : acc.stmts.length), append1(acc.stmts)(result.stmts))'],
        ['Data_Array.snoc(a.exprs)({', '(globalThis.phpursAuditCopy("record.exprs", a.exprs.length), Data_Array.snoc(a.exprs))({'],
      ]) {
        assert.equal(contents.split(before).length, 2, before);
        contents = contents.replace(before, after);
      }
      return { contents, loader: 'js', resolveDir: path.dirname(args.path) };
    });
  },
}] : [];
await build({ entryPoints: [path.join(root, 'audit/2026-10-03/linear-accumulator/library.mjs')],
  outfile: destination, bundle: true, platform: 'node', format: 'esm', sourcemap: true, plugins });
