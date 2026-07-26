const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  `genCurry args captures stmts`,
  `genCurry currentModPrefix allArities args captures stmts`
);

code = code.replace(
  `genNativeCurry (safeFuncName name) args stmts`,
  `genNativeCurry currentModPrefix allArities (safeFuncName name) args stmts`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
