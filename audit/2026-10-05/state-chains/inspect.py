"""Assert the pinned optimized bodies behind the PHP experiment, not a compiler pass."""
import argparse
import hashlib
import json
from pathlib import Path

from prepare import save


def shape(value):
    if isinstance(value, list):
        return [shape(child) for child in value]
    if not isinstance(value, dict):
        return value
    tag = value.get('$ctor')
    if tag == 'Typed':
        return shape(value['value1'])
    if tag == 'TypeApp':
        return shape(value['value0'])
    return [tag, *[shape(value[key]) for key in sorted(value) if key != '$ctor']]


def walk(value):
    if isinstance(value, dict):
        yield value
        for child in value.values():
            yield from walk(child)
    elif isinstance(value, list):
        for child in value:
            yield from walk(child)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    file = artifacts / 'optimized.json'
    module = json.loads(file.read_text())
    bindings = {binding['value0']: binding['value1'] for group in module['bindings'] for binding in group['bindings']}
    n = lambda tag, *values: [tag, *values]
    maybe = lambda name: n('Nothing') if name is None else n('Just', name)
    local = lambda name, level: n('Local', maybe(name), level)
    var = lambda name, mod=module['name']: n('Var', n('Qualified', maybe(mod), name))
    integer = lambda value: n('Lit', n('LitInt', value))
    function = lambda name, level, body: n('Abs', [n('Tuple', maybe(name), level)], body)
    app = lambda fn, *values: n('App', fn, list(values))
    prop = lambda expr, name: n('Accessor', expr, n('GetProp', name))
    record = lambda value, state: n('Lit', n('LitRecord', [n('Prop', 'val', value), n('Prop', 'state', state)]))
    numeric = lambda operation, left, right: n('PrimOp', n('Op2', n('OpIntNum', n(operation)), left, right))
    unit = var('unit', 'Data.Unit')
    expected_get = function('s', 0, record(local('s', 0), local('s', 0)))
    expected_modify = function('f', 0, function('s', 1, record(unit,
        app(local('f', 0), prop(app(var('get'), local('s', 1)), 'val')))))
    base = function('s', 1, record(unit, local('s', 1)))
    increment = function('x', 1, numeric('OpAdd', local('x', 1), integer(1)))
    continuation = function('s', 2, app(var('chainModifications'),
        numeric('OpSubtract', local('v', 0), integer(1)), prop(app(local(None, 1), local('s', 2)), 'state')))
    condition = n('PrimOp', n('Op2', n('OpIntOrd', n('OpEq')), local('v', 0), integer(0)))
    expected_chain = function('v', 0, n('Branch', [n('Pair', condition, base)],
        n('Let', maybe(None), 1, app(var('modify'), increment), continuation)))
    for name, expected in [('get', expected_get), ('modify', expected_modify), ('chainModifications', expected_chain)]:
        assert shape(bindings[name]) == expected, name
    chain_type = bindings['chainModifications']['value0']
    record_type = n('Record', n('Row', [n('Tuple', 'val', n('Unit')), n('Tuple', 'state', n('Int'))], n('Nothing')))
    assert shape(chain_type) == n('Func', [n('Int'), n('Int')], record_type)
    targets = [value for value in walk(bindings['runManyTimes']) if value.get('$ctor') == 'Accessor'
               and shape(value) == prop(app(var('chainModifications'), integer(60), integer(0)), 'state')]
    assert len(targets) == 1
    recursive = [group for group in module['bindings'] if any(b['value0'] == 'chainModifications' for b in group['bindings'])]
    assert len(recursive) == 1 and recursive[0]['recursive'] and len(recursive[0]['bindings']) == 1
    instantiated = [v for v in walk(bindings['chainModifications']) if v.get('$ctor') == 'TypeApp'
                    and shape(v['value0']) == var('modify') and shape(v['value1']) == n('Int')]
    assert len(instantiated) == 1
    residual = sorted({v['value0'] for v in walk(bindings['chainModifications']) if v.get('$ctor') == 'TypeVar'})
    result = {
        'scope': 'Pinned diagnostic body checks; generic matching, refusal coverage and compiler integration are pending',
        'optimizedSHA256': hashlib.sha256(file.read_bytes()).hexdigest(),
        'builderRuntimeArity': 1, 'builderFlattenedArgumentTypes': ['Int', 'Int'],
        'builderResult': {'val': 'Unit', 'state': 'Int'},
        'recursiveGroupSize': 1, 'modifyInstantiatedAtInt': True,
        'entry': {'depth': 60, 'initialState': 0, 'projection': 'state', 'expressionHasTypedWrapper': False},
        'step': {'op': 'OpAdd', 'amount': 1, 'captures': [], 'countdown': 1},
        'remainingTypeVariablesInInnerAnnotations': residual,
        'normalizedBodies': {name: json.dumps(shape(bindings[name]), separators=(',', ':'))
                             for name in ['get', 'modify', 'chainModifications']},
    }
    save(artifacts / 'tast-evidence.json', result)
    print(json.dumps({key: value for key, value in result.items() if key != 'normalizedBodies'}, indent=2))


if __name__ == '__main__':
    main()
