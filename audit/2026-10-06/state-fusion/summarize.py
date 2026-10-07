"""Check frozen evidence and active PHP, then publish the integration measurements."""
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
    # bin/php/run -c can repack this generated executable through Spago. The
    # measured binaries and every source/compiled module are checked separately.
    for file, fingerprint in sources.items():
        if file != standalone:
            assert digest(Path(file)) == fingerprint, file
    for file, fingerprint in load('compiler-inputs.json').items():
        assert digest(REPO / file) == fingerprint, file
    compilers = load('compilers.json')
    for variant in ['baseline', 'integrated']:
        assert digest(artifacts / (variant + '.cjs')) == compilers[variant]['sha256']
    driver = load_driver()
    vendor = load('vendor.json')
    assert driver.vendor_artifacts(BENCH) == vendor
    before = (artifacts / 'baseline/output' / MODULE).read_text()
    prefix = before.split('// Test_StateMonad_runManyTimes\n')[0]
    action = before.split('// Test_StateMonad_act\n')[1]
    for variant in ['baseline', 'integrated']:
        directory = artifacts / variant
        assert php_manifest(directory / 'output') == load(variant + '-php.json')
        assert driver.vendor_artifacts(directory) == vendor
        text = (directory / 'output' / MODULE).read_text()
        assert text.startswith(prefix)
        assert text.split('// Test_StateMonad_act\n')[1].startswith(action)
    assert php_manifest(BENCH / 'output') == load('integrated-php.json'), 'active build differs from measured integrated PHP'
    measures = load('measurements.json')
    assert len(measures['isolated']) == len(measures['suite']) == 4
    assert all(row['values_validated'] and len(row['values']) == 14 for row in measures['suite'])
    assert all(row['output'] == 1200 and row['opcacheEnabled'] and row['jit']['on'] for row in measures['isolated'])
    counts = load('counts.json')
    assert len(counts) == 2 and all(row['additions'] == 1200 and row['outerAdditions'] == 20 for row in counts)
    summary = {}
    for variant in ['baseline', 'integrated']:
        isolated = [row for row in measures['isolated'] if row['variant'] == variant]
        suite = [row for row in measures['suite'] if row['variant'] == variant]
        assert len(isolated) == len(suite) == 2
        summary[variant] = {
            'isolatedMedianUs': statistics.median(row['medianUs'] for row in isolated),
            'isolatedProcessMediansUs': [row['medianUs'] for row in isolated],
            'suiteStateProcessUs': [row['times_us'][10] for row in suite],
            'suiteStateMedianUs': statistics.median(row['times_us'][10] for row in suite),
            'suiteTotalProcessMs': [row['total_ms'] for row in suite],
            'suiteTotalMedianMs': statistics.median(row['total_ms'] for row in suite),
        }
    summary['integrated']['isolatedReductionPercent'] = 100 * (1 - summary['integrated']['isolatedMedianUs'] / summary['baseline']['isolatedMedianUs'])
    summary['integrated']['suiteStateSavedUs'] = summary['baseline']['suiteStateMedianUs'] - summary['integrated']['suiteStateMedianUs']
    preparation = load('preparation.json')
    for value in preparation['variants'].values():
        value.pop('buildProfile')
    integrity = {'sourceFilesUnchanged': len(sources) - 1, 'compiledInputsUnchanged': len(load('compiler-inputs.json')),
                 'activePhpFilesMatchIntegrated': len(load('integrated-php.json')), 'measuredPhpUnchanged': True,
                 'vendorUnchanged': True, 'publicDefinitionsUnchanged': True, 'effectEntryUnchanged': True,
                 'standaloneBeforeSHA256': sources[standalone], 'standaloneAfterSHA256': digest(Path(standalone))}
    result = {'kind': 'Integrated bounded State fusion; fresh codegen with pass enabled/disabled',
              'preparation': preparation, 'compilers': compilers, 'summary': summary, 'counts': counts,
              'measurements': measures, 'integrity': integrity,
              'versions': {name: subprocess.check_output([name, '--version'], text=True).strip() for name in ['node', 'php']}}
    save(ROOT / 'results.json', result)
    print(json.dumps({'summary': summary, 'integrity': integrity, 'variants': preparation['variants']}, indent=2))


if __name__ == '__main__':
    main()
