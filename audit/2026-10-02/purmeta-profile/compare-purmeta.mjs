// V8's binary format is not canonical for equal JS values. Compare the tagged
// payload graphs, including sharing and numeric values, when their bytes differ.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import v8 from 'node:v8';

function equalGraphs(a, b) {
  const left = new Map(), right = new Map();
  const pending = [[a, b]];
  while (pending.length) {
    const [x, y] = pending.pop();
    if (x === null || typeof x !== 'object') {
      assert.ok(Object.is(x, y), `Different primitive: ${x} / ${y}`);
      continue;
    }
    assert.ok(y !== null && typeof y === 'object');
    if (left.has(x)) {
      assert.equal(left.get(x), y, 'Shared nodes must stay shared');
      continue;
    }
    assert.equal(right.has(y), false, 'Distinct nodes must stay distinct');
    left.set(x, y);
    right.set(y, x);
    assert.equal(Array.isArray(x), Array.isArray(y));
    if (Array.isArray(x)) assert.equal(x.length, y.length);
    const keys = Object.keys(x);
    assert.deepEqual(Object.keys(y), keys);
    for (const key of keys) pending.push([x[key], y[key]]);
  }
}

const [fresh, restored] = process.argv.slice(2);
const files = fs.readdirSync(fresh).filter(file => file.endsWith('.purmeta')).sort();
assert.deepEqual(fs.readdirSync(restored).filter(file => file.endsWith('.purmeta')).sort(), files);
const differentBytes = [];
for (const file of files) {
  const a = fs.readFileSync(path.join(fresh, file));
  const b = fs.readFileSync(path.join(restored, file));
  if (a.equals(b)) continue;
  try { equalGraphs(v8.deserialize(a), v8.deserialize(b)); }
  catch (error) { throw new Error(file + ': ' + error.message, { cause: error }); }
  differentBytes.push({ file, freshBytes: a.length, restoredBytes: b.length });
}
console.log(JSON.stringify({ files: files.length, byteIdenticalFiles: files.length - differentBytes.length,
  equivalentDecodedGraphs: differentBytes.length, differentBytes }, null, 2));
