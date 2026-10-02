"""Compare cached main changes and module deletion with fresh uncached b8x builds."""
import argparse
import importlib.util
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import time

REPO = Path(__file__).resolve().parents[3]
sys.dont_write_bytecode = True
spec = importlib.util.spec_from_file_location('activation', REPO / 'audit/2026-10-02/cache-activation/verify.py')
activation = importlib.util.module_from_spec(spec)
spec.loader.exec_module(activation)
m0 = activation.m0


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--snapshot', type=Path, required=True)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    artifacts.mkdir(parents=True, exist_ok=True)
    assert not any(artifacts.iterdir())
    snapshot = args.snapshot.resolve()
    assert not artifacts.is_relative_to(snapshot) and not snapshot.is_relative_to(artifacts)
    before = m0.manifest(snapshot)
    shutil.copytree(snapshot, artifacts / 'work', symlinks=True)
    shutil.copy2(REPO / 'bin/phpurs.js', artifacts / 'backend.mjs')
    shutil.copy2(REPO / 'audit/2026-10-01/build-scenarios/count-io.mjs', artifacts / 'count-io.mjs')
    work = artifacts / 'work/b8x'
    output = work / 'output'
    results = {
        'backendSHA256': m0.digest((artifacts / 'backend.mjs').read_bytes()),
        'inputFiles': len(before), 'inputManifestSHA256': m0.digest(json.dumps(before, sort_keys=True).encode()),
        'node': subprocess.check_output(['node', '--version'], text=True).strip(),
        'nodeOptions': os.environ.get('NODE_OPTIONS'), 'trials': {},
    }
    m0.save(artifacts / 'inputs.json', before)

    def hashes(php_only=False):
        return {str(file): m0.digest((output / file).read_bytes()) for file in activation.files(output)
                if not php_only or file.suffix == '.php'}

    def build(label, main_module, uncached=False):
        php_before = hashes(True)
        module_count = len(list(output.glob('*/corefn.json')))
        command = ['node', '--import', str(artifacts / 'count-io.mjs'), str(artifacts / 'backend.mjs'),
                   '--main', main_module, '--bundle'] + (['--no-cache'] if uncached else [])
        started = time.monotonic()
        with (artifacts / f'{label}.log').open('wb') as log:
            process = subprocess.run(command, cwd=work, stdout=log, stderr=subprocess.STDOUT, timeout=300,
                                     env={**os.environ, 'GOPURS_JOBS': '1', 'PHPURS_AUDIT_COUNTS': str(artifacts / f'{label}.io.json')})
        elapsed = round((time.monotonic() - started) * 1000)
        assert process.returncode == 0, f'{label}: see log'
        text = (artifacts / f'{label}.log').read_text()
        assert not re.search(r'Failed to decode|Failed to read purmeta|DIRECTIVE PARSE ERRORS|\(failed\)|cache disabled', text), label
        generated = re.findall(r'^Generating PHP code for (.*?) \(Total AST Nodes:', text, re.MULTILINE)
        assert len(set(generated)) == len(generated)
        io = json.loads((artifacts / f'{label}.io.json').read_text())
        assert io['reads']['corefn']['files'] == module_count
        assert len(io['writes']['purmeta']) == module_count
        cache = re.search(r'cache: (\d+) hits, (\d+) misses, (\d+) stores', text)
        assert bool(cache) != uncached
        php_after = hashes(True)
        changed = sorted(file for file in php_after if php_before.get(file) != php_after[file])
        assert sorted(entry['file'].removeprefix('output/') for entry in io['writes']['php']) == changed
        result = {
            'main': main_module, 'modules': module_count, 'generated': len(generated),
            'cache': list(map(int, cache.groups())) if cache else None,
            'phasesMs': dict((name, int(ms)) for name, ms in re.findall(r'^\[phpurs\] (.*?): (\d+) ms$', text, re.MULTILINE)),
            'processMs': elapsed, 'peakRSSKiB': io['peakRSSKiB'], 'reads': io['reads'],
            'phpWrites': len(changed),
            'removedPHP': sorted(php_before.keys() - php_after.keys()), 'purmetaWrites': len(io['writes']['purmeta']),
        }
        if len(changed) <= 20:
            result['changedPHP'] = changed
        results['trials'][label] = result
        m0.save(artifacts / f'{label}.modules.json', generated)
        m0.save(artifacts / f'{label}.outputs.json', hashes())
        m0.save(artifacts / 'results.json', results)
        print(label, result['cache'], 'generated:', len(generated), 'PHP writes:', len(changed),
              'removed:', result['removedPHP'], 'ms:', result['phasesMs']['backend total'], flush=True)
        return result

    def compare_fresh(label, main_module):
        # Same working/input/output paths; the control starts with CoreFn only.
        saved = artifacts / 'cached-output'
        output.rename(saved)
        output.mkdir()
        try:
            for file in saved.glob('*/corefn.json'):
                target = output / file.relative_to(saved)
                target.parent.mkdir(parents=True)
                shutil.copy2(file, target)
            build('control-' + label, main_module, True)
            identical = activation.equal_outputs(saved, output)
            results['trials'][label]['identicalFreshFiles'] = identical
            m0.save(artifacts / 'results.json', results)
        finally:
            shutil.rmtree(output)
            saved.rename(output)

    first = 'Inter.Api.Main'
    second = 'Inter.Cli.Ping.Main'
    first_input = output / first / 'corefn.json'
    first_bytes = first_input.read_bytes()
    assert m0.importers(m0.graph_from(output), first)['direct'] == []
    fill = build('fill', first)
    assert fill['cache'] == [0, 2684, 2684]
    compare_fresh('fill', first)
    initial = hashes()
    php_files = list(hashes(True))
    for file in php_files:
        os.utime(output / file, ns=(946684800000000000, 946684800000000000))
    unchanged = build('unchanged', first)
    assert unchanged['cache'] == [2684, 0, 0] and unchanged['generated'] == unchanged['phpWrites'] == 0
    assert hashes() == initial
    assert all((output / file).stat().st_mtime_ns == 946684800000000000 for file in php_files)
    unchanged['preservedPHPMtimes'] = len(php_files)
    results['initialCacheBytes'] = sum(p.stat().st_size for p in (output / '.phpurs-cache/v1').iterdir())

    switched = build('switch-main', second)
    assert switched['cache'] == [0, 2684, 2684]
    assert switched['removedPHP'] == [first + '/main.bundle.php', first + '/main.mod.php']
    assert switched['changedPHP'] == [second + '/main.bundle.php', second + '/main.mod.php']
    compare_fresh('switch-main', second)

    # Keep the old module's generated directory/files to exercise actual cleanup.
    first_input.unlink()
    deleted = build('delete-module', second)
    assert deleted['cache'] == [0, 2683, 2683]
    assert deleted['removedPHP'] == [first + '/index.php']
    assert deleted['changedPHP'] == [second + '/main.bundle.php']
    compare_fresh('delete-module', second)

    first_input.write_bytes(first_bytes)
    restored = build('restore-original', first)
    assert restored['cache'] == [2684, 0, 0] and restored['generated'] == 0
    assert restored['removedPHP'] == [second + '/main.bundle.php', second + '/main.mod.php']
    assert restored['changedPHP'] == [first + '/index.php', first + '/main.bundle.php', first + '/main.mod.php']
    assert hashes() == initial
    restored['identicalOriginalFiles'] = len(initial)
    assert m0.manifest(snapshot) == before
    m0.save(artifacts / 'results.json', results)
    print('Verified', artifacts, flush=True)


if __name__ == '__main__':
    main()
