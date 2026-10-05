"""Freeze generated inputs; rebuild a control and create two pinned PHP prototypes."""
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


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def save(path, value):
    path.write_text(json.dumps(value, indent=2) + '\n')


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
    compiled = {str(p): digest(p) for p in (REPO / 'output').glob('*/*.js')}
    sources.update(compiled)
    output = artifacts / 'baseline/output'
    corefn = sorted(active.glob('*/corefn.json'))
    for file in corefn:
        sources[str(file)] = digest(file)
        target = output / file.relative_to(active)
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(file, target)
        data = json.loads(file.read_text())
        assert 'typeTable' in data
        foreign = (BENCH / Path(data['modulePath']).with_suffix('.php')).resolve()
        if foreign.is_file():
            sources[str(foreign)] = digest(foreign)
    command = ['node', str(ROOT / 'capture.mjs'), '--main', 'App', '--output', str(output), '--profile-build']
    run = subprocess.run(command, cwd=BENCH, env={**os.environ, 'GOPURS_JOBS': '1', 'PHPURS_STATE_AUDIT': str(artifacts)},
                         text=True, capture_output=True, timeout=300)
    (artifacts / 'rebuild.log').write_text(run.stdout + run.stderr)
    assert run.returncode == 0, str(artifacts / 'rebuild.log')
    profiles = [json.loads(line.split(': ', 1)[1]) for line in run.stderr.splitlines()
                if line.startswith('[phpurs] build-profile: ')]
    assert len(profiles) == 1 and profiles[0]['status'] == 'completed'
    assert profiles[0]['phases']['translate']['completed'] == len(corefn)
    regenerated = php_manifest(output)
    assert regenerated == active_php, [name for name in regenerated if regenerated[name] != active_php.get(name)]
    source = (output / MODULE).read_text()
    begin = source.index('// Test_StateMonad_runManyTimes\n')
    end = source.index('// Test_StateMonad_act\n', begin)
    region = source[begin:end]
    needle = r"(\Test\StateMonad\majTest_majStatemajMonad_chainmajModifications(60, 0))->{'state'}"
    assert region.count(needle) == 1
    workers = (ROOT / 'workers.php').read_text().removeprefix('<?php\n')
    variants = {}
    for name, replacement in [('baseline', None), ('strict', "(phpurs_probe_state_strict(60, 0))->{'state'}"),
                               ('scalar', 'phpurs_probe_state_scalar(60, 0)')]:
        destination = artifacts / name
        if name != 'baseline':
            for file in output.rglob('*'):
                if file.is_file() and (file.suffix == '.php' or file.name == 'composer.json'):
                    target = destination / 'output' / file.relative_to(output)
                    target.parent.mkdir(parents=True, exist_ok=True)
                    shutil.copy2(file, target)
            transformed = source[:begin] + region.replace(needle, replacement) + source[end:] + '\n' + workers
            assert transformed[:begin] == source[:begin]
            (destination / 'output' / MODULE).write_text(transformed)
        for filename in ['composer.json', 'composer.lock']:
            shutil.copy2(BENCH / filename, destination / filename)
            sources[str(BENCH / filename)] = digest(BENCH / filename)
        shutil.copytree(BENCH / 'vendor', destination / 'vendor', symlinks=True)
        assert driver.vendor_artifacts(destination) == vendor
        driver.composer_inputs(destination, shutil.which('php'), os.environ.copy())
        lint = subprocess.run(['php', '-l', str(destination / 'output' / MODULE)], text=True, capture_output=True)
        assert lint.returncode == 0, lint.stdout + lint.stderr
        current = php_manifest(destination / 'output')
        assert current.keys() == regenerated.keys()
        changed = [file for file in regenerated if regenerated[file] != current[file]]
        assert changed == ([] if name == 'baseline' else [MODULE])
        variants[name] = {'changed': changed, 'phpFiles': len(current),
                          'phpBytes': sum(p.stat().st_size for p in (destination / 'output').rglob('*.php')),
                          'moduleSHA256': current[MODULE]}
        save(artifacts / (name + '-php.json'), current)
    state = {'heads': {name: subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=directory, text=True).strip()
                       for name, directory in [('phpurs', REPO), ('pbo', WORKSPACE / 'purescript-backend-optimizer-phpurs'),
                                               ('bench', WORKSPACE / 'altbak.pub-phpurs')]},
             'modules': len(corefn), 'regeneratedFilesIdentical': len(regenerated), 'variants': variants,
             'publicDefinitionsSHA256': hashlib.sha256(source[:begin].encode()).hexdigest(),
             'standaloneCLI': digest(REPO / 'bin/phpurs.js'), 'sourceFiles': len(sources),
             'artifacts': str(artifacts)}
    assert all(digest(Path(file)) == fingerprint for file, fingerprint in sources.items())
    save(artifacts / 'sources.json', sources)
    save(artifacts / 'vendor.json', vendor)
    save(artifacts / 'active-php.json', active_php)
    save(artifacts / 'preparation.json', state)
    print(json.dumps(state, indent=2))


if __name__ == '__main__':
    main()
