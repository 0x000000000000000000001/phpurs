const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(/map printExpr currentModPrefix allArities/g, 'map (printExpr currentModPrefix allArities)');
code = code.replace(/\(map \(printExpr currentModPrefix allArities\)\)/g, '(map (printExpr currentModPrefix allArities))'); // fix if they were already in parens, wait no.

// Just doing a regex for `map printExpr currentModPrefix allArities ` -> `map (printExpr currentModPrefix allArities) `
code = code.replace(/map printExpr currentModPrefix allArities ([a-zA-Z0-9_]+)/g, 'map (printExpr currentModPrefix allArities) $1');

fs.writeFileSync('src/Phpurs/Printer.purs', code);
