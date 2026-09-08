"""Diagnostic PHP copies for isolating promoted-property checks, never codegen.

Run after prepare-comparison.mjs, then measure-rbtree.py bench typed-fields
color-untyped fields-untyped. Only the isolated RBTree action uses these copies.
"""
from pathlib import Path
import re
import shutil

root = Path(__file__).resolve().parent/'raw'
original = root/'integrated/output'
for name in ['typed-fields', 'color-untyped', 'fields-untyped']:
    out = root/name/'output'
    out.mkdir(parents=True, exist_ok=True)
    for mod in original.iterdir():
        if mod.name == 'Test.RBTree': shutil.copytree(mod, out/mod.name, dirs_exist_ok=True)
        elif not (out/mod.name).exists():
            (out/mod.name).symlink_to(mod.resolve(), target_is_directory=mod.is_dir())
    p = out/'Test.RBTree/index.php'
    lines = p.read_text().splitlines(True)
    found = 0
    for i, line in enumerate(lines):
        if line.startswith('final class Test_RBTree___phpurs_enum_0_T '):
            found += 1
            for field in [0, 2]:
                checked = name == 'typed-fields' or (name == 'color-untyped' and field == 2)
                line, n = re.subn(r'public(?: int)? +\$value'+str(field), 'public '+('int ' if checked else '')+'$value'+str(field), line)
                assert n == 1
            lines[i] = line
    assert found == 1
    p.write_text(''.join(lines))
