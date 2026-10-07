import assert from 'node:assert/strict';
import fs from 'node:fs';
import {spawnSync} from 'node:child_process';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import {Just, Nothing} from '../../output/Data.Maybe/index.js';
import {Tuple} from '../../output/Data.Tuple/index.js';
import {empty as emptyMap, fromFoldable, union} from '../../output/Data.Map/index.js';
import {empty as emptySet, size} from '../../output/Data.Set/index.js';
import {foldableArray} from '../../output/Data.Foldable/index.js';
import {ordString} from '../../output/Data.Ord/index.js';
import {optimize, workerBudget} from '../../output/Phpurs.CallbackSpecialization/index.js';
import {fromForeign} from '../../output/Phpurs.NativeCallbacks/index.js';
import {translateWithCallbacks} from '../../output/Phpurs.CodeGen/index.js';
import {printPhpFile} from '../../output/Phpurs.Printer/index.js';
import {renderModuleState} from '../../output/Phpurs.ModuleState/index.js';

const int=T.Int.value, any=T.Any.value, a=new T.TypeVar('a'), b=new T.TypeVar('b');
const typed=(ty,e)=>new S.Typed(ty,e);
const q=(name,module='Demo')=>new T.Qualified(new Just(module),name);
const v=name=>new S.Var(q(name));
const local=(i,ty=int,name='x'+i)=>typed(ty,new S.Local(new Just(name),i));
const lit=n=>typed(int,new S.Lit(new T.LitInt(n)));
const abs=(ns,body)=>new S.Abs(ns.map(i=>new Tuple(new Just('x'+i),i)),body);
const app=(fn,args)=>new S.App(fn,args);
const call=(name,args)=>app(v(name),args);
const binary=(op,l,r)=>typed(int,new S.PrimOp(new S.Op2(new S.OpIntNum(op),l,r)));
const listType=element=>new T.ADT('Demo.Spine',['Demo','Spine'],[element]);
const foldSignature=new T.ForAll(['a','b'],new T.Func([new T.Func([b,a],b),b,listType(a)],b));
const cell=(index,list=local(2, listType(a)),ctor='Link')=>new S.Accessor(list,new S.GetCtorField(q(ctor),T.SumType.value,'Spine',ctor,'value'+index,index));
const isTag=(ctor,value=local(2,listType(a)))=>new S.PrimOp(new S.Op1(new S.OpIsTag(q(ctor)),value));
const foldBody=({name='fold',base=local(1,b),emptyTest=isTag('Done'),consTest=isTag('Link'),
  callback=local(0,new T.Func([b,a],b)),acc=local(1,b),head=cell(0),tail=cell(1),
  callbackUse,forward=local(0),fallback=new S.Fail('Failed pattern match')}={})=>
  new S.Branch([new S.Pair(emptyTest,base),new S.Pair(consTest,call(name,[forward,callbackUse??app(callback,[acc,head]),tail]))],fallback);
const fold=(options={})=>new Tuple(options.name??'fold',typed(options.signature??foldSignature,
  typed(foldSignature.value1,abs(options.params??[0,1,2],foldBody(options)))));
const native=(name='add',op=S.OpAdd.value,body=binary(op,local(0),local(1)))=>
  new Tuple(name,typed(new T.Func([int,int],int),abs([0,1],body)));
const consume=(callback=v('add'),initial=lit(0),items=local(1,listType(int)),target='fold')=>call(target,[callback,initial,items]);
const entry=(body=consume(),name='entry')=>new Tuple(name,typed(new T.Func([any,any],any),abs([0,1],body)));
const declaration={name:'Spine',vars:['element'],constructors:[{name:'Done',fields:[]},{name:'Link',fields:[new T.TypeVar('element'),listType(new T.TypeVar('element'))]}]};
const mod=(body=consume(),foldBinding=fold(),callback=native(),extras=[])=>({
  name:'Demo',bindings:[{recursive:false,bindings:[callback]},{recursive:true,bindings:[foldBinding]},...extras,{recursive:false,bindings:[entry(body)]}],
  dataDecls:[declaration],foreign:emptyMap,exports:emptySet,imports:emptySet,dataTypes:emptyMap,
  comments:[],reExports:emptySet,classDecls:[],implementations:emptyMap,directives:emptyMap,
});
const specialize=optimize(emptySet);
const original=mod();
const selected=specialize(original);
assert.equal(size(selected.privateNames),1);
assert.deepEqual(selected.module_.bindings.slice(0,2),original.bindings.slice(0,2),'public callback and fold unchanged');
assert.deepEqual(specialize(selected.module_).module_,selected.module_,'idempotent');
assert.equal(size(specialize(selected.module_).privateNames),0,'no repeated copies');
const x=new T.TypeVar('x$inner'),z=new T.TypeVar('z$inner');
const innerSignature=new T.Func([new T.Func([z,x],z),z,listType(x)],z);
assert.equal(size(specialize(mod(consume(),new Tuple('fold',typed(foldSignature,typed(innerSignature,fold().value1.value1.value1))))).privateNames),1,'effective signature is alpha-equivalent to the declaration scheme');
for(const n of [-17,0,7,2147483647]) assert.equal(size(specialize(mod(consume(v('add'),lit(n)))).privateNames),1,'literal Int accumulator');
assert.equal(size(specialize(mod(typed(int,new S.TypeApp(consume(typed(new T.Func([int,int],int),v('add'))),int)))).privateNames),1,'annotation wrappers');
const shared=mod(consume(),fold(),native(),[{recursive:false,bindings:[entry(consume(v('add'),lit(11)),'second')]}]);
assert.equal(size(specialize(shared).privateNames),1,'reuse one copy per fold/callback pair');
const two=mod(consume(),fold(),native(),[{recursive:false,bindings:[native('mul',S.OpMultiply.value),entry(consume(v('mul'),lit(1)),'product')]}]);
assert.equal(size(specialize(two).privateNames),2,'distinct native callback copies');
const nested=mod(consume(v('add'),lit(0),call('makeList',[consume(v('add'),lit(3))])));
const nestedSelected=specialize(nested);
assert.equal(size(nestedSelected.privateNames),1,'nested consumers share their copy');
assert.deepEqual(specialize(nestedSelected.module_).module_,nestedSelected.module_,'nested consumers are visited in one scan');

let refusals=0;
function refuse(reason,input) {
  const result=specialize(input);
  assert.equal(size(result.privateNames),0,reason);
  assert.deepEqual(result.module_,input,reason+': original AST');
  refusals++;
}
refuse('unknown callback',mod(consume(local(0))));
refuse('literal closure is not a native wrapper',mod(consume(typed(new T.Func([int,int],int),abs([3,4],binary(S.OpAdd.value,local(3),local(4)))))));
refuse('foreign callback without contract',mod(consume(new S.Var(q('add','Foreign')))));
refuse('retained partial fold',mod(call('fold',[v('add'),lit(0)])));
refuse('oversaturated fold',mod(call('fold',[v('add'),lit(0),local(1),lit(3)])));
refuse('indirect traversal',mod(app(local(0),[v('add'),lit(0),local(1)])));
refuse('foreign traversal lacks a body contract',mod(app(new S.Var(q('fold','Other')),[v('add'),lit(0),local(1)])));
refuse('dynamic seed might fail the first parameter check',mod(consume(v('add'),local(0))));
refuse('computed seed',mod(consume(v('add'),binary(S.OpAdd.value,lit(0),lit(1)))));
refuse('wrong seed type',mod(consume(v('add'),typed(T.Number.value,new S.Lit(new T.LitNumber(0))))));
refuse('untyped seed',mod(consume(v('add'),new S.Lit(new T.LitInt(0)))));
refuse('branch returning function has runtime arity one',mod(consume(),fold(),new Tuple('add',typed(new T.Func([int,int],int),abs([0],new S.Branch([new S.Pair(new S.Lit(new T.LitBoolean(true)),abs([1],binary(S.OpAdd.value,local(0),local(1))))],abs([1],lit(0))))))));
refuse('first stage effects before returned function',mod(consume(),fold(),new Tuple('add',typed(new T.Func([int,int],int),abs([0],new S.Let(new Just('x2'),2,call('observe',[local(0)]),abs([1],binary(S.OpAdd.value,local(0),local(1)))))))));
refuse('native body calls opaque code',mod(consume(),fold(),native('add',S.OpAdd.value,call('inspect',[local(0),local(1)]))));
refuse('callback capture',mod(consume(),fold(),native('add',S.OpAdd.value,binary(S.OpAdd.value,local(0),local(3)))));
refuse('wrong callback signature',mod(consume(),fold(),new Tuple('add',typed(new T.Func([int,int],any),native().value1.value1))));
refuse('nested local signature is not a saturated printer arity',mod(consume(),fold(),new Tuple('add',typed(new T.Func([int],new T.Func([int],int)),native().value1.value1))));
refuse('duplicate callback parameter',mod(consume(),fold(),new Tuple('add',typed(new T.Func([int,int],int),abs([0,0],binary(S.OpAdd.value,local(0),local(0)))))));
refuse('callback division not in proven body set',mod(consume(),fold(),native('add',S.OpDivide.value)));
refuse('reversed callback operands',mod(consume(),fold(),native('add',S.OpAdd.value,binary(S.OpAdd.value,local(1),local(0)))));
refuse('wrong fold signature',mod(consume(),fold({signature:any})));
refuse('missing effective fold signature',mod(consume(),new Tuple('fold',typed(foldSignature,fold().value1.value1.value1))));
for(const signature of [new T.Func([int,b,listType(a)],b),new T.Func([new T.Func([b,a],b),b,listType(a)],int),new T.Func([new T.Func([b,a],b)],new T.Func([b,listType(a)],b))])
  refuse('specialized/nested effective fold signature',mod(consume(),new Tuple('fold',typed(foldSignature,typed(signature,fold().value1.value1.value1)))));
refuse('wrong runtime fold arity',mod(consume(),fold({params:[0,1]})));
refuse('duplicate fold parameter',mod(consume(),fold({params:[0,1,1]})));
refuse('wrong base result',mod(consume(),fold({base:lit(0)})));
refuse('unknown empty constructor',mod(consume(),fold({emptyTest:isTag('Unknown')})));
refuse('constructor test on another input',mod(consume(),fold({consTest:isTag('Link',local(3))})));
refuse('wrong forwarded callback',mod(consume(),fold({forward:local(1)})));
refuse('changed accumulator argument',mod(consume(),fold({acc:lit(0)})));
refuse('callback local name mismatch',mod(consume(),fold({callback:local(0,any,'other')})));
refuse('callback called only once then escapes',mod(consume(),fold({callbackUse:app(local(0),[local(1)])})));
refuse('callback result consumed twice',mod(consume(),fold({callbackUse:binary(S.OpAdd.value,app(local(0),[local(1),cell(0)]),app(local(0),[local(1),cell(0)]))})));
refuse('unknown head computation',mod(consume(),fold({head:call('read',[local(2)])})));
refuse('wrong field index',mod(consume(),fold({head:cell(1)})));
refuse('wrong constructor field metadata',mod(consume(),fold({head:cell(0,local(2),'Other')})));
refuse('tail is not advanced',mod(consume(),fold({tail:local(2)})));
refuse('fallback executes unknown code',mod(consume(),fold({fallback:call('observe',[local(1)])})));
refuse('missing layout',{...original,dataDecls:[]});
refuse('wrong recursive layout',{...original,dataDecls:[{...declaration,constructors:[declaration.constructors[0],{name:'Link',fields:[new T.TypeVar('element'),int]}]}]});
refuse('extra constructor',{...original,dataDecls:[{...declaration,constructors:[...declaration.constructors,{name:'Other',fields:[]}]}]});
refuse('mutual recursion',{...original,bindings:[original.bindings[0],{recursive:true,bindings:[fold(),fold({name:'other'})]},original.bindings[2]]});
refuse('recursive callback',{...original,bindings:original.bindings.map(g=>({...g,recursive:true}))});
refuse('nonrecursive traversal',{...original,bindings:original.bindings.map(g=>({...g,recursive:false}))});
let deep=fold().value1;
for(let n=0;n<10000;n++) deep=typed(any,deep);
refuse('deep proof',mod(consume(),new Tuple('fold',deep)));
let deepType=int;
for(let n=0;n<10000;n++) deepType=new T.Func([deepType],deepType);
refuse('deep effective type metadata',mod(consume(),new Tuple('fold',typed(foldSignature,typed(deepType,fold().value1.value1.value1)))));
refuse('deep layout metadata',{...original,dataDecls:[{...declaration,constructors:[declaration.constructors[0],{name:'Link',fields:[new T.TypeVar('element'),deepType]}]}]});
let large=consume();
for(let n=0;n<14;n++) large=new S.Branch([new S.Pair(new S.Lit(new T.LitBoolean(true)),large)],large);
refuse('caller budget',mod(large));
refuse('wide caller',mod(new S.Branch(Array.from({length:100},()=>new S.Pair(new S.Lit(new T.LitBoolean(true)),consume())),consume())));
refuse('module budget',{...original,bindings:[...original.bindings,...Array.from({length:254},()=>({recursive:false,bindings:[]}))]});
refuse('layout budget',{...original,dataDecls:Array.from({length:129},()=>declaration)});
const callbacks=Array.from({length:workerBudget+1},(_,n)=>native('add'+n));
const entries=callbacks.map(({value0:name},i)=>entry(consume(v(name)),'e'+i));
const capped=specialize({...original,bindings:[{recursive:false,bindings:callbacks},original.bindings[1],{recursive:false,bindings:entries}]});
assert.equal(size(capped.privateNames),workerBudget,'bounded private copies');
const collision=mod(consume(),fold(),native(),[{recursive:false,bindings:[entry(lit(0),'__PHPURS_FOLDCB_0_collision')]}]);
assert.ok(JSON.stringify(specialize(collision).module_).includes('__phpurs_foldcb_1_0'),'fresh case-insensitive names');
function rename(value) {
  if(typeof value==='string') return value.replaceAll('Demo','Elsewhere').replaceAll('Spine','Sequence').replaceAll('Link','Pair').replaceAll('Done','Stop').replaceAll('fold','traverse').replaceAll('add','combine');
  if(Array.isArray(value)) return value.map(rename);
  if(value && typeof value==='object') return Object.assign(Object.create(Object.getPrototypeOf(value)),Object.fromEntries(Object.entries(value).map(([key,item])=>[key,rename(item)])));
  return value;
}
assert.equal(size(specialize(rename(original)).privateNames),1,'no benchmark/type/constructor names in fold proof');

const semiringSource=fs.readFileSync(new URL('../../../phpurs-prelude/src/Data/Semiring.php',import.meta.url),'utf8');
const map=entries=>fromFoldable(T.ordIdent)(foldableArray)(entries.map(([k,v])=>new Tuple(k,v)));
const ffiBindings=map([['intAdd',new Just(new T.Func([int,int],int))],['intMul',new Just(new T.Func([int,int],int))]]);
const ffiCallbacks=fromForeign('Data.Semiring')(ffiBindings)(semiringSource);
assert.equal(size(ffiCallbacks),2,'verified foreign source and emitted wrapper signatures');
for(const source of ['',semiringSource+'\n',semiringSource.replace('$a + $b','inspect($a, $b)')])
  assert.equal(size(fromForeign('Data.Semiring')(ffiBindings)(source)),0,'unknown foreign bytes have no contract');
assert.equal(size(fromForeign('Other')(ffiBindings)(semiringSource)),0,'module contract');
assert.equal(size(fromForeign('Data.Semiring')(emptyMap)(semiringSource)),0,'missing foreign signatures');
assert.equal(size(fromForeign('Data.Semiring')(map([['intAdd',new Just(new T.Func([int],int))]]))(semiringSource)),0,'runtime wrapper arity must be two');
const imported=mod(consume(new S.Var(q('intAdd','Data.Semiring'))));
assert.equal(size(optimize(ffiCallbacks)(imported).privateNames),1,'foreign evidence selects the fold');
assert.equal(size(specialize(imported).privateNames),0,'flattened callback type alone cannot select the fold');

function render(input,proofs=emptySet,externalArities=emptyMap) {
  const file=translateWithCallbacks(proofs)([])(input);
  return printPhpFile(false)('')(union(ordString)(file.arities)(externalArities))(file);
}
function run(php,checks) {
  const result=spawnSync('php',['-d','opcache.enable_cli=0'],{input:php+'\n'+checks+'\necho "Done\\n";',encoding:'utf8'});
  assert.equal(result.status,0,result.stdout+result.stderr);
  assert.equal(result.stdout,'Done\n');
}
const php=render(original);
assert.ok(!/\$GLOBALS\['[^']*__phpurs_foldcb_/.test(php),'workers stay private');
const worker=php.slice(php.indexOf('// Demo___phpurs_foldcb_'));
assert.match(worker,/\\Demo\\majDemo_add\(/,'call native wrapper, not primitive arithmetic');
assert.doesNotMatch(worker,/\(\(\$x0_0\)/,'no two-stage callback application');
assert.match(worker,/goto tco_loop_[^;]*foldcb/,'traversal stays a TCO loop');
const helpers=`
function listOf($values) { $list=new Demo_Done(); foreach(array_reverse($values) as $v) $list=new Demo_Link($v,$list); return $list; }
function outcome($f) { try { return ['value',serialize($f())]; } catch (\\Throwable $e) { return [get_class($e),$e->getMessage()]; } }
`;
run(php,helpers+`
foreach ([[],[1],[1,2,3],[-3,5,-7],range(1,450),[PHP_INT_MAX],[PHP_INT_MAX,1],[PHP_INT_MIN,-1],[PHP_INT_MAX,PHP_INT_MAX],[PHP_INT_MIN,PHP_INT_MIN],[PHP_INT_MAX,1,-1],[1.0,2],[true,null],['2',3],['invalid'],[(object)[]]] as $values) {
  $list=listOf($values); $saved=serialize($list);
  $before=outcome(fn()=>majDemo_fold($GLOBALS['Demo_add'],0,$list));
  $after=outcome(fn()=>majDemo_entry(null,$list));
  if ($before!==$after || serialize($list)!==$saved) throw new \\Exception('fold parity '.serialize([$values,$before,$after]));
}
foreach ([(object)[],new Demo_Link(7,(object)[])] as $bad) {
  if (outcome(fn()=>majDemo_fold($GLOBALS['Demo_add'],0,$bad))!==outcome(fn()=>majDemo_entry(null,$bad))) throw new \\Exception('original failure location');
}
$events=[];
$callback=function($acc) use (&$events) { $events[]=['first',$acc]; return function($item) use (&$events,$acc) { $events[]=['second',$item]; return $acc+$item; }; };
$partial=majDemo_fold($callback,0);
if (!$partial instanceof \\Closure || $events!==[] || $partial(listOf([1,2]))!==3 || $partial(listOf([3]))!==3 || $events!==[['first',0],['second',1],['first',1],['second',2],['first',0],['second',3]]) throw new \\Exception('public callback order/retention');
$same=new \\RuntimeException('callback');
foreach ([fn($acc)=>throw $same, fn($acc)=>fn($item)=>throw $same] as $throwing) {
  $caught=false; try { majDemo_fold($throwing,0,listOf([1])); } catch (\\RuntimeException $e) { $caught=true; if($e!==$same) throw new \\Exception('exception identity'); }
  if (!$caught) throw new \\Exception('exception swallowed');
}
// The raw primitive would return float; the existing native wrapper must fail.
$overflow=outcome(fn()=>majDemo_entry(null,listOf([PHP_INT_MAX,1])));
if ($overflow[0]!=='TypeError' || !str_contains($overflow[1],'majDemo_add(): Return value')) throw new \\Exception('native return check lost');
`);
run(render(mod(consume(v('add'),lit(0),call('items',[local(0)])))),helpers+`
$calls=0;
$GLOBALS['Demo_items']=function($values) use (&$calls) { ++$calls; return listOf($values); };
if (majDemo_entry([1,2,3],null)!==6 || $calls!==1 || majDemo_entry([4],null)!==4 || $calls!==2) throw new \\Exception('list input evaluation');
`);
for(const [name,op,initial] of [['subtract',S.OpSubtract.value,17],['multiply',S.OpMultiply.value,1]]) {
  run(render(mod(consume(v(name),lit(initial)),fold(),native(name,op))),helpers+`
  foreach ([[],[1,2,3],[-3,0,7],[PHP_INT_MAX,2],[PHP_INT_MIN,2]] as $values) {
    $list=listOf($values);
    if (outcome(fn()=>majDemo_fold($GLOBALS['Demo_${name}'],${initial},$list))!==outcome(fn()=>majDemo_entry(null,$list))) throw new \\Exception('native operation parity');
  }`);
}
const foreignState=renderModuleState({emitModules:true,emitBundle:false})([])(semiringSource)(emptyMap)({...original,name:'Data.Semiring',bindings:[],dataDecls:[],foreign:ffiBindings});
const foreignPhp=foreignState.modularPhp.value0+'\n'+render(imported,ffiCallbacks,foreignState.arities).replace(/^<\?php/,'');
run(foreignPhp,helpers+`
foreach ([[],[1,2,3],range(1,450),[PHP_INT_MAX,1],[PHP_INT_MIN,-1]] as $values) {
  $list=listOf($values);
  if (outcome(fn()=>majDemo_fold($GLOBALS['Data_Semiring_intAdd'],0,$list))!==outcome(fn()=>majDemo_entry(null,$list))) throw new \\Exception('foreign wrapper parity');
}
`);
console.log(`callback-specialization: ${refusals} refusals, native arity/source proofs, retained public callbacks, overflow/error parity, layouts, copies and budgets passed`);
