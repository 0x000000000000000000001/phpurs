const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  /getFreeVars :: Map String String -> TcoExpr -> Array String\ngetFreeVars bound tcoExpr =\n  let TcoAnalysis { usages } = tcoAnalysisOf tcoExpr\n      localKeys = Array.mapMaybe \\\(\\\(Tuple ref _\\) -> case ref of\n        TcoLocal mbIdent lvl -> Just \\(localId mbIdent lvl\\)\n        _ -> Nothing\n      \\) \\(Map.toUnfoldable usages :: Array _\\)\n  in Array.filter \\\(\\v -> Map.member v bound\\) localKeys/,
  `getFreeVars :: Map String String -> TcoExpr -> Array String\ngetFreeVars bound tcoExpr =\n  let TcoAnalysis { usages } = tcoAnalysisOf tcoExpr\n      localKeys = Array.mapMaybe (\\(Tuple ref _) -> case ref of\n        TcoLocal mbIdent lvl -> Just (localId mbIdent lvl)\n        _ -> Nothing\n      ) (Map.toUnfoldable usages :: Array _)\n      fvs = Array.filter (\\v -> Map.member v bound) localKeys\n  in Debug.trace ("getFreeVars: localKeys=" <> show localKeys <> " boundKeys=" <> show (Map.keys bound) <> " result=" <> show fvs) \\_ -> fvs`
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
