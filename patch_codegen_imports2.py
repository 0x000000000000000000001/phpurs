import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

code = code.replace("import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..), tcoAnalysisOf, unTcoExpr)", "import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..), tcoAnalysisOf, unTcoExpr, getTcoRole)")

if "import Data.Set (Set)" not in code:
    code = code.replace("import Data.Set as Set", "import Data.Set (Set)\nimport Data.Set as Set")

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
