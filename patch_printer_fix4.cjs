const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  `printPhpFile isBundle ffiString allArities file =
  let currentModPrefix = if length file.namespace > 0 then joinWith "_" file.namespace <> "_" else ""
  let`,
  `printPhpFile isBundle ffiString allArities file =
  let
    currentModPrefix = if length file.namespace > 0 then joinWith "_" file.namespace <> "_" else ""`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
