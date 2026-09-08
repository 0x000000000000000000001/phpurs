// Run after npm run build. Exercise the generator directly so PBO cannot erase
// the sibling bindings whose result expressions must remain distinct.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import * as Syn from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as Core from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { analyze } from '../../output/PureScript.Backend.Optimizer.Codegen.Tco/index.js';
import { translateExprImpl } from '../../output/Phpurs.CodeGen/index.js';
import { printExpr } from '../../output/Phpurs.Printer/index.js';
import { PhpReturn } from '../../output/Phpurs.PhpAst/index.js';
import { empty, insert } from '../../output/Data.Map/index.js';
import { ordString } from '../../output/Data.Ord/index.js';
import { Nothing, Just } from '../../output/Data.Maybe/index.js';

const none = Nothing.value;
const int = n => new Syn.Lit(new Core.LitInt(n));
const binding = n => new Syn.Let(none, 1, int(n), new Syn.Local(none, 1));
const subtract = (left, right) => new Syn.PrimOp(new Syn.Op2(new Syn.OpIntNum(Syn.OpSubtract.value), left, right));
const branch = new Syn.Branch([new Syn.Pair(new Syn.Local(new Just('flag'), 0), binding(11))], binding(22));

function generate(expr, isTail = true, loops = [], bound = empty) {
  const result = translateExprImpl('Test')([])(empty)(bound)(none)(loops)(isTail)(0)(analyze([])(expr));
  return [...result.stmts, new PhpReturn(result.expr)].map(printExpr('Test')(empty)).join(';\n') + ';';
}

function execute(body, expected) {
  const result = spawnSync('php', ['-d', 'opcache.enable_cli=0'], {
    input: `<?php\nerror_reporting(E_ALL);\nfunction run($flag_0) {\n${body}\n}\necho json_encode([run(true), run(false)], JSON_THROW_ON_ERROR);`,
    encoding: 'utf8',
  });
  assert.equal(result.status, 0, result.stderr);
  assert.equal(result.stderr, '');
  assert.deepEqual(JSON.parse(result.stdout), expected);
}

const siblings = generate(subtract(binding(11), binding(22)));
assert.ok(!siblings.includes('_slot'), 'sibling operands must retain separate bindings');
execute(siblings, [-11, -11]);

const alternatives = generate(branch);
assert.equal((alternatives.match(/\$__local_var_1_slot =/g) ?? []).length, 2);
execute(alternatives, [11, 22]);
execute(generate(subtract(branch, binding(3))), [8, 19]);

const loop = [{ ident: 'loop', params: [], varPrefix: 'loop_var_', labelName: 'loop_label' }];
const loopBody = generate(branch, true, loop);
assert.ok(!loopBody.includes('_slot'), 'TCO contexts must retain separate bindings');
execute(loopBody, [11, 22]);

const bound = insert(ordString)('__local_var_1')('__local_var_1_slot')(empty);
const nestedBody = generate(binding(22), true, [], bound);
assert.ok(!nestedBody.includes('$__local_var_1_slot ='), 'an active binding must not be reused');
execute('$__local_var_1_slot = 11;\n' + nestedBody, [22, 22]);
console.log('branch-slots: 5 generated PHP scenarios passed');
