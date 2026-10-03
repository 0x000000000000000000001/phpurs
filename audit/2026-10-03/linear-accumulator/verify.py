"""Compare frozen backends in ABBA order with pre-existing identical b8x outputs."""
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


def save(file, data):
    file.write_text(json.dumps(data, indent=2) + '\n')


def files(directory, selected=lambda p: True):
    return {str(p.relative_to(directory)): digest(p) for p in sorted(directory.rglob('*'))
            if p.is_file() and selected(p)}


def is_output(file):
    return '.phpurs-cache' not in file.parts and (file.suffix == '.php' or file.name in
           {'corefn.json', 'composer.json', '.phpurs-outputs.json'})


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--snapshot', type=Path, required=True)
    parser.add_argument('--reference-output', type=Path, required=True)
    parser.add_argument('--before', type=Path, required=True)
    parser.add_argument('--after', type=Path, default=REPO / 'bin/phpurs.js')
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts, snapshot = args.artifacts.resolve(), args.snapshot.resolve()
    reference = args.reference_output.resolve()
    assert not artifacts.is_relative_to(snapshot) and not snapshot.is_relative_to(artifacts)
    assert not artifacts.is_relative_to(reference) and not reference.is_relative_to(artifacts)
    artifacts.mkdir(parents=True, exist_ok=True)
    assert not any(artifacts.iterdir())
    original = files(snapshot)
    expected = files(reference, is_output)
    shutil.copytree(snapshot, artifacts / 'work', symlinks=True)
    output, work = artifacts / 'work/b8x/output', artifacts / 'work/b8x'
    for name in expected:
        target = output / name
        if name.endswith('corefn.json'):
            assert digest(target) == expected[name]
        else:
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(reference / name, target)
        if name.endswith('.php'):
            os.utime(target, ns=(946684800000000000, 946684800000000000))
    assert files(output, is_output) == expected
    save(artifacts / 'expected-outputs.json', expected)
    for label in ['before', 'after']:
        shutil.copy2(getattr(args, label), artifacts / (label + '.mjs'))
    shutil.copy2(REPO / 'audit/2026-10-01/build-scenarios/count-io.mjs', artifacts / 'count-io.mjs')
    count = len(list(output.glob('*/corefn.json')))
    results = {'inputFiles': len(original), 'modules': count, 'jobs': 1,
               'inputManifestSHA256': hashlib.sha256(json.dumps(original, sort_keys=True).encode()).hexdigest(),
               'beforeSHA256': digest(artifacts / 'before.mjs'), 'afterSHA256': digest(artifacts / 'after.mjs'),
               'node': subprocess.check_output(['node', '--version'], text=True).strip(), 'trials': {}}
    for index, variant in enumerate(['before', 'after', 'after', 'before'], 1):
        label = f'{index}-{variant}'
        command = ['node', '--import', str(artifacts / 'count-io.mjs'), str(artifacts / (variant + '.mjs')),
                   '--main', 'Inter.Api.Main', '--bundle', '--no-cache', '--profile-build']
        started = time.monotonic()
        with (artifacts / (label + '.stdout')).open('w') as out, (artifacts / (label + '.stderr')).open('w') as err:
            run = subprocess.run(command, cwd=work, stdout=out, stderr=err, timeout=600,
                                 env={**os.environ, 'GOPURS_JOBS': '1',
                                      'PHPURS_AUDIT_COUNTS': str(artifacts / (label + '.io.json'))})
        process_ms = (time.monotonic() - started) * 1000
        assert run.returncode == 0, label
        assert (artifacts / (label + '.stdout')).read_text() == ''
        stderr = (artifacts / (label + '.stderr')).read_text()
        assert not re.search(r'Failed to decode|Failed to read purmeta|DIRECTIVE PARSE ERRORS|\(failed\)', stderr)
        profiles = re.findall(r'^\[phpurs\] build-profile: (.+)$', stderr, re.M)
        assert len(profiles) == 1
        profile = json.loads(profiles[0])
        assert profile['version'] == 1 and profile['status'] == 'completed'
        for phase in ['corefn.decode', 'optimize', 'translate']:
            assert profile['phases'][phase]['completed'] == count
        assert profile['phases']['diagnostics']['calls'] == 0
        assert all(m['failed'] == m['cancelled'] == 0 for m in profile['phases'].values())
        save(artifacts / (label + '.profile.json'), profile)
        io = json.loads((artifacts / (label + '.io.json')).read_text())
        assert io['reads']['corefn']['files'] == len(io['writes']['purmeta']) == count
        assert io['writes']['php'] == []
        assert files(output, is_output) == expected
        php = [name for name in expected if name.endswith('.php')]
        assert all((output / name).stat().st_mtime_ns == 946684800000000000 for name in php)
        row = {'processMs': process_ms, 'peakRSSKiB': io['peakRSSKiB'], 'identicalFiles': len(expected),
               'phpFiles': len(php), 'phpWrites': 0, 'totalMs': profile['totalMs'],
               'phaseMs': {name: metric['ms'] for name, metric in profile['phases'].items()},
               'targetModule': next(m for m in profile['modules'] if m['name'] == 'Data.CodePoint.Unicode.Internal')}
        results['trials'][label] = row
        save(artifacts / 'results.json', results)
        print(label, len(expected), 'identical files;', round(row['phaseMs']['translate']), 'ms translate;',
              round(process_ms), 'ms process', flush=True)
    assert files(snapshot) == original
    for name, fingerprint in original.items():
        assert digest(artifacts / 'work' / name) == fingerprint
    assert not (output / '.phpurs-cache').exists()
    results['inputsUnchanged'] = True
    save(artifacts / 'results.json', results)
    print('All four builds preserve inputs, PHP bytes and mtimes.', flush=True)


if __name__ == '__main__':
    main()
