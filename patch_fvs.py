import re

with open('src/Phpurs/CodeGen.purs', 'r') as f:
    content = f.read()

getFreeVars_def = """getFreeVars :: Map String String -> TcoExpr -> Array String
getFreeVars bound tcoExpr =
  let TcoAnalysis { usages } = tcoAnalysisOf tcoExpr
      localKeys = Array.mapMaybe (\\(Tuple ref _) -> case ref of
        TcoLocal mbIdent lvl -> Just (localId mbIdent lvl)
        _ -> Nothing
      ) (Map.toUnfoldable usages :: Array _)
  in Array.filter (\\v -> Map.member v bound) localKeys"""

content = content.replace(getFreeVars_def, "")

# Replace getFreeVars calls with Array.fromFoldable (freeVars ...)
content = re.sub(r'getFreeVars bound (\w+Expr)', r'Array.fromFoldable (freeVars \1)', content)
content = re.sub(r'getFreeVars bound body', r'Array.fromFoldable (freeVars body)', content)

with open('src/Phpurs/CodeGen.purs', 'w') as f:
    f.write(content)

