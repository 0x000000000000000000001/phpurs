import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

code = code.replace("(toArray fns)", "fns")

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
