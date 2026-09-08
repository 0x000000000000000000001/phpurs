"""Internal value equivalence and constructor counts, outside timed runs."""
import json
from pathlib import Path
import re
import shutil
import subprocess

root = Path(__file__).resolve().parent
checks,counts = {},{}
for name in ['baseline','private']:
    original = root/'raw/rbtree'/name
    ns = '\\Test\\RBTree'+('\\Phpurs_Internal_Enum_0' if name=='private' else '')
    run = subprocess.run(['php','-d','opcache.enable_cli=0',str(root/'validate-rbtree-internal.php'),str(original),ns+'\\majTest_majRmajBmajTree_insert'],text=True,capture_output=True)
    assert run.returncode==0 and not run.stderr,(name,run.stdout,run.stderr)
    checks[name] = json.loads(run.stdout)
    output = root/'raw/rbtree'/(name+'-counts')
    output.mkdir(exist_ok=True)
    for mod in original.iterdir():
        if mod.name == 'Test.RBTree': shutil.copytree(mod,output/mod.name,dirs_exist_ok=True)
        elif not (output/mod.name).exists():
            (output/mod.name).symlink_to(mod.resolve(),target_is_directory=mod.is_dir())
    source = (original/'Test.RBTree/index.php').read_text()
    for ctor in ['R','B','E','T']:
        pattern = r'(final class Test_RBTree_'+ctor+r' \{[^\n]+__construct\([^\n]*?\)) \{\}'
        source,n = re.subn(pattern,lambda m:m[1]+" { $k = __NAMESPACE__ . '\\\\"+ctor+"'; $GLOBALS['r10_counts'][$k] = ($GLOBALS['r10_counts'][$k] ?? 0) + 1; }",source)
        assert n == (2 if name=='private' and ctor=='T' else 1),(name,ctor,n)
    for fn in ['ins','balance']:
        pattern = r'(function majTest_majRmajBmajTree_'+fn+r'\([^\n]+\) \{)'
        source,n = re.subn(pattern,lambda m:m[1]+"\n$k = __NAMESPACE__ . '\\\\"+fn+"'; $GLOBALS['r10_counts'][$k] = ($GLOBALS['r10_counts'][$k] ?? 0) + 1;",source)
        assert n == (2 if name=='private' else 1),(name,fn,n)
    (output/'Test.RBTree/index.php').write_text(source)
    run = subprocess.run(['php','-d','opcache.enable_cli=0',str(root/'count-rbtree.php'),str(output)],text=True,capture_output=True)
    assert run.returncode==0 and not run.stderr,(name,run.stdout,run.stderr)
    counts[name] = json.loads(run.stdout)['counts']
    print(name,counts[name],flush=True)
assert checks['baseline']==checks['private']
public = 'Test\\RBTree\\'
private = public+'Phpurs_Internal_Enum_0\\'
assert counts['baseline'][public+'T']==counts['private'][private+'T']==2583932
assert counts['baseline'][public+'ins']==counts['private'][private+'ins']==2283976
assert counts['baseline'][public+'E']==counts['private'][public+'E']==200001
assert counts['private'].get(public+'T',0)==0
assert counts['private'].get(public+'R',0)==counts['private'].get(public+'B',0)==0
assert counts['baseline'][public+'R']==199978 and counts['baseline'][public+'B']==299956
(root/'rbtree-internal-checks.json').write_text(json.dumps(checks,indent=2)+'\n')
(root/'rbtree-counts.json').write_text(json.dumps(counts,indent=2)+'\n')
print('Internal trees/old roots and counts agree; public enum allocation is absent from the specialized action.')
