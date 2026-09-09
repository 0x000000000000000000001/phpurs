import json
from pathlib import Path
import subprocess

root = Path(__file__).resolve().parent
rows = {}
for name in ['baseline', 'nullable-leaves', 'null-checks']:
    command = ['php', '-d', 'opcache.enable_cli=0', str(root/'validate.php'),
               str(root/'raw'/name/'output'), 'objects' if name == 'baseline' else 'nullable']
    result = subprocess.run(command, text=True, capture_output=True)
    (root/(name+'-validation.log')).write_text(result.stdout+result.stderr)
    assert result.returncode == 0 and not result.stderr, (name, result.stdout, result.stderr)
    rows[name] = json.loads(result.stdout)
    print(name+': public/private trees, invariants, retained roots and boundaries passed', flush=True)
assert all(v == rows['baseline'] for v in rows.values())
(root/'validation.json').write_text(json.dumps(rows, indent=2)+'\n')
