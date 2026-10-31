// Static $exports validation runs before emission; runtime guards stay as a fallback.
import assert from 'node:assert/strict';
import test from 'node:test';
import { Just } from '../../../output/Data.Maybe/index.js';
import { fromFoldable } from '../../../output/Data.Map/index.js';
import { foldableArray } from '../../../output/Data.Foldable/index.js';
import { ordString } from '../../../output/Data.Ord/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { genForeignModule } from '../../../output/Phpurs.GenNativeForeign/index.js';

const int = T.Int.value;
const fn = new T.Func([int], int);
const bindings = fromFoldable(ordString)(foldableArray)([
  new Tuple('add', new Just(fn)),
  new Tuple('value', new Just(int)),
]);
const errorsFor = (source) => genForeignModule({ moduleName: 'Check', bindings, source }).errors;

test('valid exports produce no errors', () => {
  assert.deepEqual(
    errorsFor(`<?php
$exports['add'] = function($a) { return $a; };
$exports['value'] = 1;`),
    [],
  );
});

test('helper exports are allowed and missing keys stay runtime diagnostics', () => {
  assert.deepEqual(
    errorsFor(`<?php
$exports['add'] = function($a) { return $a; };`),
    [],
    'missing imports are reported by the generated runtime wrapper',
  );
  assert.deepEqual(
    errorsFor(`<?php
$exports['add'] = function($a) { return $a; };
$exports['value'] = 1;
$exports['helper'] = 2;`),
    [],
    'extra PHP helpers are not foreign imports',
  );
});

test('arity mismatch is reported', () => {
  const errors = errorsFor(`<?php
$exports['add'] = function($a, $b) { return $a; };
$exports['value'] = 1;`);
  assert.equal(errors.length, 1);
  assert.match(errors[0], /Check\.add: expected 1 parameters, found 2/);
});

test('strict types and namespace are rejected in fragments', () => {
  assert.match(
    errorsFor(`<?php
declare(strict_types=1);
$exports['add'] = function($a) { return $a; };
$exports['value'] = 1;`)[0],
    /must not contain `declare\(strict_types=1\)`/,
  );
  assert.match(
    errorsFor(`<?php
namespace Foo;
$exports['add'] = function($a) { return $a; };
$exports['value'] = 1;`)[0],
    /must not contain `namespace`/,
  );
});

test('conditional exports keep runtime demand-driven behavior', () => {
  assert.deepEqual(
    errorsFor(`<?php
if (false) $exports['add'] = function($a) { return $a; };
$exports['value'] = 1;`),
    [],
  );
});
