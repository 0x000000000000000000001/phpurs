"""Sequential A/B/C runs, reversed; existing Bench.purs times and workload."""
import json
import pathlib
import re
import subprocess

root = pathlib.Path(__file__).resolve().parent
variants = ['baseline', 'integrated']
expected = ['7', '55', '202950', '100000', '20000', '125', '100000',
            '21536', '22', '10000000', '1200', '1000000', '202950', '5']
rows = []
for mode in ['bench', 'full']:
    runner = root.parent / ('lazy/runner.php' if mode == 'bench' else 'rbtree-after-r0/runner.php')
    for number, names in enumerate([variants, list(reversed(variants))], 1):
        for name in names:
            command = ['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1',
                       '-d', 'opcache.file_update_protection=0', '-d', 'opcache.jit_buffer_size=128M',
                       '-d', 'opcache.jit=1255', str(runner), str(root / 'raw' / name / 'output'), mode]
            result = subprocess.run(command, capture_output=True, text=True)
            (root / f'{mode}-{number}-{name}.log').write_text(result.stdout + result.stderr)
            assert result.returncode == 0 and not result.stderr, (name, result.stdout[-1000:], result.stderr)
            row = json.loads(result.stdout.splitlines()[-1])
            assert row['jit']['on'] and not row['xdebug_loaded'], row
            row.update(variant=name, round=number, command=command)
            if mode == 'full':
                outputs = re.findall(r'\(Output & Warm-up\)\s*\n([^\n]+)', result.stdout)
                times = [float(t) for t in re.findall(r'([0-9.]+) μs', result.stdout)]
                assert outputs == expected and len(times) == 14, (name, outputs, times)
                row.update(outputs=outputs, best_us=times, total_ms=sum(times)/1000,
                           rbtree_ms=times[8]/1000, lazy_ms=times[11]/1000)
            else:
                assert row['result'] == '1000000'
                row['lazy_ms'] = row['best_of_ten_us']/1000
            rows.append(row)
            (root / 'measurements.json').write_text(json.dumps(rows, indent=2) + '\n')
            print(json.dumps({k: row[k] for k in ['mode', 'round', 'variant', 'lazy_ms', 'total_ms'] if k in row}), flush=True)
