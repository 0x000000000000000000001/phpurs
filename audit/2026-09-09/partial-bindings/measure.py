"""Fresh sequential processes, reversed order, unchanged Bench.purs clock."""
import json
from pathlib import Path
import re
import subprocess
import sys

root = Path(__file__).resolve().parent
mode = sys.argv[1] if len(sys.argv) > 1 else 'bench'
names = sys.argv[2:] or ['baseline', 'integrated']
runner = root.parents[1]/'2026-09-08/r10-integrated/rbtree-runner.php'
flags = ['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1',
         '-d', 'opcache.file_cache=', '-d', 'opcache.file_update_protection=0',
         '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']
expected = ['7','55','202950','100000','20000','125','100000','21536','22','10000000','1200','1000000','202950','5']
rows = []
for number, order in enumerate([names, list(reversed(names))], 1):
    for name in order:
        command = flags + ([str(root/'raw'/name/'output/App/main.mod.php')] if mode == 'full' else [str(runner), str(root/'raw'/name/'output'), mode])
        result = subprocess.run(command, text=True, capture_output=True)
        (root/f'{mode}-{number}-{name}.log').write_text(result.stdout+result.stderr)
        assert result.returncode == 0 and not result.stderr, (name, result.stdout, result.stderr)
        row = {} if mode == 'full' else json.loads(result.stdout.splitlines()[-1])
        if mode != 'full':
            assert row['jit']['on'] and not row['xdebug_loaded']
        row.update(variant=name, round=number, command=command)
        if mode == 'full':
            outputs = re.findall(r'\(Output & Warm-up\)\s*\n([^\n]+)', result.stdout)
            times = [float(t) for t in re.findall(r'([0-9.]+) μs', result.stdout)]
            assert outputs == expected and len(times) == 14, (outputs,times)
            row.update(outputs=outputs, times_us=times, rbtree_ms=times[8]/1000, church_ms=times[6]/1000, total_ms=sum(times)/1000)
        else:
            assert row['result'] == '22'
            row['rbtree_ms'] = row['best_of_ten_us']/1000
        rows.append(row)
        (root/f'{mode}-results.json').write_text(json.dumps(rows, indent=2)+'\n')
        print(json.dumps({k:row[k] for k in ['variant','round','church_ms','rbtree_ms','total_ms','peak_memory_bytes'] if k in row}), flush=True)
