"""Snapshot the current output; change only the private Tree representation."""
import hashlib
import json
from pathlib import Path
import shutil
import subprocess

root = Path(__file__).resolve().parent
backend = root.parents[2]
worktree = backend.parents[1] / 'altbak.pub-phpurs'
active = worktree / 'run/bak/php/output'
source = (active / 'Test.RBTree/index.php').read_text()
assert hashlib.sha256(source.encode()).hexdigest() == '58d6145d3a0e15fc99792555bab37e73079591ce53dafe1226294a321c74fcc9', 'Review the patch against the new generated output first'
modules = {str(p.relative_to(active)): hashlib.sha256(p.read_bytes()).hexdigest()
           for p in sorted(active.rglob('*.php'))}
backend_sources = {str(p.relative_to(backend)): hashlib.sha256(p.read_bytes()).hexdigest()
                   for p in sorted((backend/'src').rglob('*')) if p.is_file()}
(root/'inputs.json').write_text(json.dumps({'php_files': modules, 'backend_sources': backend_sources}, indent=2)+'\n')
baseline = root/'raw/baseline/output'
for rel in modules:
    target = baseline/rel
    target.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(active/rel, target)
shutil.copy2(active/'Test.RBTree/corefn.json', root/'raw/RBTree.tast.json')
prefix = '\\Test\\RBTree\\Test_RBTree___phpurs_enum_0_'
patterns = {'alloc_E': 'new '+prefix+'E()', 'test_E': ' instanceof '+prefix+'E', 'test_T': ' instanceof '+prefix+'T'}
sites = {key: source.count(value) for key, value in patterns.items()}
assert sites['alloc_E'] == 4 and sites['test_E'] == 3 and sites['test_T'] > 0, sites
nullable = source.replace(patterns['alloc_E'], 'null').replace(patterns['test_E'], ' === null')
null_checks = nullable.replace(patterns['test_T'], ' !== null')
public_end = source.index('// Test_RBTree_act\n')
variants = {'baseline': source, 'nullable-leaves': nullable, 'null-checks': null_checks}
manifest = {}
for name, text in variants.items():
    output = root/'raw'/name/'output'
    if name != 'baseline': shutil.copytree(baseline, output, dirs_exist_ok=True)
    vendor = output.parent/'vendor'
    if not vendor.exists(): vendor.symlink_to(active.parent/'vendor', target_is_directory=True)
    assert text[:public_end] == source[:public_end], 'Public functions and classes must remain byte-identical'
    p = output/'Test.RBTree/index.php'
    p.write_text(text)
    result = subprocess.run(['php', '-l', str(p)], text=True, capture_output=True)
    assert result.returncode == 0, result.stdout+result.stderr
    manifest[name] = {'sha256': hashlib.sha256(text.encode()).hexdigest(), 'bytes': len(text.encode()), 'public_prefix_identical': True}
(root/'variants.json').write_text(json.dumps({'sites': sites, 'variants': manifest}, indent=2)+'\n')
print(json.dumps({'sites': sites, 'variants': list(variants)}), flush=True)
