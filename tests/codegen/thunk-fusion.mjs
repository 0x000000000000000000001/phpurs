// Build real typed optimizer nodes: PBO cannot erase the refusals under test.
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import {Just, Nothing} from '../../output/Data.Maybe/index.js';
import {Tuple} from '../../output/Data.Tuple/index.js';
import {empty as emptyMap} from '../../output/Data.Map/index.js';
import {empty as emptySet, size} from '../../output/Data.Set/index.js';
import {optimize, workerBudget} from '../../output/Phpurs.ThunkFusion/index.js';
import {translate} from '../../output/Phpurs.CodeGen/index.js';
import {printPhpFile} from '../../output/Phpurs.Printer/index.js';

const int = T.Int.value, unit = T.Unit.value, thunk = new T.Func([unit],int);
const typed = (ty,e) => new S.Typed(ty,e);
const q = (n,m='Demo') => new T.Qualified(new Just(m),n);
const v = n => new S.Var(q(n));
const loc = (n,ty=int) => typed(ty,new S.Local(new Just('x'+n),n));
const lit = n => typed(int,new S.Lit(new T.LitInt(n)));
const u = () => typed(unit,new S.Var(q('unit','Data.Unit')));
const app = (f,args) => new S.App(f,args);
const call = (n,args) => app(v(n),args);
const abs = (ns,body) => new S.Abs(ns.map(n=>new Tuple(new Just('x'+n),n)),body);
const op = (o,l,r) => typed(int,new S.PrimOp(new S.Op2(new S.OpIntNum(o),l,r)));
const eq = (l,r) => new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpEq.value),l,r));
const seed = n => typed(thunk,abs([2],lit(n)));
const capturedSeed = (body=loc(1)) => typed(thunk,abs([2],body));
const branch = (c,a,b) => new S.Branch([new S.Pair(c,a)],b);

function builder({name='build', operation=S.OpAdd.value, amount=1, decrement=1,
  base=loc(1,thunk), condition=eq(loc(0),lit(0)), step,
  signature=new T.Func([int,thunk,unit],int), binders=[0,1]}={}) {
  const body=step ?? op(operation,app(loc(1,thunk),[u()]),lit(amount));
  const recur=call(name,[op(S.OpSubtract.value,loc(0),lit(decrement)),typed(thunk,abs([2],body))]);
  return new Tuple(name,typed(signature,abs(binders,typed(thunk,branch(condition,base,recur)))));
}
const entry = (body,name='entry') => new Tuple(name,typed(new T.Func([unit],int),abs([0],typed(int,body))));
const consume = (depth=17,initial=seed(11),input=u(),name='build') => call(name,[lit(depth),initial,input]);
const mod = (body=consume(),build=builder(),extras=[]) => ({
  name:'Demo',bindings:[{recursive:true,bindings:[build]},...extras,{recursive:false,bindings:[entry(body)]}],
  dataDecls:[],foreign:emptyMap,exports:emptySet,imports:emptySet,dataTypes:emptyMap,
  comments:[],reExports:emptySet,classDecls:[],implementations:emptyMap,directives:emptyMap,
});
const original=mod();
const selected=optimize(original);
assert.equal(size(selected.privateNames),1);
assert.deepEqual(selected.module_.bindings[0],original.bindings[0],'public builder unchanged');
assert.equal(selected.module_.bindings.length,original.bindings.length+1);
assert.deepEqual(optimize(selected.module_).module_,selected.module_,'idempotent');
assert.equal(size(optimize(mod(consume(0))).privateNames),1,'zero depth');
const wrapped=typed(int,new S.TypeApp(consume(),int));
assert.equal(size(optimize(mod(wrapped)).privateNames),1,'Typed and TypeApp wrappers');

const dynamicEntry = (count=loc(0),initial=seed(11),name='dynamic',build='build') =>
  new Tuple(name,typed(new T.Func([int],int),abs([0],typed(int,call(build,[count,initial,u()])))));
const dynamicModule = (binding=dynamicEntry(),extras=[]) => ({...original,bindings:[
  original.bindings[0],{recursive:false,bindings:[binding,...extras]},
]});
const dynamic=dynamicModule();
const guarded=optimize(dynamic);
assert.equal(size(guarded.privateNames),2,'dynamic local uses a guard and one scalar worker');
assert.deepEqual(guarded.module_.bindings[0],dynamic.bindings[0],'dynamic public builder unchanged');
assert.deepEqual(optimize(guarded.module_).module_,guarded.module_,'guarded fusion is idempotent');
assert.equal(size(optimize(dynamicModule(dynamicEntry(typed(int,new S.TypeApp(loc(0),int))))).privateNames),2,'wrapped local depth');
const shared=optimize(dynamicModule(dynamicEntry(),[entry(consume()),dynamicEntry(loc(0),seed(7),'second')]));
assert.equal(size(shared.privateNames),2,'literal and dynamic consumers share the scalar worker and guard');

const captureEntry = (count=loc(0),initial=capturedSeed(),name='capture') =>
  new Tuple(name,typed(new T.Func([int,int],int),abs([0,1],typed(int,call('build',[count,initial,u()])))));
const captures=dynamicModule(captureEntry(),[captureEntry(lit(17),capturedSeed(),'staticcapture')]);
const fusedCaptures=optimize(captures);
assert.equal(size(fusedCaptures.privateNames),2,'static and dynamic Int captures share workers');
assert.deepEqual(fusedCaptures.module_.bindings[0],captures.bindings[0],'captured public builder unchanged');
let rescanned=fusedCaptures.module_;
for(let pass=0;pass<3;pass++) {
  const result=optimize(rescanned);
  assert.deepEqual(result.module_,rescanned,'captured fallback stays idempotent');
  assert.equal(size(result.privateNames),0,'no additional guards on rescan');
  rescanned=result.module_;
}
assert.equal(size(optimize(dynamicModule(captureEntry(lit(17)))).privateNames),1,'static captured seed');
const wrappedCapture=capturedSeed(typed(int,new S.TypeApp(loc(1),int)));
assert.equal(size(optimize(dynamicModule(captureEntry(loc(0),wrappedCapture))).privateNames),2,'wrapped capture');
const letCapture=(body=consume(17,capturedSeed(loc(4))),value=lit(7)) => new S.Let(new Just('x4'),4,value,body);
assert.equal(size(optimize(mod(letCapture())).privateNames),1,'eager let capture');
assert.equal(size(optimize(mod(new S.EffectBind(new Just('x4'),4,new S.EffectPure(lit(7)),new S.EffectPure(consume(17,capturedSeed(loc(4))))))).privateNames),1,'effect result already bound');
for(const ctor of [S.UncurriedAbs,S.UncurriedEffectAbs]) {
  const binding=new Tuple('uncurried',typed(new T.Func([int,int],int),new ctor([new Tuple(new Just('x0'),0),new Tuple(new Just('x1'),1)],typed(int,call('build',[loc(0),capturedSeed(),u()])))));
  assert.equal(size(optimize(dynamicModule(binding)).privateNames),2,'explicit uncurried parameters');
}

const refuse = (reason,m) => {
  const result=optimize(m);
  assert.equal(size(result.privateNames),0,reason);
  assert.deepEqual(result.module_,m,reason+' preserves AST');
};
refuse('negative depth',mod(consume(-1)));
refuse('untyped local depth',dynamicModule(dynamicEntry(new S.Local(new Just('depth'),0))));
refuse('wrong depth type',dynamicModule(dynamicEntry(typed(T.Number.value,new S.Local(new Just('depth'),0)))));
refuse('dynamic depth expression',dynamicModule(dynamicEntry(op(S.OpAdd.value,loc(0),lit(1)))));
refuse('foreign depth expression',dynamicModule(dynamicEntry(typed(int,app(new S.Var(q('depth','FFI')),[loc(0)])))));
refuse('dynamic unknown seed',dynamicModule(dynamicEntry(loc(0),loc(4,thunk))));
refuse('unknown callback',mod(consume(17,loc(4,thunk))));
refuse('foreign seed body',mod(consume(17,typed(thunk,abs([2],typed(int,app(new S.Var(q('inspect','FFI')),[u()])))))));
refuse('seed capture out of scope',mod(consume(17,capturedSeed(loc(4)))));
refuse('seed capture name is not bound',dynamicModule(captureEntry(loc(0),capturedSeed(typed(int,new S.Local(new Just('other'),1))))));
refuse('untyped seed capture',dynamicModule(captureEntry(loc(0),capturedSeed(new S.Local(new Just('x1'),1)))));
refuse('non-Int seed capture',dynamicModule(captureEntry(loc(0),capturedSeed(loc(1,T.Number.value)))));
refuse('computed seed capture',dynamicModule(captureEntry(loc(0),capturedSeed(op(S.OpAdd.value,loc(1),lit(1))))));
refuse('mutable field seed',dynamicModule(captureEntry(loc(0),capturedSeed(typed(int,new S.Accessor(loc(1),new S.GetProp('value')))))));
refuse('let binding not yet evaluated',mod(letCapture(lit(0),consume(17,capturedSeed(loc(4))))));
const recursiveCapture=new S.LetRec(4,[new Tuple('x4',consume(17,capturedSeed(loc(4))))],loc(4));
refuse('recursive seed is not eager',mod(recursiveCapture));
refuse('recursive binding shadows eager local',mod(letCapture(recursiveCapture)));
refuse('recursive local remains excluded in continuation',mod(new S.LetRec(4,[new Tuple('x4',lit(7))],consume(17,capturedSeed(loc(4))))));
refuse('sibling branch binding is not in scope',mod(branch(eq(lit(0),lit(0)),letCapture(lit(0)),consume(17,capturedSeed(loc(4))))));
refuse('thunk argument shadows caller local',dynamicModule(captureEntry(loc(0),typed(thunk,abs([1],loc(1))))));
refuse('seed consumes its argument',mod(consume(17,typed(thunk,abs([2],loc(2))))));
refuse('missing seed annotation',mod(consume(17,abs([2],lit(0)))));
refuse('unknown seed result',mod(consume(17,typed(new T.Func([unit],T.Any.value),abs([2],lit(0))))));
refuse('missing seed body annotation',mod(consume(17,typed(thunk,abs([2],new S.Lit(new T.LitInt(0)))))));
refuse('foreign Unit argument',mod(consume(17,seed(0),typed(unit,app(new S.Var(q('observe','FFI')),[u()])))));
refuse('unproven Unit',mod(consume(17,seed(0),loc(3,unit))));
refuse('retained function',mod(call('build',[lit(17),seed(0)])));
refuse('partial builder',mod(call('build',[lit(17)])));
refuse('overapplication',mod(call('build',[lit(17),seed(0),u(),u()])));
refuse('indirect builder',mod(app(loc(0),[lit(17),seed(0),u()])));
refuse('unflattened application',mod(app(call('build',[lit(17),seed(0)]),[u()])));
refuse('wrong countdown',mod(consume(),builder({decrement:2})));
refuse('wrong stop condition',mod(consume(),builder({condition:eq(loc(0),lit(1))})));
refuse('wrong base value',mod(consume(),builder({base:seed(0)})));
refuse('depth-dependent step',mod(consume(),builder({step:op(S.OpAdd.value,app(loc(1,thunk),[u()]),loc(0))})));
refuse('wrong forced argument',mod(consume(),builder({step:op(S.OpAdd.value,app(loc(1,thunk),[loc(2,unit)]),lit(1))})));
refuse('different function forced',mod(consume(),builder({step:op(S.OpAdd.value,app(v('unknown'),[u()]),lit(1))})));
refuse('multiple forces',mod(consume(),builder({step:op(S.OpAdd.value,app(loc(1,thunk),[u()]),app(loc(1,thunk),[u()]))})));
refuse('division may throw',mod(consume(),builder({operation:S.OpDivide.value,amount:0})));
refuse('three runtime binders',mod(consume(),builder({binders:[0,1,2]})));
refuse('shadowed binders',mod(consume(),builder({binders:[0,0]})));
refuse('unknown signature',mod(consume(),builder({signature:T.Any.value})));
refuse('wrong result type',mod(consume(),builder({signature:new T.Func([int,thunk,unit],T.Any.value)})));
refuse('missing flattened force argument',mod(consume(),builder({signature:new T.Func([int,thunk],int)})));
refuse('missing builder annotation',mod(consume(),new Tuple('build',builder().value1.value1)));
refuse('non-recursive group',{...original,bindings:original.bindings.map(g=>({...g,recursive:false}))});
refuse('mutually recursive group',{...original,bindings:[{recursive:true,bindings:[builder(),builder({name:'other'})]},original.bindings[1]]});

let deep=builder().value1;
for(let n=0;n<10000;n++) deep=typed(new T.Func([int,thunk,unit],int),deep);
refuse('deep proof budget',mod(consume(),new Tuple('build',deep)));
let big=consume();
for(let n=0;n<14;n++) big=branch(eq(lit(0),lit(0)),big,big);
refuse('large caller budget',mod(big));
refuse('wide caller budget',mod(new S.Branch(Array.from({length:100},()=>new S.Pair(eq(lit(0),lit(0)),consume())),consume())));
const many=Array.from({length:workerBudget+1},(_,n)=>({recursive:true,bindings:[builder({name:'b'+n})]}));
const uses=Array.from({length:workerBudget+1},(_,n)=>entry(consume(1,seed(0),u(),'b'+n),'e'+n));
const capped=optimize({...original,bindings:[...many,{recursive:false,bindings:uses}]});
assert.equal(size(capped.privateNames),workerBudget,'bounded number of workers');
const dynamicUses=Array.from({length:workerBudget+1},(_,n)=>dynamicEntry(loc(0),seed(0),'d'+n,'b'+n));
const dynamicCapped=optimize({...original,bindings:[...many,{recursive:false,bindings:dynamicUses}]});
assert.equal(size(dynamicCapped.privateNames),workerBudget*2,'at most one guard per selected builder');
const collision=mod(consume(),builder(),[{recursive:false,bindings:[entry(lit(0),'__PHPURS_FUSE_0_collision')]}]);
assert.ok(JSON.stringify(optimize(collision).module_).includes('__phpurs_fuse_1_build'),'case-insensitive fresh prefix');
const guardCollision=dynamicModule(dynamicEntry(),[entry(lit(0),'__PHPURS_FORCE_0_collision')]);
assert.ok(JSON.stringify(optimize(guardCollision).module_).includes('__phpurs_force_1_build'),'case-insensitive fresh guard prefix');
function rename(x) {
  if(typeof x==='string') return x.replaceAll('Demo','Elsewhere').replaceAll('build','assemble');
  if(Array.isArray(x)) return x.map(rename);
  if(x && typeof x==='object') return Object.assign(Object.create(Object.getPrototypeOf(x)),Object.fromEntries(Object.entries(x).map(([k,v])=>[k,rename(v)])));
  return x;
}
assert.equal(size(optimize(rename(original)).privateNames),1,'no benchmark names');
assert.equal(size(optimize(rename(dynamic)).privateNames),2,'dynamic fusion has no benchmark names');
assert.equal(size(optimize(rename(captures)).privateNames),2,'captured fusion has no benchmark names');

function render(m) {
  const file=translate([])(m);
  return printPhpFile(false)('')(file.arities)(file);
}
const dynamicPhp=render(dynamic);
assert.ok(!/\$GLOBALS\['[^']*__phpurs_(?:fuse|force)_/.test(dynamicPhp),'no dynamic private globals');
assert.match(dynamicPhp,/__phpurs_force_0_build/,'guard emitted');
assert.match(dynamicPhp,/>= 0/,'runtime non-negative guard');
assert.match(dynamicPhp,/goto tco_loop_[^;]*fuse/,'dynamic worker remains a loop');
const dynamicRun=spawnSync('php',[],{input:dynamicPhp+`
$GLOBALS['Data_Unit_unit']=null;
foreach ([0,1,2,17,127,1000] as $depth) {
  $actual=\\Demo\\majDemo_dynamic($depth);
  $expected=\\Demo\\majDemo_build($depth,fn($u)=>11,null);
  if(serialize($actual)!==serialize($expected)) throw new \\Exception('dynamic parity');
}
echo "Done\\n";
`,encoding:'utf8'});
assert.equal(dynamicRun.status,0,dynamicRun.stdout+dynamicRun.stderr);
assert.equal(dynamicRun.stdout,'Done\n');

// A real negative countdown never terminates. Instrument only the guard's
// fallback target so the test can check argument forwarding and route selection.
const guardStart=dynamicPhp.indexOf('function majDemo___phpurs_force_0_build(');
assert.ok(guardStart>=0);
const instrumented=dynamicPhp.slice(0,guardStart)+dynamicPhp.slice(guardStart).replaceAll('\\Demo\\majDemo_build(', '\\Demo\\probe_negative(');
assert.notEqual(instrumented,dynamicPhp,'fallback call instrumented');
const negativeRun=spawnSync('php',[],{input:instrumented+`
$GLOBALS['Data_Unit_unit']=null;
$GLOBALS['fallbacks']=0;
function probe_negative($depth,$seed,$input) {
  ++$GLOBALS['fallbacks'];
  if($depth!==-7 || $seed($input)!==11 || $input!==null) throw new \\Exception('fallback arguments');
  throw $GLOBALS['sentinel'];
}
$GLOBALS['sentinel']=new \\RuntimeException('negative route');
$caught=false;
try { \\Demo\\majDemo_dynamic(-7); }
catch (\\RuntimeException $e) { $caught=true; if($e!==$GLOBALS['sentinel']) throw new \\Exception('exception identity'); }
if(!$caught || $GLOBALS['fallbacks']!==1) throw new \\Exception('missing fallback');
if(\\Demo\\majDemo_dynamic(0)!==11 || \\Demo\\majDemo_dynamic(17)!==28 || $GLOBALS['fallbacks']!==1) throw new \\Exception('nonnegative path');
echo "Done\\n";
`,encoding:'utf8'});
assert.equal(negativeRun.status,0,negativeRun.stdout+negativeRun.stderr);
assert.equal(negativeRun.stdout,'Done\n');

const capturedPhp=render(captures);
assert.ok(!/\$GLOBALS\['[^']*__phpurs_(?:fuse|force)_/.test(capturedPhp),'captured workers stay private');
assert.match(capturedPhp,/fuse[^\n]*\(17, \$x1_1\)/,'static consumer passes its capture directly');
const capturedGuardStart=capturedPhp.indexOf('function majDemo___phpurs_force_0_build(');
assert.ok(capturedGuardStart>=0);
const capturedInstrumented=capturedPhp.slice(0,capturedGuardStart)+capturedPhp.slice(capturedGuardStart).replaceAll('\\Demo\\majDemo_build(', '\\Demo\\probe_capture(');
const capturedRun=spawnSync('php',[],{input:capturedInstrumented+`
$GLOBALS['Data_Unit_unit']=null;
$GLOBALS['fallbacks']=0;
function probe_capture($depth,$seed,$input) {
  ++$GLOBALS['fallbacks'];
  if($depth!==-7 || !$seed instanceof \\Closure || $seed($input)!==$GLOBALS['expectedSeed'] || $seed($input)!==$GLOBALS['expectedSeed'] || $input!==null) throw new \\Exception('captured fallback arguments');
  throw $GLOBALS['sentinel'];
}
$GLOBALS['sentinel']=new \\RuntimeException('captured negative route');
foreach ([-31,0,11,2147483647] as $value) {
  foreach ([0,1,2,17,127,1000] as $depth) {
    $actual=\\Demo\\majDemo_capture($depth,$value);
    $expected=\\Demo\\majDemo_build($depth,fn($u)=>$value,null);
    if(serialize($actual)!==serialize($expected)) throw new \\Exception('captured parity');
  }
  if(\\Demo\\majDemo_staticcapture(0,$value)!==$value+17) throw new \\Exception('static captured parity');
  $GLOBALS['expectedSeed']=$value;
  $caught=false;
  try { \\Demo\\majDemo_capture(-7,$value); }
  catch (\\RuntimeException $e) { $caught=true; if($e!==$GLOBALS['sentinel']) throw new \\Exception('captured exception identity'); }
  if(!$caught) throw new \\Exception('missing captured fallback');
}
if($GLOBALS['fallbacks']!==4) throw new \\Exception('captured route selection');
echo "Done\\n";
`,encoding:'utf8'});
assert.equal(capturedRun.status,0,capturedRun.stdout+capturedRun.stderr);
assert.equal(capturedRun.stdout,'Done\n');

const foreignValue = name => typed(int,app(new S.Var(q(name,'FFI')),[u()]));
const orderedCapture = new S.Let(new Just('x3'),3,foreignValue('depth'),
  new S.Let(new Just('x4'),4,foreignValue('seed'),call('build',[loc(3),capturedSeed(loc(4)),u()])));
assert.equal(size(optimize(mod(orderedCapture)).privateNames),2,'opaque values are fused only after binding');
const orderedRun=spawnSync('php',[],{input:render(mod(orderedCapture))+`
$GLOBALS['Data_Unit_unit']=null;
$events=[];
$GLOBALS['FFI_depth']=function($u) use (&$events) { $events[]='depth'; return 17; };
$GLOBALS['FFI_seed']=function($u) use (&$events) { $events[]='seed'; return 11; };
if(\\Demo\\majDemo_entry(null)!==28 || $events!==['depth','seed']) throw new \\Exception('capture evaluation order/count');
$same=new \\RuntimeException('seed failure');
$GLOBALS['FFI_seed']=function($u) use (&$events,$same) { $events[]='throw'; throw $same; };
$caught=false;
try { \\Demo\\majDemo_entry(null); }
catch (\\RuntimeException $e) { $caught=true; if($e!==$same) throw new \\Exception('seed exception identity'); }
if(!$caught || $events!==['depth','seed','depth','throw']) throw new \\Exception('seed exception order');
echo "Done\\n";
`,encoding:'utf8'});
assert.equal(orderedRun.status,0,orderedRun.stdout+orderedRun.stderr);
assert.equal(orderedRun.stdout,'Done\n');
const callbackEntry = new Tuple('observe',typed(new T.Func([thunk],int),abs([0],typed(int,consume(17,loc(0,thunk))))));
const php=render(mod(consume(),builder(),[{recursive:false,bindings:[callbackEntry]}]));
assert.ok(!/\$GLOBALS\['[^']*__phpurs_fuse_/.test(php),'no private globals');
assert.match(php,/goto tco_loop_[^;]*fuse/,'worker becomes a loop');
assert.match(php,/fuse[^\n]*\(17, 11\)/,'consumer passes scalars');
const run=spawnSync('php',[],{input:php+`
$GLOBALS['Data_Unit_unit'] = null;
if (\\Demo\\majDemo_entry(null) !== 28) throw new \\Exception('fused result');
$seedCalls = 0; $capture = 4;
$seed = function($u) use (&$seedCalls, &$capture) { ++$seedCalls; return $capture; };
$build = \\Demo\\majDemo_build(17);
$root = $build($seed);
if ($seedCalls !== 0 || !$root instanceof \\Closure) throw new \\Exception('eager or non-Closure');
if ($root(null) !== 21) throw new \\Exception('first force');
$capture = 9;
if ($root(null) !== 26 || $seedCalls !== 2) throw new \\Exception('retained root');
$same = new \\RuntimeException('same');
$threw = false;
try { \\Demo\\majDemo_build(2, function($u) use ($same) { throw $same; }, null); }
catch (\\RuntimeException $e) { $threw = true; if ($e !== $same) throw new \\Exception('exception identity'); }
if (!$threw) throw new \\Exception('exception swallowed');
$stackSeed = fn($u) => count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
$direct = \\Demo\\majDemo_build(17, $stackSeed, null);
$observed = \\Demo\\majDemo_observe($stackSeed);
if ($observed !== $direct + 1) throw new \\Exception('callback stack frames lost');
$large = PHP_INT_MAX - 1;
$ordered = \\Demo\\majDemo___phpurs_fuse_0_build(2048, $large);
$chain = \\Demo\\majDemo_build(2048, fn($u) => $large, null);
if (serialize($ordered) !== serialize($chain) || serialize($ordered) === serialize($large + 2048)) throw new \\Exception('addition shortcut');
echo "Done\\n";
`,encoding:'utf8'});
assert.equal(run.status,0,run.stdout+run.stderr);
assert.equal(run.stdout,'Done\n');

// Compare generated workers to the unchanged public builder, preserving PHP
// numeric types and rounding across overflow (not just mathematical integers).
for (const [operation,amount,seeds,depths] of [
  [S.OpAdd.value,1,[-3,0,11,2147483647],[0,1,2,17,127,1000]],
  [S.OpSubtract.value,7,[-3,0,2147483647],[0,1,17]],
  [S.OpMultiply.value,2147483647,[0,2,2147483647],[0,1,2,3,17]],
]) {
  const entries=[];
  const checks=[];
  for(const initial of seeds) for(const depth of depths) {
    const name='check'+entries.length;
    entries.push(entry(consume(depth,seed(initial)),name));
    // Inspect the private scalar result before the caller's public Int return
    // check, which rejects PHP floats in both compilations after overflow.
    checks.push(`$a=\\Demo\\majDemo___phpurs_fuse_0_build(${depth}, ${initial}); $b=\\Demo\\majDemo_build(${depth}, fn($u)=>${initial}, null); if (serialize($a)!==serialize($b)) throw new \\Exception('${name}: '.serialize([$a,$b]));`);
  }
  const m=mod(consume(),builder({operation,amount}),[{recursive:false,bindings:entries}]);
  const result=spawnSync('php',[],{input:render(m)+`\n$GLOBALS['Data_Unit_unit']=null;\n`+checks.join('\n')+'\necho "Done\\n";',encoding:'utf8'});
  assert.equal(result.status,0,result.stdout+result.stderr);
  assert.equal(result.stdout,'Done\n');
}
console.log('thunk-fusion: literal/captured seeds, lexical scope, idempotence, evaluation order, negative fallbacks, refusals, budgets, retained closures and arithmetic parity passed');
