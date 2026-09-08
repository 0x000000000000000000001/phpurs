// Typed optimizer AST tests: PBO must not inline away the refusal under test.
import assert from 'node:assert/strict';
import * as S from '../../output/PureScript.Backend.Optimizer.Syntax/index.js';
import * as T from '../../output/PureScript.Backend.Optimizer.CoreFn/index.js';
import {Just, Nothing} from '../../output/Data.Maybe/index.js';
import {Tuple} from '../../output/Data.Tuple/index.js';
import {empty as emptyMap} from '../../output/Data.Map/index.js';
import {empty as emptySet, size} from '../../output/Data.Set/index.js';
import {optimize, nodeBudget, workerBudget} from '../../output/Phpurs.EnumRegions/index.js';
import {translate} from '../../output/Phpurs.CodeGen/index.js';
import {printPhpFile} from '../../output/Phpurs.Printer/index.js';
import {spawnSync} from 'node:child_process';

const int = T.Int.value;
const color = new T.ADT('Demo.Color', ['Demo','Color'], []);
const q = (n, m='Demo') => new T.Qualified(new Just(m), n);
const v = n => new S.Var(q(n));
const loc = (n, ty=int) => new S.Typed(ty, new S.Local(new Just('x'+n), n));
const i = n => new S.Lit(new T.LitInt(n));
const typed = (t,e) => new S.Typed(t,e);
const call = (n,args) => new S.App(v(n),args);
const ctor = n => new S.CtorSaturated(q(n),T.SumType.value,'Color',n,[]);
const branch = (c,a,b) => new S.Branch([new S.Pair(c,a)],b);
const fn = (name,args,ret,body) => new Tuple(name,typed(new T.Func(args,ret),new S.Abs(args.map((_,n)=>new Tuple(new Just('x'+n),n)),typed(ret,body))));
const choose = fn('choose',[int],color,branch(new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpGt.value),loc(0),i(0))),ctor('Red'),ctor('Blue')));
const read = fn('read',[color],int,branch(new S.PrimOp(new S.Op1(new S.OpIsTag(q('Red')),loc(0,color))),i(1),i(2)));
const scoreBody = call('read',[typed(color,call('choose',[loc(0)]))]);
const score = fn('score',[int],int,scoreBody);
const mod = (entry=score, helpers=[choose,read]) => ({
  name:'Demo', bindings:[...helpers,entry].map(b=>({recursive:false,bindings:[b]})),
  dataDecls:[{name:'Color',vars:[],constructors:[{name:'Red',fields:[]},{name:'Blue',fields:[]}]}],
  foreign:emptyMap, exports:emptySet, imports:emptySet, dataTypes:emptyMap,
  comments:[], reExports:emptySet, classDecls:[], implementations:emptyMap, directives:emptyMap,
});
const original = mod();
const selected = optimize(original);
assert.equal(size(selected.privateNames),2);
assert.deepEqual(selected.module_.bindings.slice(0,2),original.bindings.slice(0,2),'public ADT workers stay intact');
assert.deepEqual(selected.module_.dataDecls,original.dataDecls,'public enum classes stay intact');
const fail = (name,m) => {
  const result=optimize(m);
  assert.equal(size(result.privateNames),0,name);
  assert.deepEqual(result.module_,m,name+' keeps the original AST');
};
fail('public ADT input',mod(fn('entry',[color],int,call('read',[loc(0,color)]))));
fail('public ADT result',mod(fn('entry',[int],color,call('choose',[loc(0)]))));
fail('indirect function',mod(fn('entry',[int],int,new S.App(new S.Local(new Just('callback'),1),[typed(color,call('choose',[loc(0)]))]))));
fail('partial application',mod(fn('entry',[int],int,call('read',[]))));
fail('overapplication',mod(fn('entry',[int],int,call('read',[ctor('Red'),i(1)]))));
fail('function passed as value',mod(fn('entry',[int],int,call('read',[v('choose')]))));
fail('nested closure',mod(fn('entry',[int],int,call('read',[new S.Abs([new Tuple(Nothing.value,1)],ctor('Red'))]))));
fail('external call in transitive worker',mod(score,[fn('choose',[int],color,new S.App(new S.Var(q('opaque','FFI')),[ctor('Red')])),read]));
fail('missing helper signature',mod(score,[new Tuple('choose',choose.value1.value1),read]));
fail('unknown helper argument type',mod(score,[fn('choose',[T.Any.value],color,ctor('Red')),read]));
fail('open layout',({...original,dataDecls:[{...original.dataDecls[0],vars:['a']}]}));
fail('record with enum passed externally',mod(fn('entry',[int],int,new S.App(new S.Var(q('inspect','FFI')),[new S.Lit(new T.LitRecord([new T.Prop('color',ctor('Red'))]))]))));
let large = ctor('Red');
// A balanced tree exceeds the node cap while staying within width/depth caps.
for(let n=1;n<nodeBudget;n*=2) large=branch(new S.Lit(new T.LitBoolean(true)),large,large);
fail('node budget',mod(score,[fn('choose',[int],color,large),read]));
const wide=new S.Branch(Array.from({length:100},()=>new S.Pair(new S.Lit(new T.LitBoolean(true)),ctor('Red'))),ctor('Blue'));
fail('width budget',mod(score,[fn('choose',[int],color,wide),read]));
let deep=ctor('Red');
for(let n=0;n<10000;n++) deep=typed(color,deep);
fail('depth budget',mod(score,[fn('choose',[int],color,deep),read]));
const chain = Array.from({length:workerBudget+1},(_,n)=>fn('c'+n,[int],color,n===workerBudget?ctor('Red'):call('c'+(n+1),[loc(0)])));
fail('worker budget',mod(fn('entry',[int],int,call('read',[typed(color,call('c0',[loc(0)]))])),[...chain,read]));
const collision=mod(score,[...original.bindings.slice(0,2).map(g=>g.bindings[0]),fn('__phpurs_enum_0_collision',[int],int,loc(0))]);
assert.equal(size(optimize(collision).privateNames),2);
assert.ok(JSON.stringify(optimize(collision).module_).includes('__phpurs_enum_1_'));
// Rename through the actual AST, preserving constructor prototypes.
function rename(x) {
  if(typeof x==='string') return x.replaceAll('Demo','Elsewhere').replaceAll('Color','Shade').replaceAll('Red','Amber').replaceAll('Blue','Violet');
  if(Array.isArray(x)) return x.map(rename);
  if(x && typeof x==='object') return Object.assign(Object.create(Object.getPrototypeOf(x)),Object.fromEntries(Object.entries(x).map(([k,v])=>[k,rename(v)])));
  return x;
}
assert.equal(size(optimize(rename(original)).privateNames),2,'no benchmark/module/constructor names are hard-coded');
const file=translate([])(original);
const php=printPhpFile(false)('')(file.arities)(file);
assert.ok(!/\$GLOBALS\['[^']*__phpurs_enum_/.test(php),'private workers are not public globals');
const run=spawnSync('php',[],{input:php+`\nif (\\Demo\\majDemo_score(1) !== 1 || \\Demo\\majDemo_score(-1) !== 2) throw new \\Exception('score');\n$color = \\Demo\\majDemo_choose(1);\nif (!$color instanceof \\Demo\\Demo_Red || \\Demo\\majDemo_read($color) !== 1) throw new \\Exception('public');\necho "Done\\n";`,encoding:'utf8'});
assert.equal(run.status,0,run.stdout+run.stderr);
assert.equal(run.stdout,'Done\n');
// A boxed nullary global must become a private constructor, never a reference
// to an uninitialized private $GLOBALS slot.
const tree=new T.ADT('Demo.Tree',['Demo','Tree'],[]);
const empty=()=>new S.CtorSaturated(q('Empty'),T.SumType.value,'Tree','Empty',[]);
const boxed=n=>new S.CtorSaturated(q('Box'),T.SumType.value,'Tree','Box',[new Tuple('value0',typed(color,call('choose',[loc(n)]))),new Tuple('value1',loc(n))]);
const maker=fn('make',[int],tree,branch(new S.PrimOp(new S.Op2(new S.OpIntOrd(S.OpGt.value),loc(0),i(0))),boxed(0),v('Empty')));
const field=new S.Accessor(loc(0,tree),new S.GetCtorField(q('Box'),T.SumType.value,'Tree','Box','value0',0));
const weigh=fn('weigh',[tree],int,branch(new S.PrimOp(new S.Op1(new S.OpIsTag(q('Empty')),loc(0,tree))),i(0),call('read',[typed(color,field)])));
const withTree=mod(fn('score',[int],int,call('weigh',[typed(tree,call('make',[loc(0)]))])),[new Tuple('Empty',empty()),choose,read,maker,weigh]);
withTree.dataDecls.push({name:'Tree',vars:[],constructors:[{name:'Empty',fields:[]},{name:'Box',fields:[color,int]}]});
const treeFile=translate([])(withTree);
const treePhp=printPhpFile(false)('')(treeFile.arities)(treeFile);
assert.ok(treePhp.includes('final class Demo___phpurs_enum_0_Box'));
assert.match(treePhp,/final class Demo_Box[^\n]*public int \$value1/);
assert.doesNotMatch(treePhp.match(/final class Demo___phpurs_enum_0_Box[^\n]*/)[0],/int \$value/);
assert.ok(!/\$GLOBALS\['[^']*__phpurs_enum_/.test(treePhp));
const treeRun=spawnSync('php',[],{input:treePhp+`\nif (\\Demo\\majDemo_score(-1)!==0 || \\Demo\\majDemo_score(1)!==1) throw new \\Exception('tree');\ntry { new \\Demo\\Demo_Box(new \\Demo\\Demo_Red(), []); throw new \\Exception('public field check lost'); } catch (\\TypeError $expected) {}\necho "Done\\n";`,encoding:'utf8'});
assert.equal(treeRun.status,0,treeRun.stdout+treeRun.stderr);
assert.equal(treeRun.stdout,'Done\n');
console.log('enum-regions: selection, boundaries, unknown types/calls, budgets, fresh names and PHP execution passed');
