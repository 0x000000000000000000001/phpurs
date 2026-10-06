"""Measure each callback substitution independently, with mirrored process order."""
import argparse
import json
from pathlib import Path
import subprocess

from prepare import ROOT, WORKSPACE, VARIANTS, load_driver, php_manifest, save

FLAGS = ['-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1', '-d', 'opcache.file_cache=',
         '-d', 'opcache.file_update_protection=0', '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']
ORDERS = {'ListOps': ['baseline', 'list-add', 'list-add', 'baseline'],
          'ArrayOps': ['baseline', 'array-add', 'array-filter', 'array-filter', 'array-add', 'baseline'],
          'suite': VARIANTS + list(reversed(VARIANTS))}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    driver = load_driver()
    vendor = json.loads((artifacts / 'vendor.json').read_text())
    results = {'phpFlags': FLAGS, 'orders': ORDERS, 'validation': [], 'isolated': [], 'suite': []}

    def checked(command, label):
        run = subprocess.run(command, text=True, capture_output=True, timeout=120)
        (artifacts / (label + '.log')).write_text(run.stdout + run.stderr)
        assert run.returncode == 0 and not run.stderr, (label, run.stdout, run.stderr)
        return run.stdout

    for variant in VARIANTS:
        text = checked(['php', '-d', 'opcache.enable_cli=0', str(ROOT / 'validate.php'),
                        str(artifacts / variant / 'output'), variant,
                        str(WORKSPACE / 'phpurs/phpurs-foreign/src/Foreign.php')], 'validation-' + variant)
        results['validation'].append(json.loads(text))
    for mode, order in ORDERS.items():
        for trial, variant in enumerate(order):
            directory = artifacts / variant
            assert php_manifest(directory / 'output') == json.loads((artifacts / (variant + '-php.json')).read_text())
            assert driver.vendor_artifacts(directory) == vendor
            script = [str(directory / 'output/App/main.mod.php')] if mode == 'suite' else [str(ROOT / 'measure-isolated.php'), str(directory / 'output'), mode]
            text = checked(['php', *FLAGS, *script], f'{mode}-{trial + 1}-{variant}')
            if mode != 'suite':
                value = json.loads(text)
                assert value['output'] == 202950 and len(value['samplesUs']) == 11
                results['isolated'].append({'variant': variant, **value})
                print(mode, variant, 'median:', value['medianUs'], 'us', flush=True)
            else:
                value = driver.validate_output(text, 'pure', None, None)
                assert value['values_validated'] and len(value['values']) == 14
                results['suite'].append({'variant': variant, **value})
                print(variant, 'total:', value['total_ms'], 'ms; List/Array:', value['times_us'][2], value['times_us'][12], 'us', flush=True)
            save(artifacts / 'measurements.json', results)


if __name__ == '__main__':
    main()
