const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  '              properties = Array.mapWithIndex (\\i arg -> let t = mapExprType arg in "public " <> (if t == "" || t == "\\\\Closure" then "" else t <> " ") <> "$" <> "value" <> show i <> ";") ctor.args',
  '              properties = Array.mapWithIndex (\\i arg -> "public $" <> "value" <> show i <> ";") ctor.args'
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
