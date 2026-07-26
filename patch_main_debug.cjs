const fs = require('fs');
let code = fs.readFileSync('src/Main.purs', 'utf8');

code = code.replace(
  `        let phpCode = printPhpFile false wrappedFfiCode allArities phpFile
        FS.writeTextFile UTF8 ("output/" <> modNameStr <> "/index.php") phpCode`,
  `        let phpCode = printPhpFile false wrappedFfiCode allArities phpFile
        let debugArities = "<?php\\n$" <> "debug_all_arities = " <> show (Map.keys allArities) <> ";\\n"
        FS.writeTextFile UTF8 ("output/" <> modNameStr <> "/index.php") (debugArities <> phpCode)`
);

fs.writeFileSync('src/Main.purs', code);
