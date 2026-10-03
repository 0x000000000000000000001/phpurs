// Separate allocation-count and uninstrumented timing runs on synthetic widths.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { performance } from 'node:perf_hooks';
import { pathToFileURL } from 'node:url';

const [library, destination, mode = 'bench'] = process.argv.slice(2);
const { CodeGen, Syntax: S, CoreFn: C, Tco, Maybe } = await import(pathToFileURL(path.resolve(library)));
const int = value => new S.Lit(new C.LitInt(value));
const binding = value => new S.Let(new Maybe.Just('x'), 0, int(value), new S.Local(new Maybe.Just('x'), 0));
const results = [];
for (const [kind, make] of [['values', int], ['statements', binding]]) {
  for (const width of mode === 'bench' ? [512, 1024, 2048, 4096, 8192] : [4096]) {
    const input = Tco.analyze([])(new S.Lit(new C.LitArray(Array.from({ length: width }, (_, i) => make(i)))));
    const translate = () => CodeGen.translateExpr(CodeGen.initialContext('Fixture'))(17)(input);
    const verify = result => {
      assert.equal(result.expr.value0.length, width);
      assert.equal(result.nextId, 17 + (kind === 'statements' ? width : 0));
      assert.equal(result.stmts.length, kind === 'statements' ? width : 0);
      if (kind === 'values') assert.deepEqual(result.expr.value0.map(value => value.value0), Array.from({ length: width }, (_, i) => i));
    };
    for (let i = 0; i < 3; i++) verify(translate());
    let copiedPrefixItems = 0;
    let counting = false;
    const slice = Array.prototype.slice;
    const concat = Array.prototype.concat;
    if (mode !== 'bench') {
      Array.prototype.slice = function (...args) { if (counting) copiedPrefixItems += this.length; return slice.apply(this, args); };
      Array.prototype.concat = function (...args) { if (counting) copiedPrefixItems += this.length; return concat.apply(this, args); };
    }
    const timesMs = [];
    try {
      for (let i = 0; i < (mode === 'bench' ? 15 : 1); i++) {
        const started = performance.now();
        counting = true;
        const output = translate();
        counting = false;
        timesMs.push(performance.now() - started);
        verify(output);
      }
    } finally { Array.prototype.slice = slice; Array.prototype.concat = concat; }
    results.push({ kind, width, timesMs, copiedPrefixItems: mode === 'bench' ? null : copiedPrefixItems });
  }
}
fs.writeFileSync(destination, JSON.stringify(results, null, 2) + '\n');
if (mode === 'check') for (const row of results) assert.ok(row.copiedPrefixItems <= 4 * row.width,
  `${row.kind}: copied ${row.copiedPrefixItems} prefix items for ${row.width} operands`);
console.log(JSON.stringify(results));
