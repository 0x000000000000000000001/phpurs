// Golden case: inline FFI exports and their public wrappers.
import * as T from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { Just } from '../../../output/Data.Maybe/index.js';
import { Tuple } from '../../../output/Data.Tuple/index.js';
import { fromFoldable } from '../../../output/Data.Map/index.js';
import { foldableArray } from '../../../output/Data.Foldable/index.js';
import { ordIdent } from '../../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import { empty as emptySet } from '../../../output/Data.Set/index.js';
import { empty as emptyMap } from '../../../output/Data.Map/index.js';
import { genForeignModule } from '../../../output/Phpurs.GenNativeForeign/index.js';
import { printPhpFile } from '../../../output/Phpurs.Printer/index.js';

export const name = 'ffi';

const int = T.Int.value;
const bindings = fromFoldable(ordIdent)(foldableArray)([
  new Tuple('add', new Just(new T.Func([int, int], int))),
  new Tuple('label', new Just(T.String.value)),
]);

const file = {
  namespace: ['Demo'],
  imports: [],
  decls: [],
  rawDecls: [],
  arities: emptyMap,
};

export function render() {
  const foreign = genForeignModule({
    moduleName: 'Demo',
    bindings,
    source: `<?php
$exports['add'] = function($a, $b) { return $a + $b; };
$exports['label'] = 'demo';
`,
  });
  return {
    'Demo.php': printPhpFile(false)(foreign.code)(foreign.arities)({
      ...file,
      exports: emptySet,
    }),
  };
}
