import sys

with open("src/Phpurs/CodeGen.purs", "r") as f:
    lines = f.read().splitlines()

# find extractUncurriedAbs
idx = -1
for i, line in enumerate(lines):
    if "extractUncurriedAbs ::" in line:
        idx = i
        break

if idx != -1:
    lines = lines[:idx]
    
lines.extend([
    "extractUncurriedAbs :: TcoExpr -> Maybe { args :: Array String, body :: TcoExpr, fvs :: Set String }",
    "extractUncurriedAbs tcoExpr@(TcoExpr _ syntax) = case syntax of",
    "  UncurriedAbs args body ->",
    "    Just { args: map (\\(Tuple mbI lvl) -> localId mbI lvl) args, body, fvs: freeVars tcoExpr }",
    "  Abs args body ->",
    "    Just { args: map (\\(Tuple mbI lvl) -> localId mbI lvl) (toArray args), body, fvs: freeVars tcoExpr }",
    "  _ -> Nothing"
])

# Also make sure traverse is imported
text = "\n".join(lines)
if "import Data.Traversable (traverse)" not in text:
    text = text.replace("import Data.Foldable (foldl, foldr, foldMap)", "import Data.Foldable (foldl, foldr, foldMap)\nimport Data.Traversable (traverse)")

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(text)

