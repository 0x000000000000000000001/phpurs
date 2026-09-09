"""Separate loading from execution order while retaining Bench.purs timing."""
import json
from pathlib import Path
import subprocess
root=Path(__file__).resolve().parent
flags=['php','-d','opcache.enable_cli=1','-d','opcache.file_cache=','-d','opcache.file_update_protection=0','-d','opcache.jit_buffer_size=128M','-d','opcache.jit=1255']
rows=[]
for order in ['tree-first','church-first']:
    for name in ['baseline','integrated','integrated','baseline']:
        p=subprocess.run(flags+[str(root/'order-runner.php'),str(root/'raw'/name/'output'),order],text=True,capture_output=True,check=True)
        assert not p.stderr,p.stderr
        row=dict(variant=name,**json.loads(p.stdout.splitlines()[-1])); rows.append(row)
        print(json.dumps({k:row[k] for k in ['variant','order','times_us']}),flush=True)
(root/'order-results.json').write_text(json.dumps(rows,indent=2)+'\n')
