// Golden case: record construction, field access and update must print
// identically, including the fused update path.
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Just, Nothing } from '../../../output/Data.Maybe/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { translate } from '../../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';

export const name = 'records';

const int = T.Int.value;
const row = new T.Record(new T.Row([new Tuple('value', int)], Nothing.value));
const local = (level, type = int) => new S.Typed(type, new S.Local(new Just('x' + level), level));
const lit = (value) => new S.Lit(new T.LitInt(value));
const typed = (type, expression) => new S.Typed(type, expression);
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

const make = fn('make', [int], row, new S.Lit(new T.LitRecord([new T.Prop('value', local(0))])));
const read = fn('read', [row], int, new S.Accessor(local(0, row), new S.GetProp('value')));
const bump = fn(
  'bump',
  [row],
  row,
  new S.Update(local(0, row), [
    new T.Prop(
      'value',
      new S.PrimOp(
        new S.Op2(
          new S.OpIntNum(S.OpAdd.value),
          new S.Accessor(local(0, row), new S.GetProp('value')),
          lit(1),
        ),
      ),
    ),
  ]),
);

const module = {
  name: 'Demo',
  bindings: [make, read, bump].map((binding) => ({ recursive: false, bindings: [binding] })),
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
