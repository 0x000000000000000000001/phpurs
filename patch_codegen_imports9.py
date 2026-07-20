import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

code = code.replace("mutGroupInfo = map (\\fn -> { ident: fromMaybe fn.ident (Map.lookup fn.ident newBound), params: fn.args }) (toArray fns)", "mutGroupInfo = map (\\fn -> { ident: fromMaybe fn.ident (Map.lookup fn.ident newBound), params: fn.args }) fns")
code = code.replace("switchCases = map", "switchCases = map")
code = code.replace(")(toArray fns)", ")(fns)")

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
