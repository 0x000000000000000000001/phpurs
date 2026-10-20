// Nullary constructors share one lazily initialized singleton per module.
// Two modules declaring the same constructor name must not share the slot.
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
import { withRuntime, moduleBlock } from './fixtures/runtime.mjs';

const int = T.Int.value;
const typed = (ty, expr) => new S.Typed(ty, expr);
const fn = (moduleName, adt, body) => new Tuple('make', typed(new T.Func([int], adt),
  new S.Abs([new Tuple(new Just('x'), 0)], body)));
const module_ = name => {
  const adt = new T.ADT(`${name}.Box`, [name, 'Box'], []);
  return {
    name,
    bindings: [
      { recursive: false, bindings: [new Tuple('Nil', typed(adt, new S.CtorDef(T.SumType.value, 'Box', 'Nil', [])))] },
      { recursive: false, bindings: [fn(name, adt, typed(adt, new S.CtorSaturated(
        new T.Qualified(new Just(name), 'Nil'), T.SumType.value, 'Box', 'Nil', [])))] },
    ],
    dataDecls: [{ name: 'Box', vars: [], constructors: [{ name: 'Nil', fields: [] }] }],
    foreign: emptyMap, exports: emptySet, imports: emptySet, dataTypes: emptyMap,
    comments: [], reExports: emptySet, classDecls: [], implementations: emptyMap, directives: emptyMap,
  };
};
const fileA = translate([])(module_('A'));
const fileB = translate([])(module_('B'));
const a = printPhpFile(false)('')(fileA.arities)(fileA);
const b = printPhpFile(false)('')(fileB.arities)(fileB);
assert.match(a, /__phpurs_data0_A_Nil/);
assert.doesNotMatch(a, /__phpurs_data0_B_Nil/);
assert.match(b, /__phpurs_data0_B_Nil/);
assert.doesNotMatch(b, /__phpurs_data0_A_Nil/);
const combined = withRuntime(a) + '\n' + moduleBlock(b);
const run = spawnSync('php', ['-d', 'opcache.enable_cli=0'], {
  input: combined + `\nnamespace {
$a1 = \\A\\majA_make(0); $b1 = \\B\\majB_make(0);
if (get_class($a1) !== 'A\\A_Nil' || get_class($b1) !== 'B\\B_Nil') throw new \\Exception('collision');
if (get_class($GLOBALS['A_Nil']) !== 'A\\A_Nil' || get_class($GLOBALS['B_Nil']) !== 'B\\B_Nil') throw new \\Exception('singleton class');
if ($GLOBALS['A_Nil'] === $GLOBALS['B_Nil']) throw new \\Exception('shared singleton');
echo "Done\\n";
}`, encoding: 'utf8' });
assert.equal(run.status, 0, run.stdout + run.stderr);
assert.equal(run.stderr, '');
assert.equal(run.stdout, 'Done\n');
console.log('nullary-constructors: per-module singleton class and Nil collision isolation passed');
