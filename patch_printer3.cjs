const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  `import Data.Maybe (Maybe(..), isJust, isNothing)`,
  `import Data.Maybe (Maybe(..), isJust, isNothing, fromMaybe)`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
