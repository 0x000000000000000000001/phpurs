"""Verify frozen inputs, generated PHP and active rebuild, then publish measurements."""
import argparse
import json
from pathlib import Path
import statistics
import subprocess

from prepare import ROOT, REPO, BENCH, MODULE, digest, load_driver, php_manifest, save


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
    prefix = before.split('// Test_ListOps_sumEvens\n')[0]
    action = before.split('// Test_ListOps_act\n')[1]
    for variant in ['baseline', 'integrated']:
        assert digest(artifacts / (variant + '.cjs')) == compilers[variant]['sha256']
        directory = artifacts / variant
        assert php_manifest(directory / 'output') == load(variant + '-php.json')
        assert driver.vendor_artifacts(directory) == vendor
        code = (directory / 'output' / MODULE).read_text()
        assert code.startswith(prefix)
        assert code.split('// Test_ListOps_act\n')[1].startswith(action)
    assert php_manifest(BENCH / 'output') == load('integrated-php.json')
    measures = load('measurements.json')
    assert len(measures['isolated']) == len(measures['suite']) == 4
    assert all(row['values_validated'] and len(row['values']) == 14 for row in measures['suite'])
    assert all(row['output'] == 202950 and row['opcacheEnabled'] and row['jit']['on'] for row in measures['isolated'])
    counts = load('counts.json')
    assert len(counts) == 2 and all(row['additions'] == row['foldVisits'] == 450 and row['cons'] == 1350 and row['nil'] == 2 for row in counts)
    summary = {}
    for variant in ['baseline', 'integrated']:
        isolated = [row for row in measures['isolated'] if row['variant'] == variant]
        suite = [row for row in measures['suite'] if row['variant'] == variant]
        assert len(isolated) == len(suite) == 2
        summary[variant] = {
            'isolatedMedianUs': statistics.median(row['medianUs'] for row in isolated),
            'isolatedProcessMediansUs': [row['medianUs'] for row in isolated],
            'suiteListProcessUs': [row['times_us'][2] for row in suite],
            'suiteListMedianUs': statistics.median(row['times_us'][2] for row in suite),
            'suiteTotalProcessMs': [row['total_ms'] for row in suite],
            'suiteTotalMedianMs': statistics.median(row['total_ms'] for row in suite),
        }
    summary['integrated']['isolatedReductionPercent'] = 100 * (1 - summary['integrated']['isolatedMedianUs'] / summary['baseline']['isolatedMedianUs'])
    summary['integrated']['isolatedSpeedup'] = summary['baseline']['isolatedMedianUs'] / summary['integrated']['isolatedMedianUs']
    summary['integrated']['suiteListSavedUs'] = summary['baseline']['suiteListMedianUs'] - summary['integrated']['suiteListMedianUs']
    validation = load('validation.json')
    for label, run in validation['runs'].items():
        assert digest(artifacts / 'validation' / (label + '.log')) == run['logSHA256']
    assert validation['standaloneBeforeSHA256'] == sources[standalone]
    assert validation['standaloneAfterSHA256'] == digest(Path(standalone))
    cache = load('cache-validation.json')
    assert cache['standaloneSHA256'] == sources[standalone]
    integrity = {'sourceFilesUnchanged': len(sources) - 1, 'compiledInputsUnchanged': len(load('compiler-inputs.json')),
                 'activePhpFilesMatchIntegrated': len(load('integrated-php.json')), 'measuredPhpUnchanged': True,
                 'vendorUnchanged': True, 'publicDefinitionsUnchanged': True, 'effectEntryUnchanged': True,
                 'standaloneBeforeSHA256': sources[standalone], 'standaloneAfterSHA256': digest(Path(standalone))}
    result = {'kind': 'Integrated bounded native callback specialization; fresh codegen with pass enabled/disabled',
              'preparation': load('preparation.json'), 'compilers': compilers, 'summary': summary, 'counts': counts,
              'measurements': measures, 'cache': cache, 'validation': validation, 'integrity': integrity,
              'versions': {name: subprocess.check_output([name, '--version'], text=True).strip() for name in ['node', 'php']}}
    save(ROOT / 'results.json', result)
    print(json.dumps({'summary': summary, 'integrity': integrity}, indent=2))


if __name__ == '__main__':
    main()
