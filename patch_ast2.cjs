const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/PhpAst.purs', 'utf8');

code = code.replace(
  `import Data.Maybe (Maybe)`,
  `import Data.Maybe (Maybe)\nimport Data.Map (Map)`
);

fs.writeFileSync('src/Phpurs/PhpAst.purs', code);
