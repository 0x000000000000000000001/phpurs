"""Attribute the private T allocations to functions and source lines."""
import json
import re
import shutil
import subprocess
from pathlib import Path

root = Path('/private/var/folders/w9/l8bnb22d6c75c401f71djbt00000gn/T/opencode/rbtree-count')
output = root / 'counts-sites' / 'output'
shutil.rmtree(root / 'counts-sites', ignore_errors=True)
shutil.copytree(root / 'output', output)

p = output / 'Test.RBTree' / 'index.php'
lines = p.read_text().split('\n')

new_re = re.compile(r'new \\Test\\RBTree\\Test_RBTree___phpurs_enum_0_T\(')
func_re = re.compile(r'^function (majTest_majRmajBmajTree_[A-Za-z_0-9]+)\(')

current_func = 'top'
out = []
sites = {}
for number, line in enumerate(lines, start=1):
    m = func_re.match(line)
    if m:
        current_func = m.group(1)
    occurrences = len(new_re.findall(line))
    if occurrences and line.lstrip().startswith('$__res = new'):
        key = f'T@{current_func}@{number}'
        sites[key] = occurrences
        indent = line[:len(line) - len(line.lstrip())]
        out.append(f"{indent}$GLOBALS['counts']['{key}'] = ($GLOBALS['counts']['{key}'] ?? 0) + {occurrences};")
    out.append(line)
p.write_text('\n'.join(out))
print('sites instrumented:', len(sites), 'occurrences:', sum(sites.values()))

script = '<?php\nrequire ' + json.dumps(str(p)) + ';\n'
script += "$GLOBALS['counts']=[]; $v=$GLOBALS['Test_RBTree_act'](); "
script += "if ($v != 22) throw new Exception('wrong result: '.var_export($v, true)); "
script += "echo json_encode($GLOBALS['counts']);"
result = subprocess.run(
    ['php', '-d', 'opcache.enable_cli=0', '-d', 'memory_limit=2048M'],
    input=script, text=True, capture_output=True,
)
assert result.returncode == 0, (result.stdout, result.stderr)
assert not result.stderr, result.stderr
counts = json.loads(result.stdout)

by_func = {}
by_site = {}
for key, value in counts.items():
    _, func, line = key.split('@')
    by_func[func] = by_func.get(func, 0) + value
    by_site[key] = value

print('total instrumented T:', sum(counts.values()))
print('--- par fonction ---')
for func, value in sorted(by_func.items(), key=lambda kv: -kv[1]):
    print(f'{value:>10} {func}')
print('--- par site (top 15) ---')
for key, value in sorted(by_site.items(), key=lambda kv: -kv[1])[:15]:
    print(f'{value:>10} {key}')
(root / 'counts-sites.json').write_text(json.dumps({'by_func': by_func, 'by_site': by_site}, indent=2) + '\n')
