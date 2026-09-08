"""Check generated private/public trees and count allocations outside timing."""
import json
from pathlib import Path
import re
import shutil
import subprocess

root = Path(__file__).resolve().parent
checks, counts, sources = {}, {}, {}
for name in ['baseline', 'integrated']:
    original = root/'raw'/name/'output'
    source = (original/'Test.RBTree/index.php').read_text()
    suffix = '__phpurs_enum_0_' if name == 'integrated' else ''
    insert = '\\Test\\RBTree\\majTest_majRmajBmajTree_' + suffix + 'insert'
    run = subprocess.run(['php', '-d', 'opcache.enable_cli=0', str(root/'validate-rbtree-internal.php'), str(original), insert], text=True, capture_output=True)
    assert run.returncode == 0 and not run.stderr, (name, run.stdout, run.stderr)
    checks[name] = json.loads(run.stdout)
    # Instrument copies only. All other module files resolve to the same variant.
    output = root/'raw'/(name+'-counts')
    output.mkdir(exist_ok=True)
    for mod in original.iterdir():
        if mod.name == 'Test.RBTree': shutil.copytree(mod, output/mod.name, dirs_exist_ok=True)
        elif not (output/mod.name).exists():
            (output/mod.name).symlink_to(mod.resolve(), target_is_directory=mod.is_dir())
    pattern = r'(final class (Test_RBTree_\w+) \{[^\n]+__construct\([^\n]*?\)) \{\}'
    source, total = re.subn(pattern, lambda m: m[1]+" { $k = '"+m[2]+"'; $GLOBALS['r10_counts'][$k] = ($GLOBALS['r10_counts'][$k] ?? 0) + 1; }", source)
    assert total == (6 if name == 'integrated' else 4), (name, total)
    pattern = r'(function (majTest_majRmajBmajTree_(?:__phpurs_enum_0_)?(?:ins|balance))\([^\n]+\) \{)'
    source, total = re.subn(pattern, lambda m: m[1]+"\n$k = '"+m[2]+"'; $GLOBALS['r10_counts'][$k] = ($GLOBALS['r10_counts'][$k] ?? 0) + 1;", source)
    assert total == (4 if name == 'integrated' else 2), (name, total)
    (output/'Test.RBTree/index.php').write_text(source)
    run = subprocess.run(['php', '-d', 'opcache.enable_cli=0', str(root/'count-rbtree.php'), str(output)], text=True, capture_output=True)
    assert run.returncode == 0 and not run.stderr, (name, run.stdout, run.stderr)
    counts[name] = json.loads(run.stdout)['counts']
    print(name, counts[name], flush=True)
assert checks['baseline'] == checks['integrated']
b, i = counts['baseline'], counts['integrated']
assert b['Test_RBTree_T'] == i['Test_RBTree___phpurs_enum_0_T'] == 2583932
assert b['Test_RBTree_E'] == i['Test_RBTree___phpurs_enum_0_E'] == 200001
assert b['majTest_majRmajBmajTree_ins'] == i['majTest_majRmajBmajTree___phpurs_enum_0_ins'] == 2283976
assert b['Test_RBTree_R'] == 199978 and b['Test_RBTree_B'] == 299956
assert all(i.get('Test_RBTree_'+c, 0) == 0 for c in ['R','B','E','T'])
assert i.get('majTest_majRmajBmajTree___phpurs_enum_0_balance',0) == 0, 'R9 must still inline balance'
(root/'rbtree-internal-checks.json').write_text(json.dumps(checks, indent=2)+'\n')
(root/'rbtree-counts.json').write_text(json.dumps(counts, indent=2)+'\n')
print('1285 insertions and retained roots agree; color allocations removed; tree allocations unchanged.')
