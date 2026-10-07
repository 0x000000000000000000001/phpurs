"""Validate executable fixtures and the normal rebuild against the frozen audit."""
import argparse
import json
import os
from pathlib import Path
import re
import subprocess

from compare import REPO, WORKSPACE, BENCH, digest, save, php_manifest, helpers

FIXTURES = ['NativeArrayCallbacks', 'FunctionFFIBoundary', 'PartialBindings', 'RecursiveDictionaryInitialization', 'EffFn']


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    parser.add_argument('--codegen-log', type=Path, required=True)
    parser.add_argument('--completed-logs', action='store_true',
                        help='Finish interrupted post-processing using already successful command logs; only repackage the CLI.')
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    logs = artifacts / 'integration'
    logs.mkdir(exist_ok=args.completed_logs)
    sources = json.loads((artifacts / 'sources.json').read_text())
    assert all(digest(Path(file)) == value for file, value in sources.items()
               if not (args.completed_logs and Path(file) == REPO / 'bin/phpurs.js'))
    frozen = json.loads((artifacts / 'results.json').read_text())
    # The runner's registry lists dependency eagerly initializes its lazy Nil.
    # Bundles include it even when the fixture only uses strict arrays. Supply
    # the existing PHP implementation instead of relying on a missing-FFI stub.
    lazy_ffi = REPO.parent / 'phpurs-lazy'
    lazy_source = lazy_ffi / 'src/Data/Lazy.php'
    lazy_digest = digest(lazy_source)
    runs = {}

    def run(label, command, cwd=REPO, force=False):
        log = logs / (label + '.log')
        if not args.completed_logs or force:
            result = subprocess.run(command, cwd=cwd, env={**os.environ, 'TMPDIR': str(artifacts.parent.parent)},
                                    text=True, capture_output=True, timeout=600)
            log.write_text(result.stdout + result.stderr)
            assert result.returncode == 0, str(log)
        runs[label] = {'command': command, 'logSHA256': digest(log)}
        return log.read_text()

    codegen = args.codegen_log.read_text()
    assert 'pass 95' in codegen and 'fail 0' in codegen
    (logs / 'codegen.log').write_text(codegen)
    runs['codegen'] = {'command': ['npm', 'run', 'test:codegen'], 'logSHA256': digest(logs / 'codegen.log')}
    for fixture in FIXTURES:
        php = run(fixture + '-php', ['./bin/test', fixture])
        assert '1 passed' in re.sub(r'\x1b\[[0-9;]*m', '', php)
        js = run(fixture + '-js', ['node', '--input-type=module', '-e',
                                 "import('./tests/runner/output-test/Main/index.js').then(m=>m.main())"])
        assert js.strip() == 'Done'
        if fixture == 'NativeArrayCallbacks':
            run('fixture-bundle-build', ['../../bin/phpurs', '--output', 'output-test', '--bundle', '--ffi', str(lazy_ffi)], REPO / 'tests/runner')
            bundle = run('fixture-bundle-run', ['php', 'output-test/Main/main.bundle.php'], REPO / 'tests/runner')
            assert bundle.strip() == 'Done'
        print(fixture + ': PHP and JavaScript Done', flush=True)

    before = frozen['compilers']['after']['sha256']
    assert digest(Path(frozen['compilers']['after']['file'])) == before
    if not args.completed_logs:
        assert digest(REPO / 'bin/phpurs.js') == before
    rebuilt = run('clean-benchmark', ['bin/php/run', '-c'], WORKSPACE / 'altbak.pub-phpurs')
    after_rebuild = digest(REPO / 'bin/phpurs.js')
    driver = helpers.load_driver()
    benchmark = driver.validate_output(rebuilt, 'pure', None, None)
    assert benchmark['values_validated'] and len(benchmark['values']) == 14
    assert php_manifest(BENCH / 'output') == json.loads((artifacts / 'after-php.json').read_text())
    assert driver.vendor_artifacts(BENCH) == json.loads((artifacts / 'vendor.json').read_text())
    # The benchmark workflow bundles through Spago. Restore the packaged CLI
    # with automatic cache identity, and require exactly the tested bytes.
    run('package-cli', ['node', 'tools/bundle.mjs'], force=True)
    assert digest(REPO / 'bin/phpurs.js') == before
    assert all(digest(Path(file)) == value for file, value in sources.items())
    assert digest(lazy_source) == lazy_digest
    result = {'codegenChecks': 95, 'fixtures': FIXTURES, 'runs': runs, 'benchmark': benchmark,
              'packagedSHA256': before, 'spagoRebundleSHA256': after_rebuild, 'packagedRestored': True,
              'activePhpMatchesFrozen': True, 'sourceFilesVerified': len(sources),
              'bundleAdditionalFFI': {'source': str(lazy_source), 'sha256': lazy_digest},
              'completedLogsRevalidated': args.completed_logs}
    save(artifacts / 'integration.json', result)
    print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
