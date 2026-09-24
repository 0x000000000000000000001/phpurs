"""Upper bound v2: direct in-place field writes (no temporary array)."""
import json
import re
import shutil
import subprocess
from pathlib import Path

root = Path('/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/rbtree-count')
work = root / 'inplace2' / 'output'
shutil.rmtree(root / 'inplace2', ignore_errors=True)
shutil.copytree(root / 'output', work)

p = work / 'Test.RBTree' / 'index.php'
lines = p.read_text().split('\n')
current_func = None
out = []
plain = 0
rootblack = 0
for line in lines:
    m = re.match(r'function (majTest_majRmajBmajTree_[A-Za-z_0-9]+)\(', line)
    if m:
        current_func = m.group(1)
    stripped = line.strip()
    # Plain path rebuild: new T(v_0, v1_1, v2_2, v3_3) with the four locals.
    m2 = re.match(r'^(\s*)\$__res = new \\Test\\RBTree\\Test_RBTree___phpurs_enum_0_T\(\$__phpurs_inline_0_0_v_0, \$__phpurs_inline_0_0_v1_1, \$__phpurs_inline_0_0_v2_2, \$__phpurs_inline_0_0_v3_3\);$', line)
    if m2 and current_func and current_func.endswith('_ins'):
        indent = m2.group(1)
        out.append(f"{indent}$v1_1->{{'value0'}} = $__phpurs_inline_0_0_v_0; $v1_1->{{'value1'}} = $__phpurs_inline_0_0_v1_1; $v1_1->{{'value2'}} = $__phpurs_inline_0_0_v2_2; $v1_1->{{'value3'}} = $__phpurs_inline_0_0_v3_3; $__res = $v1_1;")
        plain += 1
        continue
    # Root blackening in insert: new T(1, n->value1, n->value2, n->value3).
    m3 = re.match(r"^(\s*)\$__res = new \\Test\\RBTree\\Test_RBTree___phpurs_enum_0_T\(1, \(\$__local_var_2_0\)->\{'value1'\}, \(\$__local_var_2_0\)->\{'value2'\}, \(\$__local_var_2_0\)->\{'value3'\}\);$", line)
    if m3 and current_func and current_func.endswith('_insert'):
        indent = m3.group(1)
        out.append(f"{indent}$__local_var_2_0->{{'value0'}} = 1; $__res = $__local_var_2_0;")
        rootblack += 1
        continue
    out.append(line)

p.write_text('\n'.join(out))
print('plain rebuild sites replaced:', plain, '| root blackenings:', rootblack)
assert plain >= 4 and rootblack == 1

# verify result and count remaining T allocations
source = p.read_text()
pattern = r'(final class (Test_RBTree___phpurs_enum_0_T) \{[^\n]*?__construct\([^\n]*?\)) \{\}'
source, total = re.subn(pattern, lambda m: m[1] + " { $k = '" + m[2] + "'; $GLOBALS['counts'][$k] = ($GLOBALS['counts'][$k] ?? 0) + 1; }", source)
assert total == 1
p.write_text(source)
script = '<?php\nrequire ' + json.dumps(str(p)) + ';\n'
script += "$GLOBALS['counts']=[]; $v=$GLOBALS['Test_RBTree_act'](); if ($v != 22) throw new Exception('wrong result: '.var_export($v, true)); echo json_encode($GLOBALS['counts']);"
result = subprocess.run(['php', '-d', 'opcache.enable_cli=0', '-d', 'memory_limit=2048M'], input=script, text=True, capture_output=True)
assert result.returncode == 0, (result.stdout, result.stderr)
counts = json.loads(result.stdout)
print('result 22; remaining T allocations:', counts)
