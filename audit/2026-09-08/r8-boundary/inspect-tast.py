"""Read actual TAST v3 types/instantiations and preserve their source locations."""
import hashlib
import json
import pathlib

root = pathlib.Path(__file__).resolve().parent
backend = root.parents[2]
worktree = backend.parents[1] / 'altbak.pub-phpurs'

def walk(node):
    if isinstance(node, dict):
        yield node
        for value in node.values():
            yield from walk(value)
    elif isinstance(node, list):
        for value in node:
            yield from walk(value)

def inspect(filename, wanted):
    raw = filename.read_bytes()
    module = json.loads(raw)
    table = module['typeTable']
    def expand(index, seen=()):
        if index is None:
            return None
        if index in seen:
            return {'recursiveTypeId': index}
        item = dict(table[index])
        child = lambda i: expand(i, seen + (index,))
        for key in ['body', 'ret', 'row', 'tail']:
            if isinstance(item.get(key), int):
                item[key] = child(item[key])
        if 'args' in item:
            item['args'] = [child(i) for i in item['args']]
        if 'fields' in item:
            item['fields'] = [{**field, 'type': child(field['type'])} for field in item['fields']]
        if 'constraints' in item:
            item['constraints'] = [{**c, 'args': [child(i) for i in c['args']]} for c in item['constraints']]
        return item
    def head(expr):
        while expr.get('type') in ['TypeApp', 'App']:
            expr = expr['expression'] if expr['type'] == 'TypeApp' else expr['abstraction']
        if expr.get('type') == 'Var':
            value = expr['value']
            return '.'.join(value.get('moduleName', []) + [value['identifier']])
        return expr.get('type')
    nodes = list(walk(module['decls']))
    bindings = []
    for node in nodes:
        if 'identifier' in node and 'expression' in node and 'annotation' in node:
            if node['identifier'] in wanted:
                bindings.append({'name': node['identifier'], 'line': node['annotation']['sourceSpan']['start'][0],
                                 'annotation_type': expand(node['annotation'].get('type')),
                                 'expression_type': expand(node['expression']['annotation'].get('type')),
                                 'expression_form': node['expression']['type']})
    applications = []
    for node in nodes:
        if node.get('type') == 'TypeApp':
            target = head(node['expression'])
            if target in wanted:
                applications.append({'target': target, 'line': node['annotation']['sourceSpan']['start'][0],
                                     'type_argument': expand(node['typeArgument']),
                                     'annotation_type': expand(node['annotation'].get('type'))})
    return {'file': str(filename), 'sha256': hashlib.sha256(raw).hexdigest(),
            'exports': module['exports'], 'bindings': bindings, 'type_applications': applications,
            'foreign': {name: expand(ann.get('type')) for name, ann in module['foreignAnnotations'].items()},
            'variable_uses': [{'name': n['value']['identifier'], 'module': n['value'].get('moduleName'),
                               'line': n['annotation']['sourceSpan']['start'][0],
                               'type': expand(n['annotation'].get('type'))}
                              for n in nodes if n.get('type') == 'Var'
                              and n['value']['identifier'] in ['f', 'handler', 'invokeRecord', 'invokeClosure']],
            'type_app_count': sum(n.get('type') == 'TypeApp' for n in nodes)}

fixture = inspect(root / 'raw/tests/output/Main/corefn.json',
                  {'f', 'handler', 'foreignFunction', 'foreignRecord', 'returned', 'returnedRecord',
                   'Main.opaque', 'Foreign.unsafeToForeign', 'Foreign.unsafeFromForeign'})
lazy = inspect(worktree / 'run/bak/php/output/Test.LazyEvaluation/corefn.json',
               {'force', 'buildThunks', 'runManyTimes', 'Test.LazyEvaluation.defer', 'Test.LazyEvaluation.force'})
assert any(x['target'] == 'Main.opaque' and x['type_argument']['type'] == 'Func' for x in fixture['type_applications'])
assert any(x['target'] == 'Foreign.unsafeToForeign' and x['type_argument']['type'] == 'Record' for x in fixture['type_applications'])
# Use the actual call-site annotation. In this output the declaration's Handler
# synonym is Any, while invokeRecord and its argument are structural at each use.
record = next(x['type']['args'][0] for x in fixture['variable_uses'] if x['name'] == 'invokeRecord')
assert record['type'] == 'Record'
assert next(f['type'] for f in record['row']['fields'] if f['label'] == 'callback')['type'] == 'Func'
(root / 'tast-evidence.json').write_text(json.dumps({'fixture': fixture, 'lazy': lazy}, indent=2) + '\n')
print('TAST: function/record instantiations and nested callback type verified.')
print('Fixture TypeApp nodes:', fixture['type_app_count'])
print('Lazy buildThunks is exported:', 'buildThunks' in lazy['exports'])
