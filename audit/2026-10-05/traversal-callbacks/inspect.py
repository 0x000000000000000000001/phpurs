"""Record the real optimized call sites and the boundaries of this experiment."""
import argparse
import json
from pathlib import Path

from prepare import digest, save


def peel(value):
    while isinstance(value, dict) and value.get('$ctor') in ['Typed', 'TypeApp']:
        value = value['value1' if value['$ctor'] == 'Typed' else 'value0']
    return value


def walk(value):
    if isinstance(value, dict):
        yield value
        for child in value.values():
            yield from walk(child)
    elif isinstance(value, list):
        for child in value:
            yield from walk(child)


def global_name(value):
    value = peel(value)
    if value.get('$ctor') != 'Var':
        return None
    qualified = value['value0']
    return qualified['value0'].get('value0', '') + '.' + qualified['value1']


def calls(expr, target):
    return [value for value in walk(expr) if value.get('$ctor') in ['App', 'UncurriedApp']
            and global_name(value['value0']) == target]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    modules = {name: json.loads((artifacts / (name + '.optimized.json')).read_text())
               for name in ['Test.ListOps', 'Test.ArrayOps', 'Test.Primes']}
    bindings = {name: {binding['value0']: binding['value1'] for group in module['bindings'] for binding in group['bindings']}
                for name, module in modules.items()}
    list_call, = calls(bindings['Test.ListOps']['sumEvens'], 'Test.ListOps.foldl')
    array_call, = calls(bindings['Test.ArrayOps']['sumEvens'], 'Data.Foldable.foldlArray')
    for call in [list_call, array_call]:
        assert call['$ctor'] == 'App' and len(call['value1']) == 3
        assert global_name(call['value1'][0]) == 'Data.Semiring.intAdd'
        assert peel(call['value1'][1]) == {'$ctor': 'Lit', 'value0': {'$ctor': 'LitInt', 'value0': 0}}
    callback_sites = [value for value in walk(bindings['Test.ListOps']['foldl']) if value.get('$ctor') == 'App'
                      and peel(value['value0']).get('$ctor') == 'Local']
    assert len(callback_sites) == 1 and len(callback_sites[0]['value1']) == 2
    filter_call, = calls(bindings['Test.ArrayOps']['sumEvens'], 'Data.Array.filterImpl')
    assert filter_call['$ctor'] == 'UncurriedApp' and len(filter_call['value1']) == 2
    predicate = peel(filter_call['value1'][0])
    assert predicate['$ctor'] == 'Abs' and len(predicate['value0']) == 1
    operators = [node['$ctor'] for node in walk(predicate) if node.get('$ctor') in ['OpMod', 'OpEq']]
    assert sorted(operators) == ['OpEq', 'OpMod']
    assert sorted(node['value0'] for node in walk(predicate) if node.get('$ctor') == 'LitInt') == [0, 2]
    assert not any(node.get('$ctor') in ['App', 'UncurriedApp', 'Var'] for node in walk(predicate))
    level = predicate['value0'][0]['value1']
    assert all(node['value1'] == level for node in walk(predicate) if node.get('$ctor') == 'Local')
    sieve = bindings['Test.Primes']['sieve']
    assert not calls(sieve, 'Test.Primes.filter')
    assert sum(node.get('$ctor') == 'OpMod' for node in walk(sieve)) == 1
    assert sum(node.get('$ctor') == 'OpBooleanNot' for node in walk(sieve)) == 1
    recursive_calls = calls(sieve, 'Test.Primes.sieve')
    assert len(recursive_calls) == 1
    record = {
        'scope': 'Pinned sites and bodies; no generic specialization pass',
        'optimizedSHA256': {name: digest(artifacts / (name + '.optimized.json')) for name in modules},
        'list': {'traversal': 'Test.ListOps.foldl', 'callback': 'Data.Semiring.intAdd', 'callArguments': 3,
                 'callbackArgumentsPerStep': 2, 'publicFoldType': bindings['Test.ListOps']['foldl']['value0']},
        'arrayFold': {'traversal': 'Data.Foldable.foldlArray', 'callback': 'Data.Semiring.intAdd', 'callArguments': 3,
                      'bodySource': 'phpurs-foldable-traversable/src/Data/Foldable.php'},
        'arrayFilter': {'traversal': 'Data.Array.filterImpl', 'callingConvention': 'UncurriedApp',
                        'callArguments': 2, 'predicateArguments': 1, 'predicateCaptures': 0,
                        'bodySource': 'phpurs-arrays/src/Data/Array.php'},
        'primes': {'genericFilterCallsInSieve': 0, 'inlineModuloPredicates': 1,
                   'classification': 'Already specialized by PBO in the measured path'},
        'intAddBoundary': {'runtimeNativeArity': 2, 'firstParameterType': 'int', 'returnType': 'int|Closure',
                           'specialization': 'Direct saturated native call; argument and return checks retained'},
    }
    save(artifacts / 'tast-evidence.json', record)
    print(json.dumps({key: value for key, value in record.items() if key != 'list'}, indent=2))


if __name__ == '__main__':
    main()
