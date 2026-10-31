// Unary operators are typed AST nodes, not binary operators with an empty
// `PhpRaw` left operand. Traversal and printing must agree on that shape.
import assert from 'node:assert/strict';
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { PhpInt, PhpUnaryOp, PhpVar } from '../../../output/Phpurs.PhpAst/index.js';
import { children, mapChildren } from '../../../output/Phpurs.PhpAst.Traversal/index.js';
import { translateOperator1 } from '../../../output/Phpurs.CodeGen.Operators/index.js';
import { printExpr } from '../../../output/Phpurs.Printer/index.js';

const x = new PhpVar('x_0');
const cases = [
  ['OpBooleanNot', '( ! $x_0)'],
  ['OpIntBitNot', '( ~ $x_0)'],
  ['OpIntNegate', '( - $x_0)'],
  ['OpNumberNegate', '( - $x_0)'],
];

for (const [operator, printed] of cases) {
  const expr = translateOperator1(S[operator].value)(x);
  assert.ok(expr instanceof PhpUnaryOp, `${operator} builds a PhpUnaryOp`);
  assert.equal(expr.value1, x, `${operator} keeps its operand`);
  assert.equal(printExpr(emptyMap)(expr), printed, `${operator} prints its historical spelling`);
  assert.deepEqual(children(expr), [x], `${operator} exposes exactly one child`);

  const mapped = mapChildren((child) => (child === x ? new PhpInt(7) : child))(expr);
  assert.ok(mapped instanceof PhpUnaryOp, `${operator} maps back to a PhpUnaryOp`);
  assert.equal(printExpr(emptyMap)(mapped), printed.replace('$x_0', '7'));
}

console.log('unary-operators: typed nodes, traversal and printing passed');
