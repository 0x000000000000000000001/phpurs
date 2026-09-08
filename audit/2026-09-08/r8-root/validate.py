"""Check all isolated variants against the exact baseline observations."""
import json
import pathlib
import subprocess

root = pathlib.Path(__file__).resolve().parent
checks = {}
for name in ['baseline', 'root-convert', 'escaping-root']:
    result = subprocess.run(['php', '-d', 'opcache.enable_cli=0', str(root / 'validate.php'),
                             str(root / 'raw' / name / 'output')], capture_output=True, text=True)
    (root / f'validate-{name}.log').write_text(result.stdout + result.stderr)
    assert result.returncode == 0 and not result.stderr, (name, result.stdout, result.stderr)
    checks[name] = json.loads(result.stdout)
    print(name + ': values, captures, Closure/Foreign, curry, exceptions passed')
assert checks['baseline'] == checks['root-convert'] == checks['escaping-root']
(root / 'validation.json').write_text(json.dumps(checks, indent=2) + '\n')
