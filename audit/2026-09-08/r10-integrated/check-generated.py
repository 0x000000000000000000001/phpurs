"""Check the paired outputs and their equality with the full active build."""
import hashlib
import json
from pathlib import Path

root = Path(__file__).resolve().parent
backend = root.parents[2]
a = root/'raw/baseline/output'
b = root/'raw/integrated/output'
active = backend.parents[1]/'altbak.pub-phpurs/run/bak/php/output'
mods = sorted(p.parent.name for p in a.glob('*/index.php'))
changed = [m for m in mods if (a/m/'index.php').read_bytes() != (b/m/'index.php').read_bytes()]
active_diff = [m for m in mods if (active/m/'index.php').read_bytes() != (b/m/'index.php').read_bytes()]
result = {'modules':len(mods), 'changed_with_R10':changed, 'final_output_differs_from_full_build':active_diff,
          'rbtree':{name:{'bytes':len((p/'Test.RBTree/index.php').read_bytes()),
                         'sha256':hashlib.sha256((p/'Test.RBTree/index.php').read_bytes()).hexdigest()}
                    for name,p in [('baseline',a),('integrated',b)]}}
assert result['rbtree']['baseline']['sha256'] == '3755e926d62e3055b04593d242df0c9a2f61534f2eadf524be7ef56cff97b20d'
assert len(mods) == 301 and changed == ['Test.RBTree'] and not active_diff
(root/'generated-code.json').write_text(json.dumps(result, indent=2)+'\n')
print(json.dumps(result, indent=2))
