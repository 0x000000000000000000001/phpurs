const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  '  PhpCall (PhpGlobalVar mbMod ident) args ->',
  `  PhpDirectCall name args ->
    let
      argsStr = joinWith ", " (map (printExpr currentModPrefix allArities) args)
    in "$GLOBALS['" <> safeName name <> "'](" <> argsStr <> ")"
  PhpCall (PhpGlobalVar mbMod ident) args ->`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
