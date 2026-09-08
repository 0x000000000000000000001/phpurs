"""Compare fully generated outputs, toggling only the terminal inlining pass."""
import hashlib
import json
import pathlib
import re
import subprocess

root = pathlib.Path(__file__).resolve().parent
runner = root.parent / 'rbtree-after-r0/runner.php'
expected = ['7', '55', '202950', '100000', '20000', '125', '100000',
            '21536', '22', '10000000', '1200', '1000000', '202950', '5']
variants = ['baseline', 'integrated']

def execute(name, mode, log):
    output = root / 'raw' / name / 'output'
    command = ['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1',
               '-d', 'opcache.file_update_protection=0', '-d', 'opcache.jit_buffer_size=128M',
               '-d', 'opcache.jit=1255', str(runner), str(output), mode]
    result = subprocess.run(command, text=True, capture_output=True)
    (root / log).write_text(result.stdout + result.stderr)
    assert result.returncode == 0 and not result.stderr, (name, result.stderr, result.stdout[-2000:])
    row = json.loads(result.stdout.splitlines()[-1])
    assert row['jit']['on'], row
    return row, result.stdout

checks = {name: execute(name, 'validate', f'validate-{name}.log')[0]['result'] for name in variants}
assert checks['baseline'] == checks['integrated'], checks
(root / 'structural-checks.json').write_text(json.dumps(checks, indent=2) + '\n')
rows = []
for number, names in enumerate([variants, list(reversed(variants))], 1):
    for name in names:
        row, stdout = execute(name, 'full', f'full-{number}-{name}.log')
        outputs = re.findall(r'\(Output & Warm-up\)\s*\n([^\n]+)', stdout)
        times = [float(t) for t in re.findall(r'([0-9.]+) μs', stdout)]
        assert outputs == expected and len(times) == 14, (outputs, times)
        row.update(round=number, variant=name, outputs=outputs, best_us=times,
                   total_ms=sum(times)/1000, rbtree_ms=times[8]/1000)
        rows.append(row)
        (root / 'full-results.json').write_text(json.dumps(rows, indent=2) + '\n')
        print(json.dumps({'round': number, 'variant': name, 'total_ms': row['total_ms'], 'rbtree_ms': row['rbtree_ms']}), flush=True)

manifest = {}
for name in variants:
    output = root / 'raw' / name / 'output'
    files = sorted(output.rglob('index.php'))
    tree = (output / 'Test.RBTree/index.php').read_bytes()
    manifest[name] = {'php_modules': len(files), 'php_module_bytes': sum(p.stat().st_size for p in files),
                      'rbtree_bytes': len(tree), 'rbtree_sha256': hashlib.sha256(tree).hexdigest()}
(root / 'sizes.json').write_text(json.dumps(manifest, indent=2) + '\n')
