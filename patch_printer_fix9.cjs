const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  /printExpr arr/g,
  `printExpr currentModPrefix allArities arr`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
