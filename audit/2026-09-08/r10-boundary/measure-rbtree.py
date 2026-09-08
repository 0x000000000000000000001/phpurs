"""Sequential fresh-process comparisons using the unchanged Bench.purs clock."""
import json
from pathlib import Path
import re
import subprocess
import sys

root = Path(__file__).resolve().parent
mode = sys.argv[1] if len(sys.argv) > 1 else 'bench'
names = sys.argv[2:] or ['baseline', 'private']
flags = ['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1',
         '-d', 'opcache.file_cache=', '-d', 'opcache.file_update_protection=0',
         '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']
expected = ['7', '55', '202950', '100000', '20000', '125', '100000',
            '21536', '22', '10000000', '1200', '1000000', '202950', '5']
checks = {}
for name in names:
    result = subprocess.run(flags + [str(root/'validate-rbtree-public.php'), str(root/'raw/rbtree'/name)], text=True, capture_output=True)
    assert result.returncode == 0 and not result.stderr, (name, result.stdout, result.stderr)
    checks[name] = json.loads(result.stdout)
assert all(v == next(iter(checks.values())) for v in checks.values())
(root / ('rbtree-validation-' + mode + '.json')).write_text(json.dumps(checks, indent=2) + '\n')
print('Canonical trees and retained roots agree.', flush=True)
rows = []
for round_number, order in enumerate([names, list(reversed(names))], 1):
    for name in order:
        command = flags + [str(root/'rbtree-runner.php'), str(root/'raw/rbtree'/name), mode]
        result = subprocess.run(command, text=True, capture_output=True)
        (root / f'rbtree-{mode}-{round_number}-{name}.log').write_text(result.stdout + result.stderr)
        assert result.returncode == 0 and not result.stderr, (name, result.stdout, result.stderr)
        row = json.loads(result.stdout.splitlines()[-1])
        assert row['jit']['on'] and not row['xdebug_loaded'], row
        row.update(variant=name, round=round_number, command=command)
        if mode == 'full':
            outputs = re.findall(r'\(Output & Warm-up\)\s*\n([^\n]+)', result.stdout)
            times = [float(t) for t in re.findall(r'([0-9.]+) μs', result.stdout)]
            assert outputs == expected and len(times) == 14, (outputs, times)
            row.update(outputs=outputs, times_us=times, rbtree_ms=times[8]/1000, total_ms=sum(times)/1000)
        rows.append(row)
        (root / ('rbtree-' + mode + '-results.json')).write_text(json.dumps(rows, indent=2) + '\n')
        print(json.dumps({key: row[key] for key in ['variant', 'round', 'best_of_ten_us', 'peak_memory_bytes', 'rbtree_ms', 'total_ms'] if key in row}), flush=True)
