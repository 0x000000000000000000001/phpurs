// Validate the flow proof directly, including escapes PBO might optimize away.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import * as A from '../../output/Phpurs.PhpAst/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { optimize } from '../../output/Phpurs.CompactLoops/index.js';
import { printPhpFile } from '../../output/Phpurs.Printer/index.js';
import { Just, Nothing } from '../../output/Data.Maybe/index.js';
import { empty, insert } from '../../output/Data.Map/index.js';
import { ordString } from '../../output/Data.Ord/index.js';
const v = x => new A.PhpVar(x);
const n = x => new A.PhpInt(x);
const set = (x, e) => new A.PhpAssign(x, e);
const ret = e => new A.PhpReturn(e);
const call = (f, xs) => new A.PhpCall(f, xs);
const op = (s, a, b) => new A.PhpBinOp(s, a, b);
const jump = x => new A.PhpGoto(x);
const fn = (caps, body) => new A.PhpFunction(caps, [{ name: 'x', type_: '' }], '', body);
const signature = new Just({ fArgs: [T.Int.value, new T.Func([T.Int.value], T.Int.value), T.Int.value], fRet: T.Int.value });
const params = [{ name: 'n', type_: 'int' }, { name: 'acc', type_: '' }];
const next = fn(['acc'], [ret(op('+', call(v('acc'), [v('x')]), n(1)))]);
const loop = (prefix = [], node = next, suffix = []) => new A.PhpNativeFunction('Demo_build', params, '', [
  new A.PhpLabel('again'),
  new A.PhpIf(op('<=', v('n'), n(0)), [ret(v('acc'))], []),
  ...prefix, set('next', node), ...suffix,
  set('acc', v('next')), set('n', op('-', v('n'), n(1))), jump('again'),
]);
const change = body => optimize('again')(signature)(body);
const positive = loop();
const optimized = change(positive);
assert.ok(optimized instanceof A.PhpCompactLoop);
const cases = [
  ['direct FFI', loop([call(new A.PhpGlobalVar(Nothing.value, 'observe'), [v('acc')])])],
  ['record escape', loop([set('box', new A.PhpAssocArray([{ key: 'fn', value: v('acc') }]))])],
  ['field mutation', loop([new A.PhpAssignExpr(new A.PhpPropertyAccess(v('box'), 'fn'), v('acc'))])],
  ['array escape', loop([set('box', new A.PhpArray([v('acc')]))])],
  ['aliases reach FFI', loop([set('a', v('acc')), set('b', v('a')), call(new A.PhpGlobalVar(Nothing.value, 'observe'), [v('b')])])],
  ['new node escapes', loop([], next, [call(new A.PhpGlobalVar(Nothing.value, 'observe'), [v('next')])])],
  ['identity observed', loop([new A.PhpInstanceOf(v('acc'), '\\Closure')])],
  ['property read', loop([new A.PhpPropertyAccess(v('acc'), 'field')])],
  ['argument of captured function', loop([], fn(['acc'], [ret(call(v('acc'), [v('acc')]))]))],
  ['callback escapes within body', loop([], fn(['acc'], [ret(call(new A.PhpGlobalVar(Nothing.value, 'observe'), [v('acc')]))]))],
  ['returns captured function', loop([], fn(['acc'], [ret(v('acc'))]))],
  ['nested closure', loop([], fn(['acc'], [ret(fn(['acc'], [ret(n(0))]))]))],
  ['reference capture', loop([], fn(['&acc'], [ret(call(v('acc'), [v('x')]))]))],
  ['capture reassignment', loop([], fn(['acc'], [set('acc', n(0)), ret(n(1))]))],
  ['raw code', loop([new A.PhpRaw('observe($acc)')])],
  ['missing loop edge', new A.PhpNativeFunction('Demo_build', params, '', positive.value3.slice(0, -1))],
];
cases.push(['analysis node budget', loop(Array.from({ length: 2200 }, (_, i) => set('unused' + i, n(i))))]);
cases.push(['alias convergence budget', loop(Array.from({ length: 80 }, (_, i) => set('alias' + i, v(i === 0 ? 'acc' : 'alias' + (i - 1)))))]);
for (const [name, body] of cases) assert.deepEqual(change(body), body, name);
assert.deepEqual(optimize('again')(Nothing.value)(positive), positive, 'unknown TAST signature');
const anySignature = new Just({ fArgs: [T.Int.value, T.Any.value, T.Int.value], fRet: T.Int.value });
assert.deepEqual(optimize('again')(anySignature)(positive), positive, 'unproven accumulator type');
const objectResult = new Just({ fArgs: [T.Int.value, new T.Func([T.Int.value], T.Any.value), T.Int.value], fRet: T.Any.value });
assert.deepEqual(optimize('again')(objectResult)(positive), positive, 'unproven scalar result');

function execute(decl) {
  const arities = insert(ordString)('Demo_build')(2)(empty);
  const file = { namespace: ['Demo'], imports: [], rawDecls: [], arities, decls: [{ identifier: 'Demo_build', expression: decl }] };
  const php = printPhpFile(false)('')(arities)(file) + `
set_error_handler(function($level,$message,$file,$line) { throw new \\ErrorException($message,0,$level,$file,$line); });
$base = function($x) { return $x + 7; };
$build = $GLOBALS['Demo_build'];
$f = $build(20, $base);
$partial = $build(20);
$record = (object)['callback' => $f];
$typed = function(\\Closure $fn) { return $fn(3); };
$values = [$f(3),$f(5),$partial($base)(3),$build(20,$base,3),$partial($base,3),
 $f instanceof \\Closure,$typed($record->callback),$build(0,$base) === $base];
$state = 0;
$shared = function($x) use (&$state) { return ++$state; };
$a = $build(2,$shared); $b = $build(5,$shared);
$values[] = [$state,$a(0),$b(0),$a(0),$state];
$exception = new \\RuntimeException('seed');
$throws = $build(2,function($x) use ($exception) { throw $exception; });
try { $throws(0); } catch (\\RuntimeException $e) { $values[] = $e === $exception; }
echo json_encode($values,JSON_THROW_ON_ERROR);`;
  const result = spawnSync('php', ['-d', 'opcache.enable_cli=0'], { input: php, encoding: 'utf8' });
  assert.equal(result.status, 0, result.stderr);
  assert.equal(result.stderr, '');
  return JSON.parse(result.stdout);
}
const expected = [30, 32, 30, 30, 30, true, 30, true, [0, 3, 7, 5, 3], true];
assert.deepEqual(execute(positive), expected);
assert.deepEqual(execute(optimized), expected);
console.log('compact-loops: public Closure, captures/curry/exceptions and 21 refusal scenarios passed');
