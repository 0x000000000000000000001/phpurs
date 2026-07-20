import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

if "import Phpurs.Printer" not in code:
    code = code.replace("import Phpurs.PhpAst", "import Phpurs.Printer (printExpr)\nimport Phpurs.PhpAst")

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
