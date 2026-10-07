"""Validate source-contract invalidation and restoration across real CLI cache hits."""
import argparse
import json
import os
from pathlib import Path
import re
import shutil
import subprocess

from prepare import REPO, WORKSPACE, BENCH, MODULE, digest, php_manifest, save


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    artifacts = parser.parse_args().artifacts.resolve()
    root = artifacts / 'cache-validation'
    assert not root.exists()
    output = root / 'output'
    for file in (artifacts / 'baseline/output').glob('*/corefn.json'):
        target = output / file.relative_to(artifacts / 'baseline/output')
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(file, target)
    foreign = root / 'foreign/Semiring.php'
    foreign.parent.mkdir(parents=True)
    source = (WORKSPACE / 'phpurs/phpurs-prelude/src/Data/Semiring.php').read_bytes()
    foreign.write_bytes(source)
    corefn = output / 'Data.Semiring/corefn.json'
    module = json.loads(corefn.read_text())
    module['modulePath'] = str(foreign.with_suffix('.purs'))
    save(corefn, module)
    cli = REPO / 'bin/phpurs.js'
    compiler = digest(cli)
    expected = {name: json.loads((artifacts / (name + '-php.json')).read_text()) for name in ['baseline', 'integrated']}
    results = []

    def build(label, variant, cached=True):
        command = ['node', str(cli), '--main', 'App', '--output', str(output), '--profile-build']
        if not cached:
            command.append('--no-cache')
        run = subprocess.run(command, cwd=BENCH, env={**os.environ, 'GOPURS_JOBS': '1'}, text=True, capture_output=True, timeout=300)
        (root / (label + '.log')).write_text(run.stdout + run.stderr)
        assert run.returncode == 0, str(root / (label + '.log'))
        profile, = [json.loads(line.split(': ', 1)[1]) for line in run.stderr.splitlines() if line.startswith('[phpurs] build-profile: ')]
        assert profile['status'] == 'completed'
        assert php_manifest(output) == expected[variant], (label, 'PHP differs from regenerated control')
        code = f"require {str(output / MODULE)!r}; echo $GLOBALS['Test_ListOps_act'](), PHP_EOL;"
        executed = subprocess.run(['php', '-d', 'opcache.enable_cli=0', '-r', code], text=True, capture_output=True)
        assert executed.returncode == 0 and executed.stdout == '202950\n' and not executed.stderr, (executed.stdout, executed.stderr)
        counts = re.search(r'\[phpurs\] cache: (\d+) hits, (\d+) misses, (\d+) stores', run.stderr)
        row = {'label': label, 'variant': variant, 'translated': profile['phases']['translate']['completed'], 'phpFiles': len(expected[variant]), 'output': 202950}
        if cached:
            assert counts
            row.update(zip(['hits', 'misses', 'stores'], map(int, counts.groups())))
        else:
            assert counts is None
        results.append(row)
        print(json.dumps(row), flush=True)
        return row

    cold = build('cold', 'integrated')
    assert cold['hits'] == 0 and cold['misses'] == cold['translated'] == 306
    hot = build('hot', 'integrated')
    assert hot['hits'] == 306 and hot['misses'] == hot['translated'] == 0
    consumer = output / 'Test.ListOps/corefn.json'
    changed = json.loads(consumer.read_text())
    changed['comments'].append({'LineComment': ' callback contract mixed-cache probe'})
    save(consumer, changed)
    mixed = build('mixed', 'integrated')
    assert mixed['hits'] > 0 and mixed['translated'] > 0
    # Semiring's module is restored, but a fresh consumer must still obtain the
    # proof from this invocation's captured FFI source and signature.
    stat = foreign.stat()
    foreign.write_bytes(source + b'\n')
    os.utime(foreign, ns=(stat.st_atime_ns, stat.st_mtime_ns))
    changed_ffi = build('ffi-changed-same-mtime', 'baseline')
    assert changed_ffi['misses'] > 0 and changed_ffi['translated'] > 0
    foreign.write_bytes(source)
    os.utime(foreign, ns=(stat.st_atime_ns, stat.st_mtime_ns))
    restored = build('ffi-restored', 'integrated')
    assert restored['hits'] == 306 and restored['translated'] == 0
    uncached = build('uncached', 'integrated', cached=False)
    assert uncached['translated'] == 306
    assert digest(cli) == compiler
    save(artifacts / 'cache-validation.json', {'standaloneSHA256': compiler, 'runs': results, 'foreignMtimePreserved': True})


if __name__ == '__main__':
    main()
