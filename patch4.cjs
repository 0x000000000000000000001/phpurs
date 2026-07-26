const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(/translateExprImpl modNameStr ([a-zA-Z]+) namedBound newBound/g, "translateExprImpl modNameStr $1 moduleArities namedBound newBound");

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
