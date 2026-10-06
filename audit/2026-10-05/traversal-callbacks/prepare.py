"""Rebuild a frozen control; specialize one surviving callback site per copy."""
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
VARIANTS = ['baseline', 'list-add', 'array-add', 'array-filter']


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


def region(text, start, end):
    begin = text.index(start)
    finish = text.index(end, begin)
    return begin, finish, text[begin:finish]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    assert not artifacts.exists()
    artifacts.mkdir(parents=True)
    driver = load_driver()
    heads = {name: subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=directory, text=True).strip()
             for name, directory in [('phpurs', REPO), ('pbo', WORKSPACE / 'purescript-backend-optimizer-phpurs'),
                                     ('bench', WORKSPACE / 'altbak.pub-phpurs')]}
    active = BENCH / 'output'
    active_php = php_manifest(active)
    vendor = driver.vendor_artifacts(BENCH)
    assert vendor is not None
    sources = driver.inputs()
    for directory in [REPO / 'src', WORKSPACE / 'purescript-backend-optimizer-phpurs/src']:
        sources.update({str(p): digest(p) for p in directory.rglob('*') if p.is_file() and p.suffix in {'.purs', '.js'}})
    sources.update({str(p): digest(p) for p in (REPO / 'output').glob('*/*.js')})
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
    run = subprocess.run(command, cwd=BENCH, env={**os.environ, 'GOPURS_JOBS': '1', 'PHPURS_TRAVERSAL_AUDIT': str(artifacts)},
                         text=True, capture_output=True, timeout=300)
    (artifacts / 'rebuild.log').write_text(run.stdout + run.stderr)
    assert run.returncode == 0, str(artifacts / 'rebuild.log')
    profiles = [json.loads(line.split(': ', 1)[1]) for line in run.stderr.splitlines()
                if line.startswith('[phpurs] build-profile: ')]
    assert len(profiles) == 1 and profiles[0]['status'] == 'completed'
    assert profiles[0]['phases']['translate']['completed'] == len(corefn)
    regenerated = php_manifest(output)
    assert regenerated == active_php, [name for name in regenerated if regenerated[name] != active_php.get(name)]
    list_source = (output / 'Test.ListOps/index.php').read_text()
    array_source = (output / 'Test.ArrayOps/index.php').read_text()
    assert 'phpurs_probe_' not in list_source + array_source
    _, _, worker = region(list_source, 'function majTest_majListmajOps_foldl(', "$GLOBALS['Test_ListOps_foldl']")
    call = "(($v_0)($v1_1))(($v2_2)->{'value0'})"
    assert worker.count(call) == 1
    worker = worker.replace('majTest_majListmajOps_foldl', 'phpurs_probe_list_add')
    worker = worker.replace(call, r"\Data\Semiring\majData_majSemiring_intmajAdd($v1_1, ($v2_2)->{'value0'})")
    (artifacts / 'list-worker.php').write_text('<?php\n' + worker)
    workers = (ROOT / 'workers.php').read_text().removeprefix('<?php\n')
    variants = {}
    for name in VARIANTS:
        destination = artifacts / name
        changed_module = None
        if name != 'baseline':
            for file in output.rglob('*'):
                if file.is_file() and (file.suffix == '.php' or file.name == 'composer.json'):
                    target = destination / 'output' / file.relative_to(output)
                    target.parent.mkdir(parents=True, exist_ok=True)
                    shutil.copy2(file, target)
            source, module = (list_source, 'ListOps') if name == 'list-add' else (array_source, 'ArrayOps')
            begin, end, consumer = region(source, f'// Test_{module}_sumEvens\n', f'// Test_{module}_describe\n')
            target, replacement = {
                'list-add': (r'\Test\ListOps\majTest_majListmajOps_foldl(', 'phpurs_probe_list_add('),
                'array-add': (r'\Data\Foldable\majData_majFoldable_foldlmajArray(', 'phpurs_probe_array_add('),
                'array-filter': ("($GLOBALS['Data_Array_filterImpl'])(", 'phpurs_probe_array_evens('),
            }[name]
            assert consumer.count(target) == 1
            text = source[:begin] + consumer.replace(target, replacement) + source[end:] + '\n' + (worker if name == 'list-add' else workers)
            changed_module = f'Test.{module}/index.php'
            (destination / 'output' / changed_module).write_text(text)
            lint = subprocess.run(['php', '-l', str(destination / 'output' / changed_module)], text=True, capture_output=True)
            assert lint.returncode == 0, lint.stdout + lint.stderr
        for filename in ['composer.json', 'composer.lock']:
            shutil.copy2(BENCH / filename, destination / filename)
            sources[str(BENCH / filename)] = digest(BENCH / filename)
        shutil.copytree(BENCH / 'vendor', destination / 'vendor', symlinks=True)
        assert driver.vendor_artifacts(destination) == vendor
        driver.composer_inputs(destination, shutil.which('php'), os.environ.copy())
        current = php_manifest(destination / 'output')
        assert current.keys() == regenerated.keys()
        changed = [file for file in regenerated if regenerated[file] != current[file]]
        assert changed == ([] if name == 'baseline' else [changed_module])
        variants[name] = {'changed': changed, 'phpFiles': len(current),
                          'phpBytes': sum(p.stat().st_size for p in (destination / 'output').rglob('*.php'))}
        save(artifacts / (name + '-php.json'), current)
    assert all(digest(Path(file)) == fingerprint for file, fingerprint in sources.items())
    save(artifacts / 'sources.json', sources)
    save(artifacts / 'vendor.json', vendor)
    save(artifacts / 'active-php.json', active_php)
    state = {'heads': heads, 'modules': len(corefn), 'regeneratedFilesIdentical': len(regenerated), 'variants': variants,
             'standaloneCLI': digest(REPO / 'bin/phpurs.js'), 'sourceFiles': len(sources), 'artifacts': str(artifacts)}
    save(artifacts / 'preparation.json', state)
    print(json.dumps(state, indent=2))


if __name__ == '__main__':
    main()
