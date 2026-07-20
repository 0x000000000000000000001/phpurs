import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

code = code.replace("import Data.Foldable (foldl, foldr)", "import Data.Foldable (foldl, foldr, foldMap)\nimport Data.Traversable (traverse)")
code = code.replace("import Data.Newtype (unwrap)", "import Data.Newtype (unwrap)\nimport Data.Set (Set)\nimport Data.Set as Set")

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
