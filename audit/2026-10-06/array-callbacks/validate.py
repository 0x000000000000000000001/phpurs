"""Validate fixtures, bundle execution, changed workloads and the normal rebuild."""
import argparse
import json
import os
from pathlib import Path
import subprocess

from common import REPO, WORKSPACE, BENCH, VARIANTS, FLAGS, digest, load_driver, php_manifest, save

FIXTURES = ['NativeArrayCallbacks', 'NativeFoldCallbacks', 'PartialBindings', 'FunctionFFIBoundary']


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    parser.add_argument('--codegen-log', type=Path)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    logs = artifacts / 'validation'
    logs.mkdir(exist_ok=True)
    results, workloads = {}, []

    def run(label, command, cwd=REPO):
        result = subprocess.run(command, cwd=cwd, text=True, capture_output=True, timeout=600,
                                env={**os.environ, 'TMPDIR': str(artifacts.parent)})
        logfile = logs / (label + '.log')
        logfile.write_text(result.stdout + result.stderr)
        assert result.returncode == 0, str(logfile)
        results[label] = {'command': command, 'logSHA256': digest(logfile)}
        return result

    if args.codegen_log:
        text = args.codegen_log.read_text()
        (logs / 'codegen.log').write_text(text)
        results['codegen'] = {'command': ['npm', 'run', 'test:codegen'], 'logSHA256': digest(logs / 'codegen.log')}
    else:
        text = run('codegen', ['npm', 'run', 'test:codegen']).stdout
    assert 'pass 86' in text and 'fail 0' in text
    for fixture in FIXTURES:
        php = run(fixture + '-php', ['./bin/test', fixture])
        assert '1 passed' in php.stdout
        js = run(fixture + '-js', ['node', '--input-type=module', '-e',
                                 "import('./tests/runner/output-test/Main/index.js').then(m=>m.main())"])
        assert js.stdout.strip() == 'Done' and not js.stderr
        if fixture == 'NativeArrayCallbacks':
            code = (REPO / 'tests/runner/output-test/Main/index.php').read_text()
            assert code.count('// Main___phpurs_arraycb_') >= 3
            captured = code.split('// Main_captured\n')[1].split('// Main_')[0]
            assert '__phpurs_arraycb_' not in captured
            (logs / (fixture + '.php')).write_text(code)
            run('array-bundle-build', ['../../bin/phpurs', '--output', 'output-test', '--bundle'], REPO / 'tests/runner')
            bundle = run('array-bundle-run', ['php', 'output-test/Main/main.bundle.php'], REPO / 'tests/runner')
            assert bundle.stdout.strip() == 'Done'
        print(fixture + ': PHP and JavaScript Done', flush=True)
    baseline_workloads = {}
    for variant in VARIANTS:
        output = artifacts / variant / 'output'
        for module, expected in [('WorkloadArrayInt', '35406173408'), ('WorkloadArrayNumber', None), ('WorkloadMap', '400040000')]:
            code = f"require {str(output / ('Test.' + module + '/index.php'))!r}; "
            code += f"try {{$result=['value'=>(string)$GLOBALS['Test_{module}_act']()];}} catch (\\Throwable $e) {{$result=['error'=>get_class($e),'message'=>$e->getMessage()];}}"
            if module == 'WorkloadArrayNumber':
                # This Purust-only workload already fails at an unavailable
                # Number.floor FFI in PHP. Compare that error and exercise its
                # changed filter directly before reaching the unrelated floor.
                code += "$result['sumProbe']=$GLOBALS['Test_WorkloadArrayNumber_sumValues']([1.0,2.0,3.0,4.0]);"
                code += "$result['filterProbe']=$GLOBALS['Test_WorkloadArrayNumber_sumFiltered']([1.0,2.0,3.0,4.0]);"
            code += 'echo json_encode($result), PHP_EOL;'
            result = run(variant + '-' + module, ['php', *FLAGS, '-r', code])
            assert not result.stderr
            value = json.loads(result.stdout)
            if expected is not None:
                assert value == {'value': expected}, (variant, module, value)
            else:
                assert value['error'] == 'TypeError' and 'majData_majNumber_floor(): Return value' in value['message']
                assert value['sumProbe'] == value['filterProbe'] == 10
            if variant == 'baseline':
                baseline_workloads[module] = value
            assert value == baseline_workloads[module], (variant, module, value)
            workloads.append({'variant': variant, 'module': module, **value})
    before = digest(REPO / 'bin/phpurs.js')
    rebuilt = run('clean-benchmark', ['bin/php/run', '-c'], WORKSPACE / 'altbak.pub-phpurs')
    driver = load_driver()
    benchmark = driver.validate_output(rebuilt.stdout, 'pure', None, None)
    assert benchmark['values_validated'] and len(benchmark['values']) == 14
    assert php_manifest(BENCH / 'output') == json.loads((artifacts / 'integrated-php.json').read_text())
    assert driver.vendor_artifacts(BENCH) == json.loads((artifacts / 'vendor.json').read_text())
    save(artifacts / 'validation.json', {'runs': results, 'fixtures': FIXTURES, 'codegenChecks': 86, 'workloads': workloads,
         'benchmark': benchmark, 'standaloneBeforeSHA256': before, 'standaloneAfterSHA256': digest(REPO / 'bin/phpurs.js')})
    print(json.dumps({'codegen': 86, 'fixtures': len(FIXTURES), 'workloads': workloads, 'benchmark': benchmark}, indent=2))


if __name__ == '__main__':
    main()
