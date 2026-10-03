"""Compare frozen before/after CLIs on b8x and validate detailed phase reports."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import time

REPO = Path(__file__).resolve().parents[3]


def digest(file):
    return hashlib.sha256(file.read_bytes()).hexdigest()


def save(file, value):
    file.write_text(json.dumps(value, indent=2) + '\n')


def inputs(directory):
    return {str(p.relative_to(directory)): digest(p) for p in sorted(directory.rglob('*')) if p.is_file()}


def outputs(directory):
    return {str(p.relative_to(directory)): digest(p) for p in sorted(directory.rglob('*'))
            if p.is_file() and (p.suffix == '.php' or p.name in {'corefn.json', 'composer.json', '.phpurs-outputs.json'})
            and '.phpurs-cache' not in p.parts}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--snapshot', type=Path, required=True)
    parser.add_argument('--before', type=Path, required=True)
    parser.add_argument('--after', type=Path, default=REPO / 'bin/phpurs.js')
    parser.add_argument('--artifacts', type=Path, required=True)
    parser.add_argument('--resume', action='store_true', help='resume completed checkpoints with the same inputs/tools')
    args = parser.parse_args()
    snapshot, artifacts = args.snapshot.resolve(), args.artifacts.resolve()
    artifacts.mkdir(parents=True, exist_ok=True)
    assert not artifacts.is_relative_to(snapshot) and not snapshot.is_relative_to(artifacts)
    original = inputs(snapshot)
    if not args.resume:
        assert not any(artifacts.iterdir())
        shutil.copytree(snapshot, artifacts / 'work', symlinks=True)
        for label in ['before', 'after']:
            shutil.copy2(getattr(args, label), artifacts / (label + '.mjs'))
        shutil.copy2(REPO / 'audit/2026-10-01/build-scenarios/count-io.mjs', artifacts / 'count-io.mjs')
    work, output = artifacts / 'work/b8x', artifacts / 'work/b8x/output'
    count = len(list(output.glob('*/corefn.json')))
    results = {'inputFiles': len(original),
               'inputManifestSHA256': hashlib.sha256(json.dumps(original, sort_keys=True).encode()).hexdigest(),
               'beforeSHA256': digest(artifacts / 'before.mjs'), 'afterSHA256': digest(artifacts / 'after.mjs'),
               'node': subprocess.check_output(['node', '--version'], text=True).strip(), 'trials': {}}
    expected = None
    php_files = []
    if args.resume:
        previous = json.loads((artifacts / 'results.json').read_text())
        for key, value in results.items():
            if key != 'trials':
                assert previous[key] == value, key
        for label in ['before', 'after']:
            assert digest(getattr(args, label)) == results[label + 'SHA256']
        results = previous
        expected = json.loads((artifacts / 'expected-outputs.json').read_text())
        assert outputs(output) == expected
        php_files = [p for p in expected if p.endswith('.php')]
        # A killed seed may have published only a prefix of persistent states.
        if 'cache-seed' not in results['trials']:
            shutil.rmtree(output / '.phpurs-cache', ignore_errors=True)
    for label, executable, flags in [
        ('before', 'before', ['--no-cache']),
        ('full-profile', 'after', ['--no-cache', '--profile-build']),
        ('cache-seed', 'after', []),
        ('cache-profile', 'after', ['--profile-build']),
    ]:
        if label in results['trials']:
            continue
        command = ['node', '--import', str(artifacts / 'count-io.mjs'), str(artifacts / (executable + '.mjs')),
                   '--main', 'Inter.Api.Main', '--bundle', *flags]
        started = time.monotonic()
        with (artifacts / (label + '.stdout')).open('w') as out, (artifacts / (label + '.stderr')).open('w') as err:
            result = subprocess.run(command, cwd=work, stdout=out, stderr=err, timeout=600,
                                    env={**os.environ, 'GOPURS_JOBS': '1',
                                         'PHPURS_AUDIT_COUNTS': str(artifacts / (label + '.io.json'))})
        assert result.returncode == 0, label
        stdout = (artifacts / (label + '.stdout')).read_text()
        stderr = (artifacts / (label + '.stderr')).read_text()
        assert not re.search(r'Failed to decode|Failed to read purmeta|DIRECTIVE PARSE ERRORS|\(failed\)', stdout + stderr)
        if executable == 'after':
            assert stdout == '', label
        actual = outputs(output)
        io = json.loads((artifacts / (label + '.io.json')).read_text())
        assert io['reads']['corefn']['files'] == count
        assert len(io['writes']['purmeta']) == count
        if expected is None:
            expected = actual
            save(artifacts / 'expected-outputs.json', expected)
            php_files = [p for p in actual if p.endswith('.php')]
            for p in php_files:
                os.utime(output / p, ns=(946684800000000000, 946684800000000000))
        else:
            assert actual == expected, label
            assert not io['writes']['php'], label
            assert all((output / p).stat().st_mtime_ns == 946684800000000000 for p in php_files)
        profiles = re.findall(r'^\[phpurs\] build-profile: (.+)$', stderr, re.M)
        assert len(profiles) == int('--profile-build' in flags)
        profile = json.loads(profiles[0]) if profiles else None
        if profile:
            assert profile['version'] == 1 and profile['status'] == 'completed'
            phases = profile['phases']
            assert phases['corefn.decode']['completed'] == count
            assert phases['php.write']['completed'] == len(php_files)
            assert phases['diagnostics']['calls'] == 0
            assert all(m['failed'] == m['cancelled'] == 0 for m in phases.values())
            cached = label == 'cache-profile'
            assert phases['optimize']['completed'] == phases['translate']['completed'] == (0 if cached else count)
            assert phases['print']['completed'] == 2 + (0 if cached else count)
            assert len(profile['modules']) == count
            assert all(m['source'] == ('cache' if cached else 'optimized') for m in profile['modules'])
            save(artifacts / (label + '.profile.json'), profile)
        cache = re.search(r'cache: (\d+) hits, (\d+) misses, (\d+) stores', stderr)
        if label == 'cache-profile':
            assert cache and list(map(int, cache.groups())) == [count, 0, 0]
        row = {'processMs': (time.monotonic() - started) * 1000,
               'phaseTotalsMs': dict((name, int(ms)) for name, ms in re.findall(r'^\[phpurs\] (.*?): (\d+) ms$', stderr, re.M)),
               'profile': None if profile is None else {k: v for k, v in profile.items() if k != 'modules'},
               'peakRSSKiB': io['peakRSSKiB'], 'identicalFiles': len(actual), 'phpFiles': len(php_files),
               'phpWrites': len(io['writes']['php']), 'cache': list(map(int, cache.groups())) if cache else None}
        if profile:
            optimized = [m for m in profile['modules'] if m['source'] == 'optimized']
            row['slowestOptimization'] = sorted(optimized, key=lambda m: m['phases']['optimize']['ms'], reverse=True)[:10]
        results['trials'][label] = row
        save(artifacts / 'results.json', results)
        print(label, len(actual), 'identical files;', len(io['writes']['php']), 'PHP writes;', round(row['processMs']), 'ms', flush=True)
    assert inputs(snapshot) == original
    for filename, fingerprint in original.items():
        assert digest(artifacts / 'work' / filename) == fingerprint, filename
    print('Input corpus unchanged; artifacts:', artifacts, flush=True)


if __name__ == '__main__':
    main()
