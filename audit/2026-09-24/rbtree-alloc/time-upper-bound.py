"""Rough ABBA timing of the in-place experiment (machine is shared)."""
import json
import re
import shutil
import subprocess
from pathlib import Path

root = Path('/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/rbtree-count')

# Clean the counting instrumentation from the in-place variant for timings.
clean = root / 'inplace2-clean' / 'output'
shutil.rmtree(root / 'inplace2-clean', ignore_errors=True)
shutil.copytree(root / 'inplace2' / 'output', clean)
p = clean / 'Test.RBTree' / 'index.php'
s = p.read_text()
s = re.sub(
    r"(final class Test_RBTree___phpurs_enum_0_T \{[^\n]*?__construct\([^\n]*?\)) \{ \$k = 'Test_RBTree___phpurs_enum_0_T'; \$GLOBALS\['counts'\]\[\$k\] = \(\$GLOBALS\['counts'\]\[\$k\] \?\? 0\) \+ 1; \}",
    r"\1 {}",
    s,
)
assert "'counts'" not in s
p.write_text(s)

VARIANTS = {
    'original': root / 'output' / 'Test.RBTree' / 'index.php',
    'inplace': clean / 'Test.RBTree' / 'index.php',
}

DRIVER = """<?php
require __PATH__;
for ($i = 0; $i < __WARM__; $i++) { $v = $GLOBALS['Test_RBTree_act'](); if ($v != 22) throw new Exception('bad'); }
$best = null;
for ($round = 0; $round < __ROUNDS__; $round++) {
    $t0 = hrtime(true);
    $v = $GLOBALS['Test_RBTree_act']();
    if ($v != 22) throw new Exception('bad');
    $ms = (hrtime(true) - $t0) / 1e6;
    if ($best === null || $ms < $best) { $best = $ms; }
}
echo json_encode(['best_ms' => $best, 'peak' => memory_get_peak_usage(true) / 1048576.0]);
"""


def run(path):
    script = (DRIVER
              .replace('__PATH__', json.dumps(str(path)))
              .replace('__WARM__', '1')
              .replace('__ROUNDS__', '3'))
    result = subprocess.run(
        ['php', '-d', 'opcache.enable_cli=0', '-d', 'memory_limit=2048M'],
        input=script, text=True, capture_output=True,
    )
    if result.returncode != 0:
        print('STDOUT:', result.stdout[:500])
        print('STDERR:', result.stderr[:500])
        raise SystemExit(1)
    return json.loads(result.stdout)


results = {}
order = ['original', 'inplace', 'inplace', 'original']
for index, name in enumerate(order):
    data = run(VARIANTS[name])
    results.setdefault(name, []).append(data)
    print(f"{index+1}/4 {name:>9}: best {data['best_ms']:8.3f} ms  peak {data['peak']:.0f} MiB")

for name, values in results.items():
    best = min(v['best_ms'] for v in values)
    print(f"{name}: best {best:.3f} ms over {len(values)} runs")
