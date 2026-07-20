import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

search = """extractUncurriedAbs tcoExpr@(TcoExpr _ expr) = case expr of
  UncurriedAbs args body -> Just { args: map (\\(Tuple mbI lvl) -> localId mbI lvl) args, body, fvs: freeVars tcoExpr }
  Abs args body -> Just { args: map (\\(Tuple mbI lvl) -> localId mbI lvl) (toArray args), body, fvs: freeVars tcoExpr }
  _ -> Nothing"""

replace = """extractUncurriedAbs tcoExpr@(TcoExpr _ expr) = case expr of
  UncurriedAbs args body -> Just { args: map (\\(Tuple mbI lvl) -> localId mbI lvl) args, body, fvs: freeVars tcoExpr }
  Abs args body -> Just { args: map (\\(Tuple mbI lvl) -> localId mbI lvl) (toArray args), body, fvs: freeVars tcoExpr }
  Let lvl binds body -> case extractUncurriedAbs body of
    Just r -> Just r { body = TcoExpr (tcoAnalysisOf body) (Let lvl binds r.body) }
    Nothing -> Nothing
  _ -> Nothing"""

code = code.replace(search, replace)

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
