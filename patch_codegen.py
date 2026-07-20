import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

# 1. Import getTcoRole
if "getTcoRole" not in code:
    code = code.replace("hasTcoRole, tcoAnalysisOf", "hasTcoRole, getTcoRole, tcoAnalysisOf")
    code = code.replace("import Data.Traversable (foldr)", "import Data.Traversable (foldr, traverse)\nimport Data.Array (find)")

# 2. Update LoopCtx
code = code.replace("type LoopCtx = { ident :: String, params :: Array String }", "type LoopCtx = { ident :: String, params :: Array String, mutGroup :: Maybe (Array { ident :: String, params :: Array String }) }")

# 3. Update newLoopCtx in Abs / UncurriedAbs
code = code.replace("Just { ident: name, params: argsArray }", "Just { ident: name, params: argsArray, mutGroup: Nothing }")
code = code.replace("Just { ident: name, params: argsArray }", "Just { ident: name, params: argsArray, mutGroup: Nothing }")

# 4. Add extractUncurriedAbs helper at the end
if "extractUncurriedAbs" not in code:
    code += """
extractUncurriedAbs :: TcoExpr -> Maybe { args :: Array String, body :: TcoExpr, fvs :: Set String }
extractUncurriedAbs tcoExpr@(TcoExpr _ expr) = case expr of
  UncurriedAbs args body -> Just { args: map (\\(Tuple mbI lvl) -> localId mbI lvl) args, body, fvs: freeVars tcoExpr }
  Abs args body -> Just { args: map (\\(Tuple mbI lvl) -> localId mbI lvl) (toArray args), body, fvs: freeVars tcoExpr }
  _ -> Nothing
"""

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
