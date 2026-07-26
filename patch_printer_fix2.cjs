const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  `map printDecl file.decls`,
  `map (printDecl allArities) file.decls`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
