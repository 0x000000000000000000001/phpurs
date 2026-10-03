"""Validate or compare opt-in PBO budgets against identical PHP on frozen b8x inputs."""
import argparse
import importlib.util
import json
import os
from pathlib import Path
import platform
import re
import shutil
import statistics
import subprocess
import sys
import time

REPO = Path(__file__).resolve().parents[3]
sys.dont_write_bytecode = True
spec = importlib.util.spec_from_file_location('profile', REPO / 'audit/2026-10-02/purmeta-profile/measure.py')
profile = importlib.util.module_from_spec(spec)
spec.loader.exec_module(profile)
activation, m0 = profile.activation, profile.m0


def summarize_comparison(results):
    comparison = results['comparison']
    summaries = {}
    reference = results['trials']['default']['profile']['reads']
    for budget in comparison['budgetsMiB']:
        samples = [results['trials'][entry['label']] for entry in comparison['schedule'] if entry['budgetMiB'] == budget]
        assert len(samples) == comparison['repetitions']
        first = samples[0]
        logical = profile.logical(first['profile'])
        for sample in samples:
            assert profile.logical(sample['profile']) == logical
            assert sample['profile']['reads']['requests'] == reference['requests']
            assert sample['profile']['reads']['blocked'] == reference['blocked']
        measurements = {
            'backendMs': [sample['phasesMs']['backend total'] for sample in samples],
            'optimizeEmitMs': [sample['phasesMs']['optimize + emit'] for sample in samples],
            'processPeakRSSKiB': [sample['processPeakRSSKiB'] for sample in samples],
            'deserializeMs': [sample['profile']['reads']['deserializeMs'] for sample in samples],
            'serializeMs': [sample['profile']['writes']['serializeMs'] for sample in samples],
            'readIOMs': [sample['profile']['reads']['ioMs'] for sample in samples],
            'writeIOMs': [sample['profile']['writes']['ioMs'] for sample in samples],
        }
        reads = logical['reads']
        summaries[str(budget)] = {
            'samples': len(samples), 'logicalProfile': logical,
            'ramHitRate': reads['ramHits'] / (reads['ramHits'] + reads['ramMisses']),
            'measurements': {key: {'min': min(values), 'median': statistics.median(values), 'max': max(values)}
                             for key, values in measurements.items()},
        }
    comparison['summaries'] = summaries


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--snapshot', type=Path, required=True)
    parser.add_argument('--artifacts', type=Path, required=True)
    parser.add_argument('--reference-output', type=Path)
    parser.add_argument('--backend', type=Path, default=REPO / 'bin/phpurs.js', help='Standalone executable to freeze for the assay')
    parser.add_argument('--budgets', type=int, nargs='+', help='Compare these MiB budgets instead of the default/zero/64 validation')
    parser.add_argument('--repetitions', type=int, default=3, help='Repetitions per comparison budget, in rotating order (default: 3)')
    args = parser.parse_args()
    if args.budgets is not None and (len(set(args.budgets)) != len(args.budgets) or any(b < 0 or b > 8589934591 for b in args.budgets)):
        parser.error('--budgets requires distinct, non-negative safe MiB counts')
    if args.repetitions < 1:
        parser.error('--repetitions must be positive')
    snapshot, artifacts = args.snapshot.resolve(), args.artifacts.resolve()
    artifacts.mkdir(parents=True, exist_ok=True)
    assert not any(artifacts.iterdir())
    assert not artifacts.is_relative_to(snapshot) and not snapshot.is_relative_to(artifacts)
    before = m0.manifest(snapshot)
    shutil.copytree(snapshot, artifacts / 'work', symlinks=True)
    shutil.copy2(args.backend.resolve(), artifacts / 'backend.mjs')
    shutil.copy2(REPO / 'audit/2026-10-01/build-scenarios/count-io.mjs', artifacts / 'count-io.mjs')
    shutil.copy2(REPO / 'tests/codegen/fixtures/purmeta-profile-probe.mjs', artifacts / 'purmeta-probe.mjs')
    work = artifacts / 'work/b8x'
    output = work / 'output'
    module_count = len(list(output.glob('*/corefn.json')))
    results = {
        'backendSHA256': m0.digest((artifacts / 'backend.mjs').read_bytes()),
        'inputFiles': len(before), 'inputManifestSHA256': m0.digest(json.dumps(before, sort_keys=True).encode()),
        'node': subprocess.check_output(['node', '--version'], text=True).strip(),
        'nodeOptions': os.environ.get('NODE_OPTIONS'), 'trials': {},
        'platform': platform.platform(), 'machine': platform.machine(),
    }
    m0.save(artifacts / 'inputs.json', before)
    baseline, order, php_files = None, None, []

    def build(label, budget=None):
        command = ['node', '--import', str(artifacts / 'count-io.mjs'), '--import', str(artifacts / 'purmeta-probe.mjs'),
                   str(artifacts / 'backend.mjs'), '--main', 'Inter.Api.Main', '--bundle', '--no-cache', '--profile-purmeta', '--verbose']
        if budget is not None:
            command += ['--purmeta-cache-mib', str(budget)]
        started = time.monotonic()
        load_before = os.getloadavg()
        with (artifacts / f'{label}.log').open('wb') as log:
            process = subprocess.run(command, cwd=work, stdout=log, stderr=subprocess.STDOUT, timeout=600,
                                     env={**os.environ, 'GOPURS_JOBS': '1',
                                          'PHPURS_AUDIT_COUNTS': str(artifacts / f'{label}.io.json'),
                                          'PHPURS_PURMETA_PROBE': str(artifacts / f'{label}.purmeta-io.json')})
        elapsed = round((time.monotonic() - started) * 1000)
        assert process.returncode == 0, f'{label}: see log'
        text = (artifacts / f'{label}.log').read_text()
        assert not re.search(r'Failed to decode|Failed to read purmeta|DIRECTIVE PARSE ERRORS|\(failed\)|cache disabled|cache: ', text), label
        generated = re.findall(r'^Generating PHP code for (.*?) \(Total AST Nodes:', text, re.MULTILINE)
        assert len(set(generated)) == len(generated) == module_count
        if order is not None:
            assert generated == order
        io = json.loads((artifacts / f'{label}.io.json').read_text())
        probe = json.loads((artifacts / f'{label}.purmeta-io.json').read_text())
        assert io['reads']['corefn']['files'] == module_count
        assert len(io['writes']['purmeta']) == probe['writes']['files'] == module_count
        assert sum(entry['bytes'] for entry in io['writes']['purmeta']) == probe['writes']['bytes']
        profiles = re.findall(r'^\[phpurs\] purmeta: (.+)$', text, re.MULTILINE)
        assert len(profiles) == 1
        stats = json.loads(profiles[0])
        reads, writes = stats['reads'], stats['writes']
        assert {'attempts': reads['ioAttempts'], 'files': reads['files'], 'bytes': reads['bytes']} == probe['reads']
        assert {'attempts': writes['attempts'], 'files': writes['files'], 'bytes': writes['bytes']} == probe['writes']
        assert reads['requests'] == reads['blocked'] + reads['ramHits'] + reads['ramMisses']
        assert reads['ramMisses'] == reads['diskHits'] + reads['diskMissing'] + reads['errors']
        assert reads['errors'] == reads['diskMissing'] == writes['errors'] == 0
        assert reads['deserializations'] == reads['files']
        assert writes['serializations'] == writes['files']
        assert stats['ram']['trimCalls'] == module_count and stats['ram']['clearCalls'] == 0
        assert stats['policy']['maxSerializedBytes'] == (64 if budget is None else budget) * 1024 ** 2
        assert stats['ram']['serializedBytes'] <= stats['ram']['boundaryPeakSerializedBytes'] <= stats['policy']['maxSerializedBytes']
        assert stats['memory']['processPeakRSSKiB'] <= probe['processPeakRSSKiB']
        if budget == 0:
            assert stats['ram']['entries'] == stats['ram']['boundaryPeakEntries'] == 0
        result = {
            'budgetMiB': budget, 'generated': len(generated),
            'phasesMs': dict((name, int(ms)) for name, ms in re.findall(r'^\[phpurs\] (.*?): (\d+) ms$', text, re.MULTILINE)),
            'processMs': elapsed, 'processPeakRSSKiB': probe['processPeakRSSKiB'],
            'purmetaIO': {key: probe[key] for key in ['reads', 'writes']}, 'profile': stats,
            'phpWrites': len(io['writes']['php']),
            'loadAverageBefore': load_before, 'loadAverageAfter': os.getloadavg(),
        }
        if baseline:
            result['identicalFiles'] = activation.equal_outputs(baseline, output)
            assert result['phpWrites'] == 0
            assert all((output / file).stat().st_mtime_ns == 946684800000000000 for file in php_files)
            result['preservedPHPMtimes'] = len(php_files)
        assert not (output / '.phpurs-cache').exists()
        results['trials'][label] = result
        m0.save(artifacts / f'{label}.modules.json', generated)
        m0.save(artifacts / 'results.json', results)
        print(label, 'modules:', len(generated), 'purmeta reads:', reads['files'], 'boundary bytes:', stats['ram']['boundaryPeakSerializedBytes'],
              'backend ms:', result['phasesMs']['backend total'], 'RSS KiB:', result['processPeakRSSKiB'], flush=True)
        return generated, result

    order, default = build('default')
    if args.reference_output:
        baseline = args.reference_output.resolve()
        results['previousReferenceIdenticalFiles'] = activation.equal_outputs(baseline, output)
    else:
        baseline = artifacts / 'control-output'
        activation.copy_outputs(output, baseline)
    php_files = [file for file in activation.files(output) if file.suffix == '.php']
    for file in php_files:
        os.utime(output / file, ns=(946684800000000000, 946684800000000000))
    if args.budgets is None:
        _, zero = build('zero', 0)
        _, explicit = build('explicit-64', 64)
        assert profile.logical(default['profile']) == profile.logical(explicit['profile'])
        assert default['purmetaIO'] == explicit['purmetaIO']
        assert zero['profile']['reads']['blocked'] == default['profile']['reads']['blocked']
        assert zero['profile']['reads']['requests'] == default['profile']['reads']['requests']
        assert zero['profile']['reads']['files'] > default['profile']['reads']['files']
    else:
        schedule = []
        for repetition in range(args.repetitions):
            offset = repetition % len(args.budgets)
            for budget in args.budgets[offset:] + args.budgets[:offset]:
                schedule.append({'label': f'mib-{budget}-run-{repetition + 1}', 'budgetMiB': budget, 'round': repetition + 1})
        results['comparison'] = {'budgetsMiB': args.budgets, 'repetitions': args.repetitions, 'schedule': schedule}
        for entry in schedule:
            build(entry['label'], entry['budgetMiB'])
        summarize_comparison(results)
    assert m0.manifest(snapshot) == before
    results['verified'] = True
    m0.save(artifacts / 'results.json', results)
    print('Verified', artifacts, flush=True)


if __name__ == '__main__':
    main()
