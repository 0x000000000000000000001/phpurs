"""Count work on separate PHP copies, with JIT/OPcache disabled."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import subprocess


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    artifacts = args.artifacts.resolve()
    rows = []
    public = []
    keys = ['nodes', 'thunk_calls', 'seed_closures', 'seed_calls', 'additions', 'guards', 'workers']
    reset = ''.join(f"$GLOBALS['{key}']=0;" for key in keys)
    for variant, suffix in [('baseline', 'before'), ('integrated', 'after')]:
        source = artifacts / 'kernel' / variant / 'output'
        destination = artifacts / 'counted' / variant / 'output'
        expected = json.loads((artifacts / f'kernel-php-{suffix}.json').read_text())
        for name, fingerprint in expected.items():
            data = (source / name).read_bytes()
            assert hashlib.sha256(data).hexdigest() == fingerprint
            target = destination / name
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(source / name, target)
        file = destination / 'Main/index.php'
        text = file.read_text()
        public.append(text.split('// Main_grow\n')[1].split('// Main_run\n')[0])

        def insert(needle, counters):
            nonlocal text
            assert text.count(needle) == 1, (variant, needle)
            text = text.replace(needle, ''.join(f"++$GLOBALS['{key}'];\n" for key in counters) + needle)

        insert('$__tco_1 = new class($v1_1)', ['nodes'])
        insert("$__res = (($v1_1)($GLOBALS['Data_Unit_unit']) + 1);", ['thunk_calls', 'additions'])
        if variant == 'baseline':
            insert('$__tco_3 = ($v3_3 + \\Main\\majMain_grow(', ['seed_closures'])
            insert('$__res = $v2_2;', ['seed_calls'])
        else:
            insert('$__tco_1 = ($value_1 + 1);', ['additions'])
            insert('$__tco_var_Main___phpurs_fuse_0_grow_remaining_0 = $remaining_0;', ['workers'])
            insert('if (($depth_0 >= 0)) {', ['guards'])
            insert('$__t0 = \\Main\\majMain_grow($depth_0, function(', ['seed_closures'])
            insert('$__res = $seed_1;', ['seed_calls'])
        file.write_text(text)
        loader = (source / 'Main/main.mod.php').read_text().split("$GLOBALS['Main_main']();")[0]
        loader = loader.replace('<?php', '<?php\n' + reset, 1)
        loader += reset + '$value=\\Main\\majMain_run(1000,1000,11,0);\n'
        loader += "echo json_encode(['output'=>$value," + ','.join(f"'{key}'=>$GLOBALS['{key}']" for key in keys) + ']),PHP_EOL;\n'
        entry = destination / 'Main/count.mod.php'
        entry.write_text(loader)
        run = subprocess.run(['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=0', str(entry)], text=True, capture_output=True)
        assert run.returncode == 0 and not run.stderr, (run.stdout, run.stderr)
        row = json.loads(run.stdout)
        assert row['output'] == 1011000 and row['additions'] == 1000000
        assert [row[k] for k in keys] == ([1000000, 1000000, 1000, 1000, 1000000, 0, 0] if variant == 'baseline'
                                        else [0, 0, 0, 0, 1000000, 1000, 1000])
        rows.append({'variant': variant, **row})
    assert public[0] == public[1]
    result = {'publicBuilderUnchanged': True, 'publicBuilderSHA256': hashlib.sha256(public[0].encode()).hexdigest(), 'counts': rows}
    (artifacts / 'counts.json').write_text(json.dumps(result, indent=2) + '\n')
    print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
