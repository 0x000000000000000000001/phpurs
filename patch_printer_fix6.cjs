const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  `genNativeCurry :: String -> Array String -> Array PhpExpr -> String
genNativeCurry name args stmts =`,
  `genNativeCurry :: String -> Map String Int -> String -> Array String -> Array PhpExpr -> String
genNativeCurry currentModPrefix allArities name args stmts =`
);

code = code.replace(
  `genCurry :: Array String -> Array String -> Array PhpExpr -> String
genCurry args captures stmts =`,
  `genCurry :: String -> Map String Int -> Array String -> Array String -> Array PhpExpr -> String
genCurry currentModPrefix allArities args captures stmts =`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
