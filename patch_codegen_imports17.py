import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

search = """        in
          { stmts: [ PhpRaw ("// DEBUG LetRec mutRecBinds=" <> (if isJust mutRecBinds then "Just" else "Nothing") <> " isLoop=" <> show isLoop <> " bindsLen=" <> show (Array.length binds)) ] <> accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }"""

replace = """        in
          { stmts: [ PhpRaw ("// DEBUG LetRec mutRecBinds=" <> (if isJust mutRecBinds then "Just" else "Nothing") <> " isLoop=" <> show isLoop <> " bindsLen=" <> show (Array.length (toArray binds))) ] <> accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }"""

code = code.replace(search, replace)

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
