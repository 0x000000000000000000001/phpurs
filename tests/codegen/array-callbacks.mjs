import assert from 'node:assert/strict';
import fs from 'node:fs';
import {spawnSync} from 'node:child_process';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import {Just,Nothing} from '../../output/Data.Maybe/index.js';
import {Tuple} from '../../output/Data.Tuple/index.js';
import {empty as emptyMap,fromFoldable,union} from '../../output/Data.Map/index.js';
import {empty as emptySet,fromFoldable as setFrom,size} from '../../output/Data.Set/index.js';
import {foldableArray} from '../../output/Data.Foldable/index.js';
import {ordString} from '../../output/Data.Ord/index.js';
import {fromForeign as callbacksFromForeign} from '../../output/Phpurs.NativeCallbacks/index.js';
import {fromForeign,FoldlArray,FilterImpl,ordTraversal} from '../../output/Phpurs.ForeignTraversals/index.js';
import {optimize,workerBudget} from '../../output/Phpurs.ArrayCallbacks/index.js';
import {translateWithContracts} from '../../output/Phpurs.CodeGen/index.js';
import {printPhpFile} from '../../output/Phpurs.Printer/index.js';
import {renderModuleState} from '../../output/Phpurs.ModuleState/index.js';

const int=T.Int.value,any=T.Any.value,bool=T.Boolean.value,a=new T.TypeVar('a'),b=new T.TypeVar('b');
const typed=(ty,e)=>new S.Typed(ty,e),lit=n=>typed(int,new S.Lit(new T.LitInt(n)));
const q=(mod,name)=>new T.Qualified(new Just(mod),name),v=(mod,name)=>new S.Var(q(mod,name));
const local=(n,name='x'+n)=>new S.Local(new Just(name),n);
const abs=(n,body)=>new S.Abs([new Tuple(new Just('x'+n),n)],body);
const op=(operator,left,right)=>new S.PrimOp(new S.Op2(operator,left,right));
const modulo=(value,divisor)=>op(new S.OpIntNum(S.OpMod.value),value,lit(divisor));
const cmp=(value,n=0,kind=S.OpEq.value)=>op(new S.OpIntOrd(kind),value,lit(n));
const pred=(divisor=2,n=0)=>abs(1,typed(bool,cmp(modulo(local(1),divisor),n)));
const fold=(callback=v('Data.Semiring','intAdd'),initial=lit(0),xs=local(0))=>new S.App(v('Data.Foldable','foldlArray'),[callback,initial,xs]);
const filter=(callback=pred(),xs=local(0))=>new S.UncurriedApp(v('Data.Array','filterImpl'),[callback,xs]);
const binding=(name,body)=>new Tuple(name,typed(new T.Func([any],any),abs(0,body)));
const mod=(body=fold(),extras=[])=>({name:'Demo',bindings:[{recursive:false,bindings:[binding('entry',body),...extras]}],dataDecls:[],foreign:emptyMap,
  exports:emptySet,imports:emptySet,dataTypes:emptyMap,comments:[],reExports:emptySet,classDecls:[],implementations:emptyMap,directives:emptyMap});
const map=entries=>fromFoldable(T.ordIdent)(foldableArray)(entries.map(([k,val])=>new Tuple(k,new Just(val))));
const foldType=new T.ForAll(['a','b'],new T.Func([new T.Func([b,a],b),b,new T.Array(a)],b));
const filterType=new T.ForAll(['a'],new T.ADT('Data.Function.Uncurried.Fn2',['Data','Function','Uncurried','Fn2'],[new T.Func([a],bool),new T.Array(a),new T.Array(a)]));
const foreigns=[
  ['Data.Semiring','phpurs-prelude','Semiring',[['intAdd',new T.Func([int,int],int)],['intMul',new T.Func([int,int],int)]]],
  ['Data.Foldable','phpurs-foldable-traversable','Foldable',[['foldlArray',foldType]]],
  ['Data.Array','phpurs-arrays','Array',[['filterImpl',filterType]]],
].map(([name,pkg,file,entries])=>({name,bindings:map(entries),source:fs.readFileSync(new URL(`../../../${pkg}/src/Data/${file}.php`,import.meta.url),'utf8')}));
const [semiring,foldable,array]=foreigns;
const callbacks=callbacksFromForeign(semiring.name)(semiring.bindings)(semiring.source);
const contracts=setFrom(foldableArray)(ordTraversal)([FoldlArray.value,FilterImpl.value]);
for(const f of [foldable,array]) {
  assert.equal(size(fromForeign(f.name)(f.bindings)(f.source)),1,'exact source and signature');
  for(const source of ['',f.source+'\n',f.source.replace('return $','observe(); return $')])
    assert.equal(size(fromForeign(f.name)(f.bindings)(source)),0,'changed source has no traversal contract');
  assert.equal(size(fromForeign('Other')(f.bindings)(f.source)),0,'module identity');
  assert.equal(size(fromForeign(f.name)(emptyMap)(f.source)),0,'missing signature');
}
for(const ty of [any,new T.Func([any,any,any],any),new T.ForAll(['a','b'],new T.Func([new T.Func([a,b],b),b,new T.Array(a)],b))])
  assert.equal(size(fromForeign(foldable.name)(map([['foldlArray',ty]]))(foldable.source)),0,'fold calling/accumulator contract');
for(const ty of [any,new T.ForAll(['a'],new T.Func([new T.Func([a],bool),new T.Array(a)],new T.Array(a))),new T.ForAll(['a'],new T.ADT('Other.Fn2',['Other','Fn2'],filterType.value1.value2))])
  assert.equal(size(fromForeign(array.name)(map([['filterImpl',ty]]))(array.source)),0,'raw Fn2 contract');
const specialize=optimize(contracts)(callbacks);
for(const expr of [fold(),filter(),filter(pred(3,1)),filter(abs(1,cmp(local(1),5,S.OpGt.value))),filter(abs(1,new S.PrimOp(new S.Op1(S.OpBooleanNot.value,cmp(local(1))))))])
  assert.equal(specialize(mod(expr)).workers.length,1,'supported arithmetic/predicate');
const both=mod(fold(v('Data.Semiring','intAdd'),lit(0),filter()));
const selected=specialize(both);
assert.equal(selected.workers.length,2);
assert.deepEqual(specialize(selected.module_).module_,selected.module_,'rescan is stable');
assert.equal(specialize(selected.module_).workers.length,0,'workers not selected again');
assert.equal(specialize(mod(filter(),[binding('other',filter(pred()))])).workers.length,1,'share normalized predicate copy');
assert.equal(specialize(mod(fold(),[binding('other',fold(v('Data.Semiring','intAdd'),lit(7)))])).workers.length,1,'share native callback copy');
const localAdd=new Tuple('add',typed(new T.Func([int,int],int),new S.Abs([new Tuple(new Just('x1'),1),new Tuple(new Just('x2'),2)],op(new S.OpIntNum(S.OpAdd.value),local(1),local(2)))));
assert.equal(optimize(contracts)(emptySet)(mod(fold(v('Demo','add')), [localAdd])).workers.length,1,'known local native callback');
let refusals=0;
function refuse(reason,input,proof=contracts,cbs=callbacks) {
  const result=optimize(proof)(cbs)(input);
  assert.deepEqual(result.module_,input,reason);
  assert.equal(result.workers.length,0,reason);
  refusals++;
}
refuse('missing traversal contracts',both,emptySet);
refuse('missing native callback contract',mod(fold()),contracts,emptySet);
refuse('unknown fold callback',mod(fold(local(1))));
refuse('curried lambda callback',mod(fold(abs(1,abs(2,op(new S.OpIntNum(S.OpAdd.value),local(1),local(2)))))));
refuse('callback factory',mod(fold(new S.App(v('Opaque','make'),[lit(0)]))));
refuse('dynamic initial',mod(fold(v('Data.Semiring','intAdd'),typed(int,local(1)))));
refuse('computed initial',mod(fold(v('Data.Semiring','intAdd'),op(new S.OpIntNum(S.OpAdd.value),lit(0),lit(1)))));
refuse('untyped initial',mod(fold(v('Data.Semiring','intAdd'),new S.Lit(new T.LitInt(0)))));
refuse('nonInt initial',mod(fold(v('Data.Semiring','intAdd'),typed(T.Number.value,new S.Lit(new T.LitNumber(0))))));
refuse('partial fold',mod(new S.App(v('Data.Foldable','foldlArray'),[v('Data.Semiring','intAdd'),lit(0)])));
refuse('overapplied fold',mod(new S.App(v('Data.Foldable','foldlArray'),[v('Data.Semiring','intAdd'),lit(0),local(0),lit(1)])));
refuse('uncurried fold',mod(new S.UncurriedApp(v('Data.Foldable','foldlArray'),[v('Data.Semiring','intAdd'),lit(0),local(0)])));
refuse('different FFI fold',mod(new S.App(v('Other','foldlArray'),[v('Data.Semiring','intAdd'),lit(0),local(0)])));
refuse('curried filter calling convention',mod(new S.App(v('Data.Array','filterImpl'),[pred(),local(0)])));
refuse('partial raw filter',mod(new S.UncurriedApp(v('Data.Array','filterImpl'),[pred()])));
refuse('overapplied raw filter',mod(new S.UncurriedApp(v('Data.Array','filterImpl'),[pred(),local(0),lit(0)])));
refuse('unknown predicate',mod(filter(local(1))));
refuse('global predicate without body',mod(filter(v('Other','isEven'))));
refuse('captured predicate',mod(filter(abs(1,cmp(modulo(local(2),2))))));
refuse('name mismatch at same level',mod(filter(abs(1,cmp(modulo(local(1,'other'),2))))));
refuse('scalar parameter check',mod(filter(typed(new T.Func([int],bool),pred()))));
refuse('typed wrapper hiding scalar check',mod(filter(typed(any,typed(new T.Func([int],bool),pred())))));
refuse('effectful predicate',mod(filter(abs(1,new S.App(v('Opaque','observe'),[local(1)])))));
refuse('predicate field read',mod(filter(abs(1,cmp(new S.Accessor(local(1),new S.GetProp('x')))))));
refuse('zero modulo',mod(filter(pred(0))));
refuse('captured modulo divisor',mod(filter(abs(1,cmp(op(new S.OpIntNum(S.OpMod.value),local(1),local(2)))))));
refuse('overflow-prone scalar arithmetic',mod(filter(abs(1,cmp(op(new S.OpIntNum(S.OpAdd.value),local(1),lit(1)))))));
refuse('unproven boolean result',mod(filter(abs(1,local(1)))));
refuse('nested function',mod(filter(abs(1,abs(2,cmp(local(2)))))));
refuse('unknown local native callback body',mod(fold(v('Demo','add')),[new Tuple('add',typed(new T.Func([int,int],int),abs(1,abs(2,new S.App(v('Opaque','add'),[local(1),local(2)])))))]));
let deep=pred();
for(let n=0;n<10000;n++) deep=typed(any,deep);
refuse('deep predicate',mod(filter(deep)));
refuse('module budget',{...both,bindings:Array.from({length:257},()=>both.bindings[0])});
refuse('declaration budget',{...both,dataDecls:Array.from({length:129},()=>({name:'Type',vars:[],constructors:[]}))});
refuse('group budget',mod(fold(),Array.from({length:64},(_,n)=>binding('f'+n,fold()))));
const capped=specialize(mod(filter(),Array.from({length:workerBudget+3},(_,n)=>binding('f'+n,filter(pred(n+3))))));
assert.equal(capped.workers.length,workerBudget,'private copy budget');
assert.ok(specialize(mod(fold(),[binding('__PHPURS_ARRAYCB_0_collision',lit(0))])).workers[0].identifier.includes('__phpurs_arraycb_1_'),'case-insensitive freshness');

let foreignPhp='',foreignArities=emptyMap;
for(const f of foreigns) {
  const state=renderModuleState({emitModules:true,emitBundle:false})([])(f.source)(foreignArities)({...mod(),name:f.name,bindings:[],foreign:f.bindings});
  foreignArities=union(ordString)(state.arities)(foreignArities);
  foreignPhp+=state.modularPhp.value0.replace(/^<\?php/,'')+'\n';
}
function render(input,proofs=contracts) {
  const file=translateWithContracts(callbacks)(proofs)([])(input);
  return '<?php\n'+foreignPhp+printPhpFile(false)('')(union(ordString)(file.arities)(foreignArities))(file).replace(/^<\?php/,'');
}
const program=mod(fold(),[
  binding('product',fold(v('Data.Semiring','intMul'),lit(1))),binding('keep',filter()),
  binding('combined',fold(v('Data.Semiring','intAdd'),lit(0),filter())),
  binding('once',fold(v('Data.Semiring','intAdd'),lit(0),new S.App(v('Demo','make'),[local(0)]))),
  binding('onceFilter',filter(pred(),new S.App(v('Demo','make'),[local(0)]))),
]);
const php=render(program);
assert.doesNotMatch(php,/\$GLOBALS\['Demo___phpurs_arraycb_/,'private workers have no export');
assert.match(php,/foreach \(\$xs as \$item\)/,'foreach order and sparse array keys');
assert.match(php,/\\is_int\(\$item\)/,'nonInt elements call the original predicate');
assert.match(php,/\\array_is_list\(\$xs\)/,'sparse folds use original FFI');
assert.match(php,/\\Data\\Semiring\\majData_majSemiring_intmajAdd\(\$acc, \(\$xs\)\[\$i\]\)/,'native return check retained');
const checks=`
function outcome($f) { try { return ['value',serialize($f())]; } catch (\\Throwable $e) { return [get_class($e),$e->getMessage()]; } }
$add=$GLOBALS['Data_Semiring_intAdd']; $mul=$GLOBALS['Data_Semiring_intMul'];
$fold=$GLOBALS['Data_Foldable_foldlArray']; $filter=$GLOBALS['Data_Array_filterImpl'];
$even=fn($x)=>($x%2)===0;
foreach ([[],[1],[1,2,3],[-7,-4,0,3,6],range(-100,100),[PHP_INT_MAX,1],[PHP_INT_MIN,-1],[PHP_INT_MAX,PHP_INT_MAX],[PHP_INT_MIN,PHP_INT_MIN],[PHP_INT_MAX,1,-1],[1.0,2],['2',3],[true,null],['invalid'],[(object)[]]] as $xs) {
  $before=serialize($xs);
  if (outcome(fn()=>$fold($add,0,$xs))!==outcome(fn()=>majDemo_entry($xs))) throw new \\Exception('addition parity');
  if (outcome(fn()=>$fold($mul,1,$xs))!==outcome(fn()=>majDemo_product($xs))) throw new \\Exception('multiplication parity');
  if (outcome(fn()=>$filter($even,$xs))!==outcome(fn()=>majDemo_keep($xs))) throw new \\Exception('predicate parity');
  if (outcome(fn()=>$fold($add,0,$filter($even,$xs)))!==outcome(fn()=>majDemo_combined($xs))) throw new \\Exception('combined parity');
  if (serialize($xs)!==$before) throw new \\Exception('mutated input');
}
$sparse=[7=>4,'key'=>3,2=>6,0=>2];
if (majDemo_keep($sparse)!==[4,6,2] || array_keys($sparse)!==[7,'key',2,0]) throw new \\Exception('sparse foreach order/dense output');
$warnings=[];
set_error_handler(function($severity,$message,$file,$line) use (&$warnings) { $warnings[]=[$severity,$message,$file,$line]; return true; });
$before=outcome(fn()=>$fold($add,0,$sparse)); $old=$warnings; $warnings=[];
$after=outcome(fn()=>majDemo_entry($sparse));
if ($before!==$after || $old!==$warnings) throw new \\Exception('sparse fallback diagnostic location');
restore_error_handler();
$events=[];
$callback=function($acc) use (&$events) { $events[]=['first',$acc]; return function($item) use (&$events,$acc) { $events[]=['second',$item]; return $acc+$item; }; };
$partial=$fold($callback,0);
if (!$partial instanceof \\Closure || $events!==[] || $partial([1,2])!==3 || $partial([3])!==3 || $events!==[['first',0],['second',1],['first',1],['second',2],['first',0],['second',3]]) throw new \\Exception('curried callback retention/order');
$same=new \\RuntimeException('sentinel');
foreach ([fn($acc)=>throw $same,fn($acc)=>fn($item)=>throw $same] as $throwing) {
  try {$fold($throwing,0,[1]);throw new \\Exception('not thrown');}catch(\\RuntimeException $e){if($e!==$same)throw new \\Exception('exception identity');}
}
$visits=[];$threshold=2;
$unknown=function($x) use (&$visits,&$threshold) {$visits[]=$x;return $x>$threshold;};
if ($filter($unknown,$sparse)!==[4,3,6] || $visits!==[4,3,6,2]) throw new \\Exception('unknown predicate order');
$threshold=5;if($filter($unknown,$sparse)!==[6])throw new \\Exception('capture retention');
$iter=new \\ArrayIterator($sparse);
if (majDemo_keep($iter)!==$filter($even,$iter)) throw new \\Exception('iterator fallback');
class Sequence implements \\Countable, \\ArrayAccess {
  public array $events=[];
  public function count():int {$this->events[]='count';return 3;}
  public function offsetExists(mixed $offset):bool{return true;}
  public function offsetGet(mixed $offset):mixed{$this->events[]=$offset;return $offset+1;}
  public function offsetSet(mixed $offset,mixed $value):void{throw new \\Exception('write');}
  public function offsetUnset(mixed $offset):void{throw new \\Exception('unset');}
}
$object=new Sequence();$expected=$fold($add,0,$object);$events=$object->events;$object->events=[];
if(majDemo_entry($object)!==$expected || $object->events!==$events || $events!==['count',0,1,2])throw new \\Exception('Countable/ArrayAccess fallback');
$calls=0;$GLOBALS['Demo_make']=function($value)use(&$calls){++$calls;return $value;};
if(majDemo_once([1,2,3])!==6 || $calls!==1 || majDemo_oncemajFilter([1,2,4])!==[2,4] || $calls!==2)throw new \\Exception('input evaluated once');
$overflow=outcome(fn()=>majDemo_entry([PHP_INT_MAX,1]));
if($overflow[0]!=='TypeError' || !str_contains($overflow[1],'majData_majSemiring_intmajAdd(): Return value'))throw new \\Exception('overflow check');
echo "Done\\n";
`;
for(const [name,code] of [['integrated',php],['baseline',render(program,emptySet)]]) {
  const run=spawnSync('php',['-d','xdebug.mode=off','-d','opcache.enable_cli=0'],{input:code+checks,encoding:'utf8'});
  assert.equal(run.status,0,name+': '+run.stdout+run.stderr);
  assert.equal(run.stdout,'Done\n');
  assert.equal(run.stderr,'');
}
console.log(`array-callbacks: ${refusals} refusals, FFI contracts, native checks, sparse/opaque fallbacks, callback order, exceptions and combined PHP parity passed`);
