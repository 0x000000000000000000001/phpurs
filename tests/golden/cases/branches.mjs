// Golden case: literal alternatives become a PHP switch, other branches stay
// as if statements.
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Just } from '../../../output/Data.Maybe/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { translate } from '../../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';

export const name = 'branches';

const int = T.Int.value;
const typed = (type, expression) => new S.Typed(type, expression);
const local = (level) => typed(int, new S.Local(new Just('x' + level), level));
const lit = (value) => typed(int, new S.Lit(new T.LitInt(value)));
const equals = (left, right) => new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpEq.value), left, right));
const greater = (left, right) => new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpGt.value), left, right));
const branch = (cases, fallback) =>
  new S.Branch(
    cases.map(([condition, body]) => new S.Pair(condition, body)),
    fallback,
  );
const fn = (name, body) =>
  new Tuple(
    name,
    typed(new T.Func([int], int), new S.Abs([new Tuple(new Just('x0'), 0)], typed(int, body))),
  );

const classify = fn(
  'classify',
  branch(
    [
      [equals(local(0), lit(0)), lit(10)],
      [equals(local(0), lit(1)), lit(20)],
    ],
    lit(30),
  ),
);
const sign = fn('sign', branch([[greater(local(0), lit(0)), lit(1)]], lit(-1)));

const module = {
  name: 'Demo',
  bindings: [classify, sign].map((binding) => ({ recursive: false, bindings: [binding] })),
  dataDecls: [],
  foreign: emptyMap,
  exports: emptySet,
  imports: emptySet,
  dataTypes: emptyMap,
  comments: [],
  reExports: emptySet,
  classDecls: [],
  implementations: emptyMap,
  directives: emptyMap,
};

export function render() {
  const file = translate([])(module);
  return { 'Demo.php': printPhpFile(false)('')(file.arities)(file) };
}
