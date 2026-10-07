import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import { Just, Nothing } from '../../output/Data.Maybe/index.js';
import { empty, fromFoldable, union } from '../../output/Data.Map/index.js';
import { foldableArray } from '../../output/Data.Foldable/index.js';
import { ordString } from '../../output/Data.Ord/index.js';
import { Tuple } from '../../output/Data.Tuple/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import * as P from '../../output/Phpurs.PhpAst/index.js';
import { genForeignModule } from '../../output/Phpurs.GenNativeForeign/index.js';
import { printExpr, printPhpFile } from '../../output/Phpurs.Printer/index.js';
import { corefn, write } from './fixtures/cache-inputs.mjs';

const int = T.Int.value, any = T.Any.value;
const fn = new T.Func([int], new T.Func([int], int));
const entries = [
  ['add', new Just(fn)], ['unary', new Just(new T.Func([int], int))],
  ['polymorphic', new Just(new T.ForAll(['a'], new T.Func([new T.TypeVar('a')], new T.TypeVar('a'))))],
  ['value', new Just(int)], ["value'", new Just(any)], ['unknown', Nothing.value],
  ['raw', new Just(new T.ADT('Data.Function.Uncurried.Fn2', ['Data', 'Function', 'Uncurried', 'Fn2'], [int, int, int]))],
  ['action', new Just(new T.ADT('Effect.Effect', ['Effect', 'Effect'], [int]))],
  ...['nil', 'zero', 'no', 'text', 'items', 'record', 'callable'].map(name => [name, new Just(any)]),
];
const bindings = fromFoldable(T.ordIdent)(foldableArray)(entries.map(([name, type]) => new Tuple(name, type)));
const foreign = source => genForeignModule({ moduleName: 'Foreign.Values', bindings, source });
const global = name => new P.PhpGlobalVar(new Just(['Foreign', 'Values']), name);
const call = (name, args) => new P.PhpCall(global(name), args.map(n => new P.PhpInt(n)));
const helpers = `
function check($condition, $message) { if (!$condition) throw new \\Exception($message); }
function missing($name, $run) {
  try { $run(); } catch (\\RuntimeException $e) {
    check($e->getMessage() === 'Missing PHP FFI export: Foreign.Values.' . $name, $e->getMessage());
    return;
  }
  throw new \\Exception('missing diagnostic for ' . $name);
}
`;

function executeForeign(source, body) {
  const ffi = foreign(source);
  const expr = ast => printExpr(ffi.arities)(ast);
  const file = { namespace: ['Foreign', 'Values'], imports: [], decls: [], rawDecls: [], arities: empty };
  const code = `<?php
namespace {
  set_error_handler(function($level, $message, $file, $line) { throw new \\ErrorException($message, 0, $level, $file, $line); });
}
${printPhpFile(true)(ffi.code)(ffi.arities)(file)}
namespace Foreign\\Values { ${helpers}\n${body(expr)}\necho "Done\\n"; }
`;
  assert.doesNotMatch(code, /public function __invoke\(\.\.\.\$args\) \{ return \$this;/);
  const result = spawnSync('php', ['-d', 'opcache.enable_cli=0'], { input: code, encoding: 'utf8', timeout: 10000 });
  assert.equal(result.status, 0, result.stdout + result.stderr);
  assert.equal(result.stderr, '');
  assert.equal(result.stdout, 'Done\n');
}

for (const [label, source] of [
  ['absent file', ''], ['empty PHP', '<?php\n  \n'],
  ['missing keys', '<?php\n$exports["other"] = 1;'],
  ['runtime export condition', '<?php\nif (false) $exports["value"] = 1;'],
]) {
  test(`missing FFI: demand-driven diagnostics with ${label}`, () => {
    executeForeign(source, expr => `
      // Loading every declaration, taking a function and retaining a partial
      // application are all valid before the missing body is actually called.
      $f = ${expr(global('add'))};
      check(\\is_callable($f), 'public function');
      $partial = ${expr(call('add', [1]))};
      check($partial instanceof \\Closure, 'retained partial');
      missing('add', fn() => $partial(2));
      missing('add', fn() => $partial(3));
      missing('add', fn() => ${expr(call('add', [1, 2]))});
      missing('add', fn() => ${expr(new P.PhpCall(call('add', [1]), [new P.PhpInt(2)]))});
      missing('unary', fn() => ${expr(call('unary', [1]))});
      missing('polymorphic', fn() => ${expr(call('polymorphic', [1]))});
      missing('value', fn() => ${expr(global('value'))});
      missing("value'", fn() => ${expr(global("value'"))});
      missing('unknown', fn() => ${expr(global('unknown'))});
      missing('raw', fn() => ${expr(call('raw', [1, 2]))});
      missing('raw', fn() => ${expr(new P.PhpDirectCall('Foreign_Values_raw', [new P.PhpInt(1), new P.PhpInt(2)]))});
      missing('action', fn() => ${expr(new P.PhpCall(global('action'), []))});
      // A value read is strict even if its consumer would ignore the value.
      $ignore = fn($x) => 42;
      missing('value', fn() => $ignore(${expr(global('value'))}));
      check(false ? ${expr(global('value'))} : true, 'untaken branch');
      $read = fn() => ${expr(global('value'))};
      missing('value', $read);
      missing('value', $read);
    `);
  });
}

test('foreign values: null, raw callables, identity, prime names and native checks survive', () => {
  executeForeign(`<?php
    $GLOBALS['ffi_initializations'] = ($GLOBALS['ffi_initializations'] ?? 0) + 1;
    $exports['add'] = function($a, $b) { return $a + $b; };
    $exports['unary'] = null;
    $exports['polymorphic'] = function($x) { throw new \\LogicException('original exception'); };
    $exports['value'] = 41;
    $exports["value'"] = 42;
    $exports['nil'] = null;
    $exports['zero'] = 0;
    $exports['no'] = false;
    $exports['text'] = '';
    $exports['items'] = [];
    $exports['record'] = (object)['n' => 0];
    $exports['raw'] = function($a, $b) { return $a + $b; };
    $exports['action'] = function() { return ++$GLOBALS['effect_calls']; };
    $exports['callable'] = 'strlen';
  `, expr => `
    check(${expr(global('value'))} === 41 && ${expr(global("value'"))} === 42, 'foreign constants');
    check(${expr(global('nil'))} === null, 'exported null');
    check(${expr(global('zero'))} === 0 && ${expr(global('no'))} === false, 'falsey scalars');
    check(${expr(global('text'))} === '' && ${expr(global('items'))} === [], 'empty data');
    $record = ${expr(global('record'))}; $record->n = 4;
    check(${expr(global('record'))} === $record && $record->n === 4, 'object identity');
    check(${expr(global('raw'))} instanceof \\Closure, 'raw callable is still a Closure');
    check(${expr(call('raw', [2, 3]))} === 5, 'raw Fn2 arity');
    check(${expr(new P.PhpDirectCall('Foreign_Values_raw', [new P.PhpInt(3), new P.PhpInt(4)]))} === 7, 'direct raw Fn2');
    check(${expr(global('callable'))} === 'strlen', 'callable string remains data');
    check((${expr(global('callable'))})('abc') === 3, 'callable string invocation');
    $GLOBALS['effect_calls'] = 0;
    $action = ${expr(global('action'))};
    check($GLOBALS['effect_calls'] === 0, 'reading an action does not run it');
    check($action() === 1 && $action() === 2, 'repeated action');
    $partial = ${expr(call('add', [1]))};
    check($partial(2) === 3 && ${expr(call('add', [2, 3]))} === 5, 'valid functions');
    try { $GLOBALS['Foreign_Values_add'](PHP_INT_MAX, 1); throw new \\Exception('unchecked overflow'); }
    catch (\\TypeError $e) { check(\\str_contains($e->getMessage(), 'Return value'), 'native Int result check'); }
    try { $GLOBALS['Foreign_Values_add']([], 1); throw new \\Exception('unchecked argument'); }
    catch (\\TypeError $e) { check(\\str_contains($e->getMessage(), 'Argument #1'), 'native Int argument check'); }
    try { ${expr(call('unary', [1]))}; throw new \\Exception('null function was called'); }
    catch (\\Error $e) { check(!\\str_contains($e->getMessage(), 'Missing PHP FFI'), 'present null is not a missing key'); }
    try { ${expr(call('polymorphic', [1]))}; throw new \\Exception('exception swallowed'); }
    catch (\\LogicException $e) { check($e->getMessage() === 'original exception', 'FFI exception identity'); }
    check($GLOBALS['ffi_initializations'] === 1, 'FFI initialization once');
    missing('unknown', fn() => ${expr(global('unknown'))});
  `);
  // Ordinary arity-zero globals never get foreign getters. Qualified and local
  // reads of a marked value share the same diagnostic path.
  const ordinary = union(ordString)(foreign('').arities)(fromFoldable(ordString)(foldableArray)([new Tuple('Foreign_Values_plain', 0)]));
  assert.equal(printExpr(ordinary)(global('plain')), "$GLOBALS['Foreign_Values_plain']");
  assert.match(printExpr(ordinary)(new P.PhpGlobalVar(Nothing.value, 'Foreign_Values_value')), /\?\? majForeign_majValues_value\(\)/);
});

const cli = process.env.PHPURS_TEST_CLI || fileURLToPath(new URL('../../bin/phpurs.js', import.meta.url));
function fixture(t) {
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), 'phpurs-missing-ffi-')));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  const variable = (moduleName, identifier, type) => ({ type: 'Var', annotation: { type }, value: { moduleName: moduleName.split('.'), identifier } });
  const literal = value => ({ type: 'Literal', annotation: { type: 0 }, value: { literalType: 'IntLiteral', value } });
  const app = (abstraction, argument, type) => ({ type: 'App', annotation: { type }, abstraction, argument });
  const binding = (identifier, body) => ({ bindType: 'NonRec', identifier, annotation: { type: 1 },
    expression: { type: 'Abs', argument: 'n', body, annotation: { type: 1 } } });
  const library = corefn('Library', [], [], ['value', 'add']);
  library.foreignAnnotations.value = { type: 0 };
  const consumer = corefn('Consumer', ['Library'], [
    binding('read', variable('Library', 'value', 0)),
    binding('call', app(app(variable('Library', 'add', 2), literal(1), 1), literal(2), 0)),
  ], [], ['@inline export read never', '@inline export call never']);
  const main = corefn('Main', ['Consumer'], [], ['main']);
  main.foreignAnnotations.main = { type: 4 };
  const unused = corefn('Unused', [], [], ['value', 'add']);
  unused.foreignAnnotations.value = { type: 0 };
  for (const input of [library, consumer, main, unused]) write(root, `output/${input.moduleName.join('.')}/corefn.json`, JSON.stringify(input));
  write(root, 'src/Main.php', `<?php
    $exports['main'] = function() {
      $mode = getenv('FFI_MODE');
      if ($mode === 'read') echo $GLOBALS['Consumer_read'](0), "\\n";
      elseif ($mode === 'call') echo $GLOBALS['Consumer_call'](0), "\\n";
      else echo "Done\\n";
    };
  `);
  return root;
}
function build(root, args) {
  const result = spawnSync(process.execPath, [cli, '--verbose', ...args], { cwd: root, encoding: 'utf8', timeout: 30000 });
  assert.equal(result.status, 0, result.stdout + result.stderr);
  assert.doesNotMatch(result.stderr, /Failed to decode|Failed to read purmeta/);
  const stats = result.stderr.match(/cache: (\d+) hits, (\d+) misses, (\d+) stores/);
  return { counts: stats?.slice(1).map(Number) ?? null, generated: [...result.stdout.matchAll(/Generating PHP code for (\S+)/g)].map(m => m[1]) };
}
function snapshot(root) {
  return Object.fromEntries(fs.readdirSync(path.join(root, 'output'), { recursive: true })
    .filter(file => file.endsWith('.php')).sort().map(file => [file, fs.readFileSync(path.join(root, 'output', file), 'utf8')]));
}
function runEntries(root, mode, expected, missingName = null) {
  for (const entry of ['main.mod.php', 'main.bundle.php']) {
    const file = path.join(root, 'output/Main', entry);
    if (!fs.existsSync(file)) continue;
    const result = spawnSync('php', ['-d', 'opcache.enable_cli=0', file], {
      cwd: root, encoding: 'utf8', timeout: 10000, env: { ...process.env, FFI_MODE: mode },
    });
    assert.equal(result.stderr, '', result.stderr);
    assert.equal(result.status, missingName ? 1 : 0, result.stdout + result.stderr);
    if (missingName) assert.ok(result.stdout.startsWith(`FATAL: Missing PHP FFI export: ${missingName}\n`), result.stdout);
    else assert.equal(result.stdout, expected);
  }
}

for (const args of [['--bundle'], ['--bundle-only']]) {
  test(`missing FFI: unreachable exports, cached value metadata and source recovery ${args[0]}`, t => {
    const root = fixture(t);
    const checkMissing = () => {
      runEntries(root, 'none', 'Done\n');
      runEntries(root, 'read', null, 'Library.value');
      runEntries(root, 'call', null, 'Library.add');
    };
    assert.deepEqual(build(root, args).counts, [0, 4, 4]);
    checkMissing();
    const cold = snapshot(root);
    assert.deepEqual(build(root, args).counts, [4, 0, 0]);
    assert.deepEqual(snapshot(root), cold);
    checkMissing();

    fs.appendFileSync(path.join(root, 'output/Consumer/corefn.json'), '\n');
    const mixed = build(root, args);
    assert.ok(!mixed.generated.includes('Library') && mixed.generated.includes('Consumer'), JSON.stringify(mixed));
    assert.ok(mixed.counts[0] > 0 && mixed.counts[1] > 0);
    assert.deepEqual(snapshot(root), cold, 'restored foreign-value metadata reaches fresh consumers');
    checkMissing();
    assert.equal(build(root, [...args, '--no-cache']).counts, null);
    assert.deepEqual(snapshot(root), cold);

    for (const source of ['', '<?php\n$exports["unrelated"] = 7;']) {
      write(root, 'src/Library.php', source);
      assert.ok(build(root, args).generated.includes('Library'));
      checkMissing();
    }
    write(root, 'src/Library.php', '<?php\n$exports["value"] = 42; $exports["add"] = function($a, $b) { return $a + $b; };');
    build(root, args);
    runEntries(root, 'read', '42\n');
    runEntries(root, 'call', '3\n');
    runEntries(root, 'none', 'Done\n');
    const present = snapshot(root);
    assert.deepEqual(build(root, args).counts, [4, 0, 0]);
    assert.deepEqual(snapshot(root), present);
    build(root, [...args, '--no-cache']);
    assert.deepEqual(snapshot(root), present);

    fs.unlinkSync(path.join(root, 'src/Library.php'));
    assert.deepEqual(build(root, args).counts, [4, 0, 0], 'original absent-source states may be restored');
    assert.deepEqual(snapshot(root), cold);
    checkMissing();
  });
}

test('missing FFI: a foreign main uses the checked read in both entrypoints', t => {
  const root = fixture(t);
  fs.unlinkSync(path.join(root, 'src/Main.php'));
  build(root, ['--bundle']);
  runEntries(root, 'none', null, 'Main.main');
  assert.deepEqual(build(root, ['--bundle']).counts, [4, 0, 0]);
  runEntries(root, 'none', null, 'Main.main');
});

test('missing FFI: an evaluated initializer demands a value, an unloaded module does not', t => {
  const root = fixture(t);
  const unused = corefn('Unused', [], [{
    bindType: 'NonRec', identifier: 'eager', annotation: { type: 0 },
    expression: { type: 'Var', annotation: { type: 0 }, value: { moduleName: ['Unused'], identifier: 'value' } },
  }], ['value']);
  unused.foreignAnnotations.value = { type: 0 };
  write(root, 'output/Unused/corefn.json', JSON.stringify(unused));
  build(root, ['--bundle']);
  const run = entry => spawnSync('php', ['-d', 'opcache.enable_cli=0', path.join(root, 'output/Main', entry)], {
    cwd: root, encoding: 'utf8', timeout: 10000, env: { ...process.env, FFI_MODE: 'none' },
  });
  const modular = run('main.mod.php');
  assert.equal(modular.status, 0, modular.stdout + modular.stderr);
  assert.equal(modular.stdout, 'Done\n');
  assert.equal(modular.stderr, '');
  // The existing bundle policy includes every loaded module and executes its
  // initializers before the entrypoint startup. That evaluated read must fail.
  const bundled = run('main.bundle.php');
  assert.notEqual(bundled.status, 0);
  assert.match(bundled.stdout + bundled.stderr, /RuntimeException: Missing PHP FFI export: Unused\.value/);
  assert.doesNotMatch(bundled.stdout + bundled.stderr, /Undefined (array key|variable)|not callable|Return value must/);
});
