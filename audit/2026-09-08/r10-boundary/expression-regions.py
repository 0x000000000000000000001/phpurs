"""Find scalar application regions inside an enclosing action, using its TAST.

The expression candidate must contain no local binding or closure. Free locals
must have consistent scalar annotations. Callee bodies use the existing proof.
"""
from copy import deepcopy
import json
from pathlib import Path
import sys
from regions import Regions, Refused, SCALARS

def descendants(value):
    if isinstance(value,dict):
        if 'annotation' in value and 'type' in value: yield value
        for key,child in value.items():
            if key != 'annotation': yield from descendants(child)
    elif isinstance(value,list):
        for child in value: yield from descendants(child)

def candidates(module):
    base = Regions(module)
    found = []
    for owner,decl in base.decls.items():
        if owner in base.constructors: continue
        for expr in descendants(decl['expression']):
            if expr.get('type') != 'App': continue
            try:
                result_type = base.ty(base.annotation(expr))
                if result_type['type'] not in SCALARS: continue
                free = {}
                for node in descendants(expr):
                    if node['type'] not in ['App','TypeApp','Var','Literal']:
                        raise Refused('lexical binding or closure inside the candidate')
                    if node['type']=='Var' and not node['value'].get('moduleName'):
                        name = node['value']['identifier']
                        ty = base.ty(base.annotation(node))
                        if ty['type'] not in SCALARS: raise Refused('non-scalar free local')
                        if name in free and free[name] != ty: raise Refused('inconsistent free-local type')
                        free[name] = ty
                if not free: continue
                synthetic = '__phpurs_scalar_region'
                if synthetic in base.decls: raise Refused('synthetic name collision')
                body = expr
                params = list(free)
                for i in reversed(range(len(params))):
                    body = {'type':'Abs','argument':params[i],'body':body,
                            'annotation':{'type':{'type':'Func','args':[free[x] for x in params[i:]],'ret':result_type}}}
                copy = deepcopy(module)
                copy['decls'].append({'bindType':'NonRec','identifier':synthetic,'expression':body})
                analyzer = Regions(copy)
                for enum in analyzer.enums():
                    result = analyzer.analyze(synthetic,enum)
                    if result['eligible']:
                        result.update(owner=owner, free_locals=free, result_type=result_type,
                                      source_span=expr['annotation']['sourceSpan'])
                        result['bindings'].remove(synthetic)
                        found.append(result)
            except Refused:
                continue
    return found

if __name__ == '__main__':
    print(json.dumps(candidates(json.loads(Path(sys.argv[1]).read_text())),indent=2))
