"""Regenerate all four pass configurations using frozen compilers and inputs."""
import argparse
import json
import os
from pathlib import Path
import shutil
import subprocess

from common import ROOT, REPO, WORKSPACE, BENCH, MODULE, VARIANTS, digest, save, php_manifest, load_driver


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    parser.add_argument('--control', type=Path)
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
    variants, manifests = {}, {}
    for variant in VARIANTS:
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
        profile, = [json.loads(line.split(': ', 1)[1]) for line in run.stderr.splitlines() if line.startswith('[phpurs] build-profile: ')]
        assert profile['status'] == 'completed' and profile['phases']['translate']['completed'] == len(corefn)
        for filename in ['composer.json', 'composer.lock']:
            sources[str(BENCH / filename)] = digest(BENCH / filename)
            shutil.copy2(BENCH / filename, directory / filename)
        shutil.copytree(BENCH / 'vendor', directory / 'vendor', symlinks=True)
        assert driver.vendor_artifacts(directory) == vendor
        driver.composer_inputs(directory, shutil.which('php'), os.environ.copy())
        manifests[variant] = php_manifest(output)
        save(artifacts / (variant + '-php.json'), manifests[variant])
        changed = [p for p in manifests['baseline'] if manifests['baseline'][p] != manifests[variant][p]]
        expected_changes = [] if variant == 'baseline' else [MODULE, 'Test.WorkloadArrayInt/index.php']
        if variant in ['filter', 'integrated']:
            expected_changes.append('Test.WorkloadArrayNumber/index.php')
        if variant in ['fold', 'integrated']:
            expected_changes.append('Test.WorkloadMap/index.php')
        assert changed == expected_changes, changed
        variants[variant] = {'phpFiles': len(manifests[variant]), 'phpBytes': sum(p.stat().st_size for p in output.rglob('*.php')),
                             'changed': changed, 'moduleSHA256': manifests[variant][MODULE]}
    if args.control:
        assert manifests['baseline'] == php_manifest(args.control.resolve()), 'disabled pass differs from previous compiler'
    assert all(digest(Path(file)) == value for file, value in sources.items())
    assert php_manifest(active) == active_php
    save(artifacts / 'sources.json', sources)
    save(artifacts / 'vendor.json', vendor)
    save(artifacts / 'active-php.json', active_php)
    result = {'artifacts': str(artifacts), 'modules': len(corefn), 'variants': variants, 'controlMatched': bool(args.control),
              'heads': {name: subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=directory, text=True).strip()
                for name, directory in [('phpurs', REPO), ('pbo', WORKSPACE / 'purescript-backend-optimizer-phpurs'), ('bench', WORKSPACE / 'altbak.pub-phpurs')]}}
    save(artifacts / 'preparation.json', result)
    print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
