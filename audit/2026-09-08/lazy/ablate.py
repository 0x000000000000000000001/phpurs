"""Isolated PHP variants; use the existing Bench.purs timing policy."""
import json, pathlib, re, subprocess
root = pathlib.Path(__file__).resolve().parent
original = pathlib.Path('/Users/0x1/Documents/htdocs/altbak.pub-phpurs/run/bak/php/output')
source = (root / 'generated-LazyEvaluation.php').read_text()
pattern = r'function\(\$v2_2\)( use \(\$v1_1\))? \{\n  \$__num = \\func_num_args\(\);\n  \$__res = ([^\n]+);\n  goto __end;;\n  __end:\n  return \$__num > 1 \? \$__res\(.*?\) : \$__res;\n}'
def plain(m): return 'function($v2_2)' + (m[1] or '') + ' { return ' + m[2] + '; }'
no_protocol, count = re.subn(pattern, plain, source)
assert count == 2, count
variants = {'baseline': source, 'unary-direct': no_protocol,
            'unary-typed': no_protocol.replace('function($v2_2) use ($v1_1) {', 'function($v2_2) use ($v1_1): int {'),
            'static': source.replace('function($v2_2)', 'static function($v2_2)')}
# Diagnostic only: preserve the chain and invocation count, replace each captured closure with an invocable object.
obj = no_protocol.replace("function($v2_2) use ($v1_1) { return (($v1_1)($GLOBALS['Data_Unit_unit']) + 1); }", 'new LazyThunk($v1_1)')
assert obj != no_protocol
variants['object-thunk'] = obj + '\nclass LazyThunk { public function __construct(public $acc) {} public function __invoke($unit) { return ($this->acc)($unit) + 1; } }\n'
def object_full(m):
    if not m[1]: return m[0]
    body = m[0][m[0].index('{') + 1:-1]
    return ('new class($v1_1) { private $capture; '
            'public function __construct($capture) { $this->capture = $capture; } '
            'public function __invoke($v2_2) { $v1_1 = $this->capture; ' + body + '} }')
variants['object-full'] = re.sub(pattern, object_full, source)
variants['object-closure'] = re.sub(pattern, lambda m: '\\Closure::fromCallable(' + object_full(m) + ')' if m[1] else m[0], source)
variants['method-closure'] = re.sub(pattern, lambda m: '(' + object_full(m) + ')->__invoke(...)' if m[1] else m[0], source)
def bound_closure(m):
    if not m[1]: return m[0]
    body = m[0][m[0].index('{') + 1:-1]
    return ('(new class($v1_1) { private $capture; '
            'public function __construct($capture) { $this->capture = $capture; } '
            'public function closure() { return function($v2_2) { $v1_1 = $this->capture; ' + body + '}; } })->closure()')
variants['bound-closure'] = re.sub(pattern, bound_closure, source)
for name, content in variants.items():
    output = root / 'raw' / name
    output.mkdir(exist_ok=True)
    for module in original.iterdir():
        if module.name != 'Test.LazyEvaluation' and not (output / module.name).exists():
            (output / module.name).symlink_to(module, target_is_directory=module.is_dir())
    (output / 'Test.LazyEvaluation').mkdir(exist_ok=True)
    filename = output / 'Test.LazyEvaluation/index.php'
    filename.write_text(content)
    subprocess.run(['php', '-l', str(filename)], check=True, capture_output=True)
def execute(name, mode):
    command = ['php', '-d', 'opcache.enable_cli=1', '-d', 'opcache.file_update_protection=0',
               '-d', f'opcache.file_cache={root / "raw/opcache"}', '-d', 'opcache.jit_buffer_size=128M', '-d', 'opcache.jit=1255']
    if name == 'gc-off': command += ['-d', 'zend.enable_gc=0']
    command += [str(root / 'runner.php'), str(root / 'raw' / ('baseline' if name == 'gc-off' else name)), mode]
    result = subprocess.run(command, text=True, capture_output=True, check=True)
    if result.stderr: raise RuntimeError(result.stderr)
    return json.loads(result.stdout.splitlines()[-1]), result.stdout
checks = {name: execute(name, 'validate')[0]['result'] for name in variants}
assert all(v == checks['baseline'] for v in checks.values())
(root / 'validation.json').write_text(json.dumps(checks, indent=2) + '\n')
ffi_checks = {name: execute(name, 'ffi')[0]['result'] for name in variants}
assert ffi_checks['baseline']['typeOf'] == 'function'
assert ffi_checks['object-full']['typeOf'] == 'object'
(root / 'ffi-validation.json').write_text(json.dumps(ffi_checks, indent=2) + '\n')
rows = []
order = list(variants) + ['gc-off']
for round_number, names in enumerate([order, list(reversed(order))], 1):
    for name in names:
        row, log = execute(name, 'bench')
        row.update(variant=name, round=round_number)
        rows.append(row)
        (root / f'bench-{round_number}-{name}.log').write_text(log)
        (root / 'ablation-results.json').write_text(json.dumps(rows, indent=2) + '\n')
        print(json.dumps({'round': round_number, 'variant': name, 'best_us': row['best_of_ten_us']}), flush=True)
