import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

search = """  Let lvl binds body -> case extractUncurriedAbs body of
    Just r -> Just r { body = TcoExpr (tcoAnalysisOf body) (Let lvl binds r.body) }"""

replace = """  Let lvl ident val body -> case extractUncurriedAbs body of
    Just r -> Just r { body = TcoExpr (tcoAnalysisOf body) (Let lvl ident val r.body) }"""

code = code.replace(search, replace)

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
