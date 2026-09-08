"""Apply the proved fixture region to a PHP copy; public ADTs remain boxed.

This adapter consumes the generated format for an experiment. It is not the
future compiler implementation and must not be used to patch production PHP.
"""
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess
from regions import Regions

root = Path(__file__).resolve().parent
original = root/'raw/tests/output'
module = json.loads((original/'Main/corefn.json').read_text())
analyzer = Regions(module)
regions = [analyzer.analyze(name, enum) for enum in analyzer.enums() for name in analyzer.decls
           if name not in analyzer.constructors]
(root/'regions.json').write_text(json.dumps(regions, indent=2)+'\n')
selected = [r for r in regions if r['eligible']]
assert len(selected) == 1, selected
region = selected[0]
namespace = '\\'.join(module['moduleName'])
modprefix = '_'.join(module['moduleName']) + '_'
private = namespace + '\\Phpurs_Internal_Enum_0'
source = (original/'Main/index.php').read_text()

# Resolve emitted symbols from the actual public binding table, not guessed
# capitalization rules. Unexpected generated shapes abort the experiment.
functions = {}
for match in re.finditer(r'^function (\w+)\([^\n]*\)[^{\n]*\{', source, re.M):
    end = source.index('\n$GLOBALS[', match.start())
    line = source[end+1:source.index('\n',end+1)]
    binding = re.match(r"\$GLOBALS\['([^']+)'\] = __NAMESPACE__",line)
    if not binding or not binding[1].startswith(modprefix): continue
    functions[binding[1][len(modprefix):]] = {
        'symbol': match[1], 'start': match.start(), 'end': end,
        'text': source[match.start():end]}
assert all(name in functions for name in region['bindings'])

enum = analyzer.layouts[region['enum']]
codes = {namespace+'\\'+modprefix+c['name']: i for i,c in enumerate(enum['constructors'])}
classes = {}
for layout in region['related_layouts']:
    if layout == region['enum']: continue
    for ctor in analyzer.layouts[layout]['constructors']:
        if not ctor['fieldTypes']: continue
        name = modprefix+ctor['name']
        match = re.search(r'^final class '+re.escape(name)+r' \{[^\n]+\}$',source,re.M)
        assert match, name
        classes[namespace+'\\'+name] = match[0]

def rewrite(body):
    for name in region['bindings']:
        symbol = functions[name]['symbol']
        body = body.replace('\\'+namespace+'\\'+symbol+'(', '\\'+private+'\\'+symbol+'(')
    for qualified in classes:
        replacement = '\\'+private+'\\'+qualified.rsplit('\\',1)[1]
        body = body.replace('new \\'+qualified+'(', 'new '+replacement+'(')
        body = body.replace(' instanceof \\'+qualified, ' instanceof '+replacement)
    for qualified, code in codes.items():
        body = body.replace('new \\'+qualified+'()', str(code))
        body = body.replace(' instanceof \\'+qualified, ' === '+str(code))
        body = body.replace("$GLOBALS['"+qualified.rsplit('\\',1)[1]+"']",str(code))
    return body

entry = functions[region['entry']]
entry_after = rewrite(entry['text'])
assert entry_after != entry['text']
changed = source[:entry['start']] + entry_after + source[entry['end']:]
private_functions = []
for name in region['bindings']:
    body = rewrite(functions[name]['text'])
    body = body.replace('return phpurs_curry_fallback(', 'return \\'+namespace+'\\phpurs_curry_fallback(')
    private_functions.append(body)
changed += '\nnamespace '+private+';\n'+'\n'.join(classes.values())+'\n'+'\n'.join(private_functions)+'\n'

output = root/'raw/private/output'
output.mkdir(parents=True,exist_ok=True)
for mod in original.iterdir():
    if mod.name == 'Main': shutil.copytree(mod,output/mod.name,dirs_exist_ok=True)
    elif not (output/mod.name).exists():
        (output/mod.name).symlink_to(mod.resolve(),target_is_directory=mod.is_dir())
(output/'Main/index.php').write_text(changed)
subprocess.run(['php','-l',str(output/'Main/index.php')],capture_output=True,check=True)

# Every other public function, constructor and binding table stays byte-identical.
assert changed[:entry['start']] == source[:entry['start']]
assert changed[entry['start']+len(entry_after):].startswith(source[entry['end']:])
result = subprocess.run(['php','-d','opcache.enable_cli=0',str(output/'Main/main.mod.php')],text=True,capture_output=True)
(root/'private-fixture.log').write_text(result.stdout+result.stderr)
assert result.returncode == 0 and result.stdout == 'Done\n' and not result.stderr, result.stdout+result.stderr
manifest = {'region':region,'private_namespace':private,'private_classes':list(classes),
            'public_functions_unchanged_except_scalar_entry':True,'public_adt_classes_unchanged':True,
            'original_bytes':len(source.encode()),'private_bytes':len(changed.encode()),
            'original_sha256':hashlib.sha256(source.encode()).hexdigest(),
            'private_sha256':hashlib.sha256(changed.encode()).hexdigest(),
            'php_exit_code':result.returncode,'stdout':result.stdout}
(root/'prototype.json').write_text(json.dumps(manifest,indent=2)+'\n')
print(json.dumps(manifest,indent=2))
