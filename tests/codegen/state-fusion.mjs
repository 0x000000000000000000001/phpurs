// Typed optimizer fixtures keep the unsafe shapes visible to the actual pass.
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import {Just, Nothing} from '../../output/Data.Maybe/index.js';
import {Tuple} from '../../output/Data.Tuple/index.js';
import {empty as emptyMap} from '../../output/Data.Map/index.js';
import {empty as emptySet, size} from '../../output/Data.Set/index.js';
import {optimize, workerBudget} from '../../output/Phpurs.StateFusion/index.js';
import {translate} from '../../output/Phpurs.CodeGen/index.js';
import {printPhpFile} from '../../output/Phpurs.Printer/index.js';

const int=T.Int.value, unit=T.Unit.value, any=T.Any.value, variable=new T.TypeVar('s');
const typed=(ty,e)=>new S.Typed(ty,e);
const q=(n,m='Demo')=>new T.Qualified(new Just(m),n);
const v=n=>new S.Var(q(n));
const loc=(n,ty=int,name='x'+n)=>typed(ty,new S.Local(new Just(name),n));
const lit=n=>typed(int,new S.Lit(new T.LitInt(n)));
const u=()=>typed(unit,new S.Var(q('unit','Data.Unit')));
const app=(fn,args)=>new S.App(fn,args);
const call=(name,args)=>app(v(name),args);
const abs=(ns,body)=>new S.Abs(ns.map(n=>new Tuple(new Just('x'+n),n)),body);
const op=(operator,left,right)=>typed(int,new S.PrimOp(new S.Op2(new S.OpIntNum(operator),left,right)));
const eq=(left,right)=>new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpEq.value),left,right));
const prop=(expr,key='state')=>new S.Accessor(expr,new S.GetProp(key));
const record=(value,state)=>new S.Lit(new T.LitRecord([new T.Prop('val',value),new T.Prop('state',state)]));
const recordType=(value=unit,state=int)=>new T.Record(new T.Row([new Tuple('val',value),new Tuple('state',state)],Nothing.value));
const getter=(body=record(loc(0,variable),loc(0,variable)),signature=new T.ForAll(['s'],new T.Func([variable],recordType(variable,variable))))=>
  new Tuple('get',typed(signature,abs([0],body)));
const modifier=(body=record(u(),app(loc(0,new T.Func([variable],variable)),[prop(call('get',[loc(1,variable)]),'val')])),
  signature=new T.ForAll(['s'],new T.Func([new T.Func([variable],variable),variable],recordType(unit,variable))))=>
  new Tuple('modify',typed(signature,abs([0],abs([1],body))));
const callback=(operation=S.OpAdd.value,amount=1)=>typed(new T.Func([int],int),abs([1],op(operation,loc(1),lit(amount))));
function builder({name='chain',condition=eq(loc(0),lit(0)),base=abs([1],record(u(),loc(1))),
  step=callback(),prepared=app(new S.TypeApp(v('modify'),int),[step]),decrement=1,
  transition=prop(app(loc(1,any),[loc(2)])),binders=[0],continuationBinders=[2],
  signature=new T.Func([int,int],recordType())}={}) {
  const next=call(name,[op(S.OpSubtract.value,loc(0),lit(decrement)),transition]);
  return new Tuple(name,typed(signature,abs(binders,new S.Branch([new S.Pair(condition,base)],
    new S.Let(new Just('x1'),1,prepared,abs(continuationBinders,next))))));
}
const consume=(depth=lit(17),initial=lit(11),name='chain')=>prop(call(name,[depth,initial]));
const entry=(body=consume(),name='entry',binders=[0,1])=>new Tuple(name,typed(new T.Func([any,any],any),abs(binders,body)));
const mod=(body=consume(),build=builder(),get=getter(),modify=modifier(),extras=[])=>({
  name:'Demo',bindings:[{recursive:false,bindings:[get,modify]},{recursive:true,bindings:[build]},
    ...extras,{recursive:false,bindings:[entry(body)]}],
  dataDecls:[],foreign:emptyMap,exports:emptySet,imports:emptySet,dataTypes:emptyMap,
  comments:[],reExports:emptySet,classDecls:[],implementations:emptyMap,directives:emptyMap,
});
const original=mod();
const selected=optimize(original);
assert.equal(size(selected.privateNames),1);
assert.deepEqual(selected.module_.bindings.slice(0,2),original.bindings.slice(0,2),'public helpers and builder unchanged');
assert.equal(size(optimize(mod(consume(lit(0)))).privateNames),1,'zero depth');
assert.equal(size(optimize(mod(typed(int,new S.TypeApp(consume(),int)))).privateNames),1,'annotation wrappers');
assert.equal(size(optimize(mod(consume(),builder(),getter(),modifier(record(u(),app(loc(0),[loc(1)]))))).privateNames),1,'inlined get');

const dynamic=mod(consume(loc(0),loc(1)));
const guarded=optimize(dynamic);
assert.equal(size(guarded.privateNames),2,'one guard and one arithmetic worker');
assert.equal(size(guarded.checkedDepths),1,'retain the native depth check');
const shared=mod(consume(loc(0),loc(1)),builder(),getter(),modifier(),[{recursive:false,bindings:[entry(consume(),'static')]}]);
assert.equal(size(optimize(shared).privateNames),2,'static and dynamic consumers share workers');
for(const input of [selected.module_,guarded.module_]) {
  let rescanned=input;
  for(let i=0;i<3;i++) {
    const result=optimize(rescanned);
    assert.deepEqual(result.module_,rescanned,'negative fallback is not a candidate on rescan');
    assert.equal(size(result.privateNames),0);
    rescanned=result.module_;
  }
}
const foreignValue=name=>typed(int,call(name,[u()]));
const letValue=(body=consume(loc(3),loc(4)),value=lit(11))=>new S.Let(new Just('x3'),3,lit(17),new S.Let(new Just('x4'),4,value,body));
assert.equal(size(optimize(mod(letValue())).privateNames),2,'strict local inputs');
assert.equal(size(optimize(mod(new S.EffectBind(new Just('x3'),3,new S.EffectPure(lit(17)),new S.EffectPure(consume(loc(3)))))).privateNames),2,'already executed effect result');
for(const ctor of [S.UncurriedAbs,S.UncurriedEffectAbs]) {
  const input={...dynamic,bindings:[...dynamic.bindings.slice(0,2),{recursive:false,bindings:[new Tuple('uncurried',new ctor([new Tuple(new Just('x0'),0),new Tuple(new Just('x1'),1)],consume(loc(0),loc(1))))]}]};
  assert.equal(size(optimize(input).privateNames),2,'explicit uncurried lexical inputs');
}

let refusals=0;
function refuse(reason,input) {
  const result=optimize(input);
  assert.equal(size(result.privateNames),0,reason);
  assert.deepEqual(result.module_,input,reason+': preserve original AST');
  refusals++;
}
refuse('negative literal',mod(consume(lit(-1))));
refuse('retained State',mod(call('chain',[lit(17)])));
refuse('escaped record',mod(call('chain',[lit(17),lit(11)])));
refuse('val projection must still execute transition',mod(prop(call('chain',[lit(17),lit(11)]),'val')));
refuse('unknown property',mod(prop(call('chain',[lit(17),lit(11)]),'other')));
refuse('nested application reserved for fallback',mod(prop(app(call('chain',[lit(17)]),[lit(11)]))));
refuse('surapplication',mod(prop(call('chain',[lit(17),lit(11),u()]))));
refuse('indirect builder',mod(prop(app(loc(0),[lit(17),lit(11)]))));
refuse('foreign builder',mod(prop(app(new S.Var(q('chain','Other')),[lit(17),lit(11)]))));
refuse('foreign depth evaluation',mod(consume(foreignValue('count'))));
refuse('foreign state evaluation',mod(consume(lit(17),foreignValue('initial'))));
refuse('computed depth',mod(consume(op(S.OpAdd.value,loc(0),lit(1)))));
refuse('computed state',mod(consume(lit(17),op(S.OpAdd.value,loc(1),lit(1)))));
refuse('mutable field input',mod(consume(lit(17),typed(int,prop(loc(1),'value')))));
refuse('untyped depth',mod(consume(new S.Local(new Just('x0'),0))));
refuse('wrong input type',mod(consume(lit(17),loc(1,T.Number.value))));
refuse('unbound depth',mod(consume(loc(4))));
refuse('unbound state',mod(consume(lit(17),loc(4))));
refuse('wrong local name',mod(consume(loc(0,int,'other'))));
refuse('not yet initialized',mod(letValue(lit(0),consume(loc(3),loc(4)))));
refuse('recursive binding is not strict',mod(new S.LetRec(4,[new Tuple('x4',lit(11))],consume(lit(17),loc(4)))));
refuse('recursive binding shadows strict local',mod(letValue(new S.LetRec(4,[new Tuple('x4',lit(11))],consume(lit(17),loc(4))))));
refuse('sibling branch scope',mod(new S.Branch([new S.Pair(eq(lit(0),lit(0)),letValue(lit(0)))],consume(lit(17),loc(4)))));
refuse('wrong countdown',mod(consume(),builder({decrement:2})));
refuse('wrong stop',mod(consume(),builder({condition:eq(loc(0),lit(1))})));
refuse('two runtime builder binders',mod(consume(),builder({binders:[0,1]})));
refuse('shadowed state parameter',mod(consume(),builder({continuationBinders:[0]})));
refuse('base state changed',mod(consume(),builder({base:abs([1],record(u(),lit(0)))})));
refuse('base val evaluates unknown code',mod(consume(),builder({base:abs([1],record(call('observe',[u()]),loc(1)))})));
refuse('base val can be a function',mod(consume(),builder({base:abs([1],record(abs([2],lit(7)),loc(1)))})));
refuse('base extra field',mod(consume(),builder({base:abs([1],new S.Lit(new T.LitRecord([new T.Prop('val',u()),new T.Prop('state',loc(1)),new T.Prop('extra',call('observe',[u()]))])))})));
refuse('unknown callback',mod(consume(),builder({step:loc(5,new T.Func([int],int))})));
refuse('foreign callback',mod(consume(),builder({step:typed(new T.Func([int],int),abs([1],call('inspect',[loc(1)])))})));
refuse('callback captures depth',mod(consume(),builder({step:typed(new T.Func([int],int),abs([1],op(S.OpAdd.value,loc(1),loc(0))))})));
refuse('callback argument shadowing depth',mod(consume(),builder({step:typed(new T.Func([int],int),abs([0],op(S.OpAdd.value,loc(0),lit(1))))})));
refuse('throwing arithmetic',mod(consume(),builder({step:callback(S.OpDivide.value,0)})));
refuse('wrong callback signature',mod(consume(),builder({step:typed(new T.Func([int],T.Number.value),callback().value1)})));
refuse('wrong state projection in transition',mod(consume(),builder({transition:prop(app(loc(1),[loc(2)]),'val')})));
refuse('wrong state passed to transition',mod(consume(),builder({transition:prop(app(loc(1),[loc(0)]))})));
refuse('unknown modifier',mod(consume(),builder({prepared:call('unknown',[callback()])})));
refuse('foreign modifier',mod(consume(),builder({prepared:app(new S.Var(q('modify','Other')),[callback()])})));
refuse('modify val has effects',mod(consume(),builder(),getter(),modifier(record(call('observe',[u()]),app(loc(0),[loc(1)])))));
refuse('get changes state',mod(consume(),builder(),getter(record(loc(0),lit(0)))));
refuse('get changes value',mod(consume(),builder(),getter(record(lit(0),loc(0)))));
refuse('get calls opaque code',mod(consume(),builder(),getter(record(call('inspect',[loc(0)]),loc(0)))));
refuse('monomorphic get adds observable Int boundary',mod(consume(),builder(),getter(undefined,new T.Func([int],recordType(int,int)))));
const checkedGet=new Tuple('get',typed(getter().value1.value0,typed(new T.Func([int],recordType(int,int)),getter().value1.value1)));
refuse('specialized inner helper annotation',mod(consume(),builder(),checkedGet));
let deepType=int;
for(let n=0;n<10000;n++) deepType=new T.Func([variable],deepType);
const nestedResultGet=new Tuple('get',typed(getter().value1.value0,typed(new T.Func([variable],deepType),getter().value1.value1)));
refuse('deep native type metadata is refused without recursion',mod(consume(),builder(),nestedResultGet));
refuse('unknown helper signature',mod(consume(),builder(),getter(),modifier(undefined,any)));
refuse('wrong builder layout',mod(consume(),builder({signature:new T.Func([int,int],recordType(int,int))})));
refuse('unknown builder signature',mod(consume(),builder({signature:any})));
refuse('nonrecursive builder',{...original,bindings:original.bindings.map(g=>({...g,recursive:false}))});
refuse('mutual recursion',{...original,bindings:[original.bindings[0],{recursive:true,bindings:[builder(),builder({name:'other'})]},original.bindings[2]]});
refuse('recursive helpers',{...original,bindings:original.bindings.map(g=>({...g,recursive:true}))});

let deep=builder().value1;
for(let n=0;n<10000;n++) deep=typed(any,deep);
refuse('deep proof budget',mod(consume(),new Tuple('chain',deep)));
let deepHelper=getter().value1;
for(let n=0;n<10000;n++) deepHelper=typed(any,deepHelper);
refuse('deep helper budget',mod(consume(),builder(),new Tuple('get',deepHelper)));
let big=consume();
for(let n=0;n<14;n++) big=new S.Branch([new S.Pair(eq(lit(0),lit(0)),big)],big);
refuse('caller node budget',mod(big));
refuse('wide caller budget',mod(new S.Branch(Array.from({length:100},()=>new S.Pair(eq(lit(0),lit(0)),consume())),consume())));
refuse('module group budget',{...original,bindings:[...original.bindings,...Array.from({length:254},()=>({recursive:false,bindings:[]}))]});
refuse('binding group budget',mod(consume(),builder(),getter(),modifier(),[{recursive:false,bindings:Array.from({length:65},(_,n)=>entry(lit(0),'unused'+n))}]));
const many=Array.from({length:workerBudget+1},(_,n)=>({recursive:true,bindings:[builder({name:'chain'+n})]}));
const uses=Array.from({length:workerBudget+1},(_,n)=>entry(consume(loc(0),loc(1),'chain'+n),'use'+n));
const capped=optimize({...original,bindings:[original.bindings[0],...many,{recursive:false,bindings:uses}]});
assert.equal(size(capped.privateNames),workerBudget*2,'bounded workers and guards');
const collision=mod(consume(loc(0)),builder(),getter(),modifier(),[{recursive:false,bindings:[entry(lit(0),'__PHPURS_STATE_0_collision'),entry(lit(0),'__PHPURS_RUNSTATE_0_collision')]}]);
assert.ok(JSON.stringify(optimize(collision).module_).includes('__phpurs_state_1_chain'));
assert.ok(JSON.stringify(optimize(collision).module_).includes('__phpurs_runstate_1_chain'));
function rename(value) {
  if(typeof value==='string') return ({Demo:'Renamed',chain:'assemble',get:'read',modify:'advance',val:'answer',state:'store'})[value]??value;
  if(Array.isArray(value)) return value.map(rename);
  if(value && typeof value==='object') return Object.assign(Object.create(Object.getPrototypeOf(value)),Object.fromEntries(Object.entries(value).map(([key,item])=>[key,rename(item)])));
  return value;
}
assert.equal(size(optimize(rename(dynamic)).privateNames),2,'module, helper and field names are not criteria');

function render(input) {
  const file=translate([])(input);
  return printPhpFile(false)('')(file.arities)(file);
}
function phpRun(php,checks) {
  const result=spawnSync('php',['-d','opcache.enable_cli=0'],{input:php+"\n$GLOBALS['Data_Unit_unit']=null;\n"+checks+'\necho "Done\\n";',encoding:'utf8'});
  assert.equal(result.status,0,result.stdout+result.stderr);
  assert.equal(result.stdout,'Done\n');
}
const php=render(dynamic);
assert.ok(!/\$GLOBALS\['[^']*__phpurs_(?:state|runstate)_/.test(php),'no public private-worker bindings');
assert.match(php,/function majDemo___phpurs_runstate_0_chain\(int \$depth_0/,'native depth check preserved');
assert.match(php,/goto tco_loop_[^;]*state/,'worker is a real loop');
phpRun(php,`
foreach ([0,1,2,17,127,2048] as $depth) foreach ([-31,0,11,2147483647,PHP_INT_MAX-1] as $initial) {
  $a=\\Demo\\majDemo_entry($depth,$initial);
  $b=\\Demo\\majDemo_chain($depth,$initial)->state;
  if (serialize($a)!==serialize($b)) throw new \\Exception('numeric parity');
}
$large=PHP_INT_MAX-1;
if (serialize(\\Demo\\majDemo_entry(2048,$large))===serialize($large+2048)) throw new \\Exception('arithmetic shortcut');
foreach (['invalid',[],(object)[]] as $bad) {
  foreach (['majDemo_entry','majDemo_chain'] as $fn) {
    $caught=false;
    try { (__NAMESPACE__.'\\\\'.$fn)($bad,0); } catch (\\TypeError $e) { $caught=true; }
    if (!$caught) throw new \\Exception('lost depth check');
  }
}
$root=\\Demo\\majDemo_chain(17);
if (!$root instanceof \\Closure) throw new \\Exception('public State representation');
$a=$root(11); $b=$root(-31);
if ($a->state!==28 || $b->state!==-14 || $a->val!==null || $a===$b) throw new \\Exception('retained State/records');
function requireClosure(\\Closure $f) { return $f(7); }
if (requireClosure($root)->state!==24) throw new \\Exception('Closure FFI');
$calls=0; $captured=3;
$unknown=\\Demo\\majDemo_modify(function($s) use (&$calls,&$captured) { ++$calls; return $s+$captured; });
if ($calls!==0 || $unknown(11)->state!==14) throw new \\Exception('callback executed early');
$captured=8;
if ($unknown(11)->state!==19 || $calls!==2) throw new \\Exception('callback reuse/capture');
$same=new \\RuntimeException('callback'); $caught=false;
try { \\Demo\\majDemo_modify(function($s) use ($same) { throw $same; },0); }
catch (\\RuntimeException $e) { $caught=true; if ($e!==$same) throw new \\Exception('exception identity'); }
if (!$caught) throw new \\Exception('missing callback failure');
`);
for(const [operation,amount] of [[S.OpSubtract.value,7],[S.OpMultiply.value,2147483647]]) {
  phpRun(render(mod(consume(loc(0),loc(1)),builder({step:callback(operation,amount)}))),`
  foreach ([0,1,2,3,17] as $depth) foreach ([-3,0,2,2147483647] as $initial) {
    if (serialize(\\Demo\\majDemo_entry($depth,$initial))!==serialize(\\Demo\\majDemo_chain($depth,$initial)->state)) throw new \\Exception('ordered arithmetic');
  }`);
}

// Instrument only the negative target, since the real negative chain diverges.
const guardStart=php.indexOf('function majDemo___phpurs_runstate_0_chain(');
assert.ok(guardStart>=0);
const instrumented=php.slice(0,guardStart)+php.slice(guardStart).replaceAll('\\Demo\\majDemo_chain(', '\\Demo\\negative(');
assert.notEqual(instrumented,php);
phpRun(instrumented,`
$calls=0; $same=new \\RuntimeException('negative'); $returns=false;
function negative($depth,$state) {
  ++$GLOBALS['calls'];
  if ($depth!==-7 || $state!==11) throw new \\Exception('fallback arguments');
  if ($GLOBALS['returns']) return (object)['val'=>fn()=>throw new \\Exception('val invoked'), 'state'=>99];
  throw $GLOBALS['same'];
}
$caught=false;
try { \\Demo\\majDemo_entry(-7,11); }
catch (\\RuntimeException $e) { $caught=true; if ($e!==$same) throw new \\Exception('negative exception identity'); }
if (!$caught || $calls!==1 || \\Demo\\majDemo_entry(0,11)!==11 || \\Demo\\majDemo_entry(17,11)!==28 || $calls!==1) throw new \\Exception('guard route');
$returns=true;
if (\\Demo\\majDemo_entry(-7,11)!==99 || $calls!==2) throw new \\Exception('negative projection');
`);
const ordered=new S.Let(new Just('x3'),3,foreignValue('depth'),new S.Let(new Just('x4'),4,foreignValue('initial'),consume(loc(3),loc(4))));
phpRun(render(mod(ordered)),`
$events=[];
$GLOBALS['Demo_depth']=function($u) use (&$events) { $events[]='depth'; return 17; };
$GLOBALS['Demo_initial']=function($u) use (&$events) { $events[]='initial'; return 11; };
if (\\Demo\\majDemo_entry(null,null)!==28 || $events!==['depth','initial']) throw new \\Exception('evaluation order/count');
$same=new \\RuntimeException('initial');
$GLOBALS['Demo_initial']=function($u) use (&$events,$same) { $events[]='throw'; throw $same; };
$caught=false;
try { \\Demo\\majDemo_entry(null,null); } catch (\\RuntimeException $e) { $caught=true; if ($e!==$same) throw new \\Exception('initial exception'); }
if (!$caught || $events!==['depth','initial','depth','throw']) throw new \\Exception('exception order');
`);
console.log(`state-fusion: ${refusals} refusals, lexical inputs, helpers/layouts, budgets, idempotence, negative guard, public contracts and 70 numeric comparisons passed`);
