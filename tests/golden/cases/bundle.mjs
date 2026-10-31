// Golden case: a bundle prefixes the shared runtime and inlines the module.
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Just } from '../../../output/Data.Maybe/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { translate } from '../../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';
import { bundleHeader } from '../../../output/Phpurs.Printer.Runtime/index.js';

export const name = 'bundle';

const int = T.Int.value;
const q = (name) => new T.Qualified(new Just('Demo'), name);
const fn = (identifier, body) =>
  new Tuple(identifier, new S.Typed(new T.Func([], int), new S.Abs([], new S.Typed(int, body))));
const main = fn(
  'main',
  new S.App(new S.Var(q('add')), [new S.Lit(new T.LitInt(1)), new S.Lit(new T.LitInt(2))]),
);

const module = {
  name: 'Demo',
  bindings: [{ recursive: false, bindings: [main] }],
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
  return {
    'bundle.php': bundleHeader + printPhpFile(true)('')(file.arities)(file),
  };
}
