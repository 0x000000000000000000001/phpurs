// Golden case: a self-recursive tail call must print the TCO loop protocol.
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Just } from '../../../output/Data.Maybe/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { translate } from '../../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';

export const name = 'tco';

const int = T.Int.value;
const q = (identifier, moduleName = 'Demo') => new T.Qualified(new Just(moduleName), identifier);
const local = (level) => new S.Typed(int, new S.Local(new Just('x' + level), level));
const lit = (value) => new S.Lit(new T.LitInt(value));
const typed = (type, expression) => new S.Typed(type, expression);
const gt = (left, right) => new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpGt.value), left, right));
const sub = (left, right) =>
  new S.PrimOp(new S.Op2(new S.OpIntNum(S.OpSubtract.value), left, right));
const branch = (condition, yes, no) => new S.Branch([new S.Pair(condition, yes)], no);
const call = (identifier, args) => new S.App(new S.Var(q(identifier)), args);
const fn = (identifier, body) =>
  new Tuple(
    identifier,
    typed(new T.Func([int], int), new S.Abs([new Tuple(new Just('x0'), 0)], typed(int, body))),
  );

const loop = fn(
  'loop',
  branch(gt(local(0), lit(0)), call('loop', [sub(local(0), lit(1))]), lit(0)),
);

const module = {
  name: 'Demo',
  bindings: [{ recursive: true, bindings: [loop] }],
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
