// Deterministic regression of repeated nested free-variable queries, usable
// with either frozen library. Constructor and set representations stay local.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const [library, destination] = process.argv.slice(2);
const { Tco, Syntax, Maybe, Tuple, Memo, FreeVars, Set } = await import(pathToFileURL(path.resolve(library)));
const freeVars = Memo?.freeVars ?? FreeVars.freeVars;
let reads = 0;
const nodes = [];
const node = syntax => {
  const expr = new Tco.TcoExpr(null, syntax);
  Object.defineProperty(expr, 'value1', { get() { reads++; return syntax; } });
  Object.freeze(expr);
  nodes.push(expr);
  return expr;
};
let expr = node(new Syntax.Local(new Maybe.Just('value'), 0));
for (let i = 0; i < 192; i++) expr = node(new Syntax.Abs([new Tuple.Tuple(new Maybe.Just('unused'), i + 1)], expr));
for (const input of nodes.toReversed()) assert.equal(Set.size(freeVars(input)), 1);
const result = { nodes: nodes.length, reads, linearBound: 32 * nodes.length, passes: reads <= 32 * nodes.length };
fs.writeFileSync(destination, JSON.stringify(result, null, 2) + '\n');
console.log(JSON.stringify(result));
