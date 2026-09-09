// Test typed optimizer nodes directly so PBO cannot simplify away a refusal.
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import {Just, Nothing} from '../../output/Data.Maybe/index.js';
import {Tuple} from '../../output/Data.Tuple/index.js';
import {empty as emptyMap} from '../../output/Data.Map/index.js';
import {empty as emptySet, size} from '../../output/Data.Set/index.js';
import {optimize, workerBudget} from '../../output/Phpurs.PartialBindings/index.js';
import {translate} from '../../output/Phpurs.CodeGen/index.js';
import {printPhpFile} from '../../output/Phpurs.Printer/index.js';

const int=T.Int.value, unary=new T.Func([int],int), chain=new T.Func([unary,int],int), factory=new T.Func([int,unary,int],int);
const typed=(ty,e)=>new S.Typed(ty,e);
const q=(n,m='Demo')=>new T.Qualified(new Just(m),n);
const v=n=>new S.Var(q(n));
const local=(n,ty=int)=>typed(ty,new S.Local(new Just('x'+n),n));
const lit=n=>typed(int,new S.Lit(new T.LitInt(n)));
const app=(f,args,ty=int)=>typed(ty,new S.App(f,args));
const call=(n,args,ty=int)=>app(v(n),args,ty);
const abs=(n,ty,body)=>typed(ty,new S.Abs([new Tuple(new Just('x'+n),n)],body));
const let_=(n,value,body,ty=chain)=>typed(ty,new S.Let(new Just('x'+n),n,value,body));
const op=(o,a,b)=>typed(int,new S.PrimOp(new S.Op2(new S.OpIntNum(o),a,b)));
const eq=(a,b)=>new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpEq.value),a,b));
const neutral=()=>new Tuple('neutral',abs(0,chain,abs(1,unary,local(1))));
function builder({name='grow',base=v('neutral'),step=1,inner,condition=eq(local(0),lit(0)),signature=factory}={}) {
  const body=inner ?? app(local(2,unary),[app(local(1,chain),[local(2,unary),local(3)])]);
  const recurse=call(name,[op(S.OpSubtract.value,local(0),lit(step))],chain);
  const successor=let_(1,recurse,abs(2,chain,abs(3,unary,body)));
  return new Tuple(name,abs(0,signature,typed(chain,new S.Branch([new S.Pair(condition,base)],successor))));
}
function composition(name='combine',left='grow',right='grow',body) {
  return new Tuple(name,abs(0,factory,let_(1,call(left,[local(0)],chain),let_(2,call(right,[local(0)],chain),
    abs(3,chain,abs(4,unary,body ?? app(local(1,chain),[app(local(2,chain),[local(3,unary)],unary),local(4)])))))));
}
const callback=(operation=S.OpAdd.value)=>abs(5,unary,op(operation,local(5),lit(3)));
const consume=(cb=callback(),name='combine',count=lit(5),seed=lit(7))=>call(name,[count,cb,seed]);
const entry=(body,name='entry')=>new Tuple(name,abs(0,new T.Func([int],int),body));
const mod=(body=consume(),build=builder(),extras=[])=>({
  name:'Demo',bindings:[{recursive:false,bindings:[neutral()]},{recursive:true,bindings:[build]},
    {recursive:false,bindings:[composition(),...extras,entry(body)]}],
  dataDecls:[],foreign:emptyMap,exports:emptySet,imports:emptySet,dataTypes:emptyMap,
  comments:[],reExports:emptySet,classDecls:[],implementations:emptyMap,directives:emptyMap,
});
const original=mod(),selected=optimize(original);
assert.equal(size(selected.privateNames),2);
assert.deepEqual(selected.module_.bindings.slice(0,2),original.bindings.slice(0,2),'public builder unchanged');
assert.deepEqual(selected.module_.bindings[2].bindings[0],original.bindings[2].bindings[0],'public composition unchanged');
assert.deepEqual(optimize(selected.module_).module_,selected.module_,'idempotent');
assert.equal(size(optimize(mod(consume(callback(),'grow',local(0)))).privateNames),1,'dynamic integer depth');
assert.equal(size(optimize(mod(consume(callback(),'grow',lit(0)))).privateNames),1,'neutral chain');
assert.equal(size(optimize(mod(typed(int,new S.TypeApp(consume(),int)))).privateNames),2,'typed application wrappers');
const refuse=(reason,m)=>{const r=optimize(m);assert.equal(size(r.privateNames),0,reason);assert.deepEqual(r.module_,m,reason+' preserves AST');};
refuse('unknown callback',mod(consume(local(8,unary))));
refuse('foreign callback',mod(consume(abs(5,unary,call('observe',[local(5)])))));
refuse('captured callback value',mod(consume(abs(5,unary,op(S.OpAdd.value,local(5),local(7))))));
refuse('division can throw',mod(consume(callback(S.OpDivide.value))));
refuse('callback missing annotation',mod(consume(callback().value1)));
refuse('wrong callback type',mod(consume(typed(T.Any.value,callback().value1))));
refuse('partial chain escapes',mod(call('combine',[lit(5)],chain)));
refuse('bound callback escapes',mod(call('combine',[lit(5),callback()],unary)));
refuse('unflattened application',mod(app(call('combine',[lit(5)],chain),[callback(),lit(7)])));
refuse('extra argument',mod(call('combine',[lit(5),callback(),lit(7),lit(1)])));
refuse('effectful depth',mod(consume(callback(),'combine',call('opaque',[lit(5)]))));
refuse('effectful seed',mod(consume(callback(),'combine',lit(5),call('opaque',[lit(7)]))));
refuse('unknown seed type',mod(consume(callback(),'combine',lit(5),local(0,T.Any.value))));
refuse('wrong countdown',mod(consume(),builder({step:2})));
refuse('wrong stop',mod(consume(),builder({condition:eq(local(0),lit(1))})));
refuse('unknown neutral producer',mod(consume(),builder({base:v('foreign')})));
refuse('unknown builder type',mod(consume(),builder({signature:T.Any.value})));
refuse('callback invoked during binding',mod(consume(),builder({inner:app(local(2,unary),[lit(0)])})));
const badNeutral=mod();badNeutral.bindings[0].bindings[0]=new Tuple('neutral',abs(0,chain,abs(1,unary,call('observe',[local(1)]))));
refuse('effectful neutral',badNeutral);
const badCompose=mod();badCompose.bindings[2].bindings[0]=composition('combine','grow','unknown');
refuse('unknown composition producer',badCompose);
const cycle=mod();cycle.bindings[2].bindings[0]=composition('combine','combine','grow');
refuse('recursive composition',cycle);
const mutual=mod();mutual.bindings[1].bindings.push(builder({name:'other'}));
refuse('mutual builder group',mutual);
let deep=builder().value1;for(let n=0;n<10000;n++)deep=typed(factory,deep);
refuse('bounded proof depth',mod(consume(),new Tuple('grow',deep)));
let huge=consume();for(let n=0;n<14;n++)huge=new S.Branch([new S.Pair(eq(lit(0),lit(0)),huge)],huge);
refuse('bounded caller size',mod(huge));
const wide=mod();wide.bindings[2].bindings=Array.from({length:100},(_,i)=>entry(consume(),'entry'+i));
refuse('bounded group width',wide);
const collision=mod(consume(),builder(),[entry(lit(0),'__PHPURS_BIND_0_collision')]);
assert.ok(JSON.stringify(optimize(collision)).includes('__phpurs_bind_1_grow'),'case-insensitive collision');
function rename(x){if(typeof x==='string')return x.replaceAll('Demo','Elsewhere').replaceAll('grow','assemble');if(Array.isArray(x))return x.map(rename);if(x&&typeof x==='object')return Object.assign(Object.create(Object.getPrototypeOf(x)),Object.fromEntries(Object.entries(x).map(([k,v])=>[k,rename(v)])));return x;}
assert.equal(size(optimize(rename(original)).privateNames),2,'no benchmark names');
const many=mod();many.bindings=[many.bindings[0],...Array.from({length:workerBudget+1},(_,i)=>({recursive:true,bindings:[builder({name:'g'+i})]})),{recursive:false,bindings:Array.from({length:workerBudget+1},(_,i)=>entry(consume(callback(),'g'+i),'e'+i))}];
assert.equal(size(optimize(many).privateNames),workerBudget,'worker cap');

const dynamic=entry(consume(callback(),'combine',local(0)),'dynamic');
const unknown=new Tuple('unknown',abs(0,new T.Func([unary],int),consume(local(0,unary),'grow')));
const file=translate([])(mod(consume(),builder(),[dynamic,unknown]));
const php=printPhpFile(false)('')(file.arities)(file);
assert.ok(!/\$GLOBALS\['[^']*__phpurs_bind_/.test(php),'no private globals, including partial calls');
assert.match(php,/bound/,'bound callback emitted');
const run=spawnSync('php',[],{input:php+`
if (\\Demo\\majDemo_entry(0) !== 82) throw new \\Exception('closed result');
foreach ([0,1,2,5,10] as $n) {
 if (\\Demo\\majDemo_dynamic($n) !== 7+3*$n*$n) throw new \\Exception('dynamic result');
 foreach ([-11,0,17] as $seed) {
  $calls=0; $f=function($x) use (&$calls) { ++$calls; return $x+3; };
  $public=\\Demo\\majDemo_grow($n); $bound=$public($f);
  if($calls !== 0) throw new \\Exception('early callback');
  if($bound($seed)!==$seed+3*$n || $bound($seed)!==$seed+3*$n || $calls!==2*$n) throw new \\Exception('reuse');
  $private=\\Demo\\majDemo___phpurs_bind_0_grow($n);
  $calls=0; $bound=$private($f);
  if($calls!==0 || $bound($seed)!==$seed+3*$n || $bound($seed)!==$seed+3*$n || $calls!==2*$n) throw new \\Exception('private reuse');
 }
}
$calls=0; $f=function($x) use (&$calls) { ++$calls; return $x+3; };
if(\\Demo\\majDemo_unknown($f)!==22 || $calls!==5) throw new \\Exception('unknown callback');
$trace=fn($x)=>count(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
if(\\Demo\\majDemo_unknown($trace)!==\\Demo\\majDemo_grow(5,$trace,7)+1) throw new \\Exception('callback stack');
$exception=new \\RuntimeException('same');$caught=false;
try { \\Demo\\majDemo_unknown(function($x) use ($exception) { throw $exception; }); }
catch(\\RuntimeException $e) { if($e!==$exception) throw new \\Exception('identity'); $caught=true; }
if(!$caught) throw new \\Exception('exception swallowed');
foreach ([fn($x)=>$x+3, fn($x)=>$x-3, fn($x)=>$x*2] as $operation) {
 foreach ([-11, PHP_INT_MAX-1] as $seed) {
  $public=\\Demo\\majDemo_grow(2048,$operation,$seed);
  $private=\\Demo\\majDemo___phpurs_bind_0_grow(2048,$operation,$seed);
  if(serialize($public)!==serialize($private)) throw new \\Exception('arithmetic sequence');
 }
}
echo "Done\\n";
`,encoding:'utf8'});
assert.equal(run.status,0,run.stdout+run.stderr);assert.equal(run.stdout,'Done\n');
console.log('partial-bindings: typed provenance, refusals, bounds and PHP semantics passed');
