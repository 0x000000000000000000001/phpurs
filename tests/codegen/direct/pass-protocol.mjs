// The pass protocol owns only scaffolding: size guards, binding traversal,
// scan budget and private names. Proofs and budgets stay in each pass.
import assert from 'node:assert/strict';
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { runState } from '../../../output/Control.Monad.State/index.js';
import { Identity } from '../../../output/Data.Identity/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet, size } from '../../../output/Data.Set/index.js';
import {
  privateName,
  scanGroups,
  smallModule,
  unchanged,
} from '../../../output/Phpurs.Pass/index.js';

const moduleOf = (bindings) => ({
  name: 'Demo',
  bindings,
  dataDecls: [],
  foreign: emptyMap,
  exports: emptySet,
  imports: emptySet,
  dataTypes: emptyMap,
  comments: [],
  reExports: emptySet,
  classDecls: [],
  implementations: emptyMap,
  directives: emptyMap,
});
const group = (count) => ({
  recursive: false,
  bindings: Array.from(
    { length: count },
    (_, index) => new Tuple('b' + index, new S.Lit(new T.LitInt(index))),
  ),
});

assert.equal(smallModule(moduleOf([])), true);
assert.equal(smallModule(moduleOf(Array.from({ length: 256 }, () => group(0)))), true);
assert.equal(smallModule(moduleOf(Array.from({ length: 257 }, () => group(0)))), false);
assert.equal(smallModule(moduleOf([group(64)])), true);
assert.equal(smallModule(moduleOf([group(65)])), false);

assert.equal(privateName('__p_')('x'), '__p_x', 'private names share one prefix convention');
const original = moduleOf([group(1)]);
const kept = unchanged(original);
assert.equal(kept.module_, original, 'an unchanged pass returns the same module');
assert.equal(size(kept.privateNames), 0);

let visits = 0;
// `State` is erased to a function returning `Identity (Tuple value state)`.
const scan = (expr) => (count) => {
  visits++;
  return new Identity(new Tuple(expr, count + 1));
};
const scanned = runState(scanGroups(scan)(original))(0);
assert.equal(scanned.value0.length, 1);
assert.equal(visits, 1, 'small bindings are scanned');
assert.equal(scanned.value1, 1, 'the state threads across bindings');

// `bounded` refuses deep or wide expressions before any recursive walk.
let deep = new S.Lit(new T.LitInt(0));
for (let depth = 0; depth < 200; depth++) deep = new S.Typed(T.Int.value, deep);
visits = 0;
runState(scanGroups(scan)(moduleOf([{ recursive: false, bindings: [new Tuple('deep', deep)] }])))(
  0,
);
assert.equal(visits, 0, 'expressions above the scan budget stay untouched');

console.log('pass-protocol: guards, private names, traversal and scan budget passed');
