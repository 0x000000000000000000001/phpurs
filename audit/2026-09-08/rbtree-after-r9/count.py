"""Actual constructor/call counts, in separate processes with JIT disabled."""
import json
from pathlib import Path
import re
import subprocess

root = Path(__file__).resolve().parent
rows = {}
for name in json.loads((root/'variants.json').read_text()):
    original = root/'raw'/name
    output = root/'raw'/(name+'-counts')
    output.mkdir(exist_ok=True)
    for module in original.iterdir():
        if module.name != 'Test.RBTree' and not (output/module.name).exists():
            (output/module.name).symlink_to(module.resolve(), target_is_directory=module.is_dir())
    source = (original/'Test.RBTree/index.php').read_text()
    source = source.replace('final class Test_RBTree_R', "$GLOBALS['rbtree_audit_counts'] = array_fill_keys(['R','B','E','T','ins','balance'], 0);\nfinal class Test_RBTree_R", 1)
    for ctor in ['R', 'B', 'E', 'T']:
        pattern = r'(final class Test_RBTree_' + ctor + r' \{[^\n]+__construct\([^\n]*?\)) \{\}'
        source, count = re.subn(pattern, lambda m: m[1] + " { $GLOBALS['rbtree_audit_counts']['" + ctor + "']++; }", source)
        assert count == 1, (name, ctor, count)
    for fn in ['ins', 'balance']:
        pattern = r'(function majTest_majRmajBmajTree_' + fn + r'\([^\n]+\) \{)'
        source, count = re.subn(pattern, lambda m: m[1] + "\n$GLOBALS['rbtree_audit_counts']['" + fn + "']++;", source)
        assert count == 1, (name, fn, count)
    (output/'Test.RBTree').mkdir(exist_ok=True)
    (output/'Test.RBTree/index.php').write_text(source)
    command = ['php', '-d', 'opcache.enable_cli=0', str(root/'runner.php'), str(output), 'single']
    result = subprocess.run(command, text=True, capture_output=True)
    assert result.returncode == 0 and not result.stderr, (name, result.stdout, result.stderr)
    (root/(name+'-counts.log')).write_text(result.stdout)
    rows[name] = json.loads(result.stdout.splitlines()[-1])['audit_counts']
    print(name, rows[name], flush=True)
assert all(r['T'] == 2583932 and r['ins'] == 2283976 and r['balance'] == 0 for r in rows.values())
assert rows['scalar-color']['R'] == rows['scalar-color']['B'] == 0
assert all(rows[name][k] == 0 for name in ['scalar-nullaries','null-checks'] for k in ['R','B','E'])
(root/'allocation-counts.json').write_text(json.dumps(rows, indent=2)+'\n')
