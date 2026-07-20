import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

code = code.replace("(\"&\" <> stateVar : useVarsLoop)", "(Array.cons (\"&\" <> stateVar) useVarsLoop)")
code = code.replace("(\"&\" <> stateVar : \"&\" <> loopVar : useVarsFn)", "(Array.cons (\"&\" <> stateVar) (Array.cons (\"&\" <> loopVar) useVarsFn))")

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
