"""Compare AST diagnostics and effective rewrite limits on the frozen b8x corpus."""
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
            if p.is_file() and (p.suffix == '.php' or p.name in {'corefn.json', 'composer.json', '.phpurs-outputs.json'})}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--snapshot', type=Path, required=True)
    parser.add_argument('--before', type=Path, required=True)
    parser.add_argument('--after', type=Path, default=REPO / 'bin/phpurs.js')
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    snapshot, artifacts = args.snapshot.resolve(), args.artifacts.resolve()
    assert not artifacts.is_relative_to(snapshot) and not snapshot.is_relative_to(artifacts)
    artifacts.mkdir(parents=True, exist_ok=True)
    assert not any(artifacts.iterdir())
    original = inputs(snapshot)
    shutil.copytree(snapshot, artifacts / 'work', symlinks=True)
    for label in ['before', 'after']:
        shutil.copy2(getattr(args, label), artifacts / (label + '.mjs'))
    shutil.copy2(REPO / 'audit/2026-10-01/build-scenarios/count-io.mjs', artifacts / 'count-io.mjs')
    work, output = artifacts / 'work/b8x', artifacts / 'work/b8x/output'
    count = len(list(output.glob('*/corefn.json')))
    results = {'inputFiles': len(original), 'modules': count,
               'inputManifestSHA256': hashlib.sha256(json.dumps(original, sort_keys=True).encode()).hexdigest(),
               'beforeSHA256': digest(artifacts / 'before.mjs'), 'afterSHA256': digest(artifacts / 'after.mjs'),
               'node': subprocess.check_output(['node', '--version'], text=True).strip(), 'trials': {}}
    expected = None
    counts_by_trial = {}
    for label, executable, flags in [
        ('before-default', 'before', []),
        ('after-default', 'after', []),
        ('after-10001', 'after', ['--rewrite-limit=10001']),
    ]:
        command = ['node', '--import', str(artifacts / 'count-io.mjs'), str(artifacts / (executable + '.mjs')),
                   '--main', 'Inter.Api.Main', '--bundle', '--no-cache', '--verbose', '--profile-build', *flags]
        started = time.monotonic()
        with (artifacts / (label + '.stdout')).open('w') as out, (artifacts / (label + '.stderr')).open('w') as err:
            result = subprocess.run(command, cwd=work, stdout=out, stderr=err, timeout=600,
                                    env={**os.environ, 'GOPURS_JOBS': '1',
                                         'PHPURS_AUDIT_COUNTS': str(artifacts / (label + '.io.json'))})
        process_ms = (time.monotonic() - started) * 1000
        assert result.returncode == 0, label
        stdout = (artifacts / (label + '.stdout')).read_text()
        stderr = (artifacts / (label + '.stderr')).read_text()
        assert not re.search(r'Failed to decode|Failed to read purmeta|DIRECTIVE PARSE ERRORS|\(failed\)', stdout + stderr)
        counts = {name: int(nodes) for name, nodes in re.findall(r'^Generating PHP code for (\S+) \(Total AST Nodes: (\d+)\)$', stdout, re.M)}
        assert len(counts) == count
        counts_by_trial[label] = counts
        save(artifacts / (label + '.counts.json'), counts)
        actual = outputs(output)
        php_files = [p for p in actual if p.endswith('.php')]
        io = json.loads((artifacts / (label + '.io.json')).read_text())
        assert io['reads']['corefn']['files'] == len(io['writes']['purmeta']) == count
        if expected is None:
            expected = actual
            save(artifacts / 'expected-outputs.json', expected)
            for p in php_files:
                os.utime(output / p, ns=(946684800000000000, 946684800000000000))
        else:
            assert actual == expected, label
            assert not io['writes']['php'], label
            assert all((output / p).stat().st_mtime_ns == 946684800000000000 for p in php_files)
        profiles = re.findall(r'^\[phpurs\] build-profile: (.+)$', stderr, re.M)
        assert len(profiles) == 1
        profile = json.loads(profiles[0])
        assert profile['version'] == 1 and profile['status'] == 'completed'
        for phase in ['corefn.decode', 'optimize', 'translate', 'diagnostics']:
            assert profile['phases'][phase]['completed'] == count
        assert profile['phases']['print']['completed'] == count + 2
        assert profile['phases']['php.write']['completed'] == len(php_files)
        assert all(m['failed'] == m['cancelled'] == 0 for m in profile['phases'].values())
        save(artifacts / (label + '.profile.json'), profile)
        row = {'processMs': process_ms, 'flags': flags, 'peakRSSKiB': io['peakRSSKiB'],
               'identicalFiles': len(actual), 'phpFiles': len(php_files), 'phpWrites': len(io['writes']['php']),
               'astNodes': sum(counts.values()), 'profile': {k: v for k, v in profile.items() if k != 'modules'},
               'largestModules': sorted(counts.items(), key=lambda item: item[1], reverse=True)[:10]}
        results['trials'][label] = row
        save(artifacts / 'results.json', results)
        print(label, len(actual), 'identical files;', row['astNodes'], 'nodes;', round(process_ms), 'ms', flush=True)
    old, new = counts_by_trial['before-default'], counts_by_trial['after-default']
    assert old.keys() == new.keys()
    assert all(new[name] >= old[name] for name in old)
    assert sum(new.values()) > sum(old.values())
    assert new == counts_by_trial['after-10001']
    assert not (output / '.phpurs-cache').exists()
    assert inputs(snapshot) == original
    for filename, fingerprint in original.items():
        assert digest(artifacts / 'work' / filename) == fingerprint, filename
    results['increasedModules'] = sum(new[name] > old[name] for name in old)
    results['inputsUnchanged'] = True
    save(artifacts / 'results.json', results)
    print('Outputs, mtimes and input corpus verified; artifacts:', artifacts, flush=True)


if __name__ == '__main__':
    main()
