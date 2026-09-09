from pathlib import Path
import json,subprocess,re,hashlib

root=Path(__file__).resolve().parent
flags=['php','-d','xdebug.mode=off','-d','opcache.enable_cli=1','-d','opcache.file_cache=','-d','opcache.file_update_protection=0','-d','opcache.jit_buffer_size=128M','-d','opcache.jit=1255']
rows=[]
for variant in ['baseline','integrated','integrated','baseline']:
    cmd=flags+[str(root/'bench'/variant/'output/Main/measure.mod.php'),'1000','1000']
    p=subprocess.run(cmd,text=True,capture_output=True)
    assert p.returncode==0 and not p.stderr,(p.stdout,p.stderr)
    row=json.loads(p.stdout);row['variant']=variant;rows.append(row)
    (root/'kernel-results.json').write_text(json.dumps(rows,indent=2)+'\n')
    print(json.dumps({k:row[k] for k in ['variant','best_ms','median_ms','output']}),flush=True)

old=root/'baseline-paired-suite/output';new=root/'integrated-paired-suite/output'
files=sorted(p.relative_to(old) for p in old.rglob('*.php'))
changed=[str(p) for p in files if not(new/p).exists() or (old/p).read_bytes()!=(new/p).read_bytes()]
comparison={'php_files':len(files),'changed':changed}
(root/'suite-code-comparison.json').write_text(json.dumps(comparison,indent=2)+'\n')
print(json.dumps(comparison),flush=True)
expected=['7','55','202950','100000','20000','125','100000','21536','22','10000000','1200','1000000','202950','5']
rows=[]
for variant in ['baseline','integrated','integrated','baseline']:
    p=subprocess.run(flags+[str(root/(variant+'-paired-suite')/'output/App/main.mod.php')],text=True,capture_output=True)
    (root/f'suite-{len(rows)+1}-{variant}.log').write_text(p.stdout+p.stderr)
    assert p.returncode==0 and not p.stderr,(p.stdout,p.stderr)
    values=re.findall(r'\(Output & Warm-up\)\s*\n([^\n]+)',p.stdout)
    times=[float(x) for x in re.findall(r'([0-9.]+) μs',p.stdout)]
    assert values==expected and len(times)==14,(values,times)
    row={'variant':variant,'outputs':values,'times_us':times,'total_ms':sum(times)/1000};rows.append(row)
    (root/'suite-results.json').write_text(json.dumps(rows,indent=2)+'\n')
    print(json.dumps({'variant':variant,'total_ms':row['total_ms'],'lazy_us':times[11]}),flush=True)
