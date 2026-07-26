const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  /let t = if a\.type_ == "mixed" then "" else if a\.type_ \/= "" then \(if i > 0 then "\?" <> a\.type_ else a\.type_\) <> " " else ""/g,
  'let t = if a.type_ == "mixed" then "" else if a.type_ /= "" && i == 0 then a.type_ <> " " else ""'
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
