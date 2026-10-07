"""Measure the genuinely regenerated variants in four fresh ABBA processes."""
import argparse
import json
from pathlib import Path
import subprocess

from prepare import ROOT, load_driver, php_manifest, save

FLAGS = ['-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1', '-d', 'opcache.file_cache=',
         '-d', 'opcache.file_update_protection=0', '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']
ORDER = ['baseline', 'integrated', 'integrated', 'baseline']


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    driver = load_driver()
    vendor = json.loads((artifacts / 'vendor.json').read_text())
    results = {'phpFlags': FLAGS, 'order': ORDER, 'isolated': [], 'suite': []}
    kernel = ROOT.parents[1] / '2026-10-05/state-chains/measure-isolated.php'
    for mode in ['isolated', 'suite']:
        for trial, variant in enumerate(ORDER):
            directory = artifacts / variant
            assert php_manifest(directory / 'output') == json.loads((artifacts / (variant + '-php.json')).read_text())
            assert driver.vendor_artifacts(directory) == vendor
            script = [str(kernel), str(directory / 'output')] if mode == 'isolated' else [str(directory / 'output/App/main.mod.php')]
            run = subprocess.run(['php', *FLAGS, *script], text=True, capture_output=True, timeout=180)
            (artifacts / f'{mode}-{trial + 1}-{variant}.log').write_text(run.stdout + run.stderr)
            assert run.returncode == 0 and not run.stderr, (run.stdout, run.stderr)
            if mode == 'isolated':
                value = json.loads(run.stdout)
                assert value['output'] == 1200 and len(value['samplesUs']) == 11
                print(variant, 'isolated median:', value['medianUs'], 'us', flush=True)
            else:
                value = driver.validate_output(run.stdout, 'pure', None, None)
                assert value['values_validated'] and len(value['values']) == 14
                print(variant, 'suite total:', value['total_ms'], 'ms; State:', value['times_us'][10], 'us', flush=True)
            results[mode].append({'variant': variant, **value})
            save(artifacts / 'measurements.json', results)


if __name__ == '__main__':
    main()
