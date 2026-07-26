const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  /printExpr body/g,
  `printExpr currentModPrefix allArities body`
);

code = code.replace(
  /printExpr expr/g,
  `printExpr currentModPrefix allArities expr`
);

code = code.replace(
  /printExpr decl.expression/g,
  `printExpr currentModPrefix allArities decl.expression`
);

// double check for any other printExpr calls that are missing currentModPrefix
code = code.replace(/printExpr (?!(currentModPrefix allArities))/g, 'printExpr currentModPrefix allArities ');

// clean up any printExpr currentModPrefix allArities currentModPrefix allArities
code = code.replace(/printExpr currentModPrefix allArities currentModPrefix allArities/g, 'printExpr currentModPrefix allArities');

// fix signature
code = code.replace(/printExpr currentModPrefix allArities ::/g, 'printExpr ::');
code = code.replace(/printExpr currentModPrefix allArities currentModPrefix allArities expr = case expr of/g, 'printExpr currentModPrefix allArities expr = case expr of');

fs.writeFileSync('src/Phpurs/Printer.purs', code);
