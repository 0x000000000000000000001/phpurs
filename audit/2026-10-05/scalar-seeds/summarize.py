"""Verify saved evidence and publish the compact audit record."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import subprocess

ROOT = Path(__file__).resolve().parent
REPO = ROOT.parents[2]


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    load = lambda name: json.loads((artifacts / name).read_text())
    result = load('results.json')
    counts = load('counts.json')
    result['baselineState'] = load('baseline.json')
    result['fixtures'] = load('fixtures.json')
    result['workCounts'] = counts
    assert counts['publicBuilderUnchanged']
    assert result['comparisons']['suite'] == {'files': 307, 'changed': []}
    assert result['comparisons']['kernel']['changed'] == ['Main/index.php']
    for filename, fingerprint in load('source-inputs.json').items():
        assert digest(Path(filename)) == fingerprint, filename
    for filename, fingerprint in load('compiler-inputs.json').items():
        assert digest(REPO / filename) == fingerprint, filename
    for variant in ['baseline', 'integrated']:
        assert digest(artifacts / (variant + '.cjs')) == result['compilers'][variant]['sha256']
    for corpus in ['kernel', 'suite']:
        for variant, suffix in [('baseline', 'before'), ('integrated', 'after')]:
            directory = artifacts / corpus / variant / 'output'
            actual = {str(p.relative_to(directory)): digest(p) for p in sorted(directory.rglob('*.php'))}
            assert actual == load(corpus + '-php-' + suffix + '.json')
    codegen = (artifacts / 'codegen.log').read_text()
    assert re.search(r'pass 83\b', codegen) and re.search(r'fail 0\b', codegen)
    result['codegen'] = {'passed': 83, 'failed': 0, 'logSHA256': digest(artifacts / 'codegen.log')}
    result['runtimeImprovementPercent'] = 100 * (1 - result['kernelMediansMs']['integrated'] / result['kernelMediansMs']['baseline'])
    result['sourceSHA256'] = {filename: digest(REPO / filename) for filename in [
        'src/Phpurs/ThunkFusion.purs', 'tests/codegen/thunk-fusion.mjs',
        'tests/passing/ImmediateThunkFusion.purs', 'tests/passing/ImmediateThunkFusion.js',
        'tests/passing/ImmediateThunkFusion.php']}
    result['standaloneCLI'] = {'bytes': (REPO / 'bin/phpurs.js').stat().st_size,
                               'sha256': digest(REPO / 'bin/phpurs.js')}
    result['versions'] = {name: subprocess.check_output([name, '--version'], text=True).strip()
                          for name in ['node', 'purs', 'spago', 'php']}
    result['artifacts'] = str(artifacts)
    (ROOT / 'results.json').write_text(json.dumps(result, indent=2) + '\n')
    print('Verified evidence; kernel median reduction:', result['runtimeImprovementPercent'], '%')


if __name__ == '__main__':
    main()
