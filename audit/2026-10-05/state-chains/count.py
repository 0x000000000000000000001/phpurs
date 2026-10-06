"""Instrument copies of the generated State module; preserve measured files."""
import argparse
import json
from pathlib import Path
import re
import subprocess

from prepare import MODULE, php_manifest, save


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    keys = ['incrementClosures', 'partialClosures', 'continuationClosures', 'baseClosures', 'records', 'additions', 'outerAdditions']
    reset = ''.join(f"$GLOBALS['{key}']=0;" for key in keys)
    results = []
    for variant in ['baseline', 'strict', 'scalar']:
        output = artifacts / variant / 'output'
        assert php_manifest(output) == json.loads((artifacts / (variant + '-php.json')).read_text())
        # Only the State file is copied; all imports point to the frozen siblings.
        text = (output / MODULE).read_text()
        text = re.sub(r"__DIR__ \. '/\.\./([^']+)'", lambda match: repr(str(output / match[1])), text)

        def insert(needle, key, count=1):
            nonlocal text
            assert text.count(needle) == count, (variant, needle, text.count(needle))
            text = text.replace(needle, f"++$GLOBALS['{key}'];\n" + needle)

        # A State-specific native currying fallback creates the partial modify.
        insert('return function($a) use ($fn, $args, $expected) {', 'partialClosures')
        insert("$__local_var_1_0 = ($GLOBALS['Test_StateMonad_modify'])(function($x_1) {", 'incrementClosures')
        insert('$__t1 = function($s_2) use ($__local_var_1_0, $v_0) {', 'continuationClosures')
        insert('$__t1 = function($s_1) {', 'baseClosures')
        insert('$__res = (object)[', 'records', 5)
        insert('$__res = ($x_1 + 1);', 'additions')
        insert('$__tco_1 = ($v1_1 + ', 'outerAdditions')
        if variant != 'baseline':
            insert('$result = majTest_majStatemajMonad_modify(function($value) {', 'incrementClosures')
            insert('return $value + 1;', 'additions')
            insert("return (object)['val' => $GLOBALS['Data_Unit_unit'], 'state' => $state];", 'records')
            insert('$state = $state + 1;', 'additions')
        destination = artifacts / 'counted'
        destination.mkdir(exist_ok=True)
        module = destination / (variant + '.php')
        module.write_text(text)
        entry = destination / (variant + '-run.php')
        entry.write_text('<?php\n' + reset + '\nrequire ' + repr(str(module)) + ';\n' + reset + '\n'
                         + "$value=$GLOBALS['Test_StateMonad_act']();\n"
                         + "echo json_encode(['output'=>$value," + ','.join(f"'{key}'=>$GLOBALS['{key}']" for key in keys) + ']),PHP_EOL;\n')
        # The self-require now points to the original State module; suppress it
        # to keep this one instrumented definition in the process.
        self_require = "require_once " + repr(str(output) + '/Test.StateMonad/index.php') + ';'
        assert text.count(self_require) == 1
        module.write_text(text.replace(self_require, ''))
        run = subprocess.run(['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=0', str(entry)], text=True, capture_output=True)
        assert run.returncode == 0 and not run.stderr, (run.stdout, run.stderr)
        row = json.loads(run.stdout)
        expected = {'baseline': [1200, 1200, 1200, 20, 2420, 1200, 20],
                    'strict': [1200, 0, 0, 0, 2420, 1200, 20], 'scalar': [0, 0, 0, 0, 0, 1200, 20]}[variant]
        assert row['output'] == 1200 and [row[key] for key in keys] == expected, row
        results.append({'variant': variant, **row})
    save(artifacts / 'counts.json', results)
    print(json.dumps(results, indent=2))


if __name__ == '__main__':
    main()
