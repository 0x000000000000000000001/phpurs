"""Compile the captured-seed kernel and current benchmark TAST with a frozen pair."""
import argparse
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import shutil
import statistics
import subprocess
import time

ROOT = Path(__file__).resolve().parent
REPO = ROOT.parents[2]
WORKSPACE = REPO.parents[1]
RUNNER = REPO / 'tests/runner'
BENCH = WORKSPACE / 'altbak.pub-phpurs/run/bak/php/modes/pure'
FLAGS = ['-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1',
         '-d', 'opcache.file_cache=', '-d', 'opcache.file_update_protection=0',
         '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def save(path, value):
    path.write_text(json.dumps(value, indent=2) + '\n')


def run(command, cwd, log):
    start = time.monotonic()
    process = subprocess.run(command, cwd=cwd, text=True, capture_output=True,
                             env={**os.environ, 'GOPURS_JOBS': '1'}, timeout=300)
    log.write_text(process.stdout + process.stderr)
    assert process.returncode == 0, (command, str(log), process.returncode)
    return process, (time.monotonic() - start) * 1000


def manifest(directory):
    return {str(p.relative_to(directory)): digest(p) for p in sorted(directory.rglob('*.php'))}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    compilers = json.loads((artifacts / 'compilers.json').read_text())
    for variant in ['baseline', 'integrated']:
        assert digest(artifacts / (variant + '.cjs')) == compilers[variant]['sha256']
    results = {'compilers': compilers, 'phpFlags': FLAGS, 'builds': {}, 'comparisons': {}, 'kernel': [], 'suite': []}
    spec = importlib.util.spec_from_file_location('php_driver', WORKSPACE / 'altbak.pub-phpurs/bin/php/driver.py')
    driver = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(driver)
    # The normal runner owns this scratch source directory.
    for suffix in ['purs', 'php', 'js']:
        shutil.copy2(ROOT / ('bench.' + suffix), RUNNER / ('src/Main.' + suffix))
    run(['spago', 'build', '-q', '--output', 'output-test'], RUNNER, artifacts / 'kernel-purs.log')
    node, _ = run(['node', '--input-type=module', '-e', "import('./output-test/Main/index.js').then(m=>m.main())"],
                  RUNNER, artifacts / 'kernel-js.log')
    assert node.stdout.strip() == '1011000'
    results['kernelJavaScript'] = node.stdout.strip()
    source_inputs = {}
    expected_vendor = driver.vendor_artifacts(BENCH)
    assert expected_vendor is not None
    for corpus, cwd, source, entry in [('kernel', RUNNER, RUNNER / 'output-test', 'Main'),
                                       ('suite', BENCH, BENCH / 'output', 'App')]:
        corefn = sorted(source.glob('*/corefn.json'))
        assert corefn
        for path in corefn:
            source_inputs[str(path)] = digest(path)
            data = json.loads(path.read_text())
            assert 'typeTable' in data
            ffi = (cwd / Path(data['modulePath']).with_suffix('.php')).resolve()
            if ffi.is_file():
                source_inputs[str(ffi)] = digest(ffi)
        for variant in ['baseline', 'integrated']:
            destination = artifacts / corpus / variant
            output = destination / 'output'
            output.mkdir(parents=True, exist_ok=False)
            for path in corefn:
                target = output / path.relative_to(source)
                target.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(path, target)
            process, elapsed = run(['node', str(artifacts / (variant + '.cjs')), '--main', entry,
                                    '--output', str(output), '--no-cache', '--profile-build'],
                                   cwd, artifacts / f'{corpus}-{variant}-build.log')
            assert 'Failed to decode' not in process.stdout + process.stderr
            profiles = [json.loads(line.split(': ', 1)[1]) for line in process.stderr.splitlines()
                        if line.startswith('[phpurs] build-profile: ')]
            assert len(profiles) == 1 and profiles[0]['status'] == 'completed'
            assert profiles[0]['phases']['translate']['completed'] == len(corefn)
            results['builds'][f'{corpus}-{variant}'] = {'processMs': elapsed, 'modules': len(corefn),
                                                      'phpFiles': len(manifest(output)),
                                                      'phpBytes': sum(p.stat().st_size for p in output.rglob('*.php'))}
            if corpus == 'kernel':
                # Keep the generated loader, replace only its final invocation.
                loader = (output / 'Main/main.mod.php').read_text()
                assert loader.count("$GLOBALS['Main_main']();") == 1
                loader = loader.split("$GLOBALS['Main_main']();")[0]
                body = (ROOT / 'measure-kernel.php').read_text().removeprefix('<?php\n')
                (output / 'Main/measure.mod.php').write_text(loader + body)
            else:
                for filename in ['composer.json', 'composer.lock']:
                    shutil.copy2(BENCH / filename, destination / filename)
                    source_inputs[str(BENCH / filename)] = digest(BENCH / filename)
                shutil.copytree(BENCH / 'vendor', destination / 'vendor', symlinks=True)
                assert driver.vendor_artifacts(destination) == expected_vendor
                driver.composer_inputs(destination, shutil.which('php'), os.environ.copy())
        before = manifest(artifacts / corpus / 'baseline/output')
        after = manifest(artifacts / corpus / 'integrated/output')
        assert before.keys() == after.keys()
        results['comparisons'][corpus] = {'files': len(before), 'changed': [name for name in before if before[name] != after[name]]}
        save(artifacts / (corpus + '-php-before.json'), before)
        save(artifacts / (corpus + '-php-after.json'), after)
    for variant in ['baseline', 'integrated', 'integrated', 'baseline']:
        directory = artifacts / 'kernel' / variant
        process, _ = run(['php', *FLAGS, str(directory / 'output/Main/measure.mod.php'), '1000', '1000', '11'],
                         directory, artifacts / f'kernel-{len(results["kernel"])}-{variant}.log')
        assert not process.stderr
        row = json.loads(process.stdout)
        assert row['output'] == 1011000 and len(row['samples_ms']) == 10
        results['kernel'].append({'variant': variant, **row})
        print(variant, 'kernel median:', row['median_ms'], 'ms', flush=True)
    for variant in ['baseline', 'integrated', 'integrated', 'baseline']:
        directory = artifacts / 'suite' / variant
        assert driver.vendor_artifacts(directory) == expected_vendor
        process, _ = run(['php', *FLAGS, str(directory / 'output/App/main.mod.php')], directory,
                         artifacts / f'suite-{len(results["suite"])}-{variant}.log')
        assert not process.stderr
        validated = driver.validate_output(process.stdout, 'pure', None, None)
        assert validated['values_validated'] and len(validated['values']) == 14
        results['suite'].append({'variant': variant, **validated})
        print(variant, 'suite:', validated['total_ms'], 'ms', flush=True)
    for filename, fingerprint in source_inputs.items():
        assert digest(Path(filename)) == fingerprint, filename
    assert driver.vendor_artifacts(BENCH) == expected_vendor
    for corpus in ['kernel', 'suite']:
        for variant, suffix in [('baseline', 'before'), ('integrated', 'after')]:
            assert manifest(artifacts / corpus / variant / 'output') == json.loads((artifacts / (corpus + '-php-' + suffix + '.json')).read_text())
    results['inputsUnchanged'] = True
    results['inputFiles'] = len(source_inputs)
    results['inputManifestSHA256'] = hashlib.sha256(json.dumps(source_inputs, sort_keys=True).encode()).hexdigest()
    results['kernelMediansMs'] = {variant: statistics.median(row['median_ms'] for row in results['kernel'] if row['variant'] == variant)
                                for variant in ['baseline', 'integrated']}
    save(artifacts / 'source-inputs.json', source_inputs)
    save(artifacts / 'results.json', results)
    print(json.dumps(results['comparisons'], indent=2), flush=True)


if __name__ == '__main__':
    main()
