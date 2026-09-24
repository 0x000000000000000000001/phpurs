// Direct PHP AST tests for the ownership pass: a private worker parameter is
// promoted to a reference (and its rebuild becomes an in-place update) only
// when every call site can hand over ownership. A call site that cannot (a
// literal, a call result, a live binding) keeps the parameter by value, so
// shared values are never mutated.
import assert from 'node:assert/strict';
import * as P from '../../output/Phpurs.PhpAst/index.js';
import {optimize} from '../../output/Phpurs.ArrayRefs/index.js';
import {fromFoldable as setFromFoldable} from '../../output/Data.Set/index.js';
import {fromFoldable as mapFromFoldable} from '../../output/Data.Map/index.js';
import {foldableArray} from '../../output/Data.Foldable/index.js';
import {ordInt, ordString} from '../../output/Data.Ord/index.js';
import {Just} from '../../output/Data.Maybe/index.js';
import {Tuple} from '../../output/Data.Tuple/index.js';

const INS = 'Test___phpurs_enum_0_ins';
const T_CLASS = '\\Test\\Test___phpurs_enum_0_T';

const candidates = mapFromFoldable(ordString)(foldableArray)([new Tuple(INS, setFromFoldable(foldableArray)(ordInt)([1]))]);
const privateClasses = setFromFoldable(foldableArray)(ordString)([T_CLASS]);

// The worker shape produced for the RBTree `ins`: a TCO loop header, a
// recursive call on an owned field, then a rebuild from the node's own fields.
function insDecl(extraCall = null) {
  const recursiveCall = new P.PhpCall(
    new P.PhpCall(new P.PhpGlobalVar(new Just(['Test']), '__phpurs_enum_0_ins'), [new P.PhpVar('v_0')]),
    [new P.PhpPropertyAccess(new P.PhpVar('v1_1'), 'value1')],
  );
  const body = [
    new P.PhpLabel('tco_loop_Test___phpurs_enum_0_ins'),
    new P.PhpAssign('v_0', new P.PhpVar('__tco_var_Test___phpurs_enum_0_ins_v_0')),
    new P.PhpAssign('v1_1', new P.PhpVar('__tco_var_Test___phpurs_enum_0_ins_v1_1')),
    new P.PhpIf(
      new P.PhpBinOp('===', new P.PhpVar('v1_1'), new P.PhpRaw('null')),
      [new P.PhpReturn(new P.PhpVar('v1_1'))],
      [],
    ),
    new P.PhpAssign('__phpurs_inline_0_0_v_0', new P.PhpPropertyAccess(new P.PhpVar('v1_1'), 'value0')),
    new P.PhpAssign('__phpurs_inline_0_0_v1_1', recursiveCall),
    new P.PhpAssign('__phpurs_inline_0_0_v2_2', new P.PhpPropertyAccess(new P.PhpVar('v1_1'), 'value2')),
    new P.PhpAssign('__phpurs_inline_0_0_v3_3', new P.PhpPropertyAccess(new P.PhpVar('v1_1'), 'value3')),
    new P.PhpReturn(new P.PhpNew(T_CLASS, [
      new P.PhpVar('__phpurs_inline_0_0_v_0'),
      new P.PhpVar('__phpurs_inline_0_0_v1_1'),
      new P.PhpVar('__phpurs_inline_0_0_v2_2'),
      new P.PhpVar('__phpurs_inline_0_0_v3_3'),
    ])),
  ];
  return new P.PhpPrivateFunction(INS, [{name: 'v_0', type_: ''}, {name: 'v1_1', type_: ''}], '', body);
}

// A caller that hands over a literal cannot be trusted, so the parameter must
// stay by value and the rebuild must stay an allocation.
function actDecl(value) {
  const call = new P.PhpCall(
    new P.PhpCall(new P.PhpGlobalVar(new Just(['Test']), '__phpurs_enum_0_ins'), [new P.PhpInt(1)]),
    [value],
  );
  const inner = new P.PhpFunction([], [], '', [new P.PhpReturn(call)]);
  const outer = new P.PhpCall(new P.PhpFunction([], [], '', [new P.PhpReturn(inner)]), []);
  return {identifier: 'Test_act', expression: new P.PhpGlobalAssign('Test_act', outer)};
}

const file = decls => ({namespace: ['Test'], rawDecls: [], decls, imports: [], arities: mapFromFoldable(ordString)(foldableArray)([])});

function walk(node, f) {
  if (!node || typeof node !== 'object') return;
  f(node);
  if (Array.isArray(node)) { for (const child of node) walk(child, f); return; }
  for (const key of Object.keys(node)) walk(node[key], f);
}

const has = (root, pred) => {
  let found = false;
  walk(root, node => { if (!found && pred(node)) found = true; });
  return found;
};

const isFieldWrite = node =>
  node instanceof P.PhpAssignExpr
  && node.value0 instanceof P.PhpPropertyAccess
  && node.value0.value0 instanceof P.PhpVar
  && node.value0.value0.value0 === 'v1_1'
  && node.value0.value1 === 'value1';

// 1. Self-recursive worker on an owned field: the parameter is promoted and
//    the rebuild becomes an in-place field write returning the node.
const insEntry = {identifier: INS, expression: insDecl()};
const promoted = optimize(candidates)(privateClasses)(file([insEntry]));
const promotedIns = promoted.decls.find(d => d.identifier === INS);
assert.equal(promotedIns.expression.value1[1].type_, '&', 'owned parameter promoted to a reference');
assert.ok(has(promotedIns.expression, isFieldWrite), 'rebuild rewritten as an in-place field write');
assert.ok(has(promotedIns.expression, n => n instanceof P.PhpReturn && n.value0 instanceof P.PhpVar && n.value0.value0 === 'v1_1'), 'rebuild returns the owned node');
assert.equal(has(promotedIns.expression, n => n instanceof P.PhpNew && n.value0 === T_CLASS), false, 'no allocation left for the rebuilt node');

// 2. A caller passing a literal cannot hand over ownership: the whole
//    representation (signature and rebuild) stays by value.
for (const literal of [new P.PhpRaw('null'), new P.PhpInt(0), new P.PhpCall(new P.PhpVar('mk'), [])]) {
  const refused = optimize(candidates)(privateClasses)(file([insEntry, actDecl(literal)]));
  const refusedIns = refused.decls.find(d => d.identifier === INS);
  assert.notEqual(refusedIns.expression.value1[1].type_, '&', 'literal call site refuses the promotion');
  assert.equal(has(refusedIns.expression, isFieldWrite), false, 'literal call site refuses the mutation');
  assert.ok(has(refusedIns.expression, n => n instanceof P.PhpNew && n.value0 === T_CLASS), 'rebuild stays an allocation');
}

// 3. A call site that passes a live variable (not a parameter, not a consumed
//    TCO register) also refuses the promotion.
const live = new P.PhpCall(
  new P.PhpCall(new P.PhpGlobalVar(new Just(['Test']), '__phpurs_enum_0_ins'), [new P.PhpInt(1)]),
  [new P.PhpVar('kept')],
);
const liveBody = [
  new P.PhpAssign('kept', new P.PhpRaw('null')),
  new P.PhpAssign('out', live),
  new P.PhpReturn(new P.PhpVar('kept')),
];
const liveDecl = {identifier: 'Test_live', expression: new P.PhpGlobalAssign('Test_live', new P.PhpFunction([], [], '', liveBody))};
const refusedLive = optimize(candidates)(privateClasses)(file([insEntry, liveDecl]));
const refusedIns2 = refusedLive.decls.find(d => d.identifier === INS);
assert.notEqual(refusedIns2.expression.value1[1].type_, '&', 'live binding refuses the promotion');
assert.equal(has(refusedIns2.expression, isFieldWrite), false, 'live binding refuses the mutation');

console.log('array-refs: ownership promotion, in-place rebuilds and refusals passed');
