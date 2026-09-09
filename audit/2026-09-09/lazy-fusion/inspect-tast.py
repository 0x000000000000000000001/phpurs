"""Record the exact typed call being probed, with conservative entry refusals.

This diagnostic is scoped to the pinned PHP program. It does not implement the
generic recursive-builder proof or its transport through PBO optimizations.
"""
from copy import deepcopy
import hashlib
import json
from pathlib import Path

root = Path(__file__).resolve().parent
source = root.parents[2].parents[1]/'altbak.pub-phpurs/run/bak/php/output/Test.LazyEvaluation/corefn.json'
module = json.loads(source.read_text())
table = module['typeTable']
mn = module['moduleName']
bindings = {b['identifier']:b for group in module['decls'] for b in group.get('binds',[group])}

def resolve(ref, depth=0):
    if depth>32: return None
    ty = table[ref] if isinstance(ref,int) and 0<=ref<len(table) else ref
    if not isinstance(ty,dict): return None
    result = dict(ty)
    for k in ['args','ret','body']:
        if k in result:
            result[k] = [resolve(x,depth+1) for x in result[k]] if k=='args' else resolve(result[k],depth+1)
    return result

def typ(expr): return resolve(expr.get('annotation',{}).get('type'))
def peel(expr):
    while expr.get('type')=='TypeApp': expr=expr['expression']
    return expr
def app(expr):
    args=[]
    expr=peel(expr)
    while expr.get('type')=='App':
        args.insert(0,expr['argument']);expr=peel(expr['abstraction'])
    return expr,args
def named(expr,name):
    return expr.get('type')=='Var' and expr.get('value',{}).get('moduleName')==mn and expr['value'].get('identifier')==name
def literal(expr):
    if expr.get('type')=='Literal' and expr.get('value',{}).get('literalType')=='IntLiteral': return expr['value']['value']
    return None
integer={'type':'Int'}
seed_type={'type':'Func','args':[{'type':'Unit'}],'ret':integer}
def admissible(expr):
    head,args=app(expr)
    if not named(head,'force') or len(args)!=1 or typ(expr)!=integer: return False
    head,args=app(args[0])
    if not named(head,'buildThunks') or len(args)!=2: return False
    depth,wrapped=args
    if typ(depth)!=integer or literal(depth) is None or literal(depth)<0: return False
    head,args=app(wrapped)
    if not named(head,'defer') or len(args)!=1: return False
    seed=args[0]
    return (seed.get('type')=='Abs' and typ(seed)==seed_type and
            literal(seed['body']) is not None and typ(seed['body'])==integer)
def walk(value):
    if isinstance(value,dict):
        if 'type' in value: yield value
        for k,v in value.items():
            if k!='annotation': yield from walk(v)
    elif isinstance(value,list):
        for v in value: yield from walk(v)

selected=[e for e in walk(bindings['runManyTimes']['expression']) if admissible(e)]
assert len(selected)==1
call=selected[0]
def parts(expr):
    _,force_args=app(expr); _,build_args=app(force_args[0]); _,defer_args=app(build_args[1])
    return build_args[0],defer_args[0]
depth,seed=parts(call)
refusals={}
for name in ['negative_depth','dynamic_depth','unknown_seed','ffi_seed_body','missing_seed_type','unknown_result_type']:
    mutant=deepcopy(call);n,s=parts(mutant)
    if name=='negative_depth': n['value']['value']=-1
    elif name=='dynamic_depth': n.clear();n.update({'type':'Var','value':{'identifier':'depth'},'annotation':deepcopy(depth['annotation'])})
    elif name=='unknown_seed': s.clear();s.update({'type':'Var','value':{'identifier':'seed'},'annotation':deepcopy(seed['annotation'])})
    elif name=='ffi_seed_body': s['body']={'type':'App','abstraction':{'type':'Var','value':{'moduleName':['Foreign'],'identifier':'inspectStack'}},'argument':deepcopy(seed['body']),'annotation':deepcopy(seed['body']['annotation'])}
    elif name=='missing_seed_type': s.pop('annotation')
    else: mutant['annotation']['type']={'type':'Any'}
    refusals[name]=not admissible(mutant)
assert all(refusals.values())
result={
    'input_sha256':hashlib.sha256(source.read_bytes()).hexdigest(),
    'module':mn,'builder_type':typ(bindings['buildThunks']['expression']),
    'consumer_type':typ(bindings['runManyTimes']['expression']),
    'depth':literal(depth),'seed_type':typ(seed),'seed_literal':literal(seed['body']),
    'forced_result_type':typ(call),'refused_mutants':refusals,
    'scope':'Pinned-program entry check only; generic body proof and backend integration still pending',
}
(root/'tast-evidence.json').write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps(result,indent=2))
