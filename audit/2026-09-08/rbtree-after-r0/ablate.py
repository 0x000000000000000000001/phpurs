"""Diagnostic copies only: isolate costs, retaining the existing Bench action."""
import hashlib
import json
import pathlib
import re
import subprocess
import sys

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
# Collapse branch result temporaries only; preserve all branch tests and constructors.
variants['branch-results'] = change_function(source, 'balance', lambda body:
    re.sub(r'\$__t\d+', '$__branch_result', body))
# Bypass join chains after construction, but retain the public curry protocol.
def direct_results(body):
    body, count = re.subn(r'\$__t\d+ = (new [^\n]+);', r'$__res = \1; goto __end;', body)
    assert count == 40, count
    return body
variants['direct-results'] = change_function(source, 'balance', direct_results)
# Diagnostic: the same four ordered patterns, each with a shared failure path.
# Keep constructor classes, field reads, allocation counts and curry handling.
def compact_balance(body):
    start = body.index('  $__t4 = null;')
    end = body.index('  __end:')
    T = r'\Test\RBTree\Test_RBTree_T'
    R = r'\Test\RBTree\Test_RBTree_R'
    B = r'\Test\RBTree\Test_RBTree_B'
    def field(obj, n): return f"({obj})->{{'value{n}'}}"
    l, r = '$v1_1', '$v3_3'
    ll, lr, rl, rr = field(l,1), field(l,3), field(r,1), field(r,3)
    cases = [
        ([(l,T),(field(l,0),R),(ll,T),(field(ll,0),R)],
         [field(ll,1),field(ll,2),field(ll,3),field(l,2),lr,'$v2_2',r]),
        ([(l,T),(field(l,0),R),(lr,T),(field(lr,0),R)],
         [ll,field(l,2),field(lr,1),field(lr,2),field(lr,3),'$v2_2',r]),
        ([(r,T),(field(r,0),R),(rl,T),(field(rl,0),R)],
         [l,'$v2_2',field(rl,1),field(rl,2),field(rl,3),field(r,2),rr]),
        ([(r,T),(field(r,0),R),(rr,T),(field(rr,0),R)],
         [l,'$v2_2',rl,field(r,2),field(rr,1),field(rr,2),field(rr,3)])]
    code = f'  if ($v_0 instanceof {B}) {{\n'
    for conditions, (a,x,b,y,c,z,d) in cases:
        condition = ' && '.join(f'{obj} instanceof {cls}' for obj,cls in conditions)
        code += f'    if ({condition}) {{\n'
        code += f'      $__res = new {T}(new {R}(), new {T}(new {B}(), {a}, {x}, {b}), {y}, new {T}(new {B}(), {c}, {z}, {d}));\n'
        code += '      goto __end;\n    }\n'
    code += f'  }}\n  $__res = new {T}($v_0, $v1_1, $v2_2, $v3_3);\n'
    return body[:start] + code + body[end:]
variants['compact-patterns'] = change_function(source, 'balance', compact_balance)
def inline_balance(ins_body):
    balance_start = variants['compact-patterns'].index('function majTest_majRmajBmajTree_balance(')
    balance_body = variants['compact-patterns'][balance_start:]
    balance_body = balance_body[balance_body.index('  if ($v_0 instanceof'):balance_body.index('  __end:')]
    mapping = {'v_0': '__balance0', 'v1_1': '__balance1', 'v2_2': '__balance2', 'v3_3': '__balance3'}
    balance_body = re.sub(r'\$(v_0|v1_1|v2_2|v3_3)\b', lambda m: '$' + mapping[m[1]], balance_body)
    def replace_call(m):
        text = m[1]
        args, depth, start = [], 0, 0
        for i, c in enumerate(text):
            if c == '(': depth += 1
            elif c == ')': depth -= 1
            elif c == ',' and depth == 0:
                args.append(text[start:i].strip()); start = i + 1
        args.append(text[start:].strip())
        assert len(args) == 4, args
        return '\n'.join(f'$__balance{i} = {a};' for i,a in enumerate(args)) + '\n' + balance_body + 'goto __end;'
    body, count = re.subn(r'\$__t\d+ = \\Test\\RBTree\\majTest_majRmajBmajTree_balance\(([^\n]+)\);', replace_call, ins_body)
    assert count == 2, count
    return body
variants['inline-balance'] = change_function(source, 'ins', inline_balance)
exact = source
for name in ['balance', 'ins', 'depth', 'insert', 'buildmajTree']:
    exact = change_function(exact, name, exact_body)
variants['exact-arity'] = exact
no_tco = source
for name in ['ins', 'depth']:
    no_tco = change_function(no_tco, name, lambda body: '\n'.join(
        line for line in body.split('\n') if '__tco_var_' not in line and 'tco_loop_' not in line))
variants['no-dead-tco'] = no_tco

(root / 'raw/opcache').mkdir(parents=True, exist_ok=True)
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
suite = sys.argv[1] if len(sys.argv) > 1 else 'inline'
assert suite in ['controls', 'inline'], 'Choose controls or inline'
order = (['baseline', 'inline-balance', 'branch-results'] if suite == 'inline' else
         ['baseline', 'branch-results', 'direct-results', 'compact-patterns', 'exact-arity', 'singletons', 'no-dead-tco', 'gc-off'])
prefix = 'inline' if suite == 'inline' else 'bench'
results_file = 'inline-results.json' if suite == 'inline' else 'ablation-results.json'
for round_number, names in enumerate([order, list(reversed(order))], 1):
    for name in names:
        row, log = execute(name, 'bench')
        row['round'] = round_number
        (root / f'{prefix}-{round_number}-{name}.log').write_text(log)
        rows.append(row)
        (root / results_file).write_text(json.dumps(rows, indent=2) + '\n')
        print(json.dumps({'round': round_number, 'variant': name,
                          'best_us': row['best_of_ten_us'], 'peak_bytes': row['peak_memory_bytes']}), flush=True)
