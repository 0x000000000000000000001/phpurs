"""Count traversal work on separate, non-timed PHP copies."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess

from common import VARIANTS, php_manifest, save


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    artifacts = parser.parse_args().artifacts.resolve()
    keys = ['nativeEntries', 'partialClosures', 'additions', 'foldVisits', 'rangeValues', 'filterVisits', 'predicateCalls', 'predicateOps', 'filteredValues']
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
             "function majData_majSemiring_intmajAdd(int $v0, $v1 = null): int|\\Closure { ++$GLOBALS['nativeEntries'];")
        insert('Data.Semiring', 'return function($a) use ($fn, $args, $expected) {', 'partialClosures')
        insert('Data.Semiring', 'return $a + $b;', 'additions')
        insert('Data.Foldable', '$acc = $f1($xs[$i]);', 'foldVisits')
        insert('Data.Array', '$result[] = $i;', 'rangeValues', 2)
        edit('Data.Array', 'if ($f($x)) $res[] = $x;', "++$GLOBALS['filterVisits']; if ($f($x)) { ++$GLOBALS['filteredValues']; $res[] = $x; }")
        edit('Test.ArrayOps', '$__res = (($x_1 % 2) === 0);', "++$GLOBALS['predicateCalls']; ++$GLOBALS['predicateOps']; $__res = (($x_1 % 2) === 0);", 2)
        if variant in ['fold', 'integrated']:
            insert('Test.ArrayOps', r'$acc = \Data\Semiring\majData_majSemiring_intmajAdd($acc, ($xs)[$i]);', 'foldVisits')
        if variant in ['filter', 'integrated']:
            edit('Test.ArrayOps', 'foreach ($xs as $item) {', "foreach ($xs as $item) { ++$GLOBALS['filterVisits'];")
            edit('Test.ArrayOps', '(($item % 2) === 0)', "((++$GLOBALS['predicateOps'] > 0) && (($item % 2) === 0))")
            edit('Test.ArrayOps', '$result[] = $item;', "{ ++$GLOBALS['filteredValues']; $result[] = $item; }")
        code = reset + '\nrequire ' + repr(str(output / 'Test.ArrayOps/index.php')) + ';\n' + reset
        code += "\n$result=$GLOBALS['Test_ArrayOps_act'](); echo json_encode(['output'=>$result," + ','.join(f"'{key}'=>$GLOBALS['{key}']" for key in keys) + ']),PHP_EOL;'
        run = subprocess.run(['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=0', '-r', code], text=True, capture_output=True)
        assert run.returncode == 0 and not run.stderr, (run.stdout, run.stderr)
        value = json.loads(run.stdout)
        saturated = variant in ['fold', 'integrated']
        filtered = variant in ['filter', 'integrated']
        expected = [450 if saturated else 900, 0 if saturated else 450, 450, 450, 900, 900, 0 if filtered else 900, 900, 450]
        assert value['output'] == 202950 and [value[key] for key in keys] == expected, value
        results.append({'variant': variant, **value})
    save(artifacts / 'counts.json', results)
    print(json.dumps(results, indent=2))


if __name__ == '__main__':
    main()
