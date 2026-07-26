const fs = require('fs');
let code = fs.readFileSync('src/Main.purs', 'utf8');

code = code.replace(
  `import Data.Map as Map`,
  `import Data.Map as Map\nimport Data.Tuple (Tuple(..))`
);

fs.writeFileSync('src/Main.purs', code);
