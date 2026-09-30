// EffectFn bodies execute once and return their result unchanged, including
// deferred effects and cancelers. Type annotations must not change that boundary.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Just } from '../../output/Data.Maybe/index.js';
import { Tuple } from '../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../output/Data.Map/index.js';
import { empty as emptySet } from '../../output/Data.Set/index.js';
import { translate } from '../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../output/Phpurs.Printer/index.js';

const local = (name, level) => new S.Local(new Just(name), level);
const global = name => new S.Var(new T.Qualified(new Just('EffectResults'), name));
const string = value => new S.Lit(new T.LitString(value));
const typed = value => new S.Typed(T.Any.value, value);
const typeApplied = value => new S.TypeApp(value, T.Any.value);
const mark = label => new S.UncurriedEffectApp(global('mark'), [string(label)]);
const innerEffect = new S.EffectDefer(mark('inner'));
const sequence = result => new S.EffectBind(new Just('ignored'), 1, mark('outer'), result);
const source = local('source', 0);
const callback = local('callback', 1);

const bodies = [
  ['explicit', sequence(new S.EffectPure(innerEffect))],
  ['typedResult', sequence(typed(new S.EffectPure(innerEffect)))],
  ['typedBody', typed(sequence(typed(new S.EffectPure(innerEffect))))],
  ['typeApplication', typeApplied(sequence(typeApplied(new S.EffectPure(innerEffect))))],
  ['opaque', source],
  ['typedOpaque', typed(source)],
  ['deferred', new S.EffectDefer(sequence(new S.EffectPure(innerEffect)))],
  ['uncurriedCall', new S.UncurriedEffectApp(source, [])],
  ['typedBind', new S.EffectBind(new Just('callback'), 1,
    typed(new S.UncurriedEffectApp(source, [])), typed(new S.EffectPure(callback)))],
];

const bindings = bodies.map(([name, body]) => new Tuple(name,
  new S.UncurriedEffectAbs([new Tuple(new Just('source'), 0)], body)));
// Ordinary effect construction also stays lazy when its body carries a type.
bindings.push(new Tuple('ordinaryEffect', typed(sequence(typed(new S.EffectPure(innerEffect))))));
// A pure callable result need not be a Closure: PHP also accepts function names.
bindings.push(new Tuple('callableString', new S.UncurriedEffectAbs([], new S.EffectPure(string('strlen')))));

const file = translate([])({
  name: 'EffectResults',
  bindings: [{ recursive: false, bindings }],
  dataDecls: [], foreign: emptyMap, exports: emptySet, imports: emptySet,
  dataTypes: emptyMap, comments: [], reExports: emptySet, classDecls: [],
  implementations: emptyMap, directives: emptyMap,
});
const ffi = `
$GLOBALS['Data_Unit_unit'] = null;
$GLOBALS['effectEvents'] = [];
$GLOBALS['EffectResults_mark'] = function($label) {
  $GLOBALS['effectEvents'][] = $label;
  return 41;
};
`;
const php = printPhpFile(false)(ffi)(file.arities)(file);
const names = JSON.stringify(bodies.map(([name]) => name));
const run = spawnSync('php', ['-d', 'opcache.enable_cli=0'], {
  input: php + `
error_reporting(E_ALL);
function check($condition, $message) {
  if (!$condition) throw new \\Exception($message);
}
check($GLOBALS['effectEvents'] === [], 'constructing effects must be lazy');
$source = function() {
  $GLOBALS['effectEvents'][] = 'outer';
  return function() {
    $GLOBALS['effectEvents'][] = 'inner';
    return 41;
  };
};
foreach (json_decode('${names}', true) as $name) {
  $GLOBALS['effectEvents'] = [];
  $result = $GLOBALS['EffectResults_' . $name]($source);
  check($GLOBALS['effectEvents'] === ['outer'], $name . ': execute only the outer effect');
  check($result instanceof \\Closure, $name . ': return the inner action');
  check($result() === 41 && $result() === 41, $name . ': returned action is reusable');
  check($GLOBALS['effectEvents'] === ['outer', 'inner', 'inner'], $name . ': effect order');
}
$GLOBALS['effectEvents'] = [];
$effect = $GLOBALS['EffectResults_ordinaryEffect'];
$first = $effect();
$second = $effect();
check($GLOBALS['effectEvents'] === ['outer', 'outer'], 'ordinary effect returns delayed actions');
check($first() === 41 && $second() === 41, 'ordinary effect can be run repeatedly');
check($GLOBALS['effectEvents'] === ['outer', 'outer', 'inner', 'inner'], 'ordinary effect order');
check($GLOBALS['EffectResults_callableString']() === 'strlen', 'callable string is a value');
echo "Done\n";
`,
  encoding: 'utf8',
});
assert.equal(run.status, 0, run.stdout + run.stderr);
assert.equal(run.stderr, '');
assert.equal(run.stdout, 'Done\n');
console.log('effect-results: delayed construction, callable results, annotations and repeated execution passed');
