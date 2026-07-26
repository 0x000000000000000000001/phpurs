const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

const oldStr = `  PhpCall (PhpGlobalVar mbMod ident) args ->
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> ""
      idStr = safeName (modPrefix <> ident)
    in "($GLOBALS['" <> idStr <> "'])(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"`;

const newStr = `  PhpCall (PhpGlobalVar mbMod ident) args ->
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> currentModPrefix
      funcName = modPrefix <> ident
      idStr = safeName funcName
    in case Map.lookup funcName allArities of
      Just arity | arity == length args ->
        let nsPrefix = case mbMod of
              Just mod -> "\\\\\\\\" <> joinWith "\\\\\\\\" mod <> "\\\\\\\\"
              Nothing -> ""
        in nsPrefix <> safeFuncName funcName <> "(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"
      _ -> "($GLOBALS['" <> idStr <> "'])(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"`;

if (code.includes(oldStr)) {
    code = code.replace(oldStr, newStr);
    fs.writeFileSync('src/Phpurs/Printer.purs', code);
    console.log("Replaced successfully!");
} else {
    console.log("Could not find old string!");
}
