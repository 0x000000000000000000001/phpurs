// Golden case: Int and Number literal formatting.
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { translate } from '../../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';

export const name = 'numbers';

const int = (name, value) =>
  new Tuple(name, new S.Typed(T.Int.value, new S.Lit(new T.LitInt(value))));
const number = (name, value) =>
  new Tuple(name, new S.Typed(T.Number.value, new S.Lit(new T.LitNumber(value))));

const bindings = [
  int('zero', 0),
  int('negativeInt', -42),
  int('maxInt', 2147483647),
  number('half', 1.5),
  number('negative', -0.25),
  number('whole', 2.0),
  number('small', 0.000001),
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
