// Exercise the PHP pass before PBO can erase the cases under test.
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import * as P from '../../output/Phpurs.PhpAst/index.js';
import {optimize, nodeBudget, depthBudget, widthBudget} from '../../output/Phpurs.CopyCleanup/index.js';
import {printPhpFile} from '../../output/Phpurs.Printer/index.js';
import * as S from '../../output/Data.Set/index.js';
import * as M from '../../output/Data.Map/index.js';
import {ordString} from '../../output/Data.Ord/index.js';
import {Nothing} from '../../output/Data.Maybe/index.js';

const v=n=>new P.PhpVar(n), i=n=>new P.PhpInt(n), ret=e=>new P.PhpReturn(e);
const set=(n,e)=>new P.PhpAssign(n,e), op=(s,a,b)=>new P.PhpBinOp(s,a,b);
const box=(...xs)=>new P.PhpNew('\\Checks\\Node',xs);
const names=xs=>xs.reduce((s,n)=>S.insert(ordString)(n)(s),S.empty);
const fn=(name,body,args=['x','y'],type=P.PhpPrivateFunction)=>({
  identifier:'Checks_'+name,
  expression:new type('Checks_'+name,args.map(name=>({name,type_:''})),'',body),
});
const file=decls=>({namespace:['Checks'],imports:[],decls,arities:M.empty,
  rawDecls:['final class Node { public function __construct(public $a, public $b) {} }'],
});
const proof=decls=>({workers:names(decls.map(d=>d.identifier)),constructors:names(['\\Checks\\Node'])});
const run=f=>optimize(proof(f.decls))(f);
const copies=[set('a',v('x')),set('b',v('y')),set('result',box(v('a'),v('b'))),ret(v('result'))];
const compact=fn('compact',copies);
assert.deepEqual(run(file([compact])).decls[0].expression.value3,[ret(box(v('x'),v('y')))]);
const twice=fn('twice',[set('a',v('x')),ret(box(v('a'),v('a')))]);
assert.deepEqual(run(file([twice])).decls[0].expression.value3,[ret(box(v('x'),v('x')))]);
const entry=fn('entry',[
  set('saveX',v('x')),set('saveY',v('y')),new P.PhpLabel('unused'),
  set('x',v('saveX')),set('y',v('saveY')),
  new P.PhpIf(op('>',v('x'),i(0)),copies,[]),ret(box(v('y'),v('x'))),
]);
const cleaned=run(file([entry])).decls[0].expression.value3;
assert.deepEqual(cleaned,[new P.PhpIf(op('>',v('x'),i(0)),[ret(box(v('x'),v('y')))],[]),ret(box(v('y'),v('x')))]);

// A real loop swaps the parameters using temporaries. Both copies remain.
const loop=fn('loop',[
  set('saveX',v('x')),set('saveY',v('y')),set('count',v('n')),new P.PhpLabel('again'),
  set('x',v('saveX')),set('y',v('saveY')),
  new P.PhpIf(op('===',v('count'),i(0)),[ret(box(v('x'),v('y')))],[]),
  set('nextX',v('y')),set('nextY',v('x')),
  set('saveX',v('nextX')),set('saveY',v('nextY')),set('count',op('-',v('count'),i(1))),new P.PhpGoto('again'),
],['x','y','n']);
assert.deepEqual(run(file([loop])).decls[0],loop,'backedge and simultaneous updates');
const extraUse=fn('extraUse',[
  set('saved',v('x')),set('x',v('saved')),
  new P.PhpIf(v('saved'),[ret(box(v('x'),v('y')))],[]),ret(box(v('y'),v('x'))),
]);
assert.deepEqual(run(file([extraUse])).decls[0],extraUse,'later read keeps entry temporary');
const overwritten=fn('overwritten',[set('a',v('x')),set('x',op('+',v('x'),i(1))),ret(box(v('a'),v('x')))]);
assert.deepEqual(run(file([overwritten])).decls[0],overwritten,'source changes before return');
const permutation=fn('permutation',[
  set('a',v('x')),set('b',v('y')),set('x',v('b')),set('y',v('a')),
  new P.PhpIf(v('x'),[ret(box(v('x'),v('y')))],[]),ret(box(v('y'),v('x'))),
]);
assert.deepEqual(run(file([permutation])).decls[0],permutation,'permutation is not a round-trip');

const unchanged=(why,d)=>assert.deepEqual(run(file([d])).decls[0],d,why);
unchanged('public function',fn('public',copies,['x','y'],P.PhpNativeFunction));
const unproved=file([compact]);
assert.deepEqual(optimize({workers:S.empty,constructors:S.empty})(unproved),unproved,'proof required');
const opaqueConstructor=fn('opaqueConstructor',[set('a',v('x')),ret(new P.PhpNew('\\External',[v('a')]))]);
unchanged('constructor may run user code',opaqueConstructor);
for(const capture of ['x','&x']) unchanged('captured '+capture,fn('captured',[
  set('callback',new P.PhpFunction([capture],[],'',[ret(v('x'))])),...copies,
]));
unchanged('compact closure',fn('captured',[set('callback',new P.PhpCompactFunction(['x'],[],'',[ret(v('x'))])),...copies]));
unchanged('opaque PHP',fn('opaque',[new P.PhpRaw('observe(func_get_args())'),...copies]));
unchanged('reference argument',fn('ref',copies,['&x','y']));
unchanged('mutation through alias',fn('mutation',[new P.PhpAssignExpr(new P.PhpPropertyAccess(v('x'),'value'),i(1)),...copies]));
unchanged('unknown callback',fn('callback',[set('z',new P.PhpCall(v('f'),[v('x')])),...copies],['x','y','f']));
unchanged('call inside return',fn('effect',[set('a',v('x')),ret(box(new P.PhpDirectCall('observe',[v('x')]),v('a')))]));
const typed=fn('typed',copies);typed.expression.value1[0].type_='int';unchanged('checked signature',typed);
const tooWide=fn('wide',Array.from({length:nodeBudget+1},()=>ret(i(0))));unchanged('node/width budget',tooWide);
unchanged('wide leaves must decline before recursive return pass',fn('wide',Array.from({length:8192},()=>new P.PhpRaw('null'))));
unchanged('width boundary',fn('width',Array.from({length:widthBudget+1},()=>new P.PhpRaw('null'))));
let deep=v('x');for(let j=0;j<depthBudget+10000;j++)deep=new P.PhpPropertyAccess(deep,'a');
// Use identity instead of assert.deepEqual, which itself overflows on deep ASTs.
const deepDecl=fn('deep',[ret(deep)]);assert.equal(run(file([deepDecl])).decls[0],deepDecl,'deep input declines before recursion');
const many=fn('many',Array.from({length:100},()=>new P.PhpIf(v('x'),Array.from({length:100},()=>set('a',box(v('x'),v('y')))),[])));
unchanged('nested node budget',many);
const nestedCalls=fn('nestedCalls',[
  set('result',new P.PhpCall(new P.PhpCall(new P.PhpGlobalVar(Nothing.value,'Checks_helper'),[v('x')]),[v('y')])),
  ...copies,
]);
assert.notDeepEqual(run(file([nestedCalls])).decls[0],nestedCalls,'nested saturated calls do not block other terminal copies');
unchanged('nested unknown callback',fn('unknownNested',[
  set('z',new P.PhpCall(new P.PhpCall(v('f'),[v('x')]),[v('y')])),...copies,
],['x','y','f']));
const failure=fn('failure',[...copies,new P.PhpThrow(new P.PhpNew('\\Exception',[
  new P.PhpRaw('"Failed pattern match at " . __FILE__ . ":" . __LINE__'),
]))]);
assert.notDeepEqual(run(file([failure])).decls[0],failure,'known pattern-failure message');

// Both branches, chained aliases, reused inputs and odd/even permutations run
// against both emitted programs, not just against the shape of the AST.
const chain=fn('chain',[set('a',v('x')),set('b',v('a')),ret(box(v('b'),v('a')))]);
const declarations=[compact,twice,entry,loop,extraUse,overwritten,permutation,chain];
function execute(f) {
  const php=printPhpFile(false)('')(f.arities)(f)+`
set_error_handler(function($n,$message,$file,$line) { throw new \\ErrorException($message,0,$n,$file,$line); });
$old=new \\Checks\\Node(7,8); $expected=serialize($old); $results=[];
foreach ([[-3,9],[0,4],[5,-2]] as [$x,$y]) {
  $results[] = majChecks_compact($x,$y); $results[] = majChecks_twice($x,$y);
  $results[] = majChecks_entry($x,$y); $results[] = majChecks_extramajUse($x,$y);
  $results[] = majChecks_overwritten($x,$y); $results[] = majChecks_permutation($x,$y);
  $results[] = majChecks_loop($x,$y,3); $results[] = majChecks_loop($x,$y,4);
}
$new=majChecks_chain($old,null);
if ($new->a!==$old || $new->b!==$old || serialize($old)!==$expected) throw new \\Exception('sharing changed');
echo json_encode($results);`;
  const result=spawnSync('php',['-d','opcache.enable_cli=0'],{input:php,encoding:'utf8'});
  assert.equal(result.status,0,result.stdout+result.stderr);assert.equal(result.stderr,'');
  return JSON.parse(result.stdout);
}
const program=file(declarations);
assert.deepEqual(execute(run(program)),execute(program));
console.log('Copy cleanup: terminal aliases, entry round-trips, loops, captures, boundaries and budgets passed');
