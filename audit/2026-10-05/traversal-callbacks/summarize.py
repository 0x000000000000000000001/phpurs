"""Verify frozen evidence and publish the traversal-callback measurement record."""
import argparse
import json
from pathlib import Path
import statistics
import subprocess

from prepare import ROOT, BENCH, VARIANTS, digest, load_driver, php_manifest, save


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    load = lambda name: json.loads((artifacts / name).read_text())
    sources = load('sources.json')
    for filename, fingerprint in sources.items():
        assert digest(Path(filename)) == fingerprint, filename
    active = load('active-php.json')
    assert php_manifest(BENCH / 'output') == active
    driver = load_driver()
    vendor = load('vendor.json')
    assert driver.vendor_artifacts(BENCH) == vendor
    for variant in VARIANTS:
        directory = artifacts / variant
        assert php_manifest(directory / 'output') == load(variant + '-php.json')
        assert driver.vendor_artifacts(directory) == vendor
        for module in ['ListOps', 'ArrayOps']:
            baseline = (artifacts / 'baseline/output' / ('Test.' + module) / 'index.php').read_text()
            current = (directory / 'output' / ('Test.' + module) / 'index.php').read_text()
            assert current.startswith(baseline.split(f'// Test_{module}_sumEvens\n')[0])
            assert current.split(f'// Test_{module}_describe\n')[1].startswith(baseline.split(f'// Test_{module}_describe\n')[1])
    measures = load('measurements.json')
    assert len(measures['validation']) == 4 and len(measures['isolated']) == 10 and len(measures['suite']) == 8
    assert all(row['consumerCases'] == 20 and row['foldCases'] == 27 and row['overflowCases'] == 5 for row in measures['validation'])
    assert all(row['values_validated'] and len(row['values']) == 14 for row in measures['suite'])
    assert all(row['output'] == 202950 and row['opcacheEnabled'] and row['jit']['on'] for row in measures['isolated'])
    evidence = load('tast-evidence.json')
    for module, fingerprint in evidence['optimizedSHA256'].items():
        assert digest(artifacts / (module + '.optimized.json')) == fingerprint
    counts = load('counts.json')
    assert len(counts) == 9
    assert all(row['additions'] == row['foldVisits'] == 450 for row in counts if row['module'] != 'Primes')
    summary = {}
    baseline_total = statistics.median(row['total_ms'] for row in measures['suite'] if row['variant'] == 'baseline')
    for variant, module, index in [('list-add', 'ListOps', 2), ('array-add', 'ArrayOps', 12), ('array-filter', 'ArrayOps', 12)]:
        before = [row['medianUs'] for row in measures['isolated'] if row['module'] == module and row['variant'] == 'baseline']
        after = [row['medianUs'] for row in measures['isolated'] if row['module'] == module and row['variant'] == variant]
        assert len(before) == len(after) == 2
        suite_before = [row['times_us'][index] for row in measures['suite'] if row['variant'] == 'baseline']
        suite_after = [row['times_us'][index] for row in measures['suite'] if row['variant'] == variant]
        saved = statistics.median(suite_before) - statistics.median(suite_after)
        summary[variant] = {'module': module, 'isolatedBeforeUs': before, 'isolatedAfterUs': after,
                            'isolatedReductionPercent': 100 * (1 - statistics.median(after) / statistics.median(before)),
                            'suiteBeforeUs': suite_before, 'suiteAfterUs': suite_after,
                            'suiteSavedUs': saved, 'directSavingFractionOfBaselineTotalPercent': saved / (10 * baseline_total)}
    state_file = ROOT.parent / 'state-chains/results.json'
    state = json.loads(state_file.read_text())
    record = {'kind': 'Pinned PHP prototypes; no compiler integration', 'preparation': load('preparation.json'),
              'summary': summary, 'counts': counts, 'tast': evidence, 'measurements': measures,
              'stateComparison': {'source': '../state-chains/results.json', 'sha256': digest(state_file),
                                  'previousCampaignStateSavedUs': state['summary']['scalar']['suiteStateSavedUs'],
                                  'decision': 'Prioritize a bounded generic State integration; keep callback saturation as a separate later step'},
              'integrity': {'sourceFilesUnchanged': len(sources), 'activePhpFilesUnchanged': len(active),
                            'measuredPhpUnchanged': True, 'vendorUnchanged': True, 'publicTraversalsUnchanged': True,
                            'effectEntriesUnchanged': True},
              'versions': {name: subprocess.check_output([name, '--version'], text=True).strip() for name in ['node', 'php']}}
    save(ROOT / 'results.json', record)
    print(json.dumps({'summary': summary, 'integrity': record['integrity']}, indent=2))


if __name__ == '__main__':
    main()
