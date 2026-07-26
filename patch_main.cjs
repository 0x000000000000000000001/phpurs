const fs = require('fs');
let code = fs.readFileSync('src/Main.purs', 'utf8');

code = code.replace(
  `  bundleContentRef <- liftEffect $ Ref.new "<?php\\n\\n"`,
  `  bundleContentRef <- liftEffect $ Ref.new "<?php\\n\\n"\n  globalAritiesRef <- liftEffect $ Ref.new Map.empty`
);

code = code.replace(
  `        let
          phpModName = replaceAll (Pattern ".") (Replacement "_") modNameStr`,
  `        currentArities <- liftEffect $ Ref.read globalAritiesRef
        let allArities = Map.union phpFile.arities currentArities
        liftEffect $ Ref.write allArities globalAritiesRef

        let
          phpModName = replaceAll (Pattern ".") (Replacement "_") modNameStr`
);

code = code.replace(
  `        if args.bundle then do
          let phpCodeBundle = printPhpFile true wrappedFfiCode phpFile`,
  `        if args.bundle then do
          let phpCodeBundle = printPhpFile true wrappedFfiCode allArities phpFile`
);

code = code.replace(
  `        else pure unit

        let phpCode = printPhpFile false wrappedFfiCode phpFile`,
  `        else pure unit

        let phpCode = printPhpFile false wrappedFfiCode allArities phpFile`
);

fs.writeFileSync('src/Main.purs', code);
