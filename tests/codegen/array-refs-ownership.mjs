// Executable ownership regressions for the ArrayRefs pass. Each unsafe shape
// must either keep the parameter by value or refuse promotion, and the PHP
// program must then behave as the persistent version of the source: a value
// retained by an alias, a container or a closure never changes under a later
// insertion. The positive control keeps the promotion enabled.
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import * as P from '../../output/Phpurs.PhpAst/index.js';
import {optimize} from '../../output/Phpurs.ArrayRefs/index.js';
import {printPhpFile} from '../../output/Phpurs.Printer/index.js';
import {fromFoldable as setFromFoldable} from '../../output/Data.Set/index.js';
import {fromFoldable as mapFromFoldable} from '../../output/Data.Map/index.js';
import {foldableArray} from '../../output/Data.Foldable/index.js';
import {ordInt, ordString} from '../../output/Data.Ord/index.js';
import {Just} from '../../output/Data.Maybe/index.js';
import {Tuple} from '../../output/Data.Tuple/index.js';

const set = (ord, xs) => setFromFoldable(foldableArray)(ord)(xs);
const map = xs => mapFromFoldable(ordString)(foldableArray)(xs);

const INS = 'Test___phpurs_enum_0_ins';
const RUN = 'Test___phpurs_enum_0_run';
const TWICE = 'Test___phpurs_enum_0_twice';
const T_CLASS = '\\Test\\Test___phpurs_enum_0_T';
const INLINE = n => `__phpurs_inline_0_0_${n}`;

const v = n => new P.PhpVar(n);
const field = (n, f) => new P.PhpPropertyAccess(v(n), f);
const raw = s => new P.PhpRaw(s);
const insCall = (x, tree) => new P.PhpCall(new P.PhpCall(new P.PhpGlobalVar(new Just(['Test']), '__phpurs_enum_0_ins'), [x]), [tree]);

// The canonical private worker: a self-recursive rebuild of the visited node
// from its own fields. `before`/`after` insert observations around the
// recursive call without changing the shape the pass looks for.
function insDecl({before = [], after = []} = {}) {
  const body = [
    new P.PhpIf(new P.PhpBinOp('===', v('v1_1'), raw('null')),
      [new P.PhpReturn(new P.PhpNew(T_CLASS, [new P.PhpInt(0), raw('null'), v('v_0'), raw('null')]))], []),
    ...before,
    new P.PhpAssign(INLINE('v_0'), field('v1_1', 'value0')),
    new P.PhpAssign(INLINE('v1_1'), insCall(v('v_0'), field('v1_1', 'value1'))),
    ...after,
    new P.PhpAssign(INLINE('v2_2'), field('v1_1', 'value2')),
    new P.PhpAssign(INLINE('v3_3'), field('v1_1', 'value3')),
    new P.PhpReturn(new P.PhpNew(T_CLASS, [v(INLINE('v_0')), v(INLINE('v1_1')), v(INLINE('v2_2')), v(INLINE('v3_3'))])),
  ];
  return {identifier: INS, expression: new P.PhpPrivateFunction(INS, [{name: 'v_0', type_: ''}, {name: 'v1_1', type_: ''}], '', body)};
}

// A tail-recursive caller: its accumulator register is consumed by the
// promoted call and rewritten with the result, the only base case the pass
// accepts as an ownership transfer.
function runDecl({mid = [], finish = null, andThen = null} = {}) {
  const body = [
    new P.PhpAssign('__tco_var_run_n', v('run_n')),
    new P.PhpAssign('__tco_var_run_acc', v('run_acc')),
    new P.PhpLabel('run_loop'),
    new P.PhpAssign('run_n', v('__tco_var_run_n')),
    new P.PhpAssign('run_acc', v('__tco_var_run_acc')),
    new P.PhpIf(new P.PhpBinOp('===', v('run_n'), new P.PhpInt(0)), [new P.PhpReturn(finish ?? v('run_acc'))], []),
    ...mid,
    new P.PhpAssign('__tco_0', new P.PhpBinOp('-', v('run_n'), new P.PhpInt(1))),
    new P.PhpAssign('__tco_1', insCall(v('run_n'), v('run_acc'))),
    ...(andThen ?? []),
    new P.PhpAssign('__tco_var_run_n', v('__tco_0')),
    new P.PhpAssign('__tco_var_run_acc', v('__tco_1')),
    new P.PhpGoto('run_loop'),
  ];
  return {identifier: RUN, expression: new P.PhpPrivateFunction(RUN, [{name: 'run_n', type_: ''}, {name: 'run_acc', type_: ''}], '', body)};
}

const file = decls => ({
  namespace: ['Test'],
  rawDecls: ['final class Test___phpurs_enum_0_T { public function __construct(public $value0, public $value1, public $value2, public $value3) {} }'],
  decls,
  imports: [],
  arities: map([new Tuple(INS, 2), new Tuple(RUN, 2), new Tuple(TWICE, 2)]),
});

const candidates = map([new Tuple(INS, set(ordInt, [1]))]);
const privateClasses = set(ordString, [T_CLASS]);

const has = (root, pred) => {
  if (!root || typeof root !== 'object') return false;
  if (pred(root)) return true;
  if (Array.isArray(root)) return root.some(x => has(x, pred));
  return Object.keys(root).some(k => has(root[k], pred));
};
const isFieldWrite = node =>
  node instanceof P.PhpAssignExpr
  && node.value0 instanceof P.PhpPropertyAccess
  && node.value0.value0 instanceof P.PhpVar
  && node.value0.value0.value0 === 'v1_1'
  && node.value0.value1 === 'value1';

const insOf = out => out.decls.find(d => d.identifier === INS);
const promoted = out => insOf(out).expression.value1[1].type_ === '&';
const mutates = out => has(insOf(out).expression, isFieldWrite);

function compile(decls) {
  return optimize(candidates)(privateClasses)(file(decls));
}

const shapeHelper = `
function shape($n) { return $n === null ? '.' : '(' . shape($n->value1) . ',' . $n->value2 . ',' . shape($n->value3) . ')'; }
`;

function execute(out, harness) {
  const php = printPhpFile(false)('')(out.arities)(out) + '\n' + shapeHelper + '\n' + harness;
  const result = spawnSync('php', ['-d', 'opcache.enable_cli=0'], {input: php, encoding: 'utf8'});
  assert.equal(result.status, 0, result.stdout + result.stderr);
  assert.equal(result.stderr, '');
  return result.stdout.trim();
}

// 1. Positive control: the canonical shape promotes, mutates in place and
//    still builds the expected tree through the consumed TCO register.
const positive = compile([insDecl(), runDecl()]);
assert.equal(promoted(positive), true, 'canonical worker stays promoted');
assert.equal(mutates(positive), true, 'canonical rebuild stays an in-place update');
assert.equal(
  execute(positive, `$r = \\Test\\majTest___phpurs_enum_0_run(3, null); echo shape($r);`),
  '(((.,1,.),2,.),3,.)',
  'the promoted program still builds the tree',
);

// 2. The loop aliases the accumulator before consuming it: the retained
//    version must be refused and must keep the pre-insertion tree.
const aliased = compile([
  insDecl(),
  runDecl({
    mid: [new P.PhpAssign('__tco_keep', v('run_acc'))],
    finish: new P.PhpArray([v('run_acc'), v('__tco_keep')]),
  }),
]);
assert.equal(promoted(aliased), false, 'a retained accumulator alias refuses promotion');
assert.equal(mutates(aliased), false, 'a retained accumulator alias refuses the mutation');
assert.equal(
  execute(aliased, `
$r = \\Test\\majTest___phpurs_enum_0_run(3, null);
echo shape($r[0]), '|', shape($r[1]), '|', $r[0] !== $r[1] ? 'distinct' : 'shared';`),
  '(((.,1,.),2,.),3,.)|((.,2,.),3,.)|distinct',
  'the retained root keeps its older shape',
);

// 3. A field snapshot observed after the recursive call mutates that subtree:
//    the worker must stay by value and the snapshot must not change.
const slot = compile([
  insDecl({
    before: [
      new P.PhpAssign('__probe', field('v1_1', 'value1')),
      new P.PhpAssign('__probe_shape', new P.PhpCall(raw('shape'), [v('__probe')])),
    ],
    after: [raw('$GLOBALS["probe_ok"] = ($GLOBALS["probe_ok"] ?? true) && (shape($__probe) === $__probe_shape)')],
  }),
  runDecl(),
]);
assert.equal(promoted(slot), false, 'an observed child snapshot refuses promotion');
assert.equal(
  execute(slot, `\\Test\\majTest___phpurs_enum_0_run(3, null); echo var_export($GLOBALS["probe_ok"] ?? true, true);`),
  'true',
  'the child snapshot survives the insertion',
);

// 4. A closure captures the consumed accumulator: the object must not be
//    mutated behind the capture.
const captured = compile([
  insDecl(),
  runDecl({
    mid: [
      new P.PhpAssign('__probe', new P.PhpFunction(['run_acc'], [], '', [new P.PhpReturn(v('run_acc'))])),
      raw('$GLOBALS["probe"] = $__probe'),
      raw('$GLOBALS["probe_shape"] = shape($__probe())'),
    ],
  }),
]);
assert.equal(promoted(captured), false, 'a closure capture refuses promotion');
assert.equal(
  execute(captured, `
\\Test\\majTest___phpurs_enum_0_run(3, null);
echo shape($GLOBALS["probe"]()) === $GLOBALS["probe_shape"] ? "unchanged" : "changed";`),
  'unchanged',
  'the captured value is not mutated',
);

// 5. A container retains the accumulator across the insertion.
const boxed = compile([
  insDecl(),
  runDecl({
    mid: [
      new P.PhpAssign('__box', new P.PhpArray([v('run_acc')])),
      new P.PhpAssign('__box_shape', new P.PhpCall(raw('shape'), [new P.PhpArrayIndex(v('__box'), new P.PhpInt(0))])),
    ],
    andThen: [raw('$GLOBALS["box_ok"] = ($GLOBALS["box_ok"] ?? true) && (shape($__box[0]) === $__box_shape)')],
  }),
]);
assert.equal(promoted(boxed), false, 'a container alias refuses promotion');
assert.equal(
  execute(boxed, `\\Test\\majTest___phpurs_enum_0_run(3, null); echo var_export($GLOBALS["box_ok"] ?? true, true);`),
  'true',
  'the boxed value is not mutated',
);

// 6. Two insertions into the same retained root: a local argument is not an
//    ownership transfer, so both trees come out of the insertion intact.
const twice = compile([
  insDecl(),
  {
    identifier: TWICE,
    expression: new P.PhpPrivateFunction(TWICE, [{name: 'x', type_: ''}, {name: 't', type_: ''}], '', [
      new P.PhpAssign('a', insCall(v('x'), v('t'))),
      new P.PhpAssign('b', insCall(new P.PhpBinOp('+', v('x'), new P.PhpInt(1)), v('t'))),
      new P.PhpReturn(new P.PhpArray([v('a'), v('b')])),
    ]),
  },
]);
assert.equal(promoted(twice), false, 'a shared root refuses promotion');
assert.equal(
  execute(twice, `
$r = \\Test\\majTest___phpurs_enum_0_twice(3, null);
echo shape($r[0]), '|', shape($r[1]);`),
  '(.,3,.)|(.,4,.)',
  'both insertions keep their own result',
);

// 7. A construction that repeats a child creates an internal DAG; the whole
//    file must fall back to functional rebuilds so the two fields keep their
//    own versions.
const SHARED = 'Test___phpurs_enum_0_shared';
const shared = compile([
  insDecl(),
  runDecl(),
  {
    identifier: SHARED,
    expression: new P.PhpPrivateFunction(SHARED, [{name: 'x', type_: ''}], '', [
      new P.PhpReturn(new P.PhpNew(T_CLASS, [new P.PhpInt(0), v('x'), new P.PhpInt(5), v('x')])),
    ]),
  },
]);
assert.equal(promoted(shared), false, 'a shared child construction disables the pass');
assert.equal(
  execute(shared, `
$leaf = new \\Test\\Test___phpurs_enum_0_T(0, null, 1, null);
$tree = \\Test\\majTest___phpurs_enum_0_shared($leaf);
$r = \\Test\\majTest___phpurs_enum_0_ins(3, $tree);
echo shape($r);`),
  '(((.,3,.),1,.),5,(.,1,.))',
  'the two branches keep their own subtrees',
);

// 8. The callee itself must not retain its parameter: a worker that stores it
//    in a container or captures it in a closure is refused by the self-check.
const leakedContainer = compile([
  insDecl({ before: [new P.PhpAssign('__leak', new P.PhpArray([v('v1_1')]))] }),
  runDecl(),
]);
assert.equal(promoted(leakedContainer), false, 'a callee storing its parameter refuses promotion');

const leakedClosure = compile([
  insDecl({ before: [new P.PhpAssign('__leak', new P.PhpFunction(['v1_1'], [], '', [new P.PhpReturn(v('v1_1'))]))] }),
  runDecl(),
]);
assert.equal(promoted(leakedClosure), false, 'a callee capturing its parameter refuses promotion');

console.log('array-refs-ownership: retained roots, aliases, snapshots, captures, containers and shared children passed');
