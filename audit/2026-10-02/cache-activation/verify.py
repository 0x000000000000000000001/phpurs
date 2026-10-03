"""Exercise the packaged CLI on frozen b8x inputs, with uncached controls."""
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
spec = importlib.util.spec_from_file_location('m0', REPO / 'audit/2026-10-01/build-scenarios/measure.py')
m0 = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m0)


def files(root):
    return sorted(p.relative_to(root) for p in root.rglob('*')
                  if p.is_file() and '.phpurs-cache' not in p.relative_to(root).parts)


def copy_outputs(source, target):
    for file in files(source):
        (target / file).parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source / file, target / file)


def equal_outputs(expected, actual):
    names = files(expected)
    assert files(actual) == names
    for file in names:
        assert (expected / file).read_bytes() == (actual / file).read_bytes(), file
    return len(names)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--snapshot', type=Path, required=True)
    parser.add_argument('--artifacts', type=Path, required=True)
    parser.add_argument('--reference-output', type=Path)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    artifacts.mkdir(parents=True, exist_ok=True)
    assert not any(artifacts.iterdir())
    print('Artifacts:', artifacts, flush=True)
    snapshot = args.snapshot.resolve()
    assert not artifacts.is_relative_to(snapshot) and not snapshot.is_relative_to(artifacts)
    before = m0.manifest(snapshot)
    shutil.copytree(snapshot, artifacts / 'work', symlinks=True)
    shutil.copy2(REPO / 'bin/phpurs.js', artifacts / 'backend.mjs')
    shutil.copy2(REPO / 'audit/2026-10-01/build-scenarios/count-io.mjs', artifacts / 'count-io.mjs')
    work = artifacts / 'work/b8x'
    output = work / 'output'
    results = {'backendSHA256': m0.digest((artifacts / 'backend.mjs').read_bytes()),
               'inputFiles': len(before), 'inputManifestSHA256': m0.digest(json.dumps(before, sort_keys=True).encode()),
               'node': subprocess.check_output(['node', '--version'], text=True).strip(),
               'nodeOptions': os.environ.get('NODE_OPTIONS'), 'trials': {}}
    m0.save(artifacts / 'inputs.json', before)

    def build(label, uncached=False):
        command = ['node', '--import', str(artifacts / 'count-io.mjs'), str(artifacts / 'backend.mjs'),
                   '--main', 'Inter.Api.Main', '--bundle', '--verbose'] + (['--no-cache'] if uncached else [])
        started = time.monotonic()
        with (artifacts / f'{label}.log').open('wb') as log:
            process = subprocess.run(command, cwd=work, stdout=log, stderr=subprocess.STDOUT, timeout=300,
                                     env={**os.environ, 'GOPURS_JOBS': '1', 'PHPURS_AUDIT_COUNTS': str(artifacts / f'{label}.io.json')})
        assert process.returncode == 0, f'{label}: see log'
        text = (artifacts / f'{label}.log').read_text()
        assert not re.search(r'Failed to decode|Failed to read purmeta|DIRECTIVE PARSE ERRORS|\(failed\)|cache disabled', text), label
        generated = re.findall(r'^Generating PHP code for (.*?) \(Total AST Nodes:', text, re.MULTILINE)
        assert len(set(generated)) == len(generated)
        counts = json.loads((artifacts / f'{label}.io.json').read_text())
        assert counts['reads']['corefn']['files'] == 2684
        assert len(counts['writes']['purmeta']) == 2684
        cache = re.search(r'cache: (\d+) hits, (\d+) misses, (\d+) stores', text)
        assert bool(cache) != uncached
        result = {'generated': len(generated), 'cache': list(map(int, cache.groups())) if cache else None,
                  'phasesMs': dict((name, int(ms)) for name, ms in re.findall(r'^\[phpurs\] (.*?): (\d+) ms$', text, re.MULTILINE)),
                  'processMs': round((time.monotonic() - started) * 1000), 'peakRSSKiB': counts['peakRSSKiB'],
                  'reads': counts['reads'], 'phpWrites': len(counts['writes']['php']), 'purmetaWrites': len(counts['writes']['purmeta'])}
        results['trials'][label] = result
        m0.save(artifacts / f'{label}.modules.json', generated)
        m0.save(artifacts / 'results.json', results)
        print(label, json.dumps(result), flush=True)
        return generated, counts, result

    order, _, _ = build('control-unchanged', True)
    assert len(order) == 2684
    baseline = artifacts / 'control-output'
    copy_outputs(output, baseline)
    if args.reference_output:
        results['previousReferenceIdenticalFiles'] = equal_outputs(args.reference_output.resolve(), output)
    generated, io, result = build('fill')
    assert generated == order and result['cache'] == [0, 2684, 2684] and result['phpWrites'] == 0
    result['identicalFiles'] = equal_outputs(baseline, output)
    php_files = [file for file in files(output) if file.suffix == '.php']
    for file in php_files:
        os.utime(output / file, ns=(946684800000000000, 946684800000000000))
    shutil.rmtree(work / '.purmeta')
    generated, io, result = build('unchanged')
    assert not generated and result['cache'] == [2684, 0, 0] and result['phpWrites'] == 0
    result['identicalFiles'] = equal_outputs(baseline, output)
    assert all((output / file).stat().st_mtime_ns == 946684800000000000 for file in php_files)
    result['preservedPHPMtimes'] = len(php_files)
    results['initialCacheBytes'] = sum(p.stat().st_size for p in (output / '.phpurs-cache/v1').iterdir())

    for name in ['leaf', 'dependency']:
        copy_outputs(baseline, output)
        mutation = m0.mutate(artifacts / 'work', m0.SCENARIOS[name])
        generated, io, result = build(name)
        index = order.index(mutation['module'])
        assert generated == order[index:]
        assert result['cache'] == [index, len(order) - index, len(order) - index]
        result['mutation'] = mutation
        cached = artifacts / (name + '-cached-output')
        copy_outputs(output, cached)
        changed = [str(file) for file in php_files if (output / file).read_bytes() != (baseline / file).read_bytes()]
        assert sorted(entry['file'].removeprefix('output/') for entry in io['writes']['php']) == sorted(changed)
        result['changedPHPFiles'] = len(changed)
        for file in files(output):
            if file.suffix == '.php' or file.name == 'composer.json':
                (output / file).unlink()
        build('control-' + name, True)
        result['identicalFiles'] = equal_outputs(cached, output)
    assert m0.manifest(snapshot) == before
    m0.save(artifacts / 'results.json', results)
    print(json.dumps(results, indent=2), flush=True)


if __name__ == '__main__':
    main()
