"""Check final integrity and publish the measured, separately generated variants."""
import argparse
import json
from pathlib import Path
import statistics
import subprocess

from common import ROOT, REPO, BENCH, MODULE, VARIANTS, digest, load_driver, php_manifest, save


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    artifacts = parser.parse_args().artifacts.resolve()
    load = lambda filename: json.loads((artifacts / filename).read_text())
    sources = load('sources.json')
    standalone = str(REPO / 'bin/phpurs.js')
    for file, fingerprint in sources.items():
        if file != standalone:
            assert digest(Path(file)) == fingerprint, file
    for file, fingerprint in load('compiler-inputs.json').items():
        assert digest(REPO / file) == fingerprint, file
    compilers = load('compilers.json')
    driver = load_driver()
    vendor = load('vendor.json')
    assert driver.vendor_artifacts(BENCH) == vendor
    before = (artifacts / 'baseline/output' / MODULE).read_text()
    action = before.split('// Test_ArrayOps_act\n')[1]
    for variant in VARIANTS:
        assert digest(artifacts / (variant + '.cjs')) == compilers[variant]['sha256']
        directory = artifacts / variant
        assert php_manifest(directory / 'output') == load(variant + '-php.json')
        assert driver.vendor_artifacts(directory) == vendor
        code = (directory / 'output' / MODULE).read_text()
        assert code.split('// Test_ArrayOps_act\n')[1].startswith(action)
        for module in ['Data.Array', 'Data.Foldable', 'Data.Semiring', 'Test.ListOps', 'Test.StateMonad']:
            assert load(variant + '-php.json')[module + '/index.php'] == load('baseline-php.json')[module + '/index.php']
    assert php_manifest(BENCH / 'output') == load('integrated-php.json')
    measures = load('measurements.json')
    assert len(measures['isolated']) == len(measures['suite']) == 8
    assert all(row['values_validated'] and len(row['values']) == 14 for row in measures['suite'])
    assert all(row['output'] == 202950 and row['opcacheEnabled'] and row['jit']['on'] for row in measures['isolated'])
    counts = load('counts.json')
    assert len(counts) == 4 and all(row['additions'] == row['foldVisits'] == row['filteredValues'] == 450 and row['rangeValues'] == row['filterVisits'] == row['predicateOps'] == 900 for row in counts)
    summary = {}
    for variant in VARIANTS:
        isolated = [row for row in measures['isolated'] if row['variant'] == variant]
        suite = [row for row in measures['suite'] if row['variant'] == variant]
        assert len(isolated) == len(suite) == 2
        summary[variant] = {
            'isolatedMedianUs': statistics.median(row['medianUs'] for row in isolated),
            'isolatedProcessMediansUs': [row['medianUs'] for row in isolated],
            'suiteArrayProcessUs': [row['times_us'][12] for row in suite],
            'suiteArrayMedianUs': statistics.median(row['times_us'][12] for row in suite),
            'suiteTotalProcessMs': [row['total_ms'] for row in suite],
            'suiteTotalMedianMs': statistics.median(row['total_ms'] for row in suite),
        }
        summary[variant]['isolatedReductionPercent'] = 100 * (1 - summary[variant]['isolatedMedianUs'] / summary['baseline']['isolatedMedianUs'])
        summary[variant]['isolatedSpeedup'] = summary['baseline']['isolatedMedianUs'] / summary[variant]['isolatedMedianUs']
        summary[variant]['suiteArraySavedUs'] = summary['baseline']['suiteArrayMedianUs'] - summary[variant]['suiteArrayMedianUs']
    validation = load('validation.json')
    for label, run in validation['runs'].items():
        assert digest(artifacts / 'validation' / (label + '.log')) == run['logSHA256']
    assert validation['standaloneBeforeSHA256'] == sources[standalone]
    assert validation['standaloneAfterSHA256'] == digest(Path(standalone))
    cache = load('cache-validation.json')
    assert cache['standaloneSHA256'] == sources[standalone]
    integrity = {'sourceFilesUnchanged': len(sources) - 1, 'compiledInputsUnchanged': len(load('compiler-inputs.json')),
                 'activePhpFilesMatchIntegrated': len(load('integrated-php.json')), 'measuredPhpUnchanged': True,
                 'vendorUnchanged': True, 'publicFfiDefinitionsUnchanged': True, 'arrayEffectEntryUnchanged': True,
                 'standaloneBeforeSHA256': sources[standalone], 'standaloneAfterSHA256': digest(Path(standalone))}
    result = {'kind': 'Integrated contracted Array FFI callback specialization', 'preparation': load('preparation.json'),
              'compilers': compilers, 'summary': summary, 'counts': counts, 'measurements': measures,
              'cache': cache, 'validation': validation, 'integrity': integrity,
              'versions': {name: subprocess.check_output([name, '--version'], text=True).strip() for name in ['node', 'php']}}
    save(ROOT / 'results.json', result)
    print(json.dumps({'summary': summary, 'integrity': integrity}, indent=2))


if __name__ == '__main__':
    main()
