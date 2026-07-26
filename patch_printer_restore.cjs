const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

// 1. Update printExpr signature
code = code.replace(
  `printExpr :: PhpExpr -> String`,
  `printExpr :: String -> Map String Int -> PhpExpr -> String`
);

// 2. Update printDecl signature
code = code.replace(
  `printDecl :: PhpDecl -> String
printDecl decl = resolveContinues $ case decl.expression of`,
  `printDecl :: String -> Map String Int -> PhpDecl -> String
printDecl currentModPrefix allArities decl = resolveContinues $ case decl.expression of`
);

// 3. Update printCaseAlternative signature
code = code.replace(
  `printCaseAlternative :: PhpCaseAlternative -> String`,
  `printCaseAlternative :: String -> Map String Int -> PhpCaseAlternative -> String`
);
code = code.replace(
  `printCaseAlternative alt =`,
  `printCaseAlternative currentModPrefix allArities alt =`
);

// 4. Update genCurry and genNativeCurry
code = code.replace(
  `genNativeCurry :: String -> Array String -> Array PhpExpr -> String
genNativeCurry name args stmts =`,
  `genNativeCurry :: String -> Map String Int -> String -> Array String -> Array PhpExpr -> String
genNativeCurry currentModPrefix allArities name args stmts =`
);
code = code.replace(
  `genCurry :: Array String -> Array String -> Array PhpExpr -> String
genCurry args captures stmts =`,
  `genCurry :: String -> Map String Int -> Array String -> Array String -> Array PhpExpr -> String
genCurry currentModPrefix allArities args captures stmts =`
);

// 5. Replace printExpr calls
code = code.replace(/printExpr /g, 'printExpr currentModPrefix allArities ');
// Fix the signature we just replaced
code = code.replace(/printExpr currentModPrefix allArities ::/g, 'printExpr ::');

// 6. Replace printDecl calls
code = code.replace(/map printDecl file\.decls/g, 'map (printDecl currentModPrefix allArities) file.decls');

// 7. Replace printCaseAlternative calls
code = code.replace(/map printCaseAlternative alts/g, 'map (printCaseAlternative currentModPrefix allArities) alts');

// 8. Replace genCurry calls
code = code.replace(/genCurry args captures stmts/g, 'genCurry currentModPrefix allArities args captures stmts');
code = code.replace(/genNativeCurry \(safeFuncName name\) args stmts/g, 'genNativeCurry currentModPrefix allArities (safeFuncName name) args stmts');

// 9. Fix printPhpFile to pass allArities
code = code.replace(
  `printPhpFile :: Boolean -> String -> PhpFile -> String
printPhpFile isBundle ffiString file =`,
  `printPhpFile :: Boolean -> String -> Map String Int -> PhpFile -> String
printPhpFile isBundle ffiString allArities file =`
);
code = code.replace(
  `printPhpFile isBundle ffiString file =
  let
    rawDeclsStr = joinWith "\\n" file.rawDecls`,
  `printPhpFile isBundle ffiString allArities file =
  let
    currentModPrefix = if length file.namespace > 0 then joinWith "_" file.namespace <> "_" else ""
    rawDeclsStr = joinWith "\\n" file.rawDecls`
);

// 10. The Native Call Optimization (PhpDirectCall) for PhpCall
const oldPhpCall = `  PhpCall (PhpGlobalVar mbMod ident) args ->
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> ""
      idStr = safeName (modPrefix <> ident)
    in "($GLOBALS['" <> idStr <> "'])(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"`;

const newPhpCall = `  PhpCall (PhpGlobalVar mbMod ident) args ->
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

code = code.replace(oldPhpCall, newPhpCall);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
