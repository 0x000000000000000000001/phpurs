// Closed records whose every field is updated are built directly. Partial
// updates, old-record observers and failing values keep the clone path.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { empty as emptyMap } from '../../output/Data.Map/index.js';
import { empty as emptySet } from '../../output/Data.Set/index.js';
import { Just, Nothing } from '../../output/Data.Maybe/index.js';
import { Tuple } from '../../output/Data.Tuple/index.js';
import { translate } from '../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../output/Phpurs.Printer/index.js';

const int = T.Int.value;
const record = fields => new T.Record(new T.Row(fields, Nothing.value));
const dRecord = record([new Tuple('e', int), new Tuple('f', int)]);
const bRecord = record([new Tuple('c', int), new Tuple('d', dRecord)]);
const r = record([new Tuple('a', int), new Tuple('b', bRecord)]);
const typed = (ty, expr) => new S.Typed(ty, expr);
const local = (name, level) => new S.Local(new Just(name), level);
const lit = value => typed(int, new S.Lit(new T.LitInt(value)));
const add = (value, amount) => typed(int, new S.PrimOp(new S.Op2(new S.OpIntNum(S.OpAdd.value), value, lit(amount))));
const access = (value, key) => new S.Accessor(value, new S.GetProp(key));
const prop = (key, value) => new T.Prop(key, value);
const update = (base, props) => new S.Update(base, props);
const fullBody = typed(r, update(local('r', 0), [
  prop('a', add(access(local('r', 0), 'a'), 1)),
  prop('b', typed(bRecord, update(access(local('r', 0), 'b'), [
    prop('c', add(access(access(local('r', 0), 'b'), 'c'), 2)),
    prop('d', typed(dRecord, update(access(access(local('r', 0), 'b'), 'd'), [
      prop('e', add(access(access(access(local('r', 0), 'b'), 'd'), 'e'), 3)),
      prop('f', add(access(access(access(local('r', 0), 'b'), 'd'), 'f'), 4)),
    ]))),
  ]))),
]));
const partialBody = typed(r, update(local('r', 0), [
  prop('a', add(access(local('r', 0), 'a'), 10)),
]));
const throwingBody = typed(r, update(local('r', 0), [
  prop('a', typed(int, new S.Fail('record update failed'))),
  prop('b', access(local('r', 0), 'b')),
]));
const global = new S.Var(new T.Qualified(new Just('Demo'), 'old'));
const globalBody = typed(r, update(global, [
  prop('a', add(access(global, 'a'), 1)),
  prop('b', access(global, 'b')),
]));
const openRecord = new T.Record(new T.Row([new Tuple('a', int), new Tuple('b', bRecord)], new Just(new T.TypeVar('r'))));
const openBody = typed(openRecord, update(local('r', 0), [
  prop('a', add(access(local('r', 0), 'a'), 1)),
  prop('b', access(local('r', 0), 'b')),
]));
const fn = (name, body, ty = r) => new Tuple(name, typed(new T.Func([ty], ty), new S.Abs([new Tuple(new Just('r'), 0)], typed(ty, body))));
const module_ = {
  name: 'Demo', bindings: [{ recursive: false, bindings: [fn('full', fullBody), fn('partial', partialBody), fn('throwing', throwingBody), fn('globalUpdate', globalBody), fn('openUpdate', openBody, openRecord)] }],
  dataDecls: [], foreign: emptyMap, exports: emptySet, imports: emptySet, dataTypes: emptyMap,
  comments: [], reExports: emptySet, classDecls: [], implementations: emptyMap, directives: emptyMap,
};
const file = translate([])(module_);
const php = printPhpFile(false)('')(file.arities)(file);
const full = php.slice(php.indexOf('function majDemo_full('), php.indexOf('function majDemo_partial('));
assert.doesNotMatch(full, /clone /, 'full update must not clone');
assert.match(full, /\(object\)\["a" =>/);
assert.match(full, /"b" => \(object\)\["c" =>/);
assert.match(full, /"d" => \(object\)\["e" =>/);
assert.match(php.slice(php.indexOf('function majDemo_partial('), php.indexOf('function majDemo_throwing(')), /clone /, 'partial update keeps the clone');
assert.match(php.slice(php.indexOf('function majDemo_globalmajUpdate(')), /clone /, 'global base keeps the clone');
assert.match(php.slice(php.indexOf('function majDemo_openmajUpdate(')), /clone /, 'open row keeps the clone');

const source = php + `
set_error_handler(function($level, $message, $file, $line) { throw new \\ErrorException($message, 0, $level, $file, $line); });
$old = (object)['a' => 1, 'b' => (object)['c' => 2, 'd' => (object)['e' => 3, 'f' => 4]]];
$oldB = $old->b; $oldD = $oldB->d; $captured = null;
$observe = function() use (&$captured, $old) { $captured = $old->a; return $old->a; };
$new = \\Demo\\majDemo_full($old);
$values = [$new->a, $new->b->c, $new->b->d->e, $new->b->d->f,
  $old->a, $oldB->c, $oldD->e, $oldD->f, $observe(), $captured,
  array_keys((array)$new), array_keys((array)$new->b), array_keys((array)$new->b->d)];
$partial = \\Demo\\majDemo_partial($old);
$values[] = $partial->a; $values[] = $old->a; $values[] = $partial->b === $old->b;
try { \\Demo\\majDemo_throwing($old); $values[] = false; }
catch (\\Exception $error) { $values[] = str_starts_with($error->getMessage(), 'record update failed'); }
$values[] = $old->a;
echo json_encode($values, JSON_THROW_ON_ERROR);`;
const run = spawnSync('php', ['-d', 'opcache.enable_cli=0'], { input: source, encoding: 'utf8' });
assert.equal(run.status, 0, run.stdout + run.stderr);
assert.equal(run.stderr, '');
assert.deepEqual(JSON.parse(run.stdout), [
  2, 4, 6, 8, 1, 2, 3, 4, 1, 1,
  ['a', 'b'], ['c', 'd'], ['e', 'f'],
  11, 1, true, true, 1,
]);
console.log('record-update-fusion: full construction, partial clone, old aliases, order and exception fallback passed');
