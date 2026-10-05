"""Freeze existing PHP CoreFn, adjacent FFI and Composer inputs without rebuilding applications."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import subprocess

REPO = Path(__file__).resolve().parents[3]
WORKSPACE = REPO.parents[1]
BENCH_SUFFIX = Path('altbak.pub-phpurs/run/bak/php/modes/pure')


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def save(path, value):
    path.write_text(json.dumps(value, indent=2) + '\n')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    assert not artifacts.exists()
    snapshot = artifacts / 'snapshot'
    snapshot.mkdir(parents=True)
    captured = {}

    def copy(source, target):
        target = target.resolve()
        assert target.is_relative_to(snapshot), target
        data = source.read_bytes()
        fingerprint = hashlib.sha256(data).hexdigest()
        name = str(target.relative_to(snapshot))
        if name in captured:
            assert captured[name]['sha256'] == fingerprint, name
        else:
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(data)
            captured[name] = {'source': str(source), 'sha256': fingerprint, 'bytes': len(data)}

    projects = {}
    for name, source, output, spago in [
        ('b8x', WORKSPACE / 'b8x', WORKSPACE / 'b8x/run/bak/php/output', WORKSPACE / 'b8x/run/bak/php/.spago'),
        ('bench', WORKSPACE / BENCH_SUFFIX, WORKSPACE / BENCH_SUFFIX / 'output', WORKSPACE / BENCH_SUFFIX / '.spago'),
    ]:
        suffix = Path('b8x') if name == 'b8x' else BENCH_SUFFIX
        target = snapshot / suffix
        resolve = lambda relative: spago / Path(*relative.parts[1:]) if relative.parts[0] == '.spago' else source / relative
        modules = []
        adjacent = []
        for file in sorted(output.glob('*/corefn.json')):
            data = json.loads(file.read_text())
            assert all(key in data for key in ['typeTable', 'dataDecls', 'classDecls'])
            module = '.'.join(data['moduleName'])
            assert module == file.parent.name
            module_path = Path(data['modulePath'])
            assert not module_path.is_absolute()
            copy(file, target / 'output' / module / 'corefn.json')
            modules.append(module)
            foreign = module_path.with_suffix('.php')
            if data.get('foreign') and resolve(foreign).is_file():
                copy(resolve(foreign), target / foreign)
                adjacent.append(str(foreign))
            # Composer roots inferred by PackagePaths are ancestors of source
            # paths. Capture each existing manifest, including the project root.
            for parent in module_path.parents:
                if not parent.parts or parent == Path('..'):
                    continue
                candidate = parent / 'composer.json'
                if resolve(candidate).is_file():
                    copy(resolve(candidate), target / candidate)
        assert modules
        config = WORKSPACE / 'b8x/run/bak/php/spago.php.yaml' if name == 'b8x' else source / 'spago.yaml'
        copy(config, target / 'spago.yaml')
        if name == 'bench':
            for file in ['composer.json', 'composer.lock']:
                copy(source / file, target / file)
            shutil.copytree(source / 'vendor', artifacts / 'bench-vendor', symlinks=True)
        projects[name] = {'relativeRoot': str(suffix), 'modules': modules, 'adjacentFfi': adjacent}
    # Empty package directories do not provide FFI; capture their small manifest
    # if one is consulted through the shared .spago package-root discovery.
    for name, data in projects.items():
        source = WORKSPACE / data['relativeRoot']
        spago = WORKSPACE / 'b8x/run/bak/php/.spago' if name == 'b8x' else source / '.spago'
        for package in spago.iterdir():
            if not package.is_dir():
                continue
            roots = [p for p in package.iterdir() if p.is_dir() and p.name.startswith('v')] or [package]
            for root in roots:
                if (root / 'composer.json').is_file():
                    copy(root / 'composer.json', snapshot / data['relativeRoot'] / '.spago' / root.relative_to(spago) / 'composer.json')
    for record in captured.values():
        assert digest(Path(record['source'])) == record['sha256'], record['source']
    shutil.copy2(REPO / 'bin/phpurs.js', artifacts / 'backend.mjs')
    shutil.copy2(REPO / 'audit/2026-10-01/build-scenarios/count-io.mjs', artifacts / 'count-io.mjs')
    manifest = {name: row['sha256'] for name, row in sorted(captured.items())}
    save(artifacts / 'inputs.json', captured)
    save(artifacts / 'preparation.json', {
        'projects': projects, 'inputFiles': len(captured),
        'inputManifestSHA256': hashlib.sha256(json.dumps(manifest, sort_keys=True).encode()).hexdigest(),
        'compilerSHA256': digest(artifacts / 'backend.mjs'),
        'phpursHead': subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=REPO, text=True).strip(),
        'pboHead': subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=WORKSPACE / 'purescript-backend-optimizer-phpurs', text=True).strip(),
        'node': subprocess.check_output(['node', '--version'], text=True).strip(),
    })
    print('Frozen:', len(captured), 'inputs;', {name: len(p['modules']) for name, p in projects.items()})
    print('Compiler SHA256:', digest(artifacts / 'backend.mjs'))


if __name__ == '__main__':
    main()
