"""Measure rewrite guards on frozen inputs; failed builds never produce runtime samples."""
import argparse
import contextlib
import importlib.util
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import time
from types import SimpleNamespace

from prepare import REPO, WORKSPACE, digest, save


def output_manifest(directory):
    return {str(p.relative_to(directory)): {'sha256': digest(p), 'bytes': p.stat().st_size}
            for p in sorted(directory.rglob('*')) if p.is_file() and
            (p.suffix == '.php' or p.name in {'corefn.json', 'composer.json', '.phpurs-outputs.json'})}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    parser.add_argument('--project', choices=['b8x', 'bench'], required=True)
    parser.add_argument('--label', required=True)
    parser.add_argument('--limits', required=True, help='Comma-separated positive decimal guards')
    parser.add_argument('--repetitions', type=int, default=1)
    parser.add_argument('--diagnostic', action='store_true')
    parser.add_argument('--allow-limit-failure', action='store_true')
    parser.add_argument('--runtime', action='store_true')
    args = parser.parse_args()
    assert re.fullmatch(r'[a-z0-9-]+', args.label)
    limits = [int(value) for value in args.limits.split(',')]
    assert limits and len(limits) == len(set(limits)) and all(0 < n <= 2147483647 for n in limits)
    assert args.repetitions > 0 and not (args.diagnostic and args.runtime)
    assert not args.runtime or args.project == 'bench'
    artifacts = args.artifacts.resolve()
    preparation = json.loads((artifacts / 'preparation.json').read_text())
    inputs = json.loads((artifacts / 'inputs.json').read_text())
    assert digest(artifacts / 'backend.mjs') == preparation['compilerSHA256']
    work = artifacts / 'work'
    if not work.exists():
        shutil.copytree(artifacts / 'snapshot', work)
    project = preparation['projects'][args.project]
    cwd = work / project['relativeRoot']
    output = cwd / 'output'
    count = len(project['modules'])
    logs = artifacts / 'builds'
    logs.mkdir(exist_ok=True)
    reference = artifacts / (args.project + '-outputs.json')
    expected = json.loads(reference.read_text()) if reference.exists() else None
    driver = None
    if args.project == 'bench':
        if not (cwd / 'vendor').exists():
            shutil.copytree(artifacts / 'bench-vendor', cwd / 'vendor', symlinks=True)
        spec = importlib.util.spec_from_file_location('php_driver', WORKSPACE / 'altbak.pub-phpurs/bin/php/driver.py')
        driver = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(driver)
        expected_vendor = driver.vendor_artifacts(cwd)
        assert expected_vendor is not None
        driver_inputs = driver.inputs()
        for filename, value in [('bench-vendor.json', expected_vendor), ('bench-driver-inputs.json', driver_inputs)]:
            file = artifacts / filename
            if file.exists():
                assert json.loads(file.read_text()) == value
            else:
                save(file, value)
    result_path = artifacts / (args.label + '-' + args.project + '.json')
    assert not result_path.exists()
    rows = []
    for repeat in range(args.repetitions):
        order = limits[repeat % len(limits):] + limits[:repeat % len(limits)]
        for limit in order:
            label = f'{args.label}-{args.project}-{repeat + 1}-{limit}'
            io_file = logs / (label + '.io.json')
            assert not io_file.exists()
            command = ['node', '--import', str(artifacts / 'count-io.mjs')]
            env = {**os.environ, 'GOPURS_JOBS': '1', 'PHPURS_AUDIT_COUNTS': str(io_file)}
            if args.diagnostic:
                command += ['--import', str(Path(__file__).with_name('rewrite-probe.mjs'))]
                env['PHPURS_REWRITE_PROBE'] = str(logs / (label + '.rewrite.json'))
            command += [str(artifacts / ('diagnostic.mjs' if args.diagnostic else 'backend.mjs')),
                        '--main', 'Inter.Api.Main' if args.project == 'b8x' else 'App',
                        '--no-cache', '--profile-build', '--rewrite-limit', str(limit)]
            if args.project == 'b8x':
                command.append('--bundle')
            if args.diagnostic:
                command.append('--verbose')
            started = time.monotonic()
            with (logs / (label + '.stdout')).open('w') as out, (logs / (label + '.stderr')).open('w') as err:
                run = subprocess.run(command, cwd=cwd, env=env, stdout=out, stderr=err, timeout=600)
            process_ms = (time.monotonic() - started) * 1000
            stderr = (logs / (label + '.stderr')).read_text()
            stdout = (logs / (label + '.stdout')).read_text()
            assert not re.search(r'Failed to decode|Failed to read purmeta|DIRECTIVE PARSE ERRORS', stdout + stderr)
            profiles = re.findall(r'^\[phpurs\] build-profile: (.+)$', stderr, re.M)
            assert len(profiles) == 1, label
            profile = json.loads(profiles[0])
            save(logs / (label + '.profile.json'), profile)
            io = json.loads(io_file.read_text())
            assert io['reads']['corefn']['files'] == count
            actual = output_manifest(output)
            row = {'label': label, 'limit': limit, 'repeat': repeat + 1,
                   'diagnostic': args.diagnostic, 'exitCode': run.returncode,
                   'processMs': process_ms, 'peakRSSKiB': io['peakRSSKiB'],
                   'completedModules': profile['phases']['translate']['completed'],
                   'phaseMs': {name: metric['ms'] for name, metric in profile['phases'].items()},
                   'phpWrites': len(io['writes']['php'])}
            if run.returncode != 0:
                failure = re.search(r'Error: (.+?): Possible infinite optimization loop\.', stderr)
                assert args.allow_limit_failure and failure and profile['status'] == 'failed', label
                assert expected is not None and actual == expected
                assert io['writes']['php'] == []
                row['failedBinding'] = failure[1]
            else:
                assert profile['status'] == 'completed'
                assert all(profile['phases'][phase]['completed'] == count
                           for phase in ['corefn.decode', 'optimize', 'translate'])
                assert len(io['writes']['purmeta']) == count
                assert all(m['failed'] == m['cancelled'] == 0 for m in profile['phases'].values())
                if expected is None:
                    expected = actual
                    save(reference, expected)
                else:
                    assert actual == expected, label
                    assert io['writes']['php'] == [], label
                    assert all((output / file).stat().st_mtime_ns == 946684800000000000
                               for file in expected if file.endswith('.php'))
                php_files = [file for file in expected if file.endswith('.php')]
                row.update({'identicalFiles': len(expected), 'phpFiles': len(php_files),
                            'phpBytes': sum(expected[file]['bytes'] for file in php_files),
                            'modulePhpBytes': {name: expected[name + '/index.php']['bytes']
                                               for name in project['modules']}})
                for file in php_files:
                    os.utime(output / file, ns=(946684800000000000, 946684800000000000))
                if args.runtime:
                    php = shutil.which('php')
                    assert driver.vendor_artifacts(cwd) == expected_vendor
                    assert driver.inputs() == driver_inputs
                    driver.composer_inputs(cwd, php, env)
                    opcache = cwd / 'opcache'
                    if opcache.exists():
                        shutil.rmtree(opcache)
                    opcache.mkdir()
                    with (logs / (label + '.runtime.log')).open('w') as log, contextlib.redirect_stdout(log):
                        driver.execute(php, 'App', cwd, env, SimpleNamespace(mode='pure', test=None, expected=None))
                    row['runtime'] = json.loads((cwd / 'results.json').read_text())
                    assert row['runtime']['values_validated'] and len(row['runtime']['values']) == 14
                    assert driver.vendor_artifacts(cwd) == expected_vendor
                    assert driver.inputs() == driver_inputs
                    row['phpVersion'] = subprocess.check_output([php, '--version'], text=True).strip()
                    row['phpFlags'] = driver.PHP_FLAGS
            assert not (output / '.phpurs-cache').exists()
            for filename, record in inputs.items():
                assert digest(work / filename) == digest(artifacts / 'snapshot' / filename) == record['sha256'], filename
            rows.append(row)
            save(result_path, {'compilerSHA256': preparation['compilerSHA256'], 'project': args.project,
                               'limits': limits, 'repetitions': args.repetitions, 'inputsUnchanged': True, 'trials': rows})
            detail = row['failedBinding'] if run.returncode else f"{row['completedModules']} modules; {row['phpBytes']} PHP bytes"
            print(label, 'exit', run.returncode, f'{process_ms:.0f} ms;', detail, flush=True)


if __name__ == '__main__':
    main()
