"""Prove which constructors execute, independently of benchmark timings."""
import json
from pathlib import Path
import re
import shutil
import subprocess

root = Path(__file__).resolve().parent
results = {}
for name,path in [('baseline',root/'raw/tests/output'),('private',root/'raw/private/output')]:
    output = root/'raw'/(name+'-probe')
    output.mkdir(exist_ok=True)
    for module in path.iterdir():
        if module.name == 'Main': shutil.copytree(module,output/module.name,dirs_exist_ok=True)
        elif not (output/module.name).exists():
            (output/module.name).symlink_to(module.resolve(),target_is_directory=module.is_dir())
    source = (path/'Main/index.php').read_text()
    for ctor in ['Red','Blue','Link']:
        pattern = r'(final class Main_'+ctor+r' \{[^\n]+__construct\([^\n]*?\)) \{\}'
        source,count = re.subn(pattern,lambda m:m[1]+" { $k = __NAMESPACE__ . '\\\\"+ctor+"'; $GLOBALS['enum_probe'][$k] = ($GLOBALS['enum_probe'][$k] ?? 0) + 1; }",source)
        assert count == (2 if name=='private' and ctor=='Link' else 1),(name,ctor,count)
    (output/'Main/index.php').write_text(source)
    run = subprocess.run(['php','-d','opcache.enable_cli=0',str(root/'probe.php'),str(output)],text=True,capture_output=True)
    assert run.returncode == 0 and not run.stderr,(name,run.stdout,run.stderr)
    results[name] = json.loads(run.stdout)
for key in ['score','public_tree','public_boxed','public_weight','extended_weight','public_build_counts']:
    assert results['baseline'][key] == results['private'][key],key
assert results['private']['private_score_counts'] == {'Main\\Phpurs_Internal_Enum_0\\Link':8}
assert results['baseline']['private_score_counts']['Main\\Link'] == 8
assert results['baseline']['private_score_counts']['Main\\Red'] > 0
assert results['baseline']['private_score_counts']['Main\\Blue'] > 0
(root/'runtime-probe.json').write_text(json.dumps(results,indent=2)+'\n')
print(json.dumps(results,indent=2))
