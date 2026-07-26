const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(/currentModPrefix allArities currentModPrefix allArities/g, 'currentModPrefix allArities');

fs.writeFileSync('src/Phpurs/Printer.purs', code);
