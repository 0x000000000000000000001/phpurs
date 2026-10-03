import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as C from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { analyze, TcoExpr } from '../../output/PureScript.Backend.Optimizer.Codegen.Tco/index.js';
import { freeVars as reference } from '../../output/PureScript.Backend.Optimizer.FreeVars/index.js';
import { freeVars } from '../../output/Phpurs.FreeVars/index.js';
import { initialContext, translateExpr } from '../../output/Phpurs.CodeGen/index.js';
import { printExpr } from '../../output/Phpurs.Printer/index.js';
import { empty, insert } from '../../output/Data.Map/index.js';
import { toUnfoldable } from '../../output/Data.Set/index.js';
import { unfoldableArray } from '../../output/Data.Unfoldable/index.js';
import { ordString } from '../../output/Data.Ord/index.js';
import { Just, Nothing } from '../../output/Data.Maybe/index.js';
import { Tuple } from '../../output/Data.Tuple/index.js';

const local = (name, level) => new S.Local(name === null ? Nothing.value : new Just(name), level);
const arg = (name, level) => new Tuple(name === null ? Nothing.value : new Just(name), level);
const array = values => new S.Lit(new C.LitArray(values));
const int = n => new S.Lit(new C.LitInt(n));
const names = toUnfoldable(unfoldableArray);
const x = local('x', 0), y = local('x', 1), z = local(null, 2);
const qualified = new C.Qualified(new Just('Fixture'), 'value');

test('free vars: exact sets cover expression children and lexical binders', () => {
  const xy = ['x_0', 'x_1'];
  for (const [syntax, expected] of [
    [new S.Var(qualified), []], [x, ['x_0']], [z, ['__local_var_2']], [int(42), []],
    [array([x, y, x]), xy],
    [new S.Lit(new C.LitRecord([new C.Prop('a', x), new C.Prop('b', y)])), xy],
    [new S.App(x, [y, x]), xy], [new S.TypeApp(x, C.Any.value), ['x_0']],
    [new S.Abs([arg('x', 0)], array([x, y])), ['x_1']],
    [new S.UncurriedApp(x, [y]), xy],
    [new S.UncurriedAbs([arg('x', 0), arg(null, 2)], array([x, y, z])), ['x_1']],
    [new S.UncurriedEffectApp(x, [y]), xy],
    [new S.UncurriedEffectAbs([arg('x', 0)], array([x, y])), ['x_1']],
    [new S.Accessor(x, new S.GetProp('p')), ['x_0']],
    [new S.Update(x, [new C.Prop('p', y)]), xy],
    [new S.CtorSaturated(qualified, C.ProductType.value, 'Box', 'Box',
      [new Tuple('value0', x), new Tuple('value1', y)]), xy],
    [new S.CtorDef(C.ProductType.value, 'Box', 'Box', ['value0']), []],
    // The binder covers the body, not its own initializer.
    [new S.Let(new Just('x'), 0, x, array([x, y])), xy],
    [new S.Let(new Just('x'), 0, int(1), array([x, y])), ['x_1']],
    [new S.EffectBind(new Just('x'), 0, x, array([x, y])), xy],
    [new S.EffectBind(new Just('x'), 0, int(1), array([x, y])), ['x_1']],
    // Every recursive binder covers every initializer and the body.
    [new S.LetRec(0, [new Tuple('x', local('f', 0)), new Tuple('f', array([x, y]))],
      array([local('f', 0), local('f', 3), z])), ['__local_var_2', 'f_3', 'x_1']],
    [new S.EffectPure(x), ['x_0']], [new S.EffectDefer(y), ['x_1']],
    [new S.Branch([new S.Pair(x, y)], z), ['__local_var_2', ...xy]],
    [new S.PrimOp(new S.Op1(S.OpIntNegate.value, x)), ['x_0']],
    [new S.PrimOp(new S.Op2(new S.OpIntNum(S.OpAdd.value), x, y)), xy],
    [new S.PrimEffect(new S.EffectRefNew(x)), ['x_0']],
    [new S.PrimEffect(new S.EffectRefRead(y)), ['x_1']],
    [new S.PrimEffect(new S.EffectRefWrite(x, y)), xy],
    [S.PrimUndefined.value, []], [new S.Fail('message'), []],
    [new S.Typed(C.Any.value, new S.TypeApp(array([x, y]), C.Any.value)), xy],
  ]) {
    const expr = analyze([])(syntax);
    assert.deepEqual(names(freeVars(expr)), expected, syntax.constructor.name);
    assert.deepEqual(names(reference(expr)), expected, 'PBO: ' + syntax.constructor.name);
  }
});

test('free vars: shared subtrees and nested queries are analyzed once per immutable node', () => {
  // Count syntax reads rather than impose a noisy elapsed-time threshold.
  // Generated pattern matches can read a node more than once per analysis step;
  // the generous linear bound still rejects repeated whole-subtree traversals.
  let reads = 0;
  const nodes = [];
  const node = syntax => {
    const expr = new TcoExpr(null, syntax);
    Object.defineProperty(expr, 'value1', { get() { reads++; return syntax; } });
    Object.freeze(expr);
    nodes.push(expr);
    return expr;
  };
  let expr = node(x);
  for (let i = 0; i < 192; i++) expr = node(new S.Abs([arg('unused', i + 10)], expr));
  for (const input of nodes.toReversed()) assert.deepEqual(names(freeVars(input)), ['x_0']);
  assert.ok(reads <= 32 * nodes.length, `${reads} reads for ${nodes.length} nodes`);
  const coldReads = reads;
  for (const input of nodes) freeVars(input);
  assert.equal(reads, coldReads, 'warm queries do not read syntax');
  const dag = node(new S.Lit(new C.LitArray(Array(256).fill(expr))));
  assert.deepEqual(names(freeVars(dag)), ['x_0']);
  const dagReads = reads;
  assert.deepEqual(names(freeVars(dag)), ['x_0']);
  assert.equal(reads, dagReads, 'shared occurrences and warm queries reuse the result');
  assert.ok(reads <= 32 * nodes.length);
  // A binding's filtered result must not replace its child's free set.
  const bound = node(new S.Abs([arg('x', 0)], expr));
  assert.deepEqual(names(freeVars(bound)), []);
  assert.deepEqual(names(freeVars(expr)), ['x_0']);
  assert.ok(reads <= 32 * nodes.length);
});

function context(renames, recursiveVars = []) {
  let boundVars = empty;
  for (const [from, to] of Object.entries(renames)) boundVars = insert(ordString)(from)(to)(boundVars);
  return { ...initialContext('Fixture'), boundVars, recursiveVars };
}
const render = (expr, ctx) => {
  const result = translateExpr(ctx)(0)(expr);
  assert.deepEqual(result.stmts, []);
  return printExpr(empty)(result.expr);
};
function execute(body, expected) {
  const run = spawnSync('php', ['-d', 'opcache.enable_cli=0'], {
    encoding: 'utf8', input: '<?php\nerror_reporting(E_ALL);\n' + body,
  });
  assert.equal(run.status, 0, run.stdout + run.stderr);
  assert.equal(run.stderr, '');
  assert.deepEqual(JSON.parse(run.stdout), expected);
}

test('free vars: a shared closure uses each context\'s names and reference captures', () => {
  const sum = analyze([])(new S.Abs([arg('n', 1)],
    new S.PrimOp(new S.Op2(new S.OpIntNum(S.OpAdd.value), local('value', 0), local('n', 1)))));
  const valueCapture = render(sum, context({ value_0: 'left' }));
  const referenceCapture = render(sum, context({ value_0: 'right' }, ['right']));
  assert.match(valueCapture, /use \(\$left\)/);
  assert.match(referenceCapture, /use \(&\$right\)/);
  const n = local('n', 1);
  const recursive = analyze([])(new S.Abs([arg('n', 1)], new S.Branch([
    new S.Pair(new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpLte.value), n, int(0))), local('seed', 2)),
  ], new S.App(local('self', 0), [new S.PrimOp(new S.Op2(new S.OpIntNum(S.OpSubtract.value), n, int(1)))]))));
  const first = render(recursive, context({ self_0: 'first', seed_2: 'a' }, ['first']));
  const second = render(recursive, context({ self_0: 'second', seed_2: 'b' }, ['second']));
  execute(`$left = 10; $right = 20;
$f = ${valueCapture}; $g = ${referenceCapture};
$left = 100; $right = 200;
$a = 3; $b = 7; $first = null; $second = null;
$first = ${first}; $second = ${second};
echo json_encode([$f(1), $g(2), $first(9), $second(11), $first(1)], JSON_THROW_ON_ERROR);`,
  [11, 202, 3, 7, 3]);
});

test('free vars: shared effect nodes remain lazy, scoped and reusable', () => {
  const effect = analyze([])(new S.Typed(C.Any.value, new S.EffectBind(new Just('result'), 2,
    new S.UncurriedEffectApp(local('mark', 7), [local('value', 0)]),
    new S.TypeApp(new S.EffectPure(local('result', 2)), C.Any.value))));
  const first = render(effect, context({ value_0: 'left' }));
  const second = render(effect, context({ value_0: 'right' }));
  execute(`$events = []; $left = 3; $right = 9;
$mark_7 = function($value) use (&$events) { $events[] = $value; return $value + 1; };
$first = ${first}; $second = ${second};
$before = $events;
$results = [$second(), $first(), $second()];
echo json_encode([$before, $results, $events], JSON_THROW_ON_ERROR);`, [[], [10, 4, 10], [9, 3, 9]]);
});

test('free vars: completed expression graphs and cached nonempty sets can be collected', () => {
  // Keep the memoized function alive while releasing all caller-owned nodes.
  // A strong Map would retain both keys and values across translation calls.
  const moduleURL = name => new URL(`../../output/${name}/index.js`, import.meta.url).href;
  const run = spawnSync(process.execPath, ['--expose-gc', '--input-type=module', '-e', `
import assert from 'node:assert/strict';
import { setImmediate } from 'node:timers/promises';
import { freeVars } from ${JSON.stringify(moduleURL('Phpurs.FreeVars'))};
import { TcoExpr } from ${JSON.stringify(moduleURL('PureScript.Backend.Optimizer.Codegen.Tco'))};
import { Local, Lit } from ${JSON.stringify(moduleURL('PureScript.Backend.Optimizer.Syntax'))};
import { LitArray } from ${JSON.stringify(moduleURL('PureScript.Backend.Optimizer.CoreFn'))};
import { Just } from ${JSON.stringify(moduleURL('Data.Maybe'))};
const refs = (() => {
  const leaves = Array.from({ length: 2048 }, (_, i) => new TcoExpr(null, new Local(new Just('v'), i)));
  const root = new TcoExpr(null, new Lit(new LitArray(leaves)));
  const result = freeVars(root);
  assert.equal(freeVars(root), result);
  return [...leaves, root, result].map(value => new WeakRef(value));
})();
for (let i = 0; i < 3; i++) { await setImmediate(); global.gc(); }
assert.equal(refs.filter(ref => ref.deref() !== undefined).length, 0);
`], { encoding: 'utf8' });
  assert.equal(run.status, 0, run.stdout + run.stderr);
});
