// Golden case: ADT constructors, tag tests and branches must print identically.
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Just } from '../../../output/Data.Maybe/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { translate } from '../../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';

export const name = 'adt';

const int = T.Int.value;
const color = new T.ADT('Demo.Color', ['Demo', 'Color'], []);
const q = (identifier, moduleName = 'Demo') => new T.Qualified(new Just(moduleName), identifier);
const local = (level, type = int) => new S.Typed(type, new S.Local(new Just('x' + level), level));
const lit = (value) => new S.Lit(new T.LitInt(value));
const typed = (type, expression) => new S.Typed(type, expression);
const call = (identifier, args) => new S.App(new S.Var(q(identifier)), args);
const constructor = (tag) => new S.CtorSaturated(q(tag), T.SumType.value, 'Color', tag, []);
const branch = (condition, yes, no) => new S.Branch([new S.Pair(condition, yes)], no);
const gt = (left, right) => new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpGt.value), left, right));
const fn = (identifier, args, ret, body) =>
  new Tuple(
    identifier,
    typed(
      new T.Func(args, ret),
      new S.Abs(
        args.map((_, level) => new Tuple(new Just('x' + level), level)),
        typed(ret, body),
      ),
    ),
  );

const choose = fn(
  'choose',
  [int],
  color,
  branch(gt(local(0), lit(0)), constructor('Red'), constructor('Blue')),
);
const read = fn(
  'read',
  [color],
  int,
  branch(new S.PrimOp(new S.Op1(new S.OpIsTag(q('Red')), local(0, color))), lit(1), lit(2)),
);
const score = fn('score', [int], int, call('read', [typed(color, call('choose', [local(0)]))]));

const module = {
  name: 'Demo',
  bindings: [choose, read, score].map((binding) => ({ recursive: false, bindings: [binding] })),
  dataDecls: [
    {
      name: 'Color',
      vars: [],
      constructors: [
        { name: 'Red', fields: [] },
        { name: 'Blue', fields: [] },
      ],
    },
  ],
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
