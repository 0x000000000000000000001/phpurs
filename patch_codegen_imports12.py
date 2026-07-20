import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

if "import Debug" not in code:
    code = code.replace("import Data.Maybe", "import Debug (traceM)\nimport Data.Maybe")

search = """      isLoop = (getTcoRole (tcoAnalysisOf tcoExpr)).isLoop
      mutRecBinds = if isLoop && Array.length binds > 1 then"""

replace = """      isLoop = (getTcoRole (tcoAnalysisOf tcoExpr)).isLoop
      _ = traceM { msg: "LetRec isLoop?", isLoop, count: Array.length binds }
      mutRecBinds = if isLoop && Array.length binds > 1 then"""

code = code.replace(search, replace)

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
