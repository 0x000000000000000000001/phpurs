"""Count callback entries, partial closures and unchanged list work in copies."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess

from prepare import php_manifest, save


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    artifacts = parser.parse_args().artifacts.resolve()
    keys = ['nativeEntries', 'partialClosures', 'additions', 'cons', 'nil', 'foldVisits']
    reset = ''.join(f"$GLOBALS['{key}']=0;" for key in keys)
    results = []
    for variant in ['baseline', 'integrated']:
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

        def insert(module, needle, key):
            edit(module, needle, f"++$GLOBALS['{key}'];\n" + needle)

        edit('Data.Semiring', 'function majData_majSemiring_intmajAdd(int $v0, $v1 = null): int|\\Closure {',
             "function majData_majSemiring_intmajAdd(int $v0, $v1 = null): int|\\Closure { ++$GLOBALS['nativeEntries'];")
        insert('Data.Semiring', 'return function($a) use ($fn, $args, $expected) {', 'partialClosures')
        insert('Data.Semiring', 'return $a + $b;', 'additions')
        edit('Test.ListOps', "final class Test_ListOps_Nil { public $tag = 'Nil'; public function __construct() {} }",
             "final class Test_ListOps_Nil { public $tag = 'Nil'; public function __construct() { ++$GLOBALS['nil']; } }")
        edit('Test.ListOps', 'public function __construct(public  $value0, public  $value1) {}',
             "public function __construct(public  $value0, public  $value1) { ++$GLOBALS['cons']; }")
        insert('Test.ListOps', "$__tco_2 = (($v_0)($v1_1))(($v2_2)->{'value0'});", 'foldVisits')
        if variant == 'integrated':
            insert('Test.ListOps', r"$__tco_2 = \Data\Semiring\majData_majSemiring_intmajAdd($v1_1, ($v2_2)->{'value0'});", 'foldVisits')
        entry = output.parent / 'count.php'
        entry.write_text('<?php\n' + reset + '\nrequire ' + repr(str(output / 'Test.ListOps/index.php')) + ';\n' + reset
                         + "\n$result=$GLOBALS['Test_ListOps_act']();\necho json_encode(['output'=>$result,"
                         + ','.join(f"'{key}'=>$GLOBALS['{key}']" for key in keys) + ']),PHP_EOL;\n')
        run = subprocess.run(['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=0', str(entry)], text=True, capture_output=True)
        assert run.returncode == 0 and not run.stderr, (run.stdout, run.stderr)
        value = json.loads(run.stdout)
        expected = [900, 450, 450, 1350, 2, 450] if variant == 'baseline' else [450, 0, 450, 1350, 2, 450]
        assert value['output'] == 202950 and [value[key] for key in keys] == expected, value
        results.append({'variant': variant, **value})
    save(artifacts / 'counts.json', results)
    print(json.dumps(results, indent=2))


if __name__ == '__main__':
    main()
