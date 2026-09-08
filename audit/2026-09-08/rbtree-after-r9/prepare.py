"""Representation diagnostics on snapshots; never rewrite active generated PHP."""
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess

root = Path(__file__).resolve().parent
baseline = root / 'raw/baseline'
source = (root / 'generated-RBTree.php').read_text()
active = Path('/Users/0x1/Documents/htdocs/altbak.pub-phpurs/run/bak/php/output')
expected = json.loads((root/'inputs.json').read_text())['modules']
actual = {str(p.relative_to(active)): hashlib.sha256(p.read_bytes()).hexdigest()
          for p in sorted(active.rglob('index.php'))}
assert actual == expected, 'Active output changed; restore the audited revisions/output before reproducing.'
baseline.mkdir(parents=True, exist_ok=True)
for module in active.iterdir():
    if module.name in ['App', 'Test.RBTree']:
        shutil.copytree(module, baseline/module.name, dirs_exist_ok=True)
    elif not (baseline/module.name).exists():
        (baseline/module.name).symlink_to(module, target_is_directory=module.is_dir())
split = source.index('// Test_RBTree_T_closure')
prefix = '\\Test\\RBTree\\Test_RBTree_'
variants = {'baseline': source}
variants['singletons'] = source[:split] + re.sub(
    r'new \\Test\\RBTree\\Test_RBTree_([RBE])\(\)',
    lambda m: "$GLOBALS['Test_RBTree_" + m[1] + "']", source[split:])

def scalar_constructors(text, constructors):
    for ctor, scalar in constructors.items():
        text = text.replace('new ' + prefix + ctor + '()', scalar)
        text = text.replace(' instanceof ' + prefix + ctor, ' === ' + scalar)
    return text

variants['scalar-color'] = scalar_constructors(source, {'R': '0', 'B': '1'})
variants['scalar-nullaries'] = scalar_constructors(source, {'R': '0', 'B': '1', 'E': 'null'})
# This extra control uses the closed Tree domain to replace its remaining T tests.
variants['null-checks'] = variants['scalar-nullaries'].replace(
    ' instanceof ' + prefix + 'T', ' !== null')

manifest = {}
for name, text in variants.items():
    output = root / 'raw' / name
    output.mkdir(exist_ok=True)
    if name != 'baseline':
        for module in baseline.iterdir():
            if module.name in ['App', 'Test.RBTree']:
                shutil.copytree(module, output / module.name, dirs_exist_ok=True)
            elif not (output / module.name).exists():
                (output / module.name).symlink_to(module.resolve(), target_is_directory=module.is_dir())
    path = output / 'Test.RBTree/index.php'
    path.write_text(text)
    subprocess.run(['php', '-l', str(path)], check=True, capture_output=True)
    manifest[name] = {'bytes': len(text.encode()), 'sha256': hashlib.sha256(text.encode()).hexdigest()}
(root / 'variants.json').write_text(json.dumps(manifest, indent=2) + '\n')
print('Prepared:', ', '.join(variants), flush=True)
