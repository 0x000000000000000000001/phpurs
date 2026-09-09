"""Verify experiment scope and preserve the active output and generator."""
import hashlib
import json
from pathlib import Path

root = Path(__file__).resolve().parent
backend = root.parents[2]
active = backend.parents[1]/'altbak.pub-phpurs/run/bak/php/output'
inputs = json.loads((root/'inputs.json').read_text())
actual = {str(p.relative_to(active)): hashlib.sha256(p.read_bytes()).hexdigest()
          for p in sorted(active.rglob('*.php'))}
assert actual == inputs['php_files'], 'Active generated PHP changed'
sources = {str(p.relative_to(backend)): hashlib.sha256(p.read_bytes()).hexdigest()
           for p in sorted((backend/'src').rglob('*')) if p.is_file()}
assert sources == inputs['backend_sources'], 'Generator sources changed'
rows = {}
for name in ['baseline', 'nullable-leaves', 'null-checks']:
    output = root/'raw'/name/'output'
    files = {str(p.relative_to(output)): hashlib.sha256(p.read_bytes()).hexdigest()
             for p in sorted(output.rglob('*.php'))}
    assert files.keys() == actual.keys()
    changed = [rel for rel in files if files[rel] != actual[rel]]
    assert changed == ([] if name == 'baseline' else ['Test.RBTree/index.php'])
    public = (output/'Test.RBTree/index.php').read_text().split('// Test_RBTree_act\n')[0]
    original = (active/'Test.RBTree/index.php').read_text().split('// Test_RBTree_act\n')[0]
    assert public == original
    rows[name] = {'changed_php_files': changed, 'public_prefix_identical': True}
result = {'active_php_files_unchanged': len(actual),
          'active_modules_unchanged': sum(rel.endswith('/index.php') for rel in actual),
          'generator_files_unchanged': len(sources), 'variants': rows}
(root/'state.json').write_text(json.dumps(result, indent=2)+'\n')
print(json.dumps(result, indent=2))
