const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  'retStr = if retType == "mixed" || retType == "" then "" else ": " <> retType',
  'retStr = if retType == "mixed" || retType == "" then "" else if retType == "\\\\Closure" then ": \\\\Closure" else ": " <> retType <> "|\\\\Closure"'
);

// We need to replace it in both genCurry and genNativeCurry
code = code.replace(
  'retStr = if retType == "mixed" || retType == "" then "" else ": " <> retType',
  'retStr = if retType == "mixed" || retType == "" then "" else if retType == "\\\\Closure" then ": \\\\Closure" else ": " <> retType <> "|\\\\Closure"'
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
