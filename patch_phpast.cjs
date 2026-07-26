const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/PhpAst.purs', 'utf8');

code = code.replace(
  'PhpFunction (Array String) (Array String) (Array PhpExpr)',
  'PhpFunction (Array String) (Array { name :: String, type_ :: String }) String (Array PhpExpr)'
);

code = code.replace(
  'PhpNativeFunction String (Array String) (Array PhpExpr)',
  'PhpNativeFunction String (Array { name :: String, type_ :: String }) String (Array PhpExpr)'
);

fs.writeFileSync('src/Phpurs/PhpAst.purs', code);
