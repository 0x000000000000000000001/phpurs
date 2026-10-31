// The module-pass table and its per-module statistics stay in evaluation order.
import assert from 'node:assert/strict';
import test from 'node:test';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { translate, translateWithStats } from '../../../output/Phpurs.CodeGen/index.js';
import { aggregate, passes, run } from '../../../output/Phpurs.CodeGen.Pipeline/index.js';

const module = {
  name: 'Demo',
  bindings: [],
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
};
const table = passes(emptySet)(emptySet);

test('pipeline: six typed-AST passes in dependency order', () => {
  assert.deepEqual(
    table.map((pass) => pass.name),
    [
      'PartialBindings',
      'ThunkFusion',
      'StateFusion',
      'CallbackSpecialization',
      'ArrayCallbacks',
      'EnumRegions',
    ],
  );
  for (const pass of table) {
    assert.equal(typeof pass.run, 'function');
    assert.ok(pass.precondition.length > 0);
    assert.ok(pass.budget.length > 0);
    assert.ok(pass.fallback.length > 0);
  }
});

test('pipeline: stats count proved and unchanged passes', () => {
  assert.deepEqual(aggregate([]), {
    proved: 0,
    refusedBudget: 0,
    refusedProof: 0,
    unchanged: 0,
  });
  const result = run(table)(module);
  assert.equal(result.results.length, 6);
  const stats = aggregate(result.results);
  assert.equal(stats.proved + stats.unchanged, 6);
  assert.equal(stats.refusedBudget, 0);
  assert.equal(stats.refusedProof, 0);
});

test('pipeline: translateWithStats preserves the printed file', () => {
  const plain = translate([])(module);
  const { file, passStats } = translateWithStats(emptySet)(emptySet)([])(module);
  assert.deepEqual(file, plain);
  assert.equal(passStats.proved + passStats.unchanged, 6);
});
