"""Run codegen regressions, seven PHP/JS fixtures, then the normal clean benchmark."""
import argparse
import json
import os
from pathlib import Path
import subprocess

from prepare import REPO, WORKSPACE, BENCH, digest, load_driver, php_manifest, save

FIXTURES = ['NativeFoldCallbacks', 'ImmediateStateFusion', 'ImmediateThunkFusion',
            'PartialBindings', 'CompactClosureLoops', 'FunctionFFIBoundary', 'RecursiveDictionaryInitialization']


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    artifacts = parser.parse_args().artifacts.resolve()
    logs = artifacts / 'validation'
    logs.mkdir()
    results = {}

    def run(label, command, cwd=REPO):
        result = subprocess.run(command, cwd=cwd, text=True, capture_output=True, timeout=600,
                                env={**os.environ, 'TMPDIR': str(artifacts.parent)})
        logfile = logs / (label + '.log')
        logfile.write_text(result.stdout + result.stderr)
        assert result.returncode == 0, str(logfile)
        results[label] = {'command': command, 'logSHA256': digest(logfile)}
        return result

    codegen = run('codegen', ['npm', 'run', 'test:codegen'])
    assert 'pass 85' in codegen.stdout and 'fail 0' in codegen.stdout
    for fixture in FIXTURES:
        php = run(fixture + '-php', ['./bin/test', fixture])
        assert '1 passed' in php.stdout
        js = run(fixture + '-js', ['node', '--input-type=module', '-e',
                                 "import('./tests/runner/output-test/Main/index.js').then(m=>m.main())"])
        assert js.stdout.strip() == 'Done' and not js.stderr
        if fixture == 'NativeFoldCallbacks':
            code = (REPO / 'tests/runner/output-test/Main/index.php').read_text()
            assert '// Main___phpurs_foldcb_0_0' in code and '// Main___phpurs_foldcb_0_1' in code
            dynamic = code.split('// Main_dynamicInitial\n')[1].split('// Main_')[0]
            assert '__phpurs_foldcb_' not in dynamic
            (logs / (fixture + '.php')).write_text(code)
        print(fixture + ': PHP and JavaScript Done', flush=True)
    before = digest(REPO / 'bin/phpurs.js')
    rebuilt = run('clean-benchmark', ['bin/php/run', '-c'], WORKSPACE / 'altbak.pub-phpurs')
    driver = load_driver()
    benchmark = driver.validate_output(rebuilt.stdout, 'pure', None, None)
    assert benchmark['values_validated'] and len(benchmark['values']) == 14
    assert php_manifest(BENCH / 'output') == json.loads((artifacts / 'integrated-php.json').read_text())
    assert driver.vendor_artifacts(BENCH) == json.loads((artifacts / 'vendor.json').read_text())
    save(artifacts / 'validation.json', {'runs': results, 'fixtures': FIXTURES, 'codegenChecks': 85,
         'benchmark': benchmark, 'standaloneBeforeSHA256': before, 'standaloneAfterSHA256': digest(REPO / 'bin/phpurs.js')})
    print(json.dumps({'codegen': 85, 'fixtures': len(FIXTURES), 'benchmark': benchmark}, indent=2))


if __name__ == '__main__':
    main()
