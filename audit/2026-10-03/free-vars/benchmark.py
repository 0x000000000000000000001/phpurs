"""Regenerate the frozen pure benchmark workspace and validate its 14 values."""
import argparse
import contextlib
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
from types import SimpleNamespace


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--fixture', type=Path, required=True)
    parser.add_argument('--driver', type=Path, required=True)
    parser.add_argument('--before', type=Path, required=True)
    parser.add_argument('--after', type=Path, required=True)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    source, artifacts = args.fixture.resolve(), args.artifacts.resolve()
    assert not artifacts.exists()
    spec = importlib.util.spec_from_file_location('php_driver', args.driver.resolve())
    driver = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(driver)
    # CoreFn uses ../../../../../../phpurs/... for sibling-library FFI. Preserve
    # the original workspace depth, including its sibling link, in this copy.
    suffix = Path('altbak.pub-phpurs/run/bak/php/modes/pure')
    assert source.parts[-6:] == suffix.parts
    tree = artifacts / 'tree'
    work = tree / suffix
    work.parent.mkdir(parents=True)
    (tree / 'phpurs').symlink_to((source.parents[5] / 'phpurs').resolve(), target_is_directory=True)
    shutil.copytree(source, work, symlinks=True,
                    ignore=shutil.ignore_patterns('.purmeta', '.phpurs-cache', 'opcache', 'logs'))
    php = shutil.which('php')
    assert php
    env = {**os.environ, 'GOPURS_JOBS': '1'}
    expected_vendor = driver.vendor_artifacts(source)
    assert expected_vendor is not None and driver.vendor_artifacts(work) == expected_vendor
    expected_artifacts = driver.artifacts(source)
    assert driver.artifacts(work) == expected_artifacts

    def inputs(directory):
        corefn = {str(p.relative_to(directory)): driver.digest(p)
                  for p in sorted((directory / 'output').glob('*/corefn.json'))}
        ffi = {}
        for name in corefn:
            module = json.loads((directory / name).read_text())
            module_path = Path(module['modulePath']).with_suffix('.php')
            target = directory / module_path
            if target.is_file():
                ffi[str(module_path)] = driver.digest(target)
        return {'corefn': corefn, 'ffi': ffi, 'driver': driver.inputs()}

    original = inputs(work)
    assert original == inputs(source)
    driver.write_json(artifacts / 'inputs.json', original)
    driver.write_json(artifacts / 'expected-artifacts.json', expected_artifacts)
    results = {'php': subprocess.check_output([php, '--version'], text=True).strip(),
               'phpFlags': driver.PHP_FLAGS, 'trials': {}}
    for label in ['before', 'after']:
        compiler = getattr(args, label).resolve()
        with (artifacts / (label + '-build.log')).open('w') as log:
            subprocess.run(['node', str(compiler), '--main', 'App', '--no-cache'],
                           cwd=work, env=env, stdout=log, stderr=subprocess.STDOUT, check=True)
        assert inputs(work) == original
        assert driver.artifacts(work) == expected_artifacts
        assert driver.vendor_artifacts(work) == expected_vendor
        # Validate locked requirements and PHP/Composer configuration without
        # installing or updating any dependencies in this copied fixture.
        driver.composer_inputs(work, php, env)
        opcache = work / 'opcache'
        if opcache.exists():
            shutil.rmtree(opcache)
        opcache.mkdir()
        with (artifacts / (label + '-runtime.log')).open('w') as log, contextlib.redirect_stdout(log):
            driver.execute(php, 'App', work, env, SimpleNamespace(mode='pure', test=None, expected=None))
        runtime = json.loads((work / 'results.json').read_text())
        assert runtime['values_validated'] and len(runtime['values']) == 14
        results['trials'][label] = {'compilerSHA256': driver.digest(compiler),
                                   'artifacts': len(expected_artifacts),
                                   'generatedPhpFiles': len(list((work / 'output').rglob('*.php'))),
                                   'results': runtime}
        driver.write_json(artifacts / 'results.json', results)
        print(label, '14 validated values;', len(expected_artifacts), 'identical artifacts', flush=True)
    assert inputs(work) == inputs(source) == original
    assert driver.artifacts(source) == expected_artifacts
    results['inputsUnchanged'] = True
    driver.write_json(artifacts / 'results.json', results)


if __name__ == '__main__':
    main()
