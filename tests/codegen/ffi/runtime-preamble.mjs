// The module preamble must contain only runtime pieces referenced by the body.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import * as A from '../../../output/Phpurs.PhpAst/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';
import { empty, insert } from '../../../output/Data.Map/index.js';
import { ordString } from '../../../output/Data.Ord/index.js';
import { sharedRuntime } from '../../../output/Phpurs.Printer.Runtime/index.js';
import { curryFallback, effectMap, executeEffect, promise, promiseMap, record, refNew, refRead, refWrite, value } from '../../../output/Phpurs.RuntimeNames/index.js';

for (const name of [curryFallback, executeEffect, refNew, refRead, refWrite, value, record, promise, promiseMap, effectMap]) {
  assert.match(sharedRuntime, new RegExp(`function ${name}`), `runtime defines ${name}`);
}

const helperProbe = `${sharedRuntime}
$value = \\phpurs_record([
  'a' => 1,
  'b' => ['x' => 2, 'y' => [3, 4]],
  'c' => [['z' => 5]],
  'd' => [],
]);
if (!($value instanceof \\stdClass)) exit(1);
if ($value->a !== 1) exit(2);
if (!($value->b instanceof \\stdClass) || $value->b->x !== 2) exit(3);
if ($value->b->y !== [3, 4]) exit(4);
if (!($value->c[0] instanceof \\stdClass) || $value->c[0]->z !== 5) exit(5);
if ($value->d !== []) exit(6);
$structural = \\phpurs_value(['a' => 1, 'list' => [['b' => 2]]]);
if (!($structural instanceof \\stdClass)) exit(7);
if (!($structural->list[0] instanceof \\stdClass) || $structural->list[0]->b !== 2) exit(8);
$list = \\phpurs_value([['a' => 1]]);
if (!\\is_array($list) || !($list[0] instanceof \\stdClass)) exit(9);
$effectValue = \\phpurs_effect_map(function() { return ['a' => 1]; }, 'phpurs_value')();
if (!($effectValue instanceof \\stdClass) || $effectValue->a !== 1) exit(10);
$captured = null;
$thenable = \\phpurs_promise(function($resolve, $reject) { $resolve(['a' => 1])(); });
$mapped = \\phpurs_promise_map($thenable, 'phpurs_value');
$mapped->then(function($v) use (&$captured) { $captured = $v; return function() {}; }, null);
if (!($captured instanceof \\stdClass) || $captured->a !== 1) exit(11);
$promise = \\phpurs_promise(function($resolve, $reject) {});
if (!($promise instanceof \\PhpursThenable) || !\\is_callable([$promise, 'then'])) exit(12);
class PhpursPromise {
  public $value;
  public function then($ok, $err) { $ok($this->value); return null; }
  public function resolve($value) { $this->value = $value; }
  public function reject($error) {}
}
$native = new \\PhpursPromise();
$native->value = ['a' => 1];
$mappedPromise = \\phpurs_promise_map($native, 'phpurs_value');
if (!($mappedPromise instanceof \\PhpursPromise)) exit(13);
$captured = null;
$mappedPromise->then(function($v) use (&$captured) { $captured = $v; }, null);
if (!($captured instanceof \\stdClass) || $captured->a !== 1) exit(14);
echo "ok\\n";
`;
const probe = spawnSync('php', ['-d', 'opcache.enable_cli=0', '-r', helperProbe], { encoding: 'utf8', timeout: 10000 });
assert.equal(probe.status, 0, probe.stdout + probe.stderr);
assert.equal(probe.stdout, 'ok\n');

const render = (decls, arities = empty) => printPhpFile(false)('')(arities)({
  namespace: ['Demo'], imports: [], decls, rawDecls: [], arities,
});
const fn = { identifier: 'Demo_fn', expression: new A.PhpNativeFunction('Demo_fn', [{ name: 'x', type_: '' }], '', [new A.PhpReturn(new A.PhpVar('x'))]) };
const arities = insert(ordString)('Demo_fn')(1)(empty);
const raw = text => ({ identifier: 'Demo_raw', expression: new A.PhpRaw(text) });

const emptyPhp = render([]);
assert.doesNotMatch(emptyPhp, /Phpurs_Data/);
assert.doesNotMatch(emptyPhp, /runtime\.php/);
assert.doesNotMatch(emptyPhp, /phpurs_curry_fallback/);
assert.doesNotMatch(emptyPhp, /phpurs_execute_effect/);
assert.doesNotMatch(emptyPhp, /phpurs_ref_new/);

const nativePhp = render([fn], arities);
assert.match(nativePhp, /require_once __DIR__ \. '\/\.\.\/runtime\.php';/);
assert.match(nativePhp, /\\phpurs_curry_fallback/);
assert.doesNotMatch(nativePhp, /function phpurs_curry_fallback/);
assert.doesNotMatch(nativePhp, /Phpurs_Data/);
assert.doesNotMatch(nativePhp, /function phpurs_execute_effect/);
assert.doesNotMatch(nativePhp, /function phpurs_ref_new/);

const effectPhp = render([raw('\\phpurs_execute_effect(null)')]);
assert.match(effectPhp, /require_once __DIR__ \. '\/\.\.\/runtime\.php';/);
assert.match(effectPhp, /\\phpurs_execute_effect/);
assert.doesNotMatch(effectPhp, /function phpurs_execute_effect/);
assert.doesNotMatch(effectPhp, /Phpurs_Data/);

const refsPhp = render([raw('\\phpurs_ref_new(1)')]);
assert.match(refsPhp, /require_once __DIR__ \. '\/\.\.\/runtime\.php';/);
assert.match(refsPhp, /\\phpurs_ref_new/);
assert.doesNotMatch(refsPhp, /function phpurs_ref_new/);

const recordPhp = render([raw('\\phpurs_record(["a" => 1])')]);
assert.match(recordPhp, /require_once __DIR__ \. '\/\.\.\/runtime\.php';/);
assert.match(recordPhp, /\\phpurs_record/);
assert.doesNotMatch(recordPhp, /function phpurs_record/);

const promisePhp = render([raw('\\phpurs_promise($executor)')]);
assert.match(promisePhp, /require_once __DIR__ \. '\/\.\.\/runtime\.php';/);
assert.match(promisePhp, /\\phpurs_promise/);
assert.doesNotMatch(promisePhp, /function phpurs_promise/);

const valuePhp = render([raw('\\phpurs_value($value)')]);
assert.match(valuePhp, /require_once __DIR__ \. '\/\.\.\/runtime\.php';/);
assert.match(valuePhp, /\\phpurs_value/);
assert.doesNotMatch(valuePhp, /function phpurs_value/);

const mapsPhp = render([raw('\\phpurs_promise_map($p, "phpurs_value"); \\phpurs_effect_map($e, "phpurs_value")')]);
assert.match(mapsPhp, /require_once __DIR__ \. '\/\.\.\/runtime\.php';/);
assert.match(mapsPhp, /\\phpurs_promise_map/);
assert.match(mapsPhp, /\\phpurs_effect_map/);
assert.doesNotMatch(mapsPhp, /function phpurs_promise_map/);
assert.doesNotMatch(mapsPhp, /function phpurs_effect_map/);

const dataPhp = render([raw('new \\Demo\\Phpurs_Data2("x", null)')]);
assert.match(dataPhp, /class Phpurs_Data0/);
assert.match(dataPhp, /class Phpurs_Data12/);
console.log('runtime-preamble: selective data classes, curry, effect, reference, record and promise helpers passed');
