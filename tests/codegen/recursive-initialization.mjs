// Dictionary initialization may contain pure applications (e.g. a composed
// `pure` implementation). Construct that dictionary before invoking its getters.
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

const global = name => new S.Var(new T.Qualified(new Just('Init'), name));
const record = (name, value) => new S.Lit(new T.LitRecord([new T.Prop(name, value)]));
const readValue = new S.Accessor(global('dictionary'), new S.GetProp('value'));
const getter = new S.UncurriedAbs([], readValue);
const bindings = [
  new Tuple('reader', record('get', getter)),
  new Tuple('fromReader', new S.App(global('read'), [global('reader')])),
  // The dependency inside these callbacks matters: the callee can invoke them
  // during initialization, even though creating a closure by itself is safe.
  new Tuple('fromCallback', new S.App(global('invoke'), [getter])),
  new Tuple('fromUncurriedCallback', new S.UncurriedApp(global('invoke'), [getter])),
  new Tuple('dictionary', record('value', new S.App(global('identity'), [new S.Lit(new T.LitInt(41))]))),
];
const file = translate([])({
  name: 'Init', bindings: [{ recursive: true, bindings }],
  dataDecls: [], foreign: emptyMap, exports: emptySet, imports: emptySet,
  dataTypes: emptyMap, comments: [], reExports: emptySet, classDecls: [],
  implementations: emptyMap, directives: emptyMap,
});
const ffi = `
error_reporting(E_ALL);
set_error_handler(function($severity, $message, $file, $line) {
  throw new \\ErrorException($message, 0, $severity, $file, $line);
});
$GLOBALS['Init_identity'] = function($value) { return $value; };
$GLOBALS['Init_read'] = function($reader) { return ($reader->get)(); };
$GLOBALS['Init_invoke'] = function($callback) { return $callback(); };
`;
const php = printPhpFile(false)(ffi)(file.arities)(file);
const run = spawnSync('php', ['-d', 'opcache.enable_cli=0'], {
  input: php + `
foreach (['fromReader', 'fromCallback', 'fromUncurriedCallback'] as $name) {
  if ($GLOBALS['Init_' . $name] !== 41) throw new \\Exception($name);
}
echo "Done\n";
`,
  encoding: 'utf8',
});
assert.equal(run.status, 0, run.stdout + run.stderr);
assert.equal(run.stderr, '');
assert.equal(run.stdout, 'Done\n');
console.log('recursive-initialization: applied dictionary fields and dependencies inside callbacks passed');
