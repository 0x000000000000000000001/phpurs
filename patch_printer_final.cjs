const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  `printPhpFile :: Boolean -> String -> Map String Int -> PhpFile -> String
printPhpFile isBundle ffiString allArities file =`,
  `printPhpFile :: Boolean -> String -> Map String Int -> PhpFile -> String
printPhpFile isBundle ffiString allArities file =
  let currentModPrefix = if length file.namespace > 0 then joinWith "_" file.namespace <> "_" else ""`
);

// We must pass currentModPrefix to printDecl and printExpr!
code = code.replace(
  `printDecl :: Map String Int -> PhpDecl -> String
printDecl allArities decl =`,
  `printDecl :: String -> Map String Int -> PhpDecl -> String
printDecl currentModPrefix allArities decl =`
);

code = code.replace(
  `map (printDecl allArities) file.decls`,
  `map (printDecl currentModPrefix allArities) file.decls`
);

code = code.replace(
  `printExpr :: Map String Int -> PhpExpr -> String
printExpr allArities expr = case expr of`,
  `printExpr :: String -> Map String Int -> PhpExpr -> String
printExpr currentModPrefix allArities expr = case expr of`
);

code = code.replace(
  /printExpr allArities/g,
  `printExpr currentModPrefix allArities`
);

code = code.replace(
  `genCurry :: Map String Int -> Array String -> Array String -> Array PhpExpr -> String
genCurry allArities args captures stmts =`,
  `genCurry :: String -> Map String Int -> Array String -> Array String -> Array PhpExpr -> String
genCurry currentModPrefix allArities args captures stmts =`
);

code = code.replace(
  `genCurry allArities args captures stmts`,
  `genCurry currentModPrefix allArities args captures stmts`
);

code = code.replace(
  `genNativeCurry :: Map String Int -> String -> Array String -> Array PhpExpr -> String
genNativeCurry allArities name args stmts =`,
  `genNativeCurry :: String -> Map String Int -> String -> Array String -> Array PhpExpr -> String
genNativeCurry currentModPrefix allArities name args stmts =`
);

code = code.replace(
  `genNativeCurry allArities (safeFuncName name) args stmts`,
  `genNativeCurry currentModPrefix allArities (safeFuncName name) args stmts`
);

// Now fix the PhpCall match
code = code.replace(
  `  PhpCall (PhpGlobalVar mbMod ident) args ->
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> ""
      funcName = modPrefix <> ident
      idStr = safeName funcName
    in case Map.lookup funcName allArities of
      Just arity | arity == length args -> "\\\\\\\\" <> joinWith "\\\\\\\\" (fromMaybe [] mbMod) <> "\\\\\\\\" <> safeFuncName funcName <> "(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"
      _ -> "($GLOBALS['" <> idStr <> "'])(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"`,
  `  PhpCall (PhpGlobalVar mbMod ident) args ->
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
      _ -> "($GLOBALS['" <> idStr <> "'])(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"`
);

// We also need to fix PhpGlobalVar itself to use currentModPrefix
code = code.replace(
  `  PhpGlobalVar mbMod ident -> 
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> ""
      idStr = safeName (modPrefix <> ident)`,
  `  PhpGlobalVar mbMod ident -> 
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> currentModPrefix
      idStr = safeName (modPrefix <> ident)`
);


fs.writeFileSync('src/Phpurs/Printer.purs', code);
