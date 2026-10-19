"""Compare two frozen PHPurs executables on the same benchmark CoreFn tree."""
import argparse
import hashlib
import importlib.util
import json
import re
import shutil
import statistics
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parent
REPO = ROOT.parents[2]
WORKSPACE = REPO.parents[1]
BENCH = WORKSPACE / 'altbak.pub-phpurs/run/bak/php/modes/pure'
EXEC_CWD = BENCH.parent
FLAGS = ['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1',
        '-d', 'opcache.file_cache=', '-d', 'opcache.file_update_protection=0',
        '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']
ORDER = ['control', 'exact', 'exact', 'control', 'exact', 'control', 'control', 'exact']


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def manifest(directory):
    return {str(path.relative_to(directory)): digest(path)
            for path in sorted(directory.rglob('*.php'))}


def load_driver():
    spec = importlib.util.spec_from_file_location('php_driver', WORKSPACE / 'altbak.pub-phpurs/bin/php/driver.py')
    driver = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(driver)
    return driver


def prepare(source, destination):
    if destination.exists():
        shutil.rmtree(destination)
    shutil.copytree(source, destination)
    for path in destination.rglob('*.php'):
        path.unlink()
    for name in ['.phpurs-cache', '.phpurs-outputs.json']:
        target = destination / name
        if target.is_dir():
            shutil.rmtree(target)
        elif target.exists():
            target.unlink()


def compile_variant(cli, destination, log):
    result = subprocess.run(['node', str(cli), '--no-cache', '--main', 'App', '--output', str(destination)],
                            cwd=BENCH, text=True, capture_output=True, timeout=300)
    log.write_text(result.stdout + result.stderr)
    assert result.returncode == 0, str(log)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--control', type=Path, required=True)
    parser.add_argument('--exact', type=Path, required=True)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    assert not artifacts.exists()
    artifacts.mkdir(parents=True)
    source = BENCH / 'output'
    outputs = {}
    for variant, cli in [('control', args.control.resolve()), ('exact', args.exact.resolve())]:
        destination = artifacts / (variant + '-output')
        prepare(source, destination)
        compile_variant(cli, destination, artifacts / (variant + '-generate.log'))
        outputs[variant] = destination
    control = manifest(outputs['control'])
    exact = manifest(outputs['exact'])
    assert control.keys() == exact.keys()
    changed = [name for name in control if control[name] != exact[name]]
    exact_bodies = sum(len(re.findall(r'^function \w+__phpurs_exact\(', path.read_text(), re.M))
                       for path in outputs['exact'].rglob('*.php'))
    driver = load_driver()
    rows = []
    for index, variant in enumerate(ORDER, 1):
        run = subprocess.run([*FLAGS, str(outputs[variant] / 'App/main.mod.php')],
                             cwd=EXEC_CWD, text=True, capture_output=True, timeout=120)
        assert run.returncode == 0 and not run.stderr, (variant, run.stdout, run.stderr)
        value = driver.validate_output(run.stdout, 'pure', None, None)
        assert value['values_validated'] and len(value['values']) == 14
        rows.append({'run': index, 'variant': variant, **value})
        print(index, variant, value['total_ms'], flush=True)
    for variant in ['control', 'exact']:
        assert all(row['values'] == rows[0]['values'] for row in rows if row['variant'] == variant)
    summary = {}
    for variant in ['control', 'exact']:
        selected = [row for row in rows if row['variant'] == variant]
        summary[variant] = {
            'totalMs': [row['total_ms'] for row in selected],
            'medianTotalMs': statistics.median(row['total_ms'] for row in selected),
            'medianFibUs': statistics.median(row['times_us'][1] for row in selected),
            'medianListUs': statistics.median(row['times_us'][2] for row in selected),
            'medianAckermannUs': statistics.median(row['times_us'][5] for row in selected),
            'medianRBTreeUs': statistics.median(row['times_us'][8] for row in selected),
        }
    result = {
        'compilers': {'control': {'path': str(args.control.resolve()), 'sha256': digest(args.control.resolve())},
                      'exact': {'path': str(args.exact.resolve()), 'sha256': digest(args.exact.resolve())}},
        'outputs': {'phpFiles': len(exact), 'changed': changed, 'changedCount': len(changed),
                    'exactBodies': exact_bodies,
                    'controlBytes': sum((outputs['control'] / name).stat().st_size for name in control),
                    'exactBytes': sum((outputs['exact'] / name).stat().st_size for name in exact)},
        'order': ORDER, 'rows': rows, 'summary': summary, 'artifacts': str(artifacts),
    }
    (artifacts / 'results.json').write_text(json.dumps(result, indent=2) + '\n')
    print(json.dumps({'outputs': result['outputs'], 'summary': summary}, indent=2))


if __name__ == '__main__':
    main()
