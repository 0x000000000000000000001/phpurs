const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  /\|callable/g,
  '|\\\\Closure'
);

code = code.replace(
  /== "callable"/g,
  '== "\\\\Closure"'
);

code = code.replace(
  /: callable/g,
  ': \\\\Closure'
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
