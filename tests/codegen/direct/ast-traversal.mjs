// Every PhpExpr constructor must be reachable through the shared traversal.
// The sample table is checked against the module exports, so adding a form
// without a traversal decision fails here instead of in a pass at runtime.
import assert from 'node:assert/strict';
import { Nothing } from '../../../output/Data.Maybe/index.js';
import * as Ast from '../../../output/Phpurs.PhpAst/index.js';
import {
  children,
  localChildren,
  mapChildren,
} from '../../../output/Phpurs.PhpAst.Traversal/index.js';

const leaf = new Ast.PhpVar('leaf');
const replacement = new Ast.PhpInt(7);
const none = Nothing.value;
const samples = {
  PhpFunction: () => new Ast.PhpFunction([], [], '', [leaf]),
  PhpCompactFunction: () => new Ast.PhpCompactFunction([], [], '', [leaf]),
  PhpCompactLoop: () => new Ast.PhpCompactLoop('loop', [], '', [leaf]),
  PhpNativeFunction: () => new Ast.PhpNativeFunction('f', [], '', [leaf]),
  PhpPrivateFunction: () => new Ast.PhpPrivateFunction('f', [], '', [leaf]),
  PhpGlobalAssign: () => new Ast.PhpGlobalAssign('x', leaf),
  PhpVar: () => leaf,
  PhpGlobalVar: () => new Ast.PhpGlobalVar(none, 'x'),
  PhpGlobalSlot: () => new Ast.PhpGlobalSlot('slot'),
  PhpDirectCall: () => new Ast.PhpDirectCall('f', [leaf]),
  PhpCall: () => new Ast.PhpCall(leaf, [leaf]),
  PhpNamedCall: () => new Ast.PhpNamedCall('f', [leaf]),
  PhpInt: () => new Ast.PhpInt(1),
  PhpNumber: () => new Ast.PhpNumber(1.5),
  PhpString: () => new Ast.PhpString('s'),
  PhpBoolean: () => new Ast.PhpBoolean(true),
  PhpNull: () => Ast.PhpNull.value,
  PhpArray: () => new Ast.PhpArray([leaf]),
  PhpAssocArray: () => new Ast.PhpAssocArray([{ key: 'k', value: leaf }]),
  PhpPropertyAccess: () => new Ast.PhpPropertyAccess(leaf, 'p'),
  PhpRecordAccess: () => new Ast.PhpRecordAccess(leaf, 'p'),
  PhpArrayIndex: () => new Ast.PhpArrayIndex(leaf, leaf),
  PhpAssign: () => new Ast.PhpAssign('x', leaf),
  PhpAssignExpr: () => new Ast.PhpAssignExpr(leaf, leaf),
  PhpIf: () => new Ast.PhpIf(leaf, [leaf], [leaf]),
  PhpMatch: () => new Ast.PhpMatch(leaf, [{ val: leaf, body: leaf }], leaf),
  PhpThrow: () => new Ast.PhpThrow(leaf),
  PhpTernary: () => new Ast.PhpTernary(leaf, leaf, leaf),
  PhpReturn: () => new Ast.PhpReturn(leaf),
  PhpBinOp: () => new Ast.PhpBinOp('+', leaf, leaf),
  PhpUnaryOp: () => new Ast.PhpUnaryOp('!', leaf),
  PhpWhile: () => new Ast.PhpWhile(leaf, [leaf]),
  PhpContinue: () => Ast.PhpContinue.value,
  PhpRaw: () => new Ast.PhpRaw('raw'),
  PhpUnset: () => new Ast.PhpUnset('x'),
  PhpNew: () => new Ast.PhpNew('C', [leaf]),
  PhpClone: () => new Ast.PhpClone(leaf),
  PhpSwitch: () => new Ast.PhpSwitch(leaf, [{ matchCases: [leaf], stmts: [leaf] }], none),
  PhpGoto: () => new Ast.PhpGoto('loop'),
  PhpLabel: () => new Ast.PhpLabel('loop'),
  PhpInstanceOf: () => new Ast.PhpInstanceOf(leaf, 'C'),
};

const exported = Object.keys(Ast)
  .filter((name) => /^Php[A-Z]/.test(name))
  .sort();
assert.deepEqual(
  Object.keys(samples).sort(),
  exported,
  'every PhpExpr constructor needs a traversal sample (and no sample may be stale)',
);

for (const [name, build] of Object.entries(samples)) {
  const expr = build();
  const mapped = mapChildren((child) => child)(expr);
  assert.deepEqual(mapped, expr, `${name} survives an identity mapChildren`);

  const direct = children(expr);
  assert.ok(Array.isArray(direct), `${name} has a child list`);
  if (direct.length === 0) continue;

  const rewritten = mapChildren((child) => (child === leaf ? replacement : child))(expr);
  assert.ok(
    !children(rewritten).includes(leaf),
    `${name} maps every direct child (no unvisited leaf)`,
  );
}

// Scope boundaries stay explicit: a function body is opaque to localChildren.
const fn = new Ast.PhpFunction([], [], '', [leaf]);
assert.deepEqual(children(fn), [leaf], 'children enters function bodies');
assert.deepEqual(localChildren(fn), [], 'localChildren keeps function bodies opaque');

console.log(`ast-traversal: ${exported.length} constructors covered`);
