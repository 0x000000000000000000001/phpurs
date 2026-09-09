"""Instrument copies only. These executions are never used as timing samples."""
import json
from pathlib import Path
import shutil
import subprocess

root=Path(__file__).resolve().parent
rows=[]
for name in ['baseline','integrated']:
    source=root/'raw'/name/'output'
    target=root/'raw'/('count-'+name)/'output'
    shutil.copytree(source,target,dirs_exist_ok=True)
    file=target/'Test.Church/index.php'
    php=file.read_text()
    symbol='Test_Church_'+('__phpurs_bind_0_' if name=='integrated' else '')+'fromInt'
    start=php.index('// '+symbol+'\n')
    end=php.find('\n// ',start+3)
    if end==-1: end=len(php)
    body=php[start:end]
    marker='  $__res = function($x_3) use ('
    assert body.count(marker)==1
    body=body.replace(marker,"  ++$GLOBALS['counts']['successor_closures'];\n"+marker)
    if name=='integrated':
        marker='  $__res = function($x_3) {'
        assert body.count(marker)==1
        body=body.replace(marker,"  ++$GLOBALS['counts']['neutral_bindings'];\n"+marker)
    php=php[:start]+body+php[end:]
    if name=='baseline':
        start=php.index('// Test_Church_zeroC\n');end=php.index('// Test_Church_toInt\n',start)
        body=php[start:end];marker='  if ($__num < 2) {'
        assert body.count(marker)==1
        body=body.replace(marker,marker+"\n    ++$GLOBALS['counts']['neutral_bindings'];")
        php=php[:start]+body+php[end:]
    # Instrument the literal callback in the actual act; no extra callback added.
    start=php.index('// Test_Church_act\n');end=php.find('\n// ',start+3)
    if end==-1:end=len(php)
    body=php[start:end];marker='  $__res = ($x_2 + 1);'
    assert body.count(marker)==1
    body=body.replace(marker,"  ++$GLOBALS['counts']['increments'];\n"+marker)
    file.write_text(php[:start]+body+php[end:])
    code="<?php\nrequire $argv[1].'/Test.Church/index.php';\n$GLOBALS['counts']=['successor_closures'=>0,'neutral_bindings'=>0,'increments'=>0];\n$result=$GLOBALS['Test_Church_act']();\necho json_encode(['result'=>$result,'counts'=>$GLOBALS['counts']]);\n"
    runner=root/'raw/count-runner.php';runner.write_text(code)
    p=subprocess.run(['php','-d','opcache.enable_cli=0',str(runner),str(target)],text=True,capture_output=True,check=True)
    assert not p.stderr,p.stderr
    row=json.loads(p.stdout);assert row['result']=='100000' and row['counts']['increments']==100000
    rows.append(dict(variant=name,**row))
(root/'counts.json').write_text(json.dumps(rows,indent=2)+'\n')
print(json.dumps(rows,indent=2))
