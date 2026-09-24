"""Upper-bound timing under the real benchmark flags (OPcache + JIT 1255)."""
import json
import statistics
import subprocess
from pathlib import Path

root = Path('/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/rbtree-count')

VARIANTS = {
    'original': root / 'output' / 'Test.RBTree' / 'index.php',
    'inplace': root / 'inplace2-clean' / 'output' / 'Test.RBTree' / 'index.php',
}

DRIVER = """<?php
require __PATH__;
for ($i = 0; $i < __WARM__; $i++) { $v = $GLOBALS['Test_RBTree_act'](); if ($v != 22) throw new Exception('bad'); }
$times = [];
for ($round = 0; $round < __ROUNDS__; $round++) {
    $t0 = hrtime(true);
    $v = $GLOBALS['Test_RBTree_act']();
    if ($v != 22) throw new Exception('bad');
    $times[] = (hrtime(true) - $t0) / 1e6;
}
echo json_encode(['times' => $times, 'peak' => memory_get_peak_usage(true) / 1048576.0]);
"""

FLAGS = ['-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1',
         '-d', 'opcache.file_update_protection=0',
         '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']


def run(path):
    script = (DRIVER
              .replace('__PATH__', json.dumps(str(path)))
              .replace('__WARM__', '3')
              .replace('__ROUNDS__', '10'))
    result = subprocess.run(['php'] + FLAGS, input=script, text=True, capture_output=True)
    if result.returncode != 0:
        print('STDOUT:', result.stdout[:500])
        print('STDERR:', result.stderr[:500])
        raise SystemExit(1)
    data = json.loads(result.stdout)
    return data


results = {}
order = ['original', 'inplace', 'inplace', 'original', 'original', 'inplace', 'inplace', 'original']
for index, name in enumerate(order):
    data = run(VARIANTS[name])
    results.setdefault(name, []).append(data)
    print(f"{index+1}/8 {name:>9}: min {min(data['times']):7.3f}  median {statistics.median(data['times']):7.3f}  peak {data['peak']:.0f} MiB")

print()
for name, runs in results.items():
    mins = [min(run['times']) for run in runs]
    medians = [statistics.median(run['times']) for run in runs]
    print(f"{name:>9}: best-of-runs min {min(mins):7.3f} ms | medians {[round(m, 1) for m in medians]}")
