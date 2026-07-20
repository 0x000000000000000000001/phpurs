import sys

with open("src/Phpurs/CodeGen.purs", "r") as f:
    text = f.read()

# Add traverse import if missing
if "import Data.Traversable (traverse)" not in text:
    text = text.replace("import Data.Foldable (foldl, foldr, foldMap)", "import Data.Foldable (foldl, foldr, foldMap)\nimport Data.Traversable (traverse)")

# Add Set import if missing
if "import Data.Set (Set)" not in text:
    text = text.replace("import Data.Map (Map)", "import Data.Map (Map)\nimport Data.Set (Set)\nimport Data.Set as Set")

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(text)

