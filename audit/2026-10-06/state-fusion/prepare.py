"""Regenerate both variants from identical CoreFn, FFI and frozen compiler inputs."""
import argparse
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess

ROOT = Path(__file__).resolve().parent
REPO = ROOT.parents[2]
WORKSPACE = REPO.parents[1]
BENCH = WORKSPACE / 'altbak.pub-phpurs/run/bak/php/modes/pure'
MODULE = 'Test.StateMonad/index.php'


def digest(file):
    return hashlib.sha256(file.read_bytes()).hexdigest()


def save(file, value):
    file.write_text(json.dumps(value, indent=2) + '\n')


def php_manifest(directory):
    return {str(p.relative_to(directory)): digest(p) for p in sorted(directory.rglob('*.php'))}


def load_driver():
    spec = importlib.util.spec_from_file_location('php_driver', WORKSPACE / 'altbak.pub-phpurs/bin/php/driver.py')
    driver = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(driver)
    return driver


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    parser.add_argument('--control', type=Path, help='Optional pre-change generated PHP directory')
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    assert not artifacts.exists()
    artifacts.mkdir(parents=True)
    driver = load_driver()
    active = BENCH / 'output'
    active_php = php_manifest(active)
    vendor = driver.vendor_artifacts(BENCH)
    assert vendor is not None
    sources = driver.inputs()
    for directory in [REPO / 'src', WORKSPACE / 'purescript-backend-optimizer-phpurs/src']:
        sources.update({str(p): digest(p) for p in directory.rglob('*') if p.is_file() and p.suffix in {'.purs', '.js'}})
    corefn = sorted(active.glob('*/corefn.json'))
    for file in corefn:
        sources[str(file)] = digest(file)
        data = json.loads(file.read_text())
        assert 'typeTable' in data
        foreign = (BENCH / Path(data['modulePath']).with_suffix('.php')).resolve()
        if foreign.is_file():
            sources[str(foreign)] = digest(foreign)
    freeze = subprocess.run(['node', str(ROOT / 'freeze.mjs'), str(artifacts)], cwd=REPO, text=True, capture_output=True)
    (artifacts / 'freeze.log').write_text(freeze.stdout + freeze.stderr)
    assert freeze.returncode == 0, freeze.stdout + freeze.stderr
    variants = {}
    manifests = {}
    for variant in ['baseline', 'integrated']:
        directory = artifacts / variant
        output = directory / 'output'
        for file in corefn:
            target = output / file.relative_to(active)
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(file, target)
        run = subprocess.run(['node', str(artifacts / (variant + '.cjs')), '--main', 'App', '--output', str(output), '--profile-build'],
                             cwd=BENCH, env={**os.environ, 'GOPURS_JOBS': '1'}, text=True, capture_output=True, timeout=300)
        (artifacts / (variant + '-build.log')).write_text(run.stdout + run.stderr)
        assert run.returncode == 0, str(artifacts / (variant + '-build.log'))
        profiles = [json.loads(line.split(': ', 1)[1]) for line in run.stderr.splitlines() if line.startswith('[phpurs] build-profile: ')]
        assert len(profiles) == 1 and profiles[0]['status'] == 'completed'
        assert profiles[0]['phases']['translate']['completed'] == len(corefn)
        for filename in ['composer.json', 'composer.lock']:
            sources[str(BENCH / filename)] = digest(BENCH / filename)
            shutil.copy2(BENCH / filename, directory / filename)
        shutil.copytree(BENCH / 'vendor', directory / 'vendor', symlinks=True)
        assert driver.vendor_artifacts(directory) == vendor
        driver.composer_inputs(directory, shutil.which('php'), os.environ.copy())
        manifests[variant] = php_manifest(output)
        save(artifacts / (variant + '-php.json'), manifests[variant])
        variants[variant] = {'phpFiles': len(manifests[variant]), 'phpBytes': sum(p.stat().st_size for p in output.rglob('*.php')),
                             'moduleSHA256': manifests[variant][MODULE], 'buildProfile': profiles[0]}
    assert manifests['baseline'].keys() == manifests['integrated'].keys()
    changed = [p for p in manifests['baseline'] if manifests['baseline'][p] != manifests['integrated'][p]]
    assert changed == [MODULE], changed
    if args.control:
        assert manifests['baseline'] == php_manifest(args.control.resolve()), 'disabled pass differs from pre-change control'
    before = (artifacts / 'baseline/output' / MODULE).read_text()
    after = (artifacts / 'integrated/output' / MODULE).read_text()
    marker = '// Test_StateMonad_runManyTimes\n'
    assert before.split(marker)[0] == after.split(marker)[0], 'public helpers or constructor changed'
    assert '__phpurs_state_0_chainmajModifications(60, 0)' in after, 'benchmark did not select the pass'
    assert all(digest(Path(file)) == fingerprint for file, fingerprint in sources.items())
    assert php_manifest(active) == active_php
    save(artifacts / 'sources.json', sources)
    save(artifacts / 'vendor.json', vendor)
    save(artifacts / 'active-php.json', active_php)
    result = {'artifacts': str(artifacts), 'modules': len(corefn), 'changed': changed, 'variants': variants,
              'publicDefinitionsSHA256': hashlib.sha256(before.split(marker)[0].encode()).hexdigest(),
              'controlMatched': bool(args.control), 'heads': {name: subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=path, text=True).strip()
                for name, path in [('phpurs', REPO), ('pbo', WORKSPACE / 'purescript-backend-optimizer-phpurs'), ('bench', WORKSPACE / 'altbak.pub-phpurs')]}}
    save(artifacts / 'preparation.json', result)
    print(json.dumps({key: value for key, value in result.items() if key != 'variants'}, indent=2))


if __name__ == '__main__':
    main()
