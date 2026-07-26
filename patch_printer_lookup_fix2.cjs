const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(/\\\\\\\\/g, "\\\\");

fs.writeFileSync('src/Phpurs/Printer.purs', code);
