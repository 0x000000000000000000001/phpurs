import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

if "import Data.Set (Set)" not in code:
    code = code.replace("import Data.Set as Set", "import Data.Set (Set)\nimport Data.Set as Set")

if "import Data.Array (find)" not in code:
    code = code.replace("import Data.Array as Array", "import Data.Array as Array\nimport Data.Array (find)")

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
