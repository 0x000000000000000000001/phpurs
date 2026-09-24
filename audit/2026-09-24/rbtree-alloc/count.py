"""Count RBTree allocations and calls on disposable copies (no timings)."""
import json
import re
import shutil
import subprocess
from pathlib import Path

root = Path('/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/rbtree-count')

output = root / 'counts' / 'output'
shutil.rmtree(root / 'counts', ignore_errors=True)
shutil.copytree(root / 'output', output)

p = output / 'Test.RBTree' / 'index.php'
source = p.read_text()

# 1. instrument every generated final class constructor
pattern = r'(final class (Test_RBTree_\w+) \{[^\n]*?__construct\([^\n]*?\)) \{\}'
source, total = re.subn(
    pattern,
    lambda m: m[1] + " { $k = '" + m[2] + "'; $GLOBALS['counts'][$k] = ($GLOBALS['counts'][$k] ?? 0) + 1; }",
    source,
)
print('constructors instrumented:', total)
assert total == 6, total

# 2. instrument the worker/helper functions (private and public variants)
pattern = r'(function (majTest_majRmajBmajTree_(?:__phpurs_enum_0_)?(?:ins|balance|buildmajTree|buildTree|depth|majT))\([^\n]+\) \{)'
source, total = re.subn(
    pattern,
    lambda m: m[1] + "\n$k = '" + m[2] + "'; $GLOBALS['counts'][$k] = ($GLOBALS['counts'][$k] ?? 0) + 1;",
    source,
)
print('functions instrumented:', total)
assert total >= 6, total

p.write_text(source)

script = '<?php\nrequire ' + json.dumps(str(p)) + ';\n'
script += "$GLOBALS['counts']=[]; $v=$GLOBALS['Test_RBTree_act'](); "
script += "if ($v != 22) throw new Exception('wrong result: '.var_export($v, true)); "
script += "echo json_encode($GLOBALS['counts']);"
result = subprocess.run(
    ['php', '-d', 'opcache.enable_cli=0', '-d', 'memory_limit=1024M'],
    input=script, text=True, capture_output=True,
)
assert result.returncode == 0, (result.stdout, result.stderr)
assert not result.stderr, result.stderr
counts = json.loads(result.stdout)
print(json.dumps(counts, indent=2, sort_keys=True))
(root / 'counts.json').write_text(json.dumps(counts, indent=2, sort_keys=True) + '\n')
