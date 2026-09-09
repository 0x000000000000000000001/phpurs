"""Verify the scope of the diagnostic and the unchanged active generated files."""
import hashlib
import json
from pathlib import Path

root=Path(__file__).resolve().parent
active=root.parents[2].parents[1]/'altbak.pub-phpurs/run/bak/php/output'
baseline=root/'raw/baseline/output'
fused=root/'raw/fused/output'
mods=sorted(p.parent.name for p in baseline.glob('*/index.php'))
changed=[m for m in mods if (baseline/m/'index.php').read_bytes()!=(fused/m/'index.php').read_bytes()]
active_changed=[m for m in mods if (baseline/m/'index.php').read_bytes()!=(active/m/'index.php').read_bytes()]
assert changed==['Test.LazyEvaluation'] and not active_changed
a=(baseline/'Test.LazyEvaluation/index.php').read_text()
b=(fused/'Test.LazyEvaluation/index.php').read_text()
public_end=a.index('// Test_LazyEvaluation_runManyTimes\n')
assert a[:public_end]==b[:public_end]
row={'modules_checked':len(mods),'changed_in_copy':changed,'changed_in_active_output':active_changed,
     'public_builder_prefix_identical':True,'active_lazy_sha256':hashlib.sha256(a.encode()).hexdigest()}
(root/'state.json').write_text(json.dumps(row,indent=2)+'\n')
print(json.dumps(row,indent=2))
