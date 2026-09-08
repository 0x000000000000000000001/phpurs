"""Counterexamples against the TAST analyzer, based on the compiled fixture."""
from copy import deepcopy
import json
from pathlib import Path
from regions import Regions

root = Path(__file__).resolve().parent
module = json.loads((root/'raw/tests/output/Main/corefn.json').read_text())
analyzer = Regions(module)
enum = next(iter(analyzer.enums()))
results = []
for name in ['observedColor','observedBox','applyUnknown','retainedCallback','exportColor','exportBox','exportCallback']:
    result = analyzer.analyze(name,enum)
    assert not result['eligible'], result
    results.append(result)
positive = analyzer.analyze('score',enum)
assert positive['eligible'] and positive['bindings'] == ['build','flipColor','score','weight']

def checked(label, changed, entry='score'):
    result = Regions(changed).analyze(entry,enum)
    assert not result['eligible'], (label,result)
    results.append({'case':label,**result})

changed = deepcopy(module)
a = Regions(changed)
a.decls['score']['expression']['annotation'].pop('type')
checked('missing entry annotation',changed)

changed = deepcopy(module)
a = Regions(changed)
_, body = a.shape('score')
body['annotation']['type'] = {'type':'Any'}
checked('unknown result annotation',changed)

changed = deepcopy(module)
a = Regions(changed)
_, body = a.shape('score')
body['abstraction']['value'].pop('moduleName')
checked('unknown local callable instead of direct weight',changed)

changed = deepcopy(module)
a = Regions(changed)
_, body = a.shape('score')
# build is now partially applied at this call site: reject before any rewrite.
body['argument'] = body['argument']['abstraction']
checked('partial helper application',changed)

changed = deepcopy(module)
a = Regions(changed)
args, body = a.shape('flipColor')
color_type = a.decls['Red']['expression']['annotation']['type']
boolean_type = next(i for i,t in enumerate(a.types) if t['type']=='Boolean')
# A typed, unused FFI observation hidden inside a transitive helper. The region
# analysis must see it even though the helper still returns a normal Color.
observation = {'type':'App','annotation':{'type':boolean_type},
    'abstraction':{'type':'Var','annotation':{'type':{'type':'Func','args':[color_type],'ret':boolean_type}},
                   'value':{'identifier':'inspectColor','moduleName':a.name}},
    'argument':{'type':'Var','annotation':{'type':color_type},'value':{'identifier':args[0]}}}
a.decls['flipColor']['expression']['body'] = {'type':'Let','annotation':{'type':color_type},
    'binds':[{'bindType':'NonRec','identifier':'ignoredObservation','expression':observation}],
    'expression':body}
checked('ADT escapes in transitive helper',changed)

for label,kwargs in [('binding budget',{'max_bindings':1}),('node budget',{'max_nodes':1})]:
    result = analyzer.analyze('score',enum,**kwargs)
    assert not result['eligible'] and 'budget' in result['reason']
    results.append({'case':label,**result})

# Repeat with a different module name and enum layout name to exclude matching
# fixture names as the criterion. All structural references move together.
def rename(value):
    if isinstance(value,list): return [rename(x) for x in value]
    if isinstance(value,dict): return {k:rename(v) for k,v in value.items()}
    if value == 'Main': return 'Renamed'
    if value == 'Color': return 'Signal'
    return value
renamed = Regions(rename(module))
result = renamed.analyze('score','Renamed.Signal')
assert result['eligible'] and result['bindings'] == positive['bindings']
results.append({'case':'module/enum names do not affect eligibility',**result})
(root/'analysis-checks.json').write_text(json.dumps({'positive':positive,'checks':results},indent=2)+'\n')
print('TAST region:', len(results)-1, 'refusal checks and renamed-layout positive passed')
