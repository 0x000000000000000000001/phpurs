// Golden case: array, nested-array and record literals.
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Nothing } from '../../../output/Data.Maybe/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { translate } from '../../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';

export const name = 'arrays';

const int = T.Int.value;
const boolean = T.Boolean.value;
const ints = new T.Array(int);
const intsOfInts = new T.Array(ints);
const pair = new T.Record(
  new T.Row([new Tuple('left', int), new Tuple('right', boolean)], Nothing.value),
);
const lit = (type, value) => new S.Typed(type, new S.Lit(value));
const bindings = [
  new Tuple('list', lit(ints, new T.LitArray([1, 2, 3].map((n) => lit(int, new T.LitInt(n)))))),
  new Tuple(
    'matrix',
    lit(
      intsOfInts,
      new T.LitArray([
        lit(ints, new T.LitArray([lit(int, new T.LitInt(1))])),
        lit(ints, new T.LitArray([lit(int, new T.LitInt(2)), lit(int, new T.LitInt(3))])),
      ]),
    ),
  ),
  new Tuple(
    'pair',
    lit(
      pair,
      new T.LitRecord([
        new T.Prop('left', lit(int, new T.LitInt(1))),
        new T.Prop('right', lit(boolean, new T.LitBoolean(true))),
      ]),
    ),
  ),
];

const module = {
  name: 'Demo',
  bindings: bindings.map((binding) => ({ recursive: false, bindings: [binding] })),
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
