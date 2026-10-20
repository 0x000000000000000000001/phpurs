// The module preamble must contain only runtime pieces referenced by the body.
import assert from 'node:assert/strict';
import * as A from '../../output/Phpurs.PhpAst/index.js';
import { printPhpFile } from '../../output/Phpurs.Printer/index.js';
import { empty, insert } from '../../output/Data.Map/index.js';
import { ordString } from '../../output/Data.Ord/index.js';

const render = (decls, arities = empty) => printPhpFile(false)('')(arities)({
  namespace: ['Demo'], imports: [], decls, rawDecls: [], arities,
});
const fn = { identifier: 'Demo_fn', expression: new A.PhpNativeFunction('Demo_fn', [{ name: 'x', type_: '' }], '', [new A.PhpReturn(new A.PhpVar('x'))]) };
const arities = insert(ordString)('Demo_fn')(1)(empty);
const raw = text => ({ identifier: 'Demo_raw', expression: new A.PhpRaw(text) });

const emptyPhp = render([]);
assert.doesNotMatch(emptyPhp, /Phpurs_Data/);
assert.doesNotMatch(emptyPhp, /phpurs_curry_fallback/);
assert.doesNotMatch(emptyPhp, /phpurs_execute_effect/);
assert.doesNotMatch(emptyPhp, /phpurs_ref_new/);

const nativePhp = render([fn], arities);
assert.match(nativePhp, /phpurs_curry_fallback/);
assert.doesNotMatch(nativePhp, /Phpurs_Data/);
assert.doesNotMatch(nativePhp, /phpurs_execute_effect/);
assert.doesNotMatch(nativePhp, /phpurs_ref_new/);

const effectPhp = render([raw('phpurs_execute_effect(null)')]);
assert.match(effectPhp, /phpurs_execute_effect/);
assert.doesNotMatch(effectPhp, /Phpurs_Data/);

const refsPhp = render([raw('phpurs_ref_new(1)')]);
assert.match(refsPhp, /phpurs_ref_new/);
assert.match(refsPhp, /phpurs_ref_read/);
assert.match(refsPhp, /phpurs_ref_write/);

const dataPhp = render([raw('new \\Demo\\Phpurs_Data2("x", null)')]);
assert.match(dataPhp, /class Phpurs_Data0/);
assert.match(dataPhp, /class Phpurs_Data12/);
console.log('runtime-preamble: selective data classes, curry, effect and reference helpers passed');
