"""Instrument disposable copies; never use these processes for timings."""
import json
from pathlib import Path
import re
import shutil
import subprocess

root = Path(__file__).resolve().parent
rows = {}
for name in ['baseline', 'integrated']:
    original = root/'raw'/name/'output'
    output = root/'raw'/(name+'-counts')/'output'
    shutil.copytree(original, output, dirs_exist_ok=True)
    p = output/'Test.RBTree/index.php'
    source = p.read_text()
    pattern = r'(final class (Test_RBTree_\w+) \{[^\n]+__construct\([^\n]*?\)) \{\}'
    source, total = re.subn(pattern, lambda m: m[1]+" { $k = '"+m[2]+"'; $GLOBALS['null_counts'][$k] = ($GLOBALS['null_counts'][$k] ?? 0) + 1; }", source)
    assert total == 6
    pattern = r'(function (majTest_majRmajBmajTree_(?:__phpurs_enum_0_)?(?:ins|balance))\([^\n]+\) \{)'
    source, total = re.subn(pattern, lambda m: m[1]+"\n$k = '"+m[2]+"'; $GLOBALS['null_counts'][$k] = ($GLOBALS['null_counts'][$k] ?? 0) + 1;", source)
    assert total == 4
    p.write_text(source)
    script = '<?php\nrequire '+json.dumps(str(p))+';\n'
    script += "$GLOBALS['null_counts']=[]; $v=$GLOBALS['Test_RBTree_act'](); if ($v!=='22') throw new Exception('wrong result'); echo json_encode($GLOBALS['null_counts']);"
    result = subprocess.run(['php', '-d', 'opcache.enable_cli=0'], input=script, text=True, capture_output=True)
    assert result.returncode == 0 and not result.stderr, (name, result.stdout, result.stderr)
    counts = json.loads(result.stdout)
    rows[name] = counts
    assert counts['Test_RBTree___phpurs_enum_0_T'] == 2583932
    assert counts['majTest_majRmajBmajTree___phpurs_enum_0_ins'] == 2283976
    assert counts.get('Test_RBTree___phpurs_enum_0_E', 0) == 0
    assert counts.get('majTest_majRmajBmajTree___phpurs_enum_0_balance', 0) == 0
    assert all(counts.get('Test_RBTree_'+c, 0) == 0 for c in ['R','B','E','T'])
    print(name, counts, flush=True)
(root/'counts.json').write_text(json.dumps(rows, indent=2)+'\n')
