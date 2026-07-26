const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  /\\\\Closure/g,
  'callable'
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
