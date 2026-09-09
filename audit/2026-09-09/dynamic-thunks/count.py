from pathlib import Path
import shutil,subprocess,json
r=Path(__file__).resolve().parent
results=[]
for variant in ['baseline','integrated']:
    s=r/'bench'/variant/'output';t=r/(variant+'-counted')/'output'
    shutil.copytree(s,t,dirs_exist_ok=True)
    p=t/'Main/index.php';text=p.read_text()
    needle='$__tco_1 = new class($v1_1)'
    assert text.count(needle)==1
    text=text.replace(needle,"++$GLOBALS['allocations'];\n  "+needle)
    needle='$__tco_1 = ($value_1 + 1);'
    if variant=='integrated':
        assert text.count(needle)==1
        text=text.replace(needle,"++$GLOBALS['additions'];\n  "+needle)
    p.write_text(text)
    p=t/'Main/main.mod.php';text=p.read_text()
    # Main's pure log argument can be evaluated during module initialization.
    # Initialize counters before loading; reset after loading for the counted run.
    text=text.replace('<?php',"<?php\n$GLOBALS['allocations']=0; $GLOBALS['additions']=0;",1)
    text=text.replace("$GLOBALS['Main_main']();", "$GLOBALS['allocations']=0; $GLOBALS['additions']=0; $value=\\Main\\majMain_run(1000,1000,0); echo json_encode(['output'=>$value,'allocations'=>$GLOBALS['allocations'],'additions'=>$GLOBALS['additions']]),\\PHP_EOL;")
    p.write_text(text)
    run=subprocess.run(['php','-d','xdebug.mode=off',str(p)],text=True,capture_output=True)
    assert run.returncode==0 and not run.stderr,(run.stdout,run.stderr)
    row=json.loads(run.stdout);row['variant']=variant;results.append(row)
    assert row['output']==1000000
    if variant=='baseline':assert row['allocations']==1000000
    else:assert row['allocations']==0 and row['additions']==1000000
print(json.dumps(results,indent=2))
(r/'counts.json').write_text(json.dumps(results,indent=2)+'\n')
