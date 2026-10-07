"""Measure separate fold/filter contributions and their actual combined output."""
import argparse
import json
from pathlib import Path
import subprocess

from common import ROOT, FLAGS, load_driver, php_manifest, save

ORDER = ['baseline', 'fold', 'filter', 'integrated', 'integrated', 'filter', 'fold', 'baseline']


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    artifacts = parser.parse_args().artifacts.resolve()
    driver = load_driver()
    vendor = json.loads((artifacts / 'vendor.json').read_text())
    results = {'phpFlags': FLAGS, 'order': ORDER, 'isolated': [], 'suite': []}
    kernel = ROOT.parents[1] / '2026-10-05/traversal-callbacks/measure-isolated.php'
    for mode in ['isolated', 'suite']:
        for trial, variant in enumerate(ORDER):
            directory = artifacts / variant
            assert php_manifest(directory / 'output') == json.loads((artifacts / (variant + '-php.json')).read_text())
            assert driver.vendor_artifacts(directory) == vendor
            script = [str(kernel), str(directory / 'output'), 'ArrayOps'] if mode == 'isolated' else [str(directory / 'output/App/main.mod.php')]
            run = subprocess.run(['php', *FLAGS, *script], text=True, capture_output=True, timeout=180)
            (artifacts / f'{mode}-{trial + 1}-{variant}.log').write_text(run.stdout + run.stderr)
            assert run.returncode == 0 and not run.stderr, (run.stdout, run.stderr)
            if mode == 'isolated':
                value = json.loads(run.stdout)
                assert value['output'] == 202950 and len(value['samplesUs']) == 11
                print(variant, 'isolated:', value['medianUs'], 'us', flush=True)
            else:
                value = driver.validate_output(run.stdout, 'pure', None, None)
                assert value['values_validated'] and len(value['values']) == 14
                print(variant, 'suite:', value['total_ms'], 'ms; ArrayOps:', value['times_us'][12], 'us', flush=True)
            results[mode].append({'variant': variant, **value})
            save(artifacts / 'measurements.json', results)


if __name__ == '__main__':
    main()
