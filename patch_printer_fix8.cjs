const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  /printExpr item.value/g,
  `printExpr currentModPrefix allArities item.value`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
