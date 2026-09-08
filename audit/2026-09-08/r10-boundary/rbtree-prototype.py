"""Diagnostic private workers for the proved scalar expression in RBTree.act."""
import hashlib
import json
from pathlib import Path
import re
import shutil
import subprocess
from regions import Regions

root = Path(__file__).resolve().parent
active = Path('/Users/0x1/Documents/htdocs/altbak.pub-phpurs/run/bak/php/output')
module = json.loads((active/'Test.RBTree/corefn.json').read_text())
analyzer = Regions(module)
regions = json.loads((root/'rbtree-regions.json').read_text())
region = max(regions,key=lambda r:r['inspected_nodes'])
namespace = '\\'.join(module['moduleName'])
prefix = '_'.join(module['moduleName'])+'_'
private = namespace+'\\Phpurs_Internal_Enum_0'
source = (active/'Test.RBTree/index.php').read_text()
assert hashlib.sha256(source.encode()).hexdigest() == '3755e926d62e3055b04593d242df0c9a2f61534f2eadf524be7ef56cff97b20d', 'Revalidate the generated shape after a compiler change'
functions = {}
for match in re.finditer(r'^function (\w+)\([^\n]*\)[^{\n]*\{',source,re.M):
    end = source.index('\n$GLOBALS[',match.start())
    line = source[end+1:source.index('\n',end+1)]
    binding = re.match(r"\$GLOBALS\['([^']+)'\] = __NAMESPACE__",line)
    if binding and binding[1].startswith(prefix):
        functions[binding[1][len(prefix):]] = (match[1],source[match.start():end])
assert all(name in functions for name in region['bindings'])
enum = analyzer.layouts[region['enum']]
codes = {namespace+'\\'+prefix+c['name']:i for i,c in enumerate(enum['constructors'])}
classes = {}
for layout in region['related_layouts']:
    if layout == region['enum']: continue
    for ctor in analyzer.layouts[layout]['constructors']:
        if not ctor['fieldTypes']: continue
        name = prefix+ctor['name']
        match = re.search(r'^final class '+re.escape(name)+r' \{[^\n]+\}$',source,re.M)
        assert match,name
        classes[namespace+'\\'+name] = match[0]

def rewrite(body):
    for name in region['bindings']:
        symbol = functions[name][0]
        body = body.replace('\\'+namespace+'\\'+symbol+'(', '\\'+private+'\\'+symbol+'(')
    for qualified in classes:
        replacement = '\\'+private+'\\'+qualified.rsplit('\\',1)[1]
        body = body.replace('new \\'+qualified+'(', 'new '+replacement+'(')
        body = body.replace(' instanceof \\'+qualified, ' instanceof '+replacement)
    for qualified,code in codes.items():
        body = body.replace('new \\'+qualified+'()',str(code))
        body = body.replace(' instanceof \\'+qualified,' === '+str(code))
    return body

start = source.index('// '+prefix+region['owner']+'\n')
owner = source[start:]
assert owner.count('phpurs_execute_effect(')==2 and owner.count('\\Bench\\majBench_opaque(')==1
# In this pinned generated owner, precisely these two calls form the proved
# depth(buildTree(dummy, E)) subexpression. The effect envelope stays in place.
assert sum(owner.count('\\'+namespace+'\\'+functions[n][0]+'(') for n in region['bindings']) == 2
changed_owner = rewrite(owner)
assert changed_owner != owner
private_functions = [rewrite(functions[name][1]).replace('return phpurs_curry_fallback(',
    'return \\'+namespace+'\\phpurs_curry_fallback(') for name in region['bindings']]
candidate = source[:start]+changed_owner+'\nnamespace '+private+';\n'+'\n'.join(classes.values())+'\n'+'\n'.join(private_functions)+'\n'
assert candidate[:start]==source[:start], 'Public functions/classes/bindings must remain untouched'
manifest = {}
for name,text in [('baseline',source),('private',candidate)]:
    output = root/'raw/rbtree'/name
    output.mkdir(parents=True,exist_ok=True)
    for mod in active.iterdir():
        if mod.name in ['Test.RBTree','App']: shutil.copytree(mod,output/mod.name,dirs_exist_ok=True)
        elif not (output/mod.name).exists(): (output/mod.name).symlink_to(mod.resolve(),target_is_directory=mod.is_dir())
    (output/'Test.RBTree/index.php').write_text(text)
    subprocess.run(['php','-l',str(output/'Test.RBTree/index.php')],check=True,capture_output=True)
    manifest[name] = {'sha256':hashlib.sha256(text.encode()).hexdigest(),'bytes':len(text.encode())}
manifest.update(region=region,private_namespace=private,public_code_before_act_unchanged=True)
(root/'rbtree-prototype.json').write_text(json.dumps(manifest,indent=2)+'\n')
print(json.dumps(manifest,indent=2))
