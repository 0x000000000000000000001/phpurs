"""Compare full Bench.purs runs against isolated RBTree module copies."""
import json
import pathlib
import re
import shutil
import subprocess

root = pathlib.Path(__file__).resolve().parent
original = pathlib.Path('/Users/0x1/Documents/htdocs/altbak.pub-phpurs/run/bak/php/output')
variants = ['baseline', 'inline-balance']
expected = ['7', '55', '202950', '100000', '20000', '125', '100000',
            '21536', '22', '10000000', '1200', '1000000', '202950', '5']
for name in variants:
    app = root / 'raw' / name / 'App'
    if app.is_symlink(): app.unlink()
    shutil.copytree(original / 'App', app, dirs_exist_ok=True)
vendor = root / 'raw/vendor'
if not vendor.exists(): vendor.symlink_to(original.parent / 'vendor', target_is_directory=True)

rows = []
for number, names in enumerate([variants, list(reversed(variants))], 1):
    for name in names:
        command = ['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1',
                   '-d', 'opcache.file_update_protection=0', '-d', f'opcache.file_cache={root / "raw/opcache"}',
                   '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255',
                   str(root / 'runner.php'), str(root / 'raw' / name), 'full']
        result = subprocess.run(command, text=True, capture_output=True)
        (root / f'full-{number}-{name}.log').write_text(result.stdout + result.stderr)
        assert result.returncode == 0 and not result.stderr, (name, result.stderr, result.stdout[-2000:])
        outputs = re.findall(r'\(Output & Warm-up\)\s*\n([^\n]+)', result.stdout)
        times = [float(t) for t in re.findall(r'([0-9.]+) μs', result.stdout)]
        assert outputs == expected and len(times) == 14, (outputs, times)
        row = json.loads(result.stdout.splitlines()[-1])
        row.update(round=number, variant=name, outputs=outputs, best_us=times,
                   total_ms=sum(times)/1000, rbtree_ms=times[8]/1000)
        rows.append(row)
        (root / 'full-results.json').write_text(json.dumps(rows, indent=2) + '\n')
        print(json.dumps({'round': number, 'variant': name, 'total_ms': row['total_ms'], 'rbtree_ms': row['rbtree_ms']}), flush=True)
