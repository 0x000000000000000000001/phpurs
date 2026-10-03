"""Measure the existing PBO RAM cache with independent I/O and PHP output controls."""
import argparse
import copy
import importlib.util
import json
import os
from pathlib import Path
import re
import shutil
import statistics
import subprocess
import sys
import time

REPO = Path(__file__).resolve().parents[3]
sys.dont_write_bytecode = True
spec = importlib.util.spec_from_file_location('activation', REPO / 'audit/2026-10-02/cache-activation/verify.py')
activation = importlib.util.module_from_spec(spec)
spec.loader.exec_module(activation)
m0 = activation.m0


def logical(profile):
    result = copy.deepcopy(profile)
    del result['memory']
    for group in ['reads', 'writes']:
        result[group] = {key: value for key, value in result[group].items() if not key.endswith('Ms')}
    return result


def summarize(results):
    trials = results['trials']
    samples = [trials[f'profile-{i + 1}'] for i in range(results['repetitions'])]
    control, cached, hot = [trials[name] for name in ['unprofiled', 'cache-fill', 'cache-hit']]
    module_count = control['generated']
    for sample in samples:
        assert sample['generated'] == module_count
        assert sample['purmetaIO'] == control['purmetaIO']
        assert logical(sample['profile']) == logical(samples[0]['profile'])
    assert cached['cache'] == [0, module_count, module_count]
    assert logical(cached['profile']) == logical(samples[0]['profile'])
    assert hot['cache'] == [module_count, 0, 0] and hot['generated'] == 0
    assert hot['profile']['reads']['requests'] == 0
    assert hot['purmetaIO']['writes']['files'] == module_count
    results['restoredPurmetaByteDelta'] = hot['purmetaIO']['writes']['bytes'] - control['purmetaIO']['writes']['bytes']
    results['medians'] = {
        'backendMs': statistics.median(sample['phasesMs']['backend total'] for sample in samples),
        'processPeakRSSKiB': statistics.median(sample['processPeakRSSKiB'] for sample in samples),
        **{field: statistics.median(sample['profile'][group][field] for sample in samples)
           for group, field in [('writes', 'serializeMs'), ('reads', 'deserializeMs')]},
        'readIOMs': statistics.median(sample['profile']['reads']['ioMs'] for sample in samples),
        'writeIOMs': statistics.median(sample['profile']['writes']['ioMs'] for sample in samples),
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--snapshot', type=Path, required=True)
    parser.add_argument('--artifacts', type=Path, required=True)
    parser.add_argument('--reference-output', type=Path)
    parser.add_argument('--repetitions', type=int, default=3)
    args = parser.parse_args()
    assert args.repetitions > 0
    snapshot = args.snapshot.resolve()
    artifacts = args.artifacts.resolve()
    artifacts.mkdir(parents=True, exist_ok=True)
    assert not any(artifacts.iterdir())
    assert not artifacts.is_relative_to(snapshot) and not snapshot.is_relative_to(artifacts)
    before = m0.manifest(snapshot)
    shutil.copytree(snapshot, artifacts / 'work', symlinks=True)
    shutil.copy2(REPO / 'bin/phpurs.js', artifacts / 'backend.mjs')
    shutil.copy2(REPO / 'audit/2026-10-01/build-scenarios/count-io.mjs', artifacts / 'count-io.mjs')
    shutil.copy2(REPO / 'tests/codegen/fixtures/purmeta-profile-probe.mjs', artifacts / 'purmeta-probe.mjs')
    work = artifacts / 'work/b8x'
    output = work / 'output'
    module_count = len(list(output.glob('*/corefn.json')))
    results = {
        'backendSHA256': m0.digest((artifacts / 'backend.mjs').read_bytes()),
        'inputFiles': len(before), 'inputManifestSHA256': m0.digest(json.dumps(before, sort_keys=True).encode()),
        'node': subprocess.check_output(['node', '--version'], text=True).strip(),
        'nodeOptions': os.environ.get('NODE_OPTIONS'), 'repetitions': args.repetitions, 'trials': {},
    }
    m0.save(artifacts / 'inputs.json', before)
    baseline = None
    php_files = []

    def build(label, profile=True, cached=False):
        command = ['node', '--import', str(artifacts / 'count-io.mjs'), '--import', str(artifacts / 'purmeta-probe.mjs'),
                   str(artifacts / 'backend.mjs'), '--main', 'Inter.Api.Main', '--bundle', '--verbose']
        if not cached:
            command.append('--no-cache')
        if profile:
            command.append('--profile-purmeta')
        started = time.monotonic()
        with (artifacts / f'{label}.log').open('wb') as log:
            process = subprocess.run(command, cwd=work, stdout=log, stderr=subprocess.STDOUT, timeout=300,
                                     env={**os.environ, 'GOPURS_JOBS': '1',
                                          'PHPURS_AUDIT_COUNTS': str(artifacts / f'{label}.io.json'),
                                          'PHPURS_PURMETA_PROBE': str(artifacts / f'{label}.purmeta-io.json')})
        elapsed = round((time.monotonic() - started) * 1000)
        assert process.returncode == 0, f'{label}: see log'
        text = (artifacts / f'{label}.log').read_text()
        assert not re.search(r'Failed to decode|Failed to read purmeta|DIRECTIVE PARSE ERRORS|\(failed\)|cache disabled', text), label
        generated = re.findall(r'^Generating PHP code for (.*?) \(Total AST Nodes:', text, re.MULTILINE)
        assert len(set(generated)) == len(generated)
        cache = re.search(r'cache: (\d+) hits, (\d+) misses, (\d+) stores', text)
        assert bool(cache) == cached
        io = json.loads((artifacts / f'{label}.io.json').read_text())
        probe = json.loads((artifacts / f'{label}.purmeta-io.json').read_text())
        assert io['reads']['corefn']['files'] == module_count
        assert len(io['writes']['purmeta']) == probe['writes']['files'] == module_count
        assert sum(entry['bytes'] for entry in io['writes']['purmeta']) == probe['writes']['bytes']
        profiles = re.findall(r'^\[phpurs\] purmeta: (.+)$', text, re.MULTILINE)
        assert len(profiles) == int(profile)
        stats = json.loads(profiles[0]) if profiles else None
        if stats:
            reads, writes = stats['reads'], stats['writes']
            assert {'attempts': reads['ioAttempts'], 'files': reads['files'], 'bytes': reads['bytes']} == probe['reads']
            assert {'attempts': writes['attempts'], 'files': writes['files'], 'bytes': writes['bytes']} == probe['writes']
            assert reads['requests'] == reads['blocked'] + reads['ramHits'] + reads['ramMisses']
            assert reads['ramMisses'] == reads['diskHits'] + reads['diskMissing'] + reads['errors']
            assert reads['errors'] == reads['diskMissing'] == writes['errors'] == 0
            assert reads['deserializations'] == reads['files']
            assert writes['serializations'] == writes['files']
            assert stats['ram']['trimCalls'] == module_count and stats['ram']['clearCalls'] == 0
            assert stats['ram']['boundaryPeakSerializedBytes'] <= stats['policy']['maxSerializedBytes']
            assert stats['memory']['processPeakRSSKiB'] <= probe['processPeakRSSKiB']
        result = {
            'generated': len(generated), 'cache': list(map(int, cache.groups())) if cache else None,
            'phasesMs': dict((name, int(ms)) for name, ms in re.findall(r'^\[phpurs\] (.*?): (\d+) ms$', text, re.MULTILINE)),
            'processMs': elapsed, 'processPeakRSSKiB': probe['processPeakRSSKiB'],
            'purmetaIO': {key: probe[key] for key in ['reads', 'writes']}, 'profile': stats,
            'phpWrites': len(io['writes']['php']),
        }
        if baseline:
            result['identicalFiles'] = activation.equal_outputs(baseline, output)
            assert result['phpWrites'] == 0
            assert all((output / file).stat().st_mtime_ns == 946684800000000000 for file in php_files)
            result['preservedPHPMtimes'] = len(php_files)
        results['trials'][label] = result
        m0.save(artifacts / f'{label}.modules.json', generated)
        m0.save(artifacts / 'results.json', results)
        print(label, 'generated:', len(generated), 'purmeta reads/writes:', probe['reads']['files'], probe['writes']['files'],
              'backend ms:', result['phasesMs']['backend total'], 'RSS KiB:', result['processPeakRSSKiB'], flush=True)
        return result

    fill = build('prepare-output', False)
    assert fill['generated'] == module_count
    if args.reference_output:
        baseline = args.reference_output.resolve()
        results['previousReferenceIdenticalFiles'] = activation.equal_outputs(baseline, output)
    else:
        baseline = artifacts / 'control-output'
        activation.copy_outputs(output, baseline)
    php_files = [file for file in activation.files(output) if file.suffix == '.php']
    for file in php_files:
        os.utime(output / file, ns=(946684800000000000, 946684800000000000))
    control = build('unprofiled', False)
    samples = [build(f'profile-{i + 1}') for i in range(args.repetitions)]
    for sample in samples:
        assert sample['generated'] == module_count
        assert sample['purmetaIO'] == control['purmetaIO']
        assert logical(sample['profile']) == logical(samples[0]['profile'])
    cached = build('cache-fill', True, True)
    assert cached['cache'] == [0, module_count, module_count]
    assert logical(cached['profile']) == logical(samples[0]['profile'])
    # A restored JS value may have a different V8 binary encoding. Retain a
    # fresh witness to check decoded graph equality, not just payload sizes.
    shutil.copytree(work / '.purmeta', artifacts / 'fresh-purmeta')
    hot = build('cache-hit', True, True)
    assert hot['cache'] == [module_count, 0, 0] and hot['generated'] == 0
    assert hot['profile']['reads']['requests'] == 0
    results['purmetaComparison'] = json.loads(subprocess.check_output([
        'node', str(Path(__file__).with_name('compare-purmeta.mjs')), str(artifacts / 'fresh-purmeta'), str(work / '.purmeta')], text=True))
    summarize(results)
    assert m0.manifest(snapshot) == before
    m0.save(artifacts / 'results.json', results)
    print('Verified', artifacts, json.dumps(results['medians']), flush=True)


if __name__ == '__main__':
    main()
