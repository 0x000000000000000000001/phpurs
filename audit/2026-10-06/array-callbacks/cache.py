"""Validate each FFI contract independently across cold, hot and mixed caches."""
import argparse
import json
import os
from pathlib import Path
import re
import shutil
import subprocess

from common import REPO, WORKSPACE, BENCH, MODULE, VARIANTS, digest, php_manifest, save


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
    foreign = {}
    for module, package in [('Foldable', 'phpurs-foldable-traversable'), ('Array', 'phpurs-arrays')]:
        file = root / ('foreign/' + module + '.php')
        file.parent.mkdir(parents=True, exist_ok=True)
        source = (WORKSPACE / 'phpurs' / package / ('src/Data/' + module + '.php')).read_bytes()
        file.write_bytes(source)
        foreign[module] = (file, source, file.stat())
        corefn = output / ('Data.' + module + '/corefn.json')
        data = json.loads(corefn.read_text())
        data['modulePath'] = str(file.with_suffix('.purs'))
        save(corefn, data)
    cli = REPO / 'bin/phpurs.js'
    compiler = digest(cli)
    expected = {name: json.loads((artifacts / (name + '-php.json')).read_text()) for name in VARIANTS}
    results = []

    def build(label, variant, cached=True):
        command = ['node', str(cli), '--main', 'App', '--output', str(output), '--profile-build']
        if not cached:
            command.append('--no-cache')
        run = subprocess.run(command, cwd=BENCH, env={**os.environ, 'GOPURS_JOBS': '1'}, text=True, capture_output=True, timeout=300)
        (root / (label + '.log')).write_text(run.stdout + run.stderr)
        assert run.returncode == 0, str(root / (label + '.log'))
        profile, = [json.loads(line.split(': ', 1)[1]) for line in run.stderr.splitlines() if line.startswith('[phpurs] build-profile: ')]
        assert php_manifest(output) == expected[variant], (label, 'PHP differs from frozen configuration')
        code = f"require {str(output / MODULE)!r}; echo $GLOBALS['Test_ArrayOps_act'](), PHP_EOL;"
        executed = subprocess.run(['php', '-d', 'opcache.enable_cli=0', '-r', code], text=True, capture_output=True)
        assert executed.returncode == 0 and executed.stdout == '202950\n' and not executed.stderr
        counts = re.search(r'\[phpurs\] cache: (\d+) hits, (\d+) misses, (\d+) stores', run.stderr)
        row = {'label': label, 'variant': variant, 'translated': profile['phases']['translate']['completed'], 'output': 202950}
        if cached:
            assert counts
            row.update(zip(['hits', 'misses', 'stores'], map(int, counts.groups())))
        else:
            assert counts is None
        results.append(row)
        print(json.dumps(row), flush=True)
        return row

    def change(module, changed):
        file, source, stat = foreign[module]
        file.write_bytes(source + (b'\n' if changed else b''))
        os.utime(file, ns=(stat.st_atime_ns, stat.st_mtime_ns))

    assert build('cold', 'integrated')['hits'] == 0
    assert build('hot', 'integrated')['hits'] == 306
    consumer = output / 'Test.ArrayOps/corefn.json'
    data = json.loads(consumer.read_text())
    data['comments'].append({'LineComment': ' traversal contract mixed-cache probe'})
    save(consumer, data)
    mixed = build('mixed', 'integrated')
    assert mixed['hits'] > 0 and mixed['translated'] > 0
    change('Foldable', True)
    assert build('fold-contract-changed', 'filter')['translated'] > 0
    change('Array', True)
    assert build('both-contracts-changed', 'baseline')['translated'] > 0
    change('Foldable', False)
    assert build('filter-contract-changed', 'fold')['translated'] > 0
    change('Array', False)
    assert build('restored', 'integrated')['hits'] == 306
    assert build('uncached', 'integrated', cached=False)['translated'] == 306
    assert digest(cli) == compiler
    save(artifacts / 'cache-validation.json', {'standaloneSHA256': compiler, 'runs': results, 'foreignMtimePreserved': True})


if __name__ == '__main__':
    main()
