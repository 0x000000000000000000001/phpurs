import assert from 'node:assert/strict';
import test from 'node:test';
import { Just, Nothing } from '../../output/Data.Maybe/index.js';
import { Tuple } from '../../output/Data.Tuple/index.js';
import * as C from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import { countNodes, countModuleNodes } from '../../output/Phpurs.AstMetrics/index.js';

const leaf = new S.Lit(new C.LitInt(42));
const name = new C.Qualified(new Just('Fixture'), 'value');
const args = [new Tuple(new Just('x'), 0), new Tuple(Nothing.value, 1)];
const pair = () => new S.Pair(leaf, leaf);
const props = () => [new C.Prop('a', leaf), new C.Prop('b', leaf)];

test('AST metrics: all syntax forms count expression occurrences, excluding metadata', () => {
  // Counts are explicit; a shared child still occurs once at each tree position.
  for (const [expr, expected] of [
    [new S.Var(name), 1], [new S.Local(new Just('x'), 0), 1],
    ...[new C.LitInt(1), new C.LitNumber(1.5), new C.LitString('s'), new C.LitChar('x'),
      new C.LitBoolean(true), new C.LitArray([]), new C.LitRecord([])].map(lit => [new S.Lit(lit), 1]),
    [new S.Lit(new C.LitArray([leaf, leaf])), 3],
    [new S.Lit(new C.LitRecord(props())), 3],
    [new S.App(leaf, [leaf, leaf]), 4],
    [new S.TypeApp(leaf, new C.Func([C.Int.value], C.Int.value)), 2],
    [new S.Abs(args, leaf), 2],
    [new S.UncurriedApp(leaf, [leaf, leaf]), 4],
    [new S.UncurriedAbs(args, leaf), 2],
    [new S.UncurriedEffectApp(leaf, [leaf, leaf]), 4],
    [new S.UncurriedEffectAbs(args, leaf), 2],
    [new S.Accessor(leaf, new S.GetProp('a')), 2],
    [new S.Update(leaf, props()), 4],
    [new S.CtorSaturated(name, C.ProductType.value, 'Box', 'Box',
      [new Tuple('value0', leaf), new Tuple('value1', leaf)]), 3],
    [new S.CtorDef(C.ProductType.value, 'Box', 'Box', ['value0', 'value1']), 1],
    [new S.LetRec(0, [new Tuple('a', leaf), new Tuple('b', leaf)], leaf), 4],
    [new S.Let(new Just('x'), 0, leaf, leaf), 3],
    [new S.EffectBind(new Just('x'), 0, leaf, leaf), 3],
    [new S.EffectPure(leaf), 2], [new S.EffectDefer(leaf), 2],
    [new S.Branch([pair(), pair()], leaf), 6],
    [new S.PrimOp(new S.Op1(S.OpIntNegate.value, leaf)), 2],
    [new S.PrimOp(new S.Op2(new S.OpIntNum(S.OpAdd.value), leaf, leaf)), 3],
    [new S.PrimEffect(new S.EffectRefNew(leaf)), 2],
    [new S.PrimEffect(new S.EffectRefRead(leaf)), 2],
    [new S.PrimEffect(new S.EffectRefWrite(leaf, leaf)), 3],
    [S.PrimUndefined.value, 1], [new S.Fail('message'), 1],
    [new S.Typed(new C.Func([C.Int.value, C.Int.value], C.Int.value), leaf), 2],
  ]) assert.equal(countNodes(expr), expected, expr.constructor.name);
});

test('AST metrics: nested missing forms and recursive module groups are fully counted', () => {
  const array = new S.Lit(new C.LitArray([leaf, new S.EffectPure(leaf)])); // 4
  const record = new S.Lit(new C.LitRecord([new C.Prop('xs', array)])); // 5
  const update = new S.Update(record, [new C.Prop('xs', array)]); // 10
  const branch = new S.Branch([new S.Pair(new S.PrimOp(new S.Op1(S.OpArrayLength.value, array)), update)], record); // 21
  const wrapped = new S.Typed(C.Int.value, new S.TypeApp(branch, C.Int.value)); // 23
  assert.equal(countNodes(wrapped), 23);
  assert.equal(countModuleNodes({ bindings: [] }), 0);
  assert.equal(countModuleNodes({ bindings: [
    { recursive: false, bindings: [new Tuple('first', wrapped)] },
    { recursive: true, bindings: [new Tuple('second', array), new Tuple('third', wrapped)] },
  ] }), 50);
});

test('AST metrics: deep and wide expressions do not consume the JavaScript call stack', () => {
  const size = 100000;
  let deep = leaf;
  for (let i = 0; i < size; i++) deep = new S.Typed(C.Int.value, new S.EffectDefer(deep));
  assert.equal(countNodes(deep), 2 * size + 1);
  assert.equal(countNodes(new S.Lit(new C.LitArray(Array(size).fill(leaf)))), size + 1);
  assert.equal(countNodes(new S.Lit(new C.LitRecord(Array.from({ length: size }, (_, i) => new C.Prop('p' + i, leaf))))), size + 1);
  assert.equal(countNodes(new S.App(leaf, Array(size).fill(leaf))), size + 2);
  assert.equal(countNodes(new S.Branch(Array.from({ length: size }, pair), leaf)), 2 * size + 2);
});
