import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as C from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { analyze } from '../../output/PureScript.Backend.Optimizer.Codegen.Tco/index.js';
import { initialContext, translateExpr } from '../../output/Phpurs.CodeGen/index.js';
import { printExpr } from '../../output/Phpurs.Printer/index.js';
import { PhpAssign, PhpArray, PhpFunction, PhpInt } from '../../output/Phpurs.PhpAst/index.js';
import { empty } from '../../output/Data.Map/index.js';
import { Just } from '../../output/Data.Maybe/index.js';
import { Tuple } from '../../output/Data.Tuple/index.js';

const int = value => new S.Lit(new C.LitInt(value));
const local = (name, level) => new S.Local(new Just(name), level);
const array = values => new S.Lit(new C.LitArray(values));
const letValue = value => new S.Let(new Just('x'), 0, int(value), local('x', 0));
const context = initialContext('Fixture');

test('operand accumulator: copied prefixes stay linear for wide values and statements', () => {
  // Deterministic allocation regression, independent of noisy wall-clock limits.
  // Analysis is outside the probe; only code generation contributes copies.
  for (const make of [int, letValue]) {
    const width = 4096;
    const input = analyze([])(array(Array.from({ length: width }, (_, i) => make(i))));
    const slice = Array.prototype.slice;
    const concat = Array.prototype.concat;
    let copied = 0;
    Array.prototype.slice = function (...args) { copied += this.length; return slice.apply(this, args); };
    Array.prototype.concat = function (...args) { copied += this.length; return concat.apply(this, args); };
    let output;
    try { output = translateExpr(context)(17)(input); }
    finally { Array.prototype.slice = slice; Array.prototype.concat = concat; }
    assert.ok(copied <= 4 * width, `${make.name}: copied ${copied} prefix items for ${width} operands`);
    assert.equal(output.expr.value0.length, width);
    assert.equal(output.nextId, 17 + (make === letValue ? width : 0));
    assert.equal(output.stmts.length, make === letValue ? width : 0);
  }
});

test('operand accumulator: empty, singleton and large arrays retain order and independent results', () => {
  const previous = [];
  for (const width of [0, 1, 100000]) {
    const input = analyze([])(array(Array.from({ length: width }, (_, i) => int(i))));
    // Neither input arrays nor nodes belong to the accumulator's mutable region.
    Object.freeze(input.value1.value0.value0);
    const output = translateExpr(context)(31)(input);
    assert.ok(output.expr instanceof PhpArray);
    assert.equal(output.nextId, 31);
    assert.deepEqual(output.stmts, []);
    assert.equal(output.expr.value0.length, width);
    output.expr.value0.forEach((value, i) => {
      assert.ok(value instanceof PhpInt);
      assert.equal(value.value0, i);
    });
    previous.push({ output, width });
  }
  for (const { output, width } of previous) assert.equal(output.expr.value0.length, width);
});

test('operand accumulator: sibling statements, temporary IDs and escaping captures preserve runtime order', () => {
  const closure = value => new S.Let(new Just('x'), 0,
    new S.App(local('mark', 99), [int(value)]),
    new S.Abs([new Tuple(new Just('arg'), 1)],
      new S.PrimOp(new S.Op2(new S.OpIntNum(S.OpAdd.value), local('x', 0), local('arg', 1)))));
  const operands = [closure(11), closure(22)];
  const forms = [array(operands), new S.UncurriedApp(local('collect', 98), operands),
    new S.App(local('curried', 97), operands)];
  for (const form of forms) {
    const result = translateExpr(context)(37)(analyze([])(form));
    assert.equal(result.nextId, 39);
    assert.equal(result.stmts.length, 2);
    assert.ok(result.stmts.every(stmt => stmt instanceof PhpAssign));
    assert.deepEqual(result.stmts.map(stmt => stmt.value0), ['x_0_37', 'x_0_38']);
    if (result.expr instanceof PhpArray) assert.ok(result.expr.value0.every(value => value instanceof PhpFunction));
    const php = result.stmts.map(printExpr(empty)).join(';\n') + ';\n$values = ' + printExpr(empty)(result.expr) + ';';
    const run = spawnSync('php', ['-d', 'opcache.enable_cli=0'], { encoding: 'utf8', input: `<?php
error_reporting(E_ALL);
$events = [];
$mark_99 = function ($value) use (&$events) { $events[] = $value; return $value; };
$collect_98 = function ($a, $b) { return [$a, $b]; };
$curried_97 = function ($a) { return function ($b) use ($a) { return [$a, $b]; }; };
${php}
echo json_encode([$events, $values[0](5), $values[1](5), $values[0](9), $values[1](9)], JSON_THROW_ON_ERROR);
` });
    assert.equal(run.status, 0, run.stdout + run.stderr);
    assert.equal(run.stderr, '');
    assert.deepEqual(JSON.parse(run.stdout), [[11, 22], 16, 27, 20, 31]);
  }
});
