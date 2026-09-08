"""Count actual constructors/calls separately from all timing measurements."""
import json
import pathlib
import re
import subprocess

root = pathlib.Path(__file__).resolve().parent
rows = {}
for name in ['baseline', 'integrated']:
    original = root / 'raw' / name / 'output'
    output = root / 'raw' / (name + '-counts')
    output.mkdir(exist_ok=True)
    for module in original.iterdir():
        if module.name != 'Test.RBTree' and not (output / module.name).exists():
            (output / module.name).symlink_to(module.resolve(), target_is_directory=module.is_dir())
    source = (original / 'Test.RBTree/index.php').read_text()
    source = source.replace('final class Test_RBTree_R', "$GLOBALS['rbtree_audit_counts'] = [];\nfinal class Test_RBTree_R", 1)
    for ctor in ['R', 'B', 'E', 'T']:
        pattern = r'(final class Test_RBTree_' + ctor + r' \{[^\n]+__construct\([^\n]+?\)) \{\}'
        # Nullary constructors have an empty argument list.
        pattern = pattern.replace(r'[^\n]+?\)', r'[^\n]*?\)')
        source, count = re.subn(pattern, lambda m: m[1] + " { $GLOBALS['rbtree_audit_counts']['" + ctor + "'] = ($GLOBALS['rbtree_audit_counts']['" + ctor + "'] ?? 0) + 1; }", source)
        assert count == 1, (ctor, count)
    for fn in ['ins', 'balance']:
        pattern = r'(function majTest_majRmajBmajTree_' + fn + r'\([^\n]+\) \{)'
        source, count = re.subn(pattern, lambda m: m[1] + "\n$GLOBALS['rbtree_audit_counts']['" + fn + "'] = ($GLOBALS['rbtree_audit_counts']['" + fn + "'] ?? 0) + 1;", source)
        assert count == 1, (fn, count)
    (output / 'Test.RBTree').mkdir(exist_ok=True)
    (output / 'Test.RBTree/index.php').write_text(source)
    result = subprocess.run(['php', '-d', 'opcache.enable_cli=0', str(root.parent / 'rbtree-after-r0/runner.php'), str(output), 'single'], text=True, capture_output=True)
    assert result.returncode == 0 and not result.stderr, result.stdout + result.stderr
    (root / (name + '-counts.log')).write_text(result.stdout)
    rows[name] = json.loads(result.stdout.splitlines()[-1])['audit_counts']
assert all(rows['baseline'][k] == rows['integrated'][k] for k in ['R','B','E','T','ins']), rows
assert rows['baseline']['balance'] == 2183976 and rows['integrated'].get('balance',0) == 0, rows
(root / 'allocation-counts.json').write_text(json.dumps(rows, indent=2) + '\n')
print(json.dumps(rows, indent=2))
