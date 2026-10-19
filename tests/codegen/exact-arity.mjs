// Exact native bodies are private, only for scalar monomorphic signatures.
// The public wrapper remains responsible for currying, overapplication and
// callable values.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import * as A from '../../output/Phpurs.PhpAst/index.js';
import { printPhpFile } from '../../output/Phpurs.Printer/index.js';
import { empty, insert } from '../../output/Data.Map/index.js';
import { ordString } from '../../output/Data.Ord/index.js';
import { Just } from '../../output/Data.Maybe/index.js';

const v = n => new A.PhpVar(n);
const i = n => new A.PhpInt(n);
const ret = e => new A.PhpReturn(e);
const add = (a, b) => new A.PhpBinOp('+', a, b);
const call = (name, args) => args.reduce((fn, arg) => new A.PhpCall(fn, [arg]), new A.PhpGlobalVar(new Just(['Demo']), name));
const fn = (name, args, retType, body) => ({
  identifier: `Demo_${name}`,
  expression: new A.PhpNativeFunction(`Demo_${name}`, args, retType, body),
});
const int = name => ({ name, type_: 'int' });
const any = name => ({ name, type_: '' });
const ref = name => ({ name, type_: '&' });

const addFn = fn('add', [int('a'), int('b')], 'int', [ret(add(v('a'), v('b')))]);
const useFn = fn('use', [int('x')], 'int', [ret(call('add', [v('x'), i(2)]))]);
const partialFn = fn('partial', [int('x')], '', [ret(new A.PhpCall(new A.PhpGlobalVar(new Just(['Demo']), 'add'), [v('x')]))]);
const overFn = fn('over', [int('x')], '', [ret(call('add', [v('x'), i(2), i(3)]))]);
const factoryFn = fn('factory', [int('x')], '', [
  ret(new A.PhpFunction(['x'], [int('y')], 'int', [ret(add(v('x'), v('y')))])),
]);
const closureFn = fn('closureBody', [int('x')], 'int', [
  ret(new A.PhpFunction(['x'], [], 'int', [ret(v('x'))])),
]);
const refFn = fn('ref', [ref('x')], 'int', [ret(v('x'))]);
const collisionFn = fn('collision', [int('x')], 'int', [ret(v('x'))]);
const collisionExactFn = fn('collision__phpurs_exact', [int('x')], 'int', [ret(i(5))]);
const nullFn = fn('nullArg', [any('x')], 'int', [ret(i(0))]);

const decls = [addFn, useFn, partialFn, overFn, factoryFn, closureFn, refFn, collisionFn, collisionExactFn, nullFn];
let arities = empty;
for (const decl of decls) arities = insert(ordString)(decl.identifier)(decl.expression.value1.length)(arities);
const file = { namespace: ['Demo'], imports: [], decls, rawDecls: [], arities };
const php = printPhpFile(false)('')(arities)(file);

const section = (name, next) => {
  const start = php.indexOf(`// Demo_${name}\n`);
  assert.ok(start >= 0, name);
  const end = next ? php.indexOf(`// Demo_${next}\n`, start) : php.length;
  return php.slice(start, end);
};
const addSection = section('add', 'use');
assert.match(addSection, /function majDemo_add__phpurs_exact\(int \$a, \$b\): int \{/);
assert.match(addSection, /function majDemo_add\(int \$a, \$b = null\): int\|\\Closure \{/);
assert.match(addSection, /majDemo_add__phpurs_exact\(\$a, \$b\)/);
assert.match(section('use', 'partial'), /\\Demo\\majDemo_add__phpurs_exact\(\$x, 2\)/);
assert.doesNotMatch(section('use', 'partial'), /\\Demo\\majDemo_add\(\$x, 2\)/);
assert.match(section('partial', 'over'), /\(\$GLOBALS\['Demo_add'\]\)\(\$x\)/);
assert.doesNotMatch(section('partial', 'over'), /majDemo_add__phpurs_exact/);
assert.match(section('over', 'factory'), /\\Demo\\majDemo_add\(\$x, 2\)/);
assert.doesNotMatch(section('over', 'factory'), /majDemo_add__phpurs_exact/);
assert.doesNotMatch(section('factory', 'closureBody'), /majDemo_factory__phpurs_exact/);
assert.match(section('closureBody', 'ref'), /function majDemo_closuremajBody__phpurs_exact\(int \$x\): int\|\\Closure/);
assert.doesNotMatch(section('ref', 'collision'), /majDemo_ref__phpurs_exact/);
assert.doesNotMatch(section('collision', 'collision__phpurs_exact'), /majDemo_collision__phpurs_exact\(/);
assert.match(section('nullArg', null), /function majDemo_nullmajArg\(\$x\): int\|\\Closure/);
assert.doesNotMatch(section('nullArg', null), /majDemo_nullmajArg__phpurs_exact/);

const source = php + `
set_error_handler(function($level, $message, $file, $line) { throw new \\ErrorException($message, 0, $level, $file, $line); });
$values = [];
$values[] = \\Demo\\majDemo_use(40);
$values[] = \\Demo\\majDemo_add(40, 2);
$partial = \\Demo\\majDemo_add(40);
$values[] = $partial(2);
$values[] = $partial(3);
$values[] = \\Demo\\majDemo_factory(40)(2);
$values[] = \\Demo\\majDemo_closuremajBody(7)();
$values[] = \\Demo\\majDemo_nullmajArg(null);
$x = 9;
$values[] = \\Demo\\majDemo_ref($x);
$values[] = $x;
$values[] = \\Demo\\majDemo_collision(4);
$values[] = \\Demo\\majDemo_collision__phpurs_exact(5);
try { \\Demo\\majDemo_add(PHP_INT_MAX, 1); $values[] = 'no overflow'; }
catch (\\TypeError $error) { $values[] = str_contains($error->getMessage(), 'majDemo_add__phpurs_exact(): Return value'); }
echo json_encode($values, JSON_THROW_ON_ERROR);`;
const run = spawnSync('php', ['-d', 'opcache.enable_cli=0'], { input: source, encoding: 'utf8' });
assert.equal(run.status, 0, run.stdout + run.stderr);
assert.equal(run.stderr, '');
assert.deepEqual(JSON.parse(run.stdout), [42, 42, 42, 43, 42, 7, 0, 9, 9, 4, 5, true]);
console.log('exact-arity: scalar bodies, wrapper fallbacks, collisions, refs, closures, nulls and overflow checks passed');
