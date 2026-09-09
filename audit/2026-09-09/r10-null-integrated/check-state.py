"""Check regenerated controls against the pre-integration snapshot and active PHP."""
import hashlib
import json
from pathlib import Path

root = Path(__file__).resolve().parent
backend = root.parents[2]
active = backend.parents[1]/'altbak.pub-phpurs/run/bak/php/output'
old = json.loads((root.parent/'r10-null/inputs.json').read_text())['php_files']
def hashes(output):
    return {str(p.relative_to(output)): hashlib.sha256(p.read_bytes()).hexdigest()
            for p in sorted(output.rglob('*.php'))}
control = hashes(root/'raw/baseline/output')
integrated = hashes(root/'raw/integrated/output')
assert control == old, 'Disabled feature must recover the previous output'
assert control.keys() == integrated.keys()
changed = [rel for rel in control if control[rel] != integrated[rel]]
assert changed == ['Test.RBTree/index.php'], changed
assert hashes(active) == integrated, 'Active output must use the integrated generator'
a = (root/'raw/baseline/output/Test.RBTree/index.php').read_text()
b = (root/'raw/integrated/output/Test.RBTree/index.php').read_text()
assert a.split('// Test_RBTree_act\n')[0] == b.split('// Test_RBTree_act\n')[0]
assert 'new \\Test\\RBTree\\Test_RBTree___phpurs_enum_0_E(' not in b
assert 'instanceof \\Test\\RBTree\\Test_RBTree___phpurs_enum_0_' not in b
assert "$GLOBALS['Test_RBTree___phpurs_enum_0_" not in b
result = {'php_files_checked':len(control), 'modules_checked':sum(p.endswith('/index.php') for p in control),
          'changed_modules':changed, 'control_matches_previous_output':True,
          'active_matches_integrated':True, 'public_prefix_identical':True,
          'baseline_rbtree_sha256':control['Test.RBTree/index.php'],
          'integrated_rbtree_sha256':integrated['Test.RBTree/index.php'],
          'baseline_rbtree_bytes':len(a.encode()), 'integrated_rbtree_bytes':len(b.encode())}
(root/'state.json').write_text(json.dumps(result,indent=2)+'\n')
print(json.dumps(result,indent=2))
