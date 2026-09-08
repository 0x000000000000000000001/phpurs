"""Diagnostic copies only: preserve the generated workload and public protocol."""
import hashlib
import json
import pathlib
import re
import shutil
import subprocess

root = pathlib.Path(__file__).resolve().parent
backend = root.parents[2]
original = backend.parents[1] / 'altbak.pub-phpurs/run/bak/php/output'
snapshot = root / 'raw/snapshot'
if not snapshot.exists():
    shutil.copytree(original, snapshot)
source = (snapshot / 'Test.LazyEvaluation/index.php').read_text()
(root / 'generated-LazyEvaluation.php').write_text(source)
start = source.index('function majTest_majLazymajEvaluation_buildmajThunks(')
end = source.index("\n$GLOBALS['Test_LazyEvaluation_buildThunks']", start)
body = source[start:end]
pattern = r'function\(\$v2_2\) use \(\$v1_1\) \{\n  \$__num = \\func_num_args\(\);\n  \$__res = [^\n]+;\n  goto __end;;\n  __end:\n  return \$__num > 1 \? \$__res\(.*?\) : \$__res;\n}'
match = re.search(pattern, body)
assert match, 'Generated closure shape changed; inspect it before adapting this experiment'
closure_body = match[0][match[0].index('{') + 1:-1]
obj = ('new class($v1_1) implements R8InternalThunk { private $capture; '
       'public function __construct($capture) { $this->capture = $capture; } '
       'public function __invoke($v2_2) { $v1_1 = $this->capture; ' + closure_body + '} }')
compact, count = re.subn(pattern, lambda _: obj, body)
assert count == 1
old_return = '  return 2 < $__num ? $__res(...\\array_slice(\\func_get_args(), 2)) : $__res;'
assert compact.count(old_return) == 1
wrap = 'if ($__res instanceof R8InternalThunk) { $__res = \\Closure::fromCallable($__res); }'
root_convert = compact.replace(old_return, '  ' + wrap + '\n' + old_return)
# The real benchmark overapplies buildThunks with Unit: it consumes the root
# immediately inside the public wrapper. Convert only a root that actually exits.
escaping_root = compact.replace(old_return,
    '  if (2 < $__num) { return $__res(...\\array_slice(\\func_get_args(), 2)); }\n'
    '  ' + wrap + '\n  return $__res;')
variants = {'baseline': source}
for name, changed in [('root-convert', root_convert), ('escaping-root', escaping_root)]:
    variants[name] = source[:start] + changed + source[end:] + '\ninterface R8InternalThunk {}\n'
manifest = {}
for name, content in variants.items():
    project = root / 'raw' / name
    output = project / 'output'
    output.mkdir(parents=True, exist_ok=True)
    for module in snapshot.iterdir():
        target = output / module.name
        if module.name in ['Test.LazyEvaluation', 'App']:
            shutil.copytree(module, target, dirs_exist_ok=True)
        elif not target.exists():
            target.symlink_to(module, target_is_directory=module.is_dir())
    vendor = project / 'vendor'
    if not vendor.exists():
        vendor.symlink_to(original.parent / 'vendor', target_is_directory=True)
    php = output / 'Test.LazyEvaluation/index.php'
    php.write_text(content)
    subprocess.run(['php', '-l', str(php)], check=True, capture_output=True)
    manifest[name] = {'bytes': len(content.encode()), 'sha256': hashlib.sha256(content.encode()).hexdigest()}
manifest['rbtree_sha256'] = hashlib.sha256((snapshot / 'Test.RBTree/index.php').read_bytes()).hexdigest()
assert manifest['rbtree_sha256'] == json.loads((root.parent / 'r9/sizes.json').read_text())['integrated']['rbtree_sha256'], 'Keep the measured R9 version in this comparison'
(root / 'variants.json').write_text(json.dumps(manifest, indent=2) + '\n')
print('Three isolated variants ready; R9 snapshot verified.')
