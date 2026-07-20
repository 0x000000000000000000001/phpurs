import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

# Replace PhpAssign with PhpRaw for array assignments
code = code.replace("PhpAssign (PhpRaw \"$__tco_state['id']\") (PhpString targetFn.ident)", "PhpRaw (\"$__tco_state['id'] = '\" <> targetFn.ident <> \"';\")")
code = code.replace("PhpAssign (PhpRaw (\"$__tco_state['\" <> targetFn.ident <> \"_\" <> show i <> \"']\"))", "PhpRaw <<< (\\val -> \"$__tco_state['\" <> targetFn.ident <> \"_\" <> show i <> \"'] = \" <> val <> \";\") <<< printExpr")

code = code.replace("PhpAssign (PhpRaw (\"$__tco_state['\" <> newName <> \"_\" <> show i <> \"']\")) (PhpVar argName)", "PhpRaw (\"$__tco_state['\" <> newName <> \"_\" <> show i <> \"'] = $\" <> argName <> \";\")")
code = code.replace("PhpAssign (PhpRaw (stateVar <> \"['\" <> newName <> \"_\" <> show i <> \"']\")) (PhpVar argName)", "PhpRaw (stateVar <> \"['\" <> newName <> \"_\" <> show i <> \"'] = $\" <> argName <> \";\")")

code = code.replace("PhpAssign (PhpRaw (stateVar <> \"['done']\")) (PhpBoolean true)", "PhpRaw (stateVar <> \"['done'] = true;\")")
code = code.replace("PhpAssign (PhpRaw (stateVar <> \"['res']\")) resBodyMut.expr", "PhpRaw (stateVar <> \"['res'] = \" <> printExpr resBodyMut.expr <> \";\")")

code = code.replace("PhpAssign (PhpRaw (stateVar <> \"['id']\")) (PhpString newName)", "PhpRaw (stateVar <> \"['id'] = '\" <> newName <> \"';\")")
code = code.replace("PhpAssign (PhpRaw (stateVar <> \"['done']\")) (PhpBoolean false)", "PhpRaw (stateVar <> \"['done'] = false;\")")

code = code.replace("PhpAssign (PhpRaw (stateVar <> \"['res']\")) resBody.expr", "PhpRaw (stateVar <> \"['res'] = \" <> printExpr resBody.expr <> \";\")")


with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
