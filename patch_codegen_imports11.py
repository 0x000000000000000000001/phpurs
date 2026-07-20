import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

code = code.replace("{ val: PhpString newName, body: argAssigns <> resBodyMut.stmts <> [ PhpRaw (stateVar <> \"['done'] = true;\"), PhpRaw (stateVar <> \"['res'] = \" <> printExpr resBodyMut.expr <> \";\") ] }", "{ matchCases: [PhpString newName], stmts: argAssigns <> resBodyMut.stmts <> [ PhpRaw (stateVar <> \"['done'] = true;\"), PhpRaw (stateVar <> \"['res'] = \" <> printExpr resBodyMut.expr <> \";\") ] }")

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
