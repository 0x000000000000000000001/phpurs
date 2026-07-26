const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  `  in
    { namespace: String.split (Pattern ".") (unwrap mod.name), rawDecls, decls, imports }`,
  `  in
    { namespace: String.split (Pattern ".") (unwrap mod.name), rawDecls, decls, imports, arities: moduleArities }`
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
