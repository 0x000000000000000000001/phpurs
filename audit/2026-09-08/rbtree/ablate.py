"""Diagnostic copies only: isolate costs, retaining the existing Bench action."""
import hashlib
import json
import pathlib
import re
import subprocess

root = pathlib.Path(__file__).resolve().parent
original = pathlib.Path('/Users/0x1/Documents/htdocs/altbak.pub-phpurs/run/bak/php/output')
source = (root / 'generated-RBTree.php').read_text()

def change_function(text, name, transform):
    start = text.index('function majTest_majRmajBmajTree_' + name + '(')
    end = text.index("\n$GLOBALS['Test_RBTree_", start)
    return text[:start] + transform(text[start:end]) + text[end:]

def exact_body(body):
    body, count = re.subn(r"  \$__num = \\func_num_args\(\);\n  \$__fn = [^\n]+\n  if \(\$__num < \d+\) \{\n    return phpurs_curry_fallback[^\n]+\n  }\n", '', body)
    assert count == 1
    body, count = re.subn(r"return \d+ < \$__num \? \$__res\([^\n]+\) : \$__res;", 'return $__res;', body)
    assert count == 1
    return body

variants = {'baseline': source}
# Exclude the initial constructor singleton definitions themselves.
split = source.index('// Test_RBTree_T')
variants['singletons'] = source[:split] + re.sub(
    r'new \\Test\\RBTree\\Test_RBTree_([RBE])\(\)',
    lambda match: "$GLOBALS['Test_RBTree_" + match[1] + "']", source[split:])
variants['reuse-binders'] = change_function(source, 'balance', lambda body:
    re.sub(r'\$__local_var_(\d+)_\d+', lambda match: '$__local_var_' + match[1], body))
exact = source
for name in ['balance', 'ins', 'depth', 'insert', 'buildmajTree']:
    exact = change_function(exact, name, exact_body)
variants['exact-arity'] = exact
no_tco = source
for name in ['ins', 'depth']:
    no_tco = change_function(no_tco, name, lambda body: '\n'.join(
        line for line in body.split('\n') if '__tco_var_' not in line and 'tco_loop_' not in line))
variants['no-dead-tco'] = no_tco

(root / 'raw/opcache').mkdir(exist_ok=True)
manifest = {}
for name, content in variants.items():
    output = root / 'raw' / name
    output.mkdir(exist_ok=True)
    for module in original.iterdir():
        if module.name != 'Test.RBTree' and not (output / module.name).exists():
            (output / module.name).symlink_to(module, target_is_directory=module.is_dir())
    (output / 'Test.RBTree').mkdir(exist_ok=True)
    filename = output / 'Test.RBTree/index.php'
    filename.write_text(content)
    subprocess.run(['php', '-l', str(filename)], check=True, capture_output=True)
    manifest[name] = {'source_sha256': hashlib.sha256(content.encode()).hexdigest(), 'bytes': len(content.encode())}
(root / 'variants.json').write_text(json.dumps(manifest, indent=2) + '\n')

def execute(name, mode):
    command = ['php', '-d', 'xdebug.mode=off', '-d', 'opcache.enable_cli=1',
               '-d', 'opcache.file_update_protection=0',
               '-d', f'opcache.file_cache={root / "raw/opcache"}',
               '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']
    if name == 'gc-off':
        command += ['-d', 'zend.enable_gc=0']
    command += [str(root / 'runner.php'), str(root / 'raw' / ('baseline' if name == 'gc-off' else name)), mode]
    result = subprocess.run(command, text=True, capture_output=True, check=True)
    if result.stderr:
        raise RuntimeError(result.stderr)
    row = json.loads(result.stdout.splitlines()[-1])
    row.update(variant=name, command=command)
    return row, result.stdout

checks = {name: execute(name, 'validate')[0]['result'] for name in variants}
assert all(value == checks['baseline'] for value in checks.values()), checks
(root / 'validation.json').write_text(json.dumps(checks, indent=2) + '\n')
print('Structural fingerprints agree for all variants.', flush=True)

rows = []
# Reverse order in the second round to expose time/order effects.
order = ['baseline', 'singletons', 'reuse-binders', 'exact-arity', 'no-dead-tco', 'gc-off']
for round_number, names in enumerate([order, list(reversed(order))], 1):
    for name in names:
        row, log = execute(name, 'bench')
        row['round'] = round_number
        (root / f'bench-{round_number}-{name}.log').write_text(log)
        rows.append(row)
        (root / 'ablation-results.json').write_text(json.dumps(rows, indent=2) + '\n')
        print(json.dumps({'round': round_number, 'variant': name,
                          'best_us': row['best_of_ten_us'], 'peak_bytes': row['peak_memory_bytes']}), flush=True)
