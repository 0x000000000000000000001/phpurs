const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  'Func _ _ -> "callable"',
  'Func _ _ -> ""'
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
