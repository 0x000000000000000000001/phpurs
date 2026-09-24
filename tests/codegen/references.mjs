// Runtime references: PrimEffect must reach the phpurs_ref_* runtime helpers,
// execute correctly, and contribute free variables to deferred effects.
// Run after npm run build.
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { empty as emptyMap } from '../../output/Data.Map/index.js';
import { empty as emptySet } from '../../output/Data.Set/index.js';
import { Just } from '../../output/Data.Maybe/index.js';
import { Tuple } from '../../output/Data.Tuple/index.js';
import { translate } from '../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../output/Phpurs.Printer/index.js';

const int = T.Int.value;
const i = n => new S.Lit(new T.LitInt(n));
const local = (name, lvl) => new S.Local(new Just(name), lvl);
const add = (a, b) => new S.PrimOp(new S.Op2(new S.OpIntNum(S.OpAdd.value), a, b));
const refNew = v => new S.PrimEffect(new S.EffectRefNew(v));
const refRead = r => new S.PrimEffect(new S.EffectRefRead(r));
const refWrite = (r, v) => new S.PrimEffect(new S.EffectRefWrite(r, v));
const defer = e => new S.EffectDefer(e);
const typed = (ty, expr) => new S.Typed(ty, expr);
const fn = (name, args, ret, body) => new Tuple(name, typed(new T.Func(args, ret),
  new S.Abs(args.map((_, n) => new Tuple(new Just('x' + n), n)), typed(ret, body))));
const mod = (entry) => ({
  name: 'Demo', bindings: [{ recursive: false, bindings: [entry] }],
  dataDecls: [], foreign: emptyMap, exports: emptySet, imports: emptySet, dataTypes: emptyMap,
  comments: [], reExports: emptySet, classDecls: [], implementations: emptyMap, directives: emptyMap,
});

// run n = do
//   r <- defer (refNew n)
//   v <- defer (refRead r)
//   _ <- defer (refWrite r (v + 1))
//   pure (v + 1)
const arg = local('x0', 0);
const r = local('r', 1);
const v = local('v', 2);
const body = defer(new S.EffectBind(new Just('r'), 1, defer(refNew(arg)),
  new S.EffectBind(new Just('v'), 2, defer(refRead(r)),
    new S.EffectBind(new Just('_'), 3, defer(refWrite(r, add(v, i(1)))),
      new S.EffectPure(add(v, i(1)))))));
const file = translate([])(mod(fn('run', [int], int, body)));
const php = printPhpFile(false)('')(file.arities)(file);

assert.match(php, /function phpurs_ref_new\(\$value\)/, 'the runtime defines the ref helpers');
assert.match(php, /phpurs_ref_new\(/, 'refNew reaches the runtime');
assert.match(php, /phpurs_ref_read\(/, 'refRead reaches the runtime');
assert.match(php, /phpurs_ref_write\(/, 'refWrite reaches the runtime');
assert.doesNotMatch(php, /TODO_PrimEffect/, 'no placeholder survives');
assert.match(php, /use \(\$x0_0, &\$__fn\)\s*\{\s*return phpurs_ref_new\(\$x0_0\)/, 'deferred ref effects capture their variables');

const run = spawnSync('php', [], {
  input: php + `\n$GLOBALS['Data_Unit_unit'] = null;\n$effect = \\Demo\\majDemo_run(41);\nif ($effect() !== 42) throw new \\Exception('ref result');\necho "Done\\n";`,
  encoding: 'utf8',
});
assert.equal(run.status, 0, run.stdout + run.stderr);
assert.equal(run.stdout, 'Done\n');

console.log('references: ref primitives reach the runtime, execute, and capture free variables');
