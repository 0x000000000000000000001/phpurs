"""Count the executed path separately from timings, in disposable copies."""
import json
from pathlib import Path
import shutil
import subprocess

root = Path(__file__).resolve().parent
rows = {}
for name in ['baseline','fused']:
    source = root/'raw'/name/'output'
    output = root/'raw'/(name+'-counts')/'output'
    output.mkdir(parents=True, exist_ok=True)
    for module in source.iterdir():
        if module.name == 'Test.LazyEvaluation': shutil.copytree(module,output/module.name,dirs_exist_ok=True)
        elif not (output/module.name).exists(): (output/module.name).symlink_to(module.resolve(),target_is_directory=True)
    p = output/'Test.LazyEvaluation/index.php'
    s = p.read_text()
    begin = s.index('function majTest_majLazymajEvaluation_buildmajThunks(')
    end = s.index("\n$GLOBALS['Test_LazyEvaluation_buildThunks']",begin)
    body = s[begin:end]
    for before, counter in [('  $__num = \\func_num_args();\n  $__fn','builders'),
                            ('  $__tco_1 = new class','nodes'),
                            ("  $__res = (($v1_1)($GLOBALS['Data_Unit_unit']) + 1);",'node_calls')]:
        assert body.count(before)==1
        body = body.replace(before,"++$GLOBALS['fusion_counts']['"+counter+"'];\n"+before)
    body = body.replace("  $__res = (($v1_1)($GLOBALS['Data_Unit_unit']) + 1);",
                        "++$GLOBALS['fusion_counts']['additions'];\n  $__res = (($v1_1)($GLOBALS['Data_Unit_unit']) + 1);")
    s = s[:begin]+body+s[end:]
    assert s.count('  $__res = 0;')==1
    s = s.replace('  $__res = 0;', "++$GLOBALS['fusion_counts']['seeds'];\n  $__res = 0;")
    if name == 'fused':
        for before,counter in [('        $value = $value + 1;', 'additions')]:
            assert s.count(before)==1
            s = s.replace(before,"++$GLOBALS['fusion_counts']['"+counter+"'];\n"+before)
    p.write_text(s)
    php = '<?php\nrequire '+json.dumps(str(p))+';\n'
    php += "$GLOBALS['fusion_counts']=['builders'=>0,'nodes'=>0,'node_calls'=>0,'seeds'=>0,'additions'=>0];\n"
    php += "$v=$GLOBALS['Test_LazyEvaluation_act'](); if ($v!=='1000000') throw new Exception('wrong result'); echo json_encode($GLOBALS['fusion_counts']);"
    run = subprocess.run(['php','-d','opcache.enable_cli=0'],input=php,text=True,capture_output=True)
    assert run.returncode==0 and not run.stderr,(run.stdout,run.stderr)
    rows[name] = json.loads(run.stdout)
assert rows['baseline']['builders']==1000
assert rows['baseline']['nodes']==rows['baseline']['node_calls']==1000000
assert rows['baseline']['seeds']==1000 and rows['baseline']['additions']==1000000
assert rows['fused']=={'builders':0,'nodes':0,'node_calls':0,'seeds':1000,'additions':1000000}
(root/'counts.json').write_text(json.dumps(rows,indent=2)+'\n')
print(json.dumps(rows,indent=2))
