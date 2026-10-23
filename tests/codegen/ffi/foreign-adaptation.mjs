// Foreign return adaptation: the generated wrapper must turn plain PHP arrays
// into the representation required by the PureScript type, and leave Foreign
// values alone.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { Nothing } from '../../../output/Data.Maybe/index.js';
import { Just } from '../../../output/Data.Maybe/index.js';
import { empty, fromFoldable } from '../../../output/Data.Map/index.js';
import { foldableArray } from '../../../output/Data.Foldable/index.js';
import { ordString } from '../../../output/Data.Ord/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { genForeignModule } from '../../../output/Phpurs.GenNativeForeign/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';
import { withRuntime } from '../fixtures/runtime.mjs';

const int = T.Int.value;
const any = T.Any.value;
const string = T.String.value;
const record = (fields) => new T.Record(new T.Row(fields, Nothing.value));
const foreign = new T.ADT('Foreign.Foreign', ['Foreign', 'Foreign'], []);
const promiseOf = (inner) =>
  new T.ADT('Promise.Internal.Promise', ['Promise', 'Internal', 'Promise'], [inner]);
const effectOf = (inner) => new T.ADT('Effect.Effect', ['Effect', 'Effect'], [inner]);
const objectOf = (inner) =>
  new T.ADT('Foreign.Object.Object', ['Foreign', 'Object', 'Object'], [inner]);

const bindings = fromFoldable(T.ordIdent)(foldableArray)(
  [
    ['recordValue', new Just(any)],
    ['recordTyped', new Just(record([new Tuple('a', int)]))],
    ['nestedRecord', new Just(record([new Tuple('inner', record([new Tuple('a', int)]))]))],
    ['foreignValue', new Just(foreign)],
    ['foreignRows', new Just(new T.Array(foreign))],
    ['arrayAny', new Just(new T.Array(any))],
    ['objectValue', new Just(objectOf(string))],
    ['promiseRecord', new Just(promiseOf(record([new Tuple('a', int)])))],
    ['effectPromise', new Just(effectOf(promiseOf(any)))],
  ].map(([name, type]) => new Tuple(name, type)),
);

const ffi = genForeignModule({
  moduleName: 'Adapt',
  bindings,
  source: `<?php
$exports['recordValue'] = ['a' => 1, 'nested' => ['b' => 2]];
$exports['recordTyped'] = ['a' => 1];
$exports['nestedRecord'] = ['inner' => ['a' => 1]];
$exports['foreignValue'] = ['a' => 1];
$exports['foreignRows'] = [['a' => 1]];
$exports['arrayAny'] = [['a' => 1]];
$exports['objectValue'] = ['a' => 'b'];
$exports['promiseRecord'] = \\phpurs_promise(function($resolve, $reject) { $resolve(['a' => 1])(); });
$exports['effectPromise'] = function() {
  return \\phpurs_promise(function($resolve, $reject) { $resolve(['a' => 1])(); });
};
`,
});

const file = { namespace: ['Adapt'], imports: [], decls: [], rawDecls: [], arities: empty };
const code = `<?php
namespace {
  function check($condition, $message) { if (!$condition) throw new \\Exception($message); }
}
${printPhpFile(true)(ffi.code)(ffi.arities)(file)}
namespace Adapt {
  $record = $GLOBALS['Adapt_recordValue'];
  check($record instanceof \\stdClass && $record->a === 1, 'Any root record');
  check($record->nested instanceof \\stdClass && $record->nested->b === 2, 'Any nested record');
  $typed = $GLOBALS['Adapt_recordTyped'];
  check($typed instanceof \\stdClass && $typed->a === 1, 'typed record');
  $nested = $GLOBALS['Adapt_nestedRecord'];
  check($nested instanceof \\stdClass && $nested->inner instanceof \\stdClass && $nested->inner->a === 1, 'typed nested record');
  check(\\is_array($GLOBALS['Adapt_foreignValue']), 'Foreign stays an array');
  $rows = $GLOBALS['Adapt_foreignRows'];
  check(\\is_array($rows) && \\is_array($rows[0]) && $rows[0]['a'] === 1, 'Array Foreign stays arrays');
  $anyList = $GLOBALS['Adapt_arrayAny'];
  check(\\is_array($anyList) && $anyList[0] instanceof \\stdClass, 'Array Any maps records');
  check(\\is_array($GLOBALS['Adapt_objectValue']), 'Object stays an array');
  $captured = null;
  $GLOBALS['Adapt_promiseRecord']->then(function($value) use (&$captured) {
    $captured = $value;
    return function() {};
  }, null);
  check($captured instanceof \\stdClass && $captured->a === 1, 'Promise payload record');
  $captured = null;
  $GLOBALS['Adapt_effectPromise']()->then(function($value) use (&$captured) {
    $captured = $value;
    return function() {};
  }, null);
  check($captured instanceof \\stdClass && $captured->a === 1, 'Effect Promise payload record');
  echo "ok\\n";
}
`;

const result = spawnSync('php', ['-d', 'opcache.enable_cli=0'], {
  input: withRuntime(code),
  encoding: 'utf8',
  timeout: 10000,
});
assert.equal(result.status, 0, result.stdout + result.stderr);
assert.equal(result.stderr, '');
assert.equal(result.stdout, 'ok\n');
console.log('foreign-adaptation: direct, Promise, Effect, Foreign and Object returns passed');
