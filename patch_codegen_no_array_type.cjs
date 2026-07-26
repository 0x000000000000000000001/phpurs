const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  'Record _ -> "array"',
  'Record _ -> ""'
);

code = code.replace(
  'Array _ -> "array"',
  'Array _ -> ""'
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
