// Golden case: an ADT with a nullary and a single-field constructor round-trips
// through the nullable-constructor layout and prints byte-for-byte.
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Just } from '../../../output/Data.Maybe/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { translate } from '../../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';

export const name = 'nullable';

const int = T.Int.value;
const tree = new T.ADT('Demo.Tree', ['Demo', 'Tree'], []);
const q = (name) => new T.Qualified(new Just('Demo'), name);
const local = (level, type = int) => new S.Typed(type, new S.Local(new Just('x' + level), level));
const lit = (value) => new S.Lit(new T.LitInt(value));
const branch = (condition, yes, no) => new S.Branch([new S.Pair(condition, yes)], no);
const tag = (ctor, expression) => new S.PrimOp(new S.Op1(new S.OpIsTag(q(ctor)), expression));
const ctor = (ctor, fields = []) =>
  new S.CtorSaturated(
    q(ctor),
    T.SumType.value,
    'Tree',
    ctor,
    fields.map((f, i) => new Tuple('value' + i, f)),
  );
const field = (ctor, expression) =>
  new S.Accessor(
    expression,
    new S.GetCtorField(q(ctor), T.SumType.value, 'Tree', ctor, 'value0', 0),
  );
const fn = (identifier, type, body) =>
  new Tuple(
    identifier,
    new S.Typed(type, new S.Abs([new Tuple(new Just('x0'), 0)], new S.Typed(type, body))),
  );

const make = fn(
  'make',
  new T.Func([int], tree),
  branch(
    new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpGt.value), local(0), lit(-1))),
    ctor('Box', [local(0)]),
    ctor('Empty'),
  ),
);
const read = fn(
  'read',
  new T.Func([tree], int),
  branch(
    tag('Empty', local(0, tree)),
    lit(-1),
    branch(tag('Box', local(0, tree)), field('Box', local(0, tree)), new S.Fail('unreachable')),
  ),
);
const score = fn(
  'score',
  new T.Func([int], int),
  new S.App(new S.Var(q('read')), [new S.Typed(tree, new S.App(new S.Var(q('make')), [local(0)]))]),
);

const module = {
  name: 'Demo',
  bindings: [new Tuple('Empty', ctor('Empty')), make, read, score].map((binding) => ({
    recursive: false,
    bindings: [binding],
  })),
  dataDecls: [
    {
      name: 'Tree',
      vars: [],
      constructors: [
        { name: 'Empty', fields: [] },
        { name: 'Box', fields: [int] },
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
