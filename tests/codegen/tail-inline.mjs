// Exercise the PHP AST pass directly: PBO must not erase the calls under test.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import * as A from '../../output/Phpurs.PhpAst/index.js';
import { optimize, bodyBudget, callerBudget } from '../../output/Phpurs.TailInline/index.js';
import { printPhpFile } from '../../output/Phpurs.Printer/index.js';
import { empty, insert } from '../../output/Data.Map/index.js';
import { ordString } from '../../output/Data.Ord/index.js';
import { Just, Nothing } from '../../output/Data.Maybe/index.js';

const v = n => new A.PhpVar(n);
const i = n => new A.PhpInt(n);
const ret = e => new A.PhpReturn(e);
const set = (n, e) => new A.PhpAssign(n, e);
const box = e => new A.PhpNew('\\Test\\Box', [e]);
const op = (s, a, b) => new A.PhpBinOp(s, a, b);
const global = n => new A.PhpGlobalVar(new Just(['Test']), n);
const call = (n, args) => new A.PhpCall(global(n), args);
const tick = e => new A.PhpCall(new A.PhpRaw('tick'), [e]);
const fn = (name, params, body, retType = '') => ({
  identifier: `Test_${name}`,
  expression: new A.PhpNativeFunction(`Test_${name}`, params.map(p => typeof p === 'string' ? { name: p, type_: '' } : p), retType, body),
});
const choose = fn('choose', ['a', 'b'], [
  set('result', new A.PhpRaw('null')),
  new A.PhpIf(op('>', v('a'), i(0)), [set('result', box(op('+', v('a'), v('a')))), new A.PhpGoto('done')], []),
  set('result', v('b')), new A.PhpLabel('done'), ret(v('result')),
]);
const run = fn('run', ['a'], [
  set('__phpurs_inline_0_collision', i(9)),
  new A.PhpAssignExpr(new A.PhpGlobalVar(Nothing.value, 'saved'), new A.PhpFunction(['a'], [], '', [ret(v('a'))])),
  ret(call('choose', [tick(v('a')), box(tick(op('+', v('a'), i(1))))])),
]);
const partial = fn('partial', ['a'], [ret(call('choose', [v('a')]))]);
const factory = fn('factory', ['a'], [ret(new A.PhpGlobalVar(Nothing.value, 'adder'))]);
const factoryCaller = fn('factoryCaller', ['a'], [ret(call('factory', [v('a')]))]);
const typed = fn('typed', [{ name: 'a', type_: 'int' }], [ret(box(v('a')))]);
const checkedReturn = fn('checkedReturn', ['a'], [ret(v('a'))], 'int');
const closure = fn('closure', ['a'], [ret(new A.PhpFunction(['a'], [{ name: 'x', type_: '' }], '', [ret(v('a'))]))]);
const recursive = fn('recursive', ['a'], [ret(call('recursive', [v('a')]))]);
const big = fn('big', ['a'], [...Array.from({ length: bodyBudget + 1 }, (_, n) => set('x', i(n))), ret(v('a'))]);
const fallthrough = fn('fallthrough', ['a'], [new A.PhpIf(v('a'), [ret(i(1))], [])]);
const freeLocal = fn('freeLocal', ['a'], [ret(v('unbound'))]);
const skipped = [typed, checkedReturn, closure, recursive, big, fallthrough, freeLocal];
const laterTyped = fn('laterTyped', ['a', { name: 'b', type_: 'int' }], [ret(v('b'))]);
const laterTypedCaller = fn('laterTypedCaller', ['a'], [ret(call('laterTyped', [v('a'), new A.PhpString('9')]))]);
const overapplied = fn('overapplied', ['a'], [ret(call('factory', [v('a'), i(5)]))]);
// Keep a branch result observable through a closure captured by reference.
const observableJoin = fn('observableJoin', ['a'], [
  set('result', new A.PhpRaw('null')),
  new A.PhpAssignExpr(new A.PhpGlobalVar(Nothing.value, 'observed'), new A.PhpFunction(['&result'], [], '', [ret(v('result'))])),
  new A.PhpIf(v('a'), [set('result', i(42)), new A.PhpGoto('done')], []),
  ret(call('choose', [i(2), box(i(0))])),
  new A.PhpLabel('done'), ret(v('result')),
]);
const callers = skipped.map(d => fn(d.identifier.slice(5) + 'Caller', ['a'], [ret(call(d.identifier.slice(5), [v('a')]))]));
const guard = fn('guard', ['a', 'b'], [
  new A.PhpIf(op('===', v('a'), i(0)), [new A.PhpThrow(new A.PhpString('guard failed'))], []),
  ret(v('b')),
]);
const guardCaller = fn('guardCaller', ['a'], [ret(call('guard', [tick(v('a')), tick(op('+', v('a'), i(1)))]))]);
const decls = [choose, run, partial, factory, factoryCaller, laterTyped, laterTypedCaller, overapplied, observableJoin, guard, guardCaller, ...skipped, ...callers];
let arities = empty;
for (const d of decls) arities = insert(ordString)(d.identifier)(d.expression.value1.length)(arities);
const file = { namespace: ['Test'], imports: [], arities, decls, rawDecls: [
  "final class Box { public function __construct(public $value) {} }",
  "function tick($n) { $GLOBALS['events'][] = $n; return $n; }",
  "$GLOBALS['adder'] = function($n) { return $n + 10; };",
] };
const optimized = optimize(file);
const get = (f, name) => f.decls.find(d => d.identifier === `Test_${name}`).expression;
assert.notDeepEqual(get(optimized, 'run'), get(file, 'run'), 'saturated terminal call must be inlined');
assert.deepEqual(get(optimized, 'choose'), choose.expression, 'public target stays intact');
assert.deepEqual(get(optimized, 'partial'), partial.expression, 'partial application stays on its wrapper');
for (const d of callers) assert.deepEqual(get(optimized, d.identifier.slice(5)), d.expression, d.identifier);
assert.notDeepEqual(get(optimized, 'laterTypedCaller'), laterTypedCaller.expression, 'only actual emitted PHP checks block inlining');
assert.notDeepEqual(get(optimized, 'observableJoin'), observableJoin.expression);
assert.notDeepEqual(get(optimized, 'guardCaller'), guardCaller.expression);
assert.deepEqual(get(optimized, 'overapplied'), overapplied.expression, 'overapplied target call stays intact');
const printed = printPhpFile(false)('')(arities)(optimized);
assert.match(printed, /__phpurs_inline_1_/, 'prefix must avoid existing caller locals');

function execute(f) {
  const source = printPhpFile(false)('')(arities)(f) + `
set_error_handler(function($level, $message, $file, $line) { throw new \\ErrorException($message, 0, $level, $file, $line); });
$GLOBALS['events'] = [];
$positive = $GLOBALS['Test_run'](4)->value;
$captured = $GLOBALS['saved']();
$negative = $GLOBALS['Test_run'](-2)->value;
$partial = $GLOBALS['Test_partial'](3);
$values = [$positive, $captured, $negative, $GLOBALS['events'], $partial(new Box(0))->value,
 $GLOBALS['Test_factoryCaller'](0, 5), $GLOBALS['Test_typedCaller']('7')->value,
 $GLOBALS['Test_checkedReturnCaller']('8'), $GLOBALS['Test_laterTypedCaller'](0),
 $GLOBALS['Test_overapplied'](0), $GLOBALS['Test_observableJoin'](true), $GLOBALS['observed'](),
 $GLOBALS['Test_observableJoin'](false)->value, $GLOBALS['observed']()];
$GLOBALS['events'] = [];
try { $GLOBALS['Test_guardCaller'](0); } catch (\\Exception $error) { $values[] = $error->getMessage(); }
$values[] = $GLOBALS['events'];
echo json_encode($values, JSON_THROW_ON_ERROR);`;
  const result = spawnSync('php', ['-d', 'opcache.enable_cli=0'], { input: source, encoding: 'utf8' });
  assert.equal(result.status, 0, result.stderr);
  assert.equal(result.stderr, '');
  return JSON.parse(result.stdout);
}
assert.deepEqual(execute(file), [8, 4, -1, [4, 5, -2, -1], 6, 15, 7, 8, '9', 15, 42, 42, 4, null, 'guard failed', [0, 1]]);
assert.deepEqual(execute(optimized), execute(file));
// Multiple terminal sites consume a finite per-caller budget.
const many = fn('many', ['a'], [
  ...Array.from({ length: callerBudget }, (_, n) => new A.PhpIf(op('===', v('a'), i(n)), [ret(call('choose', [v('a'), box(i(0))]))], [])),
  ret(call('choose', [v('a'), box(i(0))])),
]);
const bounded = optimize({ ...file, decls: [choose, many] });
const last = get(bounded, 'many').value3.at(-1);
assert.deepEqual(last, many.expression.value3.at(-1), 'budget exhaustion must leave calls intact');
console.log('tail-inline: evaluation order, scopes, joins, partial/overapplication, PHP type checks, recursion and budgets passed');
