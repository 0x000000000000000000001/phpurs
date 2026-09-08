// Run after building PHPurs. Execute printer output to preserve cases that
// PBO could otherwise inline away, including PHP's capture/callable boundary.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { genCurry } from '../../output/Phpurs.Printer/index.js';
import { PhpRaw, PhpReturn } from '../../output/Phpurs.PhpAst/index.js';
import { empty } from '../../output/Data.Map/index.js';

const generate = (captures, body, args = ['x']) => {
  const at = body.indexOf('return ');
  const stmts = at < 0 ? [new PhpRaw(body)] : [
    ...(at ? [new PhpRaw(body.slice(0, at))] : []),
    new PhpReturn(new PhpRaw(body.slice(at + 7))),
  ];
  return genCurry('Test')(empty)(args.map(name => ({ name, type_: '' })))('')(captures)(stmts);
};
const captured = generate(['seed'], 'return $seed + $x');
const reference = generate(['&seed'], 'return $seed + $x');
const nested = generate(['seed'], `return ${generate(['seed', 'x'], 'return $seed + $x + $y', ['y'])}`);
const partial = generate(['seed'], 'return $seed + $x + $y', ['x', 'y']);
const recursive = generate(['seed', '&go'], 'return $x <= 0 ? $seed : $go($x - 1) + 1');
const mutate = generate(['seed'], '$seed++; return $seed + $x');
const array = generate(['items'], '$items[] = $x; return $items');
const object = generate(['ref'], '$ref->value += $x; return $ref->value');
const throws = generate(['seed'], 'throw new \\RuntimeException((string)$seed)');
const effect = generate(['seed'], 'return $seed', []);
assert.ok(reference.startsWith('function('));
assert.ok(!partial.startsWith('new class('));
assert.ok(!effect.startsWith('new class('));
assert.ok(generate([], 'return $x').startsWith('function('));

const output = process.argv[2] ?? '/Users/0x1/Documents/htdocs/altbak.pub-phpurs/run/bak/php/output';
const php = `<?php
namespace Test;
require ${JSON.stringify(output + '/Test.LazyEvaluation/index.php')};
function phpurs_curry_fallback($fn, $args, $expected) {
  return \\Test\\LazyEvaluation\\phpurs_curry_fallback($fn, $args, $expected);
}
set_error_handler(function($level, $message, $file, $line) { throw new \\ErrorException($message, 0, $level, $file, $line); });
$seed = 7; $a = ${captured}; $seed = 11; $b = ${captured};
$refCapture = ${reference}; $seed = 20;
$go = ${recursive};
$multi = ${partial}; $p = $multi(1);
$chain = ${nested};
$changing = ${mutate};
$items = [1]; $array = ${array};
$ref = (object)['value' => 3]; $obj = ${object};
$thrower = ${throws};
$error = ''; try { $thrower(0); } catch (\\RuntimeException $e) { $error = $e->getMessage(); }
// Current Foreign/Aff FFI distinguishes Closure from ordinary objects.
if (!($a instanceof \\Closure)) throw new \\RuntimeException('Captured functions must retain the Closure FFI contract');
$results = [$a(3), $b(3), $a(3), $refCapture(1), $go(4), $p(2), $multi(1, 2),
  $chain(1, 2), $chain(1)(2), $changing(0), $changing(0), $array(2), $array(3),
  $obj(2), $obj(4), is_callable($a), call_user_func($a, 2), array_map($a, [1, 2]), $error];
echo json_encode($results, JSON_THROW_ON_ERROR);
`;
const result = spawnSync('php', ['-d', 'opcache.enable_cli=0'], { input: php, encoding: 'utf8' });
assert.equal(result.status, 0, result.stderr);
assert.equal(result.stderr, '');
assert.deepEqual(JSON.parse(result.stdout), [10, 14, 10, 21, 24, 23, 23, 23, 23, 21, 21,
  [1, 2], [1, 3], 5, 9, true, 9, [8, 9], '20']);
console.log('unary-callables: captures, repeated calls, currying, overapplication, recursion, FFI callbacks and exceptions passed');
