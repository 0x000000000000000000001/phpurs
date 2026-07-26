const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  `printPhpFile :: Boolean -> String -> PhpFile -> String
printPhpFile isBundle ffiString file =`,
  `printPhpFile :: Boolean -> String -> Map String Int -> PhpFile -> String
printPhpFile isBundle ffiString allArities file =`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
