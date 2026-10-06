"""Verify the campaign's inputs and outputs, then publish durable measurements."""
import argparse
import json
from pathlib import Path
import statistics
import subprocess

from prepare import ROOT, REPO, BENCH, MODULE, digest, load_driver, php_manifest, save


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    load = lambda filename: json.loads((artifacts / filename).read_text())
    preparation = load('preparation.json')
    sources = load('sources.json')
    for filename, fingerprint in sources.items():
        assert digest(Path(filename)) == fingerprint, filename
    assert php_manifest(BENCH / 'output') == load('active-php.json')
    driver = load_driver()
    vendor = load('vendor.json')
    assert driver.vendor_artifacts(BENCH) == vendor
    baseline = (artifacts / 'baseline/output' / MODULE).read_text()
    prefix = baseline.split('// Test_StateMonad_runManyTimes\n')[0]
    act = baseline.split('// Test_StateMonad_act\n')[1]
    for variant in ['baseline', 'strict', 'scalar']:
        directory = artifacts / variant
        assert php_manifest(directory / 'output') == load(variant + '-php.json')
        assert driver.vendor_artifacts(directory) == vendor
        text = (directory / 'output' / MODULE).read_text()
        assert text.startswith(prefix)
        assert text.split('// Test_StateMonad_act\n')[1].startswith(act)
    measures = load('measurements.json')
    assert len(measures['isolated']) == len(measures['suite']) == 6
    assert all(row['values_validated'] and len(row['values']) == 14 for row in measures['suite'])
    assert all(row['output'] == 1200 and row['opcacheEnabled'] and row['jit']['on'] for row in measures['isolated'])
    evidence = load('tast-evidence.json')
    assert digest(artifacts / 'optimized.json') == evidence['optimizedSHA256']
    assert measures['validation']['numericCases'] == 35 and measures['validation']['negativeFallbacks'] == 2
    counts = load('counts.json')
    assert len(counts) == 3 and all(row['additions'] == 1200 and row['outerAdditions'] == 20 for row in counts)
    summary = {}
    for variant in ['baseline', 'strict', 'scalar']:
        isolated = [row for row in measures['isolated'] if row['variant'] == variant]
        suite = [row for row in measures['suite'] if row['variant'] == variant]
        assert len(isolated) == len(suite) == 2
        summary[variant] = {
            'isolatedMedianUs': statistics.median(row['medianUs'] for row in isolated),
            'isolatedProcessMediansUs': [row['medianUs'] for row in isolated],
            'suiteStateMedianUs': statistics.median(row['times_us'][10] for row in suite),
            'suiteTotalMedianMs': statistics.median(row['total_ms'] for row in suite),
        }
    for variant in ['strict', 'scalar']:
        summary[variant]['isolatedReductionPercent'] = 100 * (1 - summary[variant]['isolatedMedianUs'] / summary['baseline']['isolatedMedianUs'])
        summary[variant]['suiteStateSavedUs'] = summary['baseline']['suiteStateMedianUs'] - summary[variant]['suiteStateMedianUs']
    state_share = summary['baseline']['suiteStateMedianUs'] / (10 * summary['baseline']['suiteTotalMedianMs'])
    result = {'kind': 'Pinned PHP prototype; no compiler integration', 'preparation': preparation,
              'summary': summary, 'stateShareOfBaselineTotalPercent': state_share, 'counts': counts,
              'tast': evidence, 'measurements': measures,
              'integrity': {'sourceFilesUnchanged': len(sources), 'activePhpFilesUnchanged': len(load('active-php.json')),
                            'measuredPhpUnchanged': True, 'vendorUnchanged': True, 'publicDefinitionsUnchanged': True,
                            'effectEntryUnchanged': True},
              'versions': {name: subprocess.check_output([name, '--version'], text=True).strip() for name in ['node', 'php']}}
    save(ROOT / 'results.json', result)
    print(json.dumps({'summary': summary, 'stateSharePercent': state_share, 'integrity': result['integrity']}, indent=2))


if __name__ == '__main__':
    main()
