"""Count actual callback stages and traversal work on separate PHP copies."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess

from prepare import VARIANTS, php_manifest, save


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    keys = ['intAddEntries', 'addPartials', 'additions', 'listCons', 'listNil', 'foldVisits',
            'arrayRangeValues', 'filterVisits', 'filteredValues', 'predicateCalls', 'predicateOps',
            'primesFilterCalls', 'primesPredicates']
    reset = ''.join(f"$GLOBALS['{key}']=0;" for key in keys)
    results = []
    for variant in VARIANTS:
        source = artifacts / variant / 'output'
        assert php_manifest(source) == json.loads((artifacts / (variant + '-php.json')).read_text())
        output = artifacts / 'counted' / variant / 'output'
        for file in source.rglob('*.php'):
            target = output / file.relative_to(source)
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(file, target)

        def edit(module, before, after, count=1):
            file = output / module / 'index.php'
            text = file.read_text()
            assert text.count(before) == count, (variant, module, before, text.count(before))
            file.write_text(text.replace(before, after))

        def insert(module, needle, key, count=1):
            edit(module, needle, f"++$GLOBALS['{key}'];\n" + needle, count)

        edit('Data.Semiring', 'function majData_majSemiring_intmajAdd(int $v0, $v1 = null): int|\\Closure {',
             "function majData_majSemiring_intmajAdd(int $v0, $v1 = null): int|\\Closure { ++$GLOBALS['intAddEntries'];")
        insert('Data.Semiring', 'return function($a) use ($fn, $args, $expected) {', 'addPartials')
        insert('Data.Semiring', 'return $a + $b;', 'additions')
        edit('Test.ListOps', "final class Test_ListOps_Nil { public $tag = 'Nil'; public function __construct() {} }",
             "final class Test_ListOps_Nil { public $tag = 'Nil'; public function __construct() { ++$GLOBALS['listNil']; } }")
        edit('Test.ListOps', 'public function __construct(public  $value0, public  $value1) {}',
             "public function __construct(public  $value0, public  $value1) { ++$GLOBALS['listCons']; }")
        insert('Test.ListOps', "$__tco_2 = (($v_0)($v1_1))(($v2_2)->{'value0'});", 'foldVisits')
        if variant == 'list-add':
            insert('Test.ListOps', r"$__tco_2 = \Data\Semiring\majData_majSemiring_intmajAdd($v1_1, ($v2_2)->{'value0'});", 'foldVisits')
        insert('Data.Foldable', '$f1 = $f($acc);', 'foldVisits')
        insert('Data.Array', '$result[] = $i;', 'arrayRangeValues', 2)
        edit('Data.Array', 'if ($f($x)) $res[] = $x;',
             "++$GLOBALS['filterVisits']; if ($f($x)) { ++$GLOBALS['filteredValues']; $res[] = $x; }")
        edit('Test.ArrayOps', 'function($x_1) {', "function($x_1) { ++$GLOBALS['predicateCalls'];", 2)
        insert('Test.ArrayOps', '$__res = (($x_1 % 2) === 0);', 'predicateOps', 2)
        if variant.startswith('array-'):
            insert('Test.ArrayOps', r'$acc = \Data\Semiring\majData_majSemiring_intmajAdd($acc, $xs[$i]);', 'foldVisits')
            edit('Test.ArrayOps', 'if (($x % 2) === 0) $res[] = $x;',
                 "++$GLOBALS['filterVisits']; ++$GLOBALS['predicateOps']; if (($x % 2) === 0) { ++$GLOBALS['filteredValues']; $res[] = $x; }")
        edit('Test.Primes', 'function majTest_majPrimes_filter($p_0, $lst_1 = null) {',
             "function majTest_majPrimes_filter($p_0, $lst_1 = null) { ++$GLOBALS['primesFilterCalls'];")
        insert('Test.Primes', "if (( ! ((($v_3)->{'value0'} % $__local_var_1_1) === 0))) {", 'primesPredicates')
        for module in ['ListOps', 'ArrayOps'] + (['Primes'] if variant == 'baseline' else []):
            entry = output.parent / (module + '-count.php')
            entry.write_text('<?php\n' + reset + '\nrequire ' + repr(str(output / ('Test.' + module) / 'index.php'))
                             + ';\n' + reset + f"\n$value=$GLOBALS['Test_{module}_act']();\n"
                             + "echo json_encode(['output'=>$value," + ','.join(f"'{key}'=>$GLOBALS['{key}']" for key in keys) + ']),PHP_EOL;\n')
            run = subprocess.run(['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=0', str(entry)], text=True, capture_output=True)
            assert run.returncode == 0 and not run.stderr, (module, run.stdout, run.stderr)
            row = json.loads(run.stdout)
            expected = dict.fromkeys(keys, 0)
            if module == 'Primes':
                xs = list(range(2, 501))
                while xs:
                    prime, *tail = xs
                    expected['primesPredicates'] += len(tail)
                    xs = [value for value in tail if value % prime != 0]
                assert row['output'] == 21536
            else:
                specialized_add = (module == 'ListOps' and variant == 'list-add') or (module == 'ArrayOps' and variant == 'array-add')
                expected.update(intAddEntries=450 if specialized_add else 900, addPartials=0 if specialized_add else 450,
                                additions=450, foldVisits=450)
                if module == 'ListOps':
                    expected.update(listCons=1350, listNil=2)
                else:
                    expected.update(arrayRangeValues=900, filterVisits=900, filteredValues=450,
                                    predicateCalls=0 if variant == 'array-filter' else 900, predicateOps=900)
                assert row['output'] == 202950
            assert {key: row[key] for key in keys} == expected, (variant, module, row, expected)
            results.append({'variant': variant, 'module': module, **row})
    save(artifacts / 'counts.json', results)
    print(json.dumps(results, indent=2))


if __name__ == '__main__':
    main()
