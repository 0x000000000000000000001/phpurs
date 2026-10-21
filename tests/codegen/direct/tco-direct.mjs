// Direct TCO registers must stage every new argument before writing parameters.
import assert from 'node:assert/strict';
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as C from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { analyze } from '../../../output/PureScript.Backend.Optimizer.Codegen.Tco/index.js';
import { initialContext, translateExpr } from '../../../output/Phpurs.CodeGen/index.js';
import { printExpr } from '../../../output/Phpurs.Printer/index.js';
import { empty } from '../../../output/Data.Map/index.js';
import { Just } from '../../../output/Data.Maybe/index.js';

const local = (name, level) => new S.Local(new Just(name), level);
// Permute the two loop parameters; both reads must happen before either write.
const call = new S.App(local('f', 0), [local('y', 1), local('x', 0)]);
const loop = direct => ({ ident: 'f_0', params: ['x_0', 'y_1'], varPrefix: '__tco_var_f_', labelName: 'tco_loop_f', direct });
const context = direct => ({ ...initialContext('Demo'), loops: [loop(direct)], isTail: true });
const render = direct => translateExpr(context(direct))(0)(analyze([])(call)).stmts.map(printExpr(empty));

assert.deepEqual(render(true), [
  '$__tco_0 = $y_1',
  '$__tco_1 = $x_0',
  '$x_0 = $__tco_0',
  '$y_1 = $__tco_1',
  'goto tco_loop_f;',
], 'direct registers stage a permutation before assignment');

assert.deepEqual(render(false), [
  '$__tco_0 = $y_1',
  '$__tco_1 = $x_0',
  '$__tco_var_f_x_0 = $__tco_0',
  '$__tco_var_f_y_1 = $__tco_1',
  'goto tco_loop_f;',
], 'buffered registers keep the old slot protocol');

console.log('tco-direct: permutation staging and buffered fallback passed');
// Computed arguments that depend on both old parameters are still staged.
const add = (a, b) => new S.PrimOp(new S.Op2(new S.OpIntNum(S.OpAdd.value), a, b));
const computed = new S.App(local('f', 0), [add(local('y', 1), new S.Lit(new C.LitInt(1))), add(local('x', 0), local('y', 1))]);
const computedStmts = translateExpr(context(true))(0)(analyze([])(computed)).stmts.map(printExpr(empty));
assert.match(computedStmts[0], /^\$__tco_0 = \(\$y_1 \+ 1\)$/);
assert.match(computedStmts[1], /^\$__tco_1 = \(\$x_0 \+ \$y_1\)$/);
assert.equal(computedStmts[2], '$x_0 = $__tco_0');
assert.equal(computedStmts[3], '$y_1 = $__tco_1');
console.log('tco-direct: computed cross-dependencies staged before writes');


// A closure built as a tail argument must capture the old parameter value
// before the direct register is updated.
const closure = new S.Abs([new S.Pair(new Just('x'), 1)], local('x', 0));
const capturedCall = new S.App(local('f', 0), [closure]);
const capturedLoop = { ident: 'f_0', params: ['x_0'], varPrefix: '__tco_var_f_', labelName: 'tco_loop_f', direct: true };
const capturedContext = { ...initialContext('Demo'), loops: [capturedLoop], isTail: true };
const captured = translateExpr(capturedContext)(0)(analyze([])(capturedCall)).stmts.map(printExpr(empty));
assert.match(captured[0], /use \(\$x_0\)/, 'closure captures the old register value');
assert.equal(captured[1], '$x_0 = $__tco_0', 'direct assignment happens after closure construction');
assert.equal(captured[2], 'goto tco_loop_f;');
console.log('tco-direct: closure captures the old parameter value');
