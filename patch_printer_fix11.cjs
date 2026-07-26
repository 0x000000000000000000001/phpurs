const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  /in case Map.lookup funcName allArities of\n\s*Just arity \| arity == length args ->\n\s*let nsPrefix = case mbMod of\n\s*Just mod -> "\\\\\\\\\\\\\\\\" <> joinWith "\\\\\\\\\\\\\\\\" mod <> "\\\\\\\\\\\\\\\\"\n\s*Nothing -> ""\n\s*in nsPrefix <> safeFuncName funcName <> "\(" <> joinWith ", " \(map \(printExpr currentModPrefix allArities\) args\) <> "\)"\n\s*_ -> "\(\$GLOBALS\['" <> idStr <> "'\]\)\(" <> joinWith ", " \(map \(printExpr currentModPrefix allArities\) args\) <> "\)"/g,
  `in case Map.lookup funcName allArities of
      Just arity | arity == length args ->
        let nsPrefix = case mbMod of
              Just mod -> "\\\\\\\\" <> joinWith "\\\\\\\\" mod <> "\\\\\\\\"
              Nothing -> ""
        in nsPrefix <> safeFuncName funcName <> "(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"
      Just arity -> "/* FALLBACK ARITY MISMATCH: " <> funcName <> " arity=" <> show arity <> " args=" <> show (length args) <> " */ ($GLOBALS['" <> idStr <> "'])(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"
      Nothing -> "/* FALLBACK NOT FOUND: " <> funcName <> " */ ($GLOBALS['" <> idStr <> "'])(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
