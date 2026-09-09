"""Pinned diagnostic PHP copies; active output and generator remain untouched."""
import hashlib
import json
from pathlib import Path
import shutil
import subprocess

root = Path(__file__).resolve().parent
backend = root.parents[2]
worktree = backend.parents[1]/'altbak.pub-phpurs'
active = worktree/'run/bak/php/output'
source = (active/'Test.LazyEvaluation/index.php').read_text()
digest = hashlib.sha256(source.encode()).hexdigest()
assert digest == 'fb5cd4930b0f9943ef48ed4d6672ce9729a2583909a0512afc1161b91db4f2cd', digest
begin = source.index('// Test_LazyEvaluation_runManyTimes\n')
end = source.index('// Test_LazyEvaluation_act\n', begin)
target = '\\Test\\LazyEvaluation\\majTest_majLazymajEvaluation_buildmajThunks(1000, function($v2_2)'
region = source[begin:end]
assert region.count(target) == 1
fused = source[:begin] + region.replace(target, 'phpurs_probe_force_chain(1000, function($v2_2)') + source[end:]
worker = (root/'worker.php').read_text().removeprefix('<?php\n')
fused += '\n' + worker
assert source[:begin] == fused[:begin], 'All public builders must stay byte-identical'
rows = {}
for name, content in [('baseline', source), ('fused', fused)]:
    output = root/'raw'/name/'output'
    output.mkdir(parents=True, exist_ok=True)
    for module in active.iterdir():
        if not module.is_dir():
            continue
        (output/module.name).mkdir(exist_ok=True)
        # Copy PHP files so relative requires also select the intended variant.
        # Metadata is shared read-only through symlinks; there is no compilation.
        for p in module.iterdir():
            dest = output/module.name/p.name
            if p.suffix == '.php':
                shutil.copy2(p, dest)
            elif not dest.exists():
                dest.symlink_to(p.resolve(), target_is_directory=p.is_dir())
    vendor = output.parent/'vendor'
    if not vendor.exists():
        vendor.symlink_to((active.parent/'vendor').resolve(), target_is_directory=True)
    (output/'Test.LazyEvaluation/index.php').write_text(content)
    lint = subprocess.run(['php', '-l', str(output/'Test.LazyEvaluation/index.php')], capture_output=True, text=True)
    assert lint.returncode == 0, lint.stdout+lint.stderr
    rows[name] = {'bytes': len(content.encode()), 'sha256': hashlib.sha256(content.encode()).hexdigest()}
(root/'prepared.json').write_text(json.dumps(rows, indent=2)+'\n')
print(json.dumps(rows, indent=2))
