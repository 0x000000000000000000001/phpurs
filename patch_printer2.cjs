const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  `"\\\\\\\\" <> __NAMESPACE__ <> "\\\\\\\\" <> safeFuncName funcName`,
  `"\\\\\\\\" <> joinWith "\\\\\\\\" (fromMaybe [] mbMod) <> "\\\\\\\\" <> safeFuncName funcName`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
