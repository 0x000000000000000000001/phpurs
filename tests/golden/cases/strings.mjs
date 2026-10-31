// Golden case: PHP string escaping and character literals.
import * as S from '../../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Just } from '../../../output/Data.Maybe/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { translate } from '../../../output/Phpurs.CodeGen/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';

export const name = 'strings';

const typed = (type, expression) => new S.Typed(type, expression);
const text = 'quote " backslash \\ dollar $var newline \n tab \t single \' end';
const bindings = [
  new Tuple('text', typed(T.String.value, new S.Lit(new T.LitString(text)))),
  new Tuple('letter', typed(T.Char.value, new S.Lit(new T.LitChar('é')))),
  new Tuple(
    'echo',
    typed(
      new T.Func([T.String.value], T.String.value),
      new S.Abs(
        [new Tuple(new Just('value'), 0)],
        typed(T.String.value, new S.Local(new Just('value'), 0)),
      ),
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
