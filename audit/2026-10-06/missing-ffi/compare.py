"""Compare frozen compilers on identical TAST/FFI and validate missing-FFI errors."""
import argparse
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess

ROOT = Path(__file__).resolve().parent
spec = importlib.util.spec_from_file_location('audit_helpers', ROOT.parent / 'callback-specialization/prepare.py')
helpers = importlib.util.module_from_spec(spec)
spec.loader.exec_module(helpers)
REPO, WORKSPACE, BENCH = helpers.REPO, helpers.WORKSPACE, helpers.BENCH
digest, save, php_manifest = helpers.digest, helpers.save, helpers.php_manifest
FLAGS = ['-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1', '-d', 'opcache.file_cache=',
         '-d', 'opcache.file_update_protection=0', '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    parser.add_argument('--before', type=Path, required=True)
    parser.add_argument('--after', type=Path, default=REPO / 'bin/phpurs.js')
    parser.add_argument('--control', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    artifacts.mkdir(parents=True, exist_ok=False)
    driver = helpers.load_driver()
    environment = {**os.environ, 'GOPURS_JOBS': '1', 'TMPDIR': str(artifacts.parent.parent)}
    sources = driver.inputs()
    active = BENCH / 'output'
    active_php = php_manifest(active)
    vendor = driver.vendor_artifacts(BENCH)
    assert vendor is not None
    corefn = sorted(active.glob('*/corefn.json'))
    for file in corefn:
        sources[str(file)] = digest(file)
        data = json.loads(file.read_text())
        assert 'typeTable' in data
        foreign = (BENCH / Path(data['modulePath']).with_suffix('.php')).resolve()
        if foreign.is_file():
            sources[str(foreign)] = digest(foreign)
    for directory in [REPO / 'src', WORKSPACE / 'purescript-backend-optimizer-phpurs/src']:
        sources.update({str(p): digest(p) for p in directory.rglob('*') if p.is_file() and p.suffix in {'.purs', '.js'}})

    def run(label, command, cwd=REPO):
        result = subprocess.run(command, cwd=cwd, env=environment, text=True, capture_output=True, timeout=300)
        log = artifacts / (label + '.log')
        log.write_text(result.stdout + result.stderr)
        assert result.returncode == 0, str(log)
        return result

    manifests, variants, compilers = {}, {}, {}
    for variant, compiler in [('before', args.before), ('after', args.after)]:
        frozen = artifacts / (variant + ('.cjs' if variant == 'before' else '.mjs'))
        shutil.copy2(compiler, frozen)
        compilers[variant] = {'file': str(frozen), 'sha256': digest(frozen)}
        directory = artifacts / variant
        output = directory / 'output'
        for file in corefn:
            target = output / file.relative_to(active)
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(file, target)
        built = run(variant + '-build', ['node', str(frozen), '--no-cache', '--main', 'App', '--output', str(output), '--profile-build'], BENCH)
        profile, = [json.loads(line.split(': ', 1)[1]) for line in built.stderr.splitlines() if line.startswith('[phpurs] build-profile: ')]
        assert profile['status'] == 'completed' and profile['phases']['translate']['completed'] == len(corefn)
        for filename in ['composer.json', 'composer.lock']:
            sources[str(BENCH / filename)] = digest(BENCH / filename)
            shutil.copy2(BENCH / filename, directory / filename)
        shutil.copytree(BENCH / 'vendor', directory / 'vendor', symlinks=True)
        assert driver.vendor_artifacts(directory) == vendor
        driver.composer_inputs(directory, shutil.which('php'), environment)
        manifests[variant] = php_manifest(output)
        save(artifacts / (variant + '-php.json'), manifests[variant])
        variants[variant] = {'phpFiles': len(manifests[variant]), 'phpBytes': sum(p.stat().st_size for p in output.rglob('*.php'))}
        assert all(digest(output / p.relative_to(active)) == digest(p) for p in corefn)
    assert manifests['before'] == php_manifest(args.control.resolve()), 'baseline differs from the preceding integrated compiler'
    assert manifests['before'].keys() == manifests['after'].keys()
    changed = [p for p in manifests['before'] if manifests['before'][p] != manifests['after'][p]]
    for file in changed:
        run('lint-' + file.split('/')[0], ['php', '-l', str(artifacts / 'after/output' / file)])
    assert all('public function __invoke(...$args) { return $this; }' not in p.read_text()
               for p in (artifacts / 'after/output').rglob('*.php'))

    workloads = []
    for variant in ['before', 'after']:
        for module, expected in [('WorkloadArrayInt', '35406173408'), ('WorkloadMap', '400040000'), ('WorkloadArrayNumber', None)]:
            output = artifacts / variant / 'output'
            code = f"require {str(output / ('Test.' + module + '/index.php'))!r}; "
            code += f"try {{$result=['value'=>(string)$GLOBALS['Test_{module}_act']()];}} catch (\\Throwable $e) {{$result=['error'=>get_class($e),'message'=>$e->getMessage()];}}"
            if expected is None:
                code += "$result['sumProbe']=$GLOBALS['Test_WorkloadArrayNumber_sumValues']([1.0,2.0,3.0,4.0]);"
                code += "$result['filterProbe']=$GLOBALS['Test_WorkloadArrayNumber_sumFiltered']([1.0,2.0,3.0,4.0]);"
            result = run(variant + '-' + module, ['php', *FLAGS, '-r', code + 'echo json_encode($result), PHP_EOL;'])
            assert not result.stderr
            value = json.loads(result.stdout)
            if expected is not None:
                assert value == {'value': expected}, value
            else:
                assert value['sumProbe'] == value['filterProbe'] == 10
                if variant == 'before':
                    assert value['error'] == 'TypeError' and 'majData_majNumber_floor(): Return value' in value['message']
                else:
                    assert value['error'] == 'RuntimeException' and value['message'] == 'Missing PHP FFI export: Data.Number.floor'
            workloads.append({'variant': variant, 'module': module, **value})

    suite = []
    order = ['before', 'after', 'after', 'before']
    for trial, variant in enumerate(order):
        directory = artifacts / variant
        assert php_manifest(directory / 'output') == manifests[variant]
        assert driver.vendor_artifacts(directory) == vendor
        result = run(f'suite-{trial + 1}-{variant}', ['php', *FLAGS, str(directory / 'output/App/main.mod.php')])
        assert not result.stderr
        value = driver.validate_output(result.stdout, 'pure', None, None)
        assert value['values_validated'] and len(value['values']) == 14
        suite.append({'variant': variant, **value})

    assert php_manifest(active) == active_php
    assert all(digest(Path(file)) == value for file, value in sources.items())
    assert all(digest(Path(info['file'])) == info['sha256'] for info in compilers.values())
    save(artifacts / 'sources.json', sources)
    save(artifacts / 'vendor.json', vendor)
    save(artifacts / 'active-php.json', active_php)
    result = {'artifacts': str(artifacts), 'modules': len(corefn), 'variants': variants, 'changed': changed,
              'compilers': compilers, 'sourceFilesVerified': len(sources), 'workloads': workloads,
              'phpFlags': FLAGS, 'order': order, 'suite': suite,
              'heads': {name: subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=directory, text=True).strip()
                        for name, directory in [('phpurs', REPO), ('pbo', WORKSPACE / 'purescript-backend-optimizer-phpurs'), ('bench', WORKSPACE / 'altbak.pub-phpurs')]}}
    save(artifacts / 'results.json', result)
    print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
