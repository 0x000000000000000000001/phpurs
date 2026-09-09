// Direct typed AST tests keep negative cases observable after optimization.
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import * as P from '../../output/Phpurs.PhpAst/index.js';
import {Just} from '../../output/Data.Maybe/index.js';
import {Tuple} from '../../output/Data.Tuple/index.js';
import * as M from '../../output/Data.Map/index.js';
import {empty as emptySet} from '../../output/Data.Set/index.js';
import {optimize} from '../../output/Phpurs.EnumRegions/index.js';
import {layout, lower, nodeBudget, depthBudget} from '../../output/Phpurs.NullableConstructors/index.js';
import {translate} from '../../output/Phpurs.CodeGen/index.js';
import {printPhpFile} from '../../output/Phpurs.Printer/index.js';

const int=T.Int.value, tree=new T.ADT('Demo.Tree',['Demo','Tree'],[]);
const q=(name,ns='Demo')=>new T.Qualified(new Just(ns),name);
const v=name=>new S.Var(q(name));
const typed=(ty,e)=>new S.Typed(ty,e);
const loc=(n,ty=int)=>typed(ty,new S.Local(new Just('x'+n),n));
const lit=n=>new S.Lit(new T.LitInt(n));
const call=(name,args)=>new S.App(v(name),args);
const tag=(name,e)=>new S.PrimOp(new S.Op1(new S.OpIsTag(q(name)),e));
const eq=(a,b)=>new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpEq.value),a,b));
const positive=n=>new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpGt.value),loc(n),lit(-1)));
const branch=(c,a,b)=>new S.Branch([new S.Pair(c,a)],b);
const ctor=(name,fields=[],type='Tree')=>new S.CtorSaturated(q(name),T.SumType.value,type,name,fields.map((f,n)=>new Tuple('value'+n,f)));
const field=(name,e,index=0,type='Tree')=>new S.Accessor(e,new S.GetCtorField(q(name),T.SumType.value,type,name,'value'+index,index));
const fn=(name,args,ret,body)=>new Tuple(name,typed(new T.Func(args,ret),new S.Abs(args.map((_,n)=>new Tuple(new Just('x'+n),n)),typed(ret,body))));
const make=fn('make',[int],tree,branch(positive(0),ctor('Box',[loc(0)]),v('Empty')));
const read=fn('read',[tree],int,branch(tag('Empty',loc(0,tree)),lit(-1),branch(tag('Box',loc(0,tree)),field('Box',loc(0,tree)),new S.Fail('unreachable'))));
const entry=fn('score',[int],int,call('read',[typed(tree,call('make',[loc(0)]))]));
const decl={name:'Tree',vars:[],constructors:[{name:'Empty',fields:[]},{name:'Box',fields:[int]}]};
const mod=(main=entry,helpers=[make,read],layouts=[decl])=>({
  name:'Demo',bindings:[new Tuple('Empty',ctor('Empty')),...helpers,main].map(b=>({recursive:false,bindings:[b]})),
  dataDecls:layouts,foreign:M.empty,exports:emptySet,imports:emptySet,dataTypes:M.empty,
  comments:[],reExports:emptySet,classDecls:[],implementations:M.empty,directives:M.empty,
});
const original=mod(), selected=optimize(original);
assert.equal(M.size(selected.nullableConstructors),2,'nullable-only region requires no enum');
assert.deepEqual(selected.module_.bindings.slice(0,3),original.bindings.slice(0,3),'original constructors and helpers untouched');
assert.deepEqual(selected.module_.dataDecls.slice(0,1),original.dataDecls,'public layout untouched');
assert.deepEqual(layout({...decl,constructors:[...decl.constructors].reverse()}),new Just({empty:'Empty',boxed:'Box'}));
assert.equal(M.size(optimize(mod(entry,[make,read],[{...decl,constructors:[...decl.constructors].reverse()}])).nullableConstructors),2);

const reject=(reason,m)=>{
  const r=optimize(m);
  assert.equal(M.size(r.nullableConstructors),0,reason);
  assert.deepEqual(r.module_,m,reason+' leaves AST unchanged');
};
reject('two empty constructors',mod(entry,[make,read],[{...decl,constructors:[...decl.constructors,{name:'OtherEmpty',fields:[]}]}]));
reject('two boxed constructors',mod(entry,[make,read],[{...decl,constructors:[...decl.constructors,{name:'OtherBox',fields:[int]}]}]));
reject('single product constructor',mod(entry,[make,read],[{...decl,constructors:[decl.constructors[1]]}]));
reject('polymorphic layout',mod(entry,[make,read],[{...decl,vars:['a']}]));
reject('unknown payload',mod(entry,[make,read],[{...decl,constructors:[decl.constructors[0],{name:'Box',fields:[T.Any.value]}]}]));
reject('function payload',mod(entry,[make,read],[{...decl,constructors:[decl.constructors[0],{name:'Box',fields:[new T.Func([int],int)]}]}]));
reject('missing layout',mod(entry,[make,read],[]));
reject('public ADT input',mod(fn('entry',[tree],int,call('read',[loc(0,tree)]))));
reject('public ADT output',mod(fn('entry',[int],tree,call('make',[loc(0)]))));
reject('foreign consumer',mod(fn('entry',[int],int,new S.App(new S.Var(q('observe','FFI')),[typed(tree,call('make',[loc(0)]))]))));
reject('unknown callback',mod(fn('entry',[new T.Func([tree],int),int],int,new S.App(loc(0),[typed(tree,call('make',[loc(1)]))]))));
reject('closure escape',mod(fn('entry',[int],new T.Func([T.Unit.value],tree),new S.Abs([new Tuple(new Just('unit'),1)],typed(tree,call('make',[loc(0)]))))));
reject('missing helper signature',mod(entry,[new Tuple('make',make.value1.value1),read]));
const other=new T.ADT('Demo.Other',['Demo','Other'],[]);
const otherDecl={name:'Other',vars:[],constructors:[{name:'NoOther',fields:[]},{name:'SomeOther',fields:[int]}]};
reject('test from another nullable domain',mod(entry,[make,fn('read',[tree],int,branch(tag('NoOther',loc(0,tree)),lit(1),lit(2)))],[decl,otherDecl]));

function render(m) {
  const file=translate([])(m);
  return printPhpFile(false)('')(file.arities)(file);
}
const php=render(original);
assert.doesNotMatch(php,/new \\Demo\\Demo___phpurs_enum_0_Empty\(/);
assert.doesNotMatch(php,/instanceof \\Demo\\Demo___phpurs_enum_0_(Empty|Box)/);
assert.match(php,/=== null/);
assert.match(php,/!== null/);
assert.match(php,/new \\Demo\\Demo_Empty\(/,'public Empty remains boxed');
assert.match(php,/instanceof \\Demo\\Demo_Box/,'public tag checks remain');
assert.doesNotMatch(php,/\$GLOBALS\['[^']*__phpurs_enum_/);
const run=spawnSync('php',[],{input:php+`
if (\\Demo\\majDemo_score(-3)!==-1 || \\Demo\\majDemo_score(9)!==9) throw new \\Exception('nullable');
$empty=\\Demo\\majDemo_make(-1); $box=\\Demo\\majDemo_make(7);
if (!$empty instanceof \\Demo\\Demo_Empty || !$box instanceof \\Demo\\Demo_Box || $box->value0!==7) throw new \\Exception('public');
try { new \\Demo\\Demo_Box([]); throw new \\Exception('type check lost'); } catch (\\TypeError $ok) {}
echo "Done\\n";`,encoding:'utf8'});
assert.equal(run.status,0,run.stdout+run.stderr);
assert.equal(run.stdout,'Done\n');

// Two nullable domains may nest, but Wrap(Empty) must remain a real object.
const outer=new T.ADT('Demo.Outer',['Demo','Outer'],[]);
const outerDecl={name:'Outer',vars:[],constructors:[{name:'NoOuter',fields:[]},{name:'Wrap',fields:[tree]}]};
const makeOuter=fn('makeOuter',[int],outer,branch(positive(0),
  ctor('Wrap',[typed(tree,branch(eq(loc(0),lit(0)),v('Empty'),call('make',[loc(0)])))],'Outer'),ctor('NoOuter',[],'Outer')));
const readOuter=fn('readOuter',[outer],int,branch(tag('NoOuter',loc(0,outer)),lit(-100),
  new S.PrimOp(new S.Op2(new S.OpIntNum(S.OpAdd.value),lit(100),call('read',[typed(tree,field('Wrap',loc(0,outer),0,'Outer'))])))));
const nested=mod(fn('score',[int],int,call('readOuter',[typed(outer,call('makeOuter',[loc(0)]))])),[make,read,makeOuter,readOuter],[decl,outerDecl]);
assert.equal(M.size(optimize(nested).nullableConstructors),4);
const nestedPhp=render(nested);
assert.match(nestedPhp,/new \\Demo\\Demo___phpurs_enum_0_Wrap\(/);
const nestedRun=spawnSync('php',[],{input:nestedPhp+`
foreach ([[-1,-100],[0,99],[9,109]] as [$n,$want]) if (\\Demo\\majDemo_score($n)!==$want) throw new \\Exception('nested null');
$wrapped=\\Demo\\majDemo_makemajOuter(0);
if (!$wrapped instanceof \\Demo\\Demo_Wrap || !$wrapped->value0 instanceof \\Demo\\Demo_Empty) throw new \\Exception('public nesting');
echo "Done\\n";`,encoding:'utf8'});
assert.equal(nestedRun.status,0,nestedRun.stdout+nestedRun.stderr);
assert.equal(nestedRun.stdout,'Done\n');

function rename(x) {
  if(typeof x==='string') return x.replaceAll('Demo','Elsewhere').replaceAll('Empty',"Nil'").replaceAll('Box','Cell');
  if(Array.isArray(x)) return x.map(rename);
  if(x && typeof x==='object') return Object.assign(Object.create(Object.getPrototypeOf(x)),Object.fromEntries(Object.entries(x).map(([k,v])=>[k,rename(v)])));
  return x;
}
assert.equal(M.size(optimize(rename(original)).nullableConstructors),2,'not tied to RBTree/names');
assert.doesNotMatch(render(rename(original)),/new \\Elsewhere\\Elsewhere___phpurs_enum_0_Nil_prime_\(/,'escaped constructor name');

// A rejected PHP budget must retain the whole representation, never one worker.
const emptyClass='\\Demo\\Demo___phpurs_enum_0_Empty';
const boxedClass='\\Demo\\Demo___phpurs_enum_0_Box';
const rawFile={...translate([])(original),decls:[{identifier:'x',expression:new P.PhpNew(emptyClass,[])}]};
// Reuse the actual proof's class names through a small PHP expression file.
const {fromFoldable}=await import('../../output/Data.Map/index.js');
const {foldableArray}=await import('../../output/Data.Foldable/index.js');
const {ordString}=await import('../../output/Data.Ord/index.js');
const names=fromFoldable(ordString)(foldableArray)([new Tuple(emptyClass,true),new Tuple(boxedClass,false)]);
assert.deepEqual(lower(names)(rawFile).decls[0].expression,new P.PhpRaw('null'));
const unchanged=(reason,e)=>{
  const file={...rawFile,decls:[...rawFile.decls,{identifier:'huge',expression:e}]};
  assert.equal(lower(names)(file),file,reason+' declines atomically');
};
let deep=new P.PhpInt(0);
for(let n=0;n<depthBudget+10000;n++) deep=new P.PhpReturn(deep);
unchanged('depth budget',deep);
let big=new P.PhpInt(0);
for(let n=1;n<nodeBudget*2;n*=2) big=new P.PhpArray([big,big]);
unchanged('node budget',big);
unchanged('width budget',new P.PhpArray(Array(8193).fill(new P.PhpInt(0))));
const effect={...rawFile,decls:[{identifier:'action',expression:new P.PhpGlobalAssign('action',new P.PhpFunction([],[], '',[new P.PhpReturn(new P.PhpNew(emptyClass,[]))]))}]};
assert.ok(JSON.stringify(lower(names)(effect)).includes('null'),'entry in Effect closure lowered');
console.log('nullable-constructors: typed domains, nested empties, public boundaries, names, budgets and generated PHP passed');
