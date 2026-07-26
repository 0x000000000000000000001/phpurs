const fs = require('fs');
let code = fs.readFileSync('src/Main.purs', 'utf8');

code = code.replace(
  `        let debugArities = "<?php\\n$" <> "debug_all_arities = " <> show (Map.keys allArities) <> ";\\n"
        FS.writeTextFile UTF8 ("output/" <> modNameStr <> "/index.php") (debugArities <> phpCode)`,
  `        let debugArities = "<?php\\n/* ARITIES:\\n" <> joinWith "\\n" (map (\\(Tuple k v) -> k <> ": " <> show v) (Map.toUnfoldable allArities :: Array (Tuple String Int))) <> "\\n*/\\n"
        FS.writeTextFile UTF8 ("output/" <> modNameStr <> "/index.php") (debugArities <> phpCode)`
);

fs.writeFileSync('src/Main.purs', code);
