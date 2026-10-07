"""Count actual work in separate instrumented copies, never timed PHP files."""
import argparse
import json
from pathlib import Path
import re
import subprocess

from prepare import MODULE, php_manifest, save


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    artifacts = parser.parse_args().artifacts.resolve()
    keys = ['incrementClosures', 'partialClosures', 'continuationClosures', 'baseClosures', 'records', 'additions', 'outerAdditions']
    reset = ''.join(f"$GLOBALS['{key}']=0;" for key in keys)
    destination = artifacts / 'counted'
    destination.mkdir(exist_ok=True)
    results = []
    for variant in ['baseline', 'integrated']:
        output = artifacts / variant / 'output'
        assert php_manifest(output) == json.loads((artifacts / (variant + '-php.json')).read_text())
        text = (output / MODULE).read_text()
        text = re.sub(r"__DIR__ \. '/\.\./([^']+)'", lambda match: repr(str(output / match[1])), text)
        self_require = 'require_once ' + repr(str(output / MODULE)) + ';'
        assert text.count(self_require) == 1
        text = text.replace(self_require, '')

        def insert(needle, key, count=1):
            nonlocal text
            assert text.count(needle) == count, (variant, needle, text.count(needle))
            text = text.replace(needle, f"++$GLOBALS['{key}'];\n" + needle)

        insert('return function($a) use ($fn, $args, $expected) {', 'partialClosures')
        insert("$__local_var_1_0 = ($GLOBALS['Test_StateMonad_modify'])(function($x_1) {", 'incrementClosures')
        insert('$__t1 = function($s_2) use ($__local_var_1_0, $v_0) {', 'continuationClosures')
        insert('$__t1 = function($s_1) {', 'baseClosures')
        insert('$__res = (object)[', 'records', 5)
        insert('$__res = ($x_1 + 1);', 'additions')
        insert('$__tco_1 = ($v1_1 + ', 'outerAdditions')
        if variant == 'integrated':
            insert('$__tco_1 = ($value_1 + 1);', 'additions')
        module = destination / (variant + '.php')
        module.write_text(text)
        entry = destination / (variant + '-run.php')
        entry.write_text('<?php\n' + reset + '\nrequire ' + repr(str(module)) + ';\n' + reset + '\n'
                         + "$value=$GLOBALS['Test_StateMonad_act']();\n"
                         + "echo json_encode(['output'=>$value," + ','.join(f"'{key}'=>$GLOBALS['{key}']" for key in keys) + ']),PHP_EOL;\n')
        run = subprocess.run(['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=0', str(entry)], text=True, capture_output=True)
        assert run.returncode == 0 and not run.stderr, (run.stdout, run.stderr)
        value = json.loads(run.stdout)
        expected = [1200, 1200, 1200, 20, 2420, 1200, 20] if variant == 'baseline' else [0, 0, 0, 0, 0, 1200, 20]
        assert value['output'] == 1200 and [value[key] for key in keys] == expected, value
        results.append({'variant': variant, **value})
    save(artifacts / 'counts.json', results)
    print(json.dumps(results, indent=2))


if __name__ == '__main__':
    main()
