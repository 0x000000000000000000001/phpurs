const fs = require('fs');
const file = '/Users/0x1/Documents/htdocs/phpurs/phpurs/src/Phpurs/CodeGen.purs';
let content = fs.readFileSync(file, 'utf-8');

const targetImport = `import Data.String as String`;
const replacementImport = `import Data.String as String\nimport Debug (traceM)`;

content = content.replace(targetImport, replacementImport);

const targetTco = `                _ = if modNameStr == "Phpurs_PhpAst" then unsafePerformEffect (Console.log ("Tco.analyze START for " <> unwrap k)) else unit`;
const replacementTco = `                _ = if modNameStr == "Phpurs_PhpAst" then traceM ("Tco.analyze START for " <> unwrap k) else unit`;

content = content.replace(targetTco, replacementTco);

fs.writeFileSync(file, content);
