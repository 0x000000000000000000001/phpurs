const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  `import Data.Maybe (Maybe(..), isJust, isNothing)`,
  `import Data.Maybe (Maybe(..), isJust, isNothing)\nimport Data.Map (Map)\nimport Data.Map as Map`
);

code = code.replace(
  `printExpr :: PhpExpr -> String\nprintExpr expr = case expr of`,
  `printExpr :: Map String Int -> PhpExpr -> String\nprintExpr allArities expr = case expr of`
);

code = code.replace(
  /printExpr/g,
  `printExpr allArities`
);

// Fix the recursive calls inside printExpr
code = code.replace(
  `printExpr allArities :: Map String Int -> PhpExpr -> String`,
  `printExpr :: Map String Int -> PhpExpr -> String`
);

code = code.replace(
  `printExpr allArities allArities expr = case expr of`,
  `printExpr allArities expr = case expr of`
);

// genCurry signature
code = code.replace(
  `genCurry :: Array String -> Array String -> Array PhpExpr -> String\ngenCurry args captures stmts =`,
  `genCurry :: Map String Int -> Array String -> Array String -> Array PhpExpr -> String\ngenCurry allArities args captures stmts =`
);

// fix calls to genCurry
code = code.replace(
  `genCurry args captures stmts`,
  `genCurry allArities args captures stmts`
);

// printDecl signature
code = code.replace(
  `printDecl :: PhpDecl -> String\nprintDecl decl = resolveContinues $ case decl.expression of`,
  `printDecl :: Map String Int -> PhpDecl -> String\nprintDecl allArities decl = resolveContinues $ case decl.expression of`
);

// genNativeCurry signature
code = code.replace(
  `genNativeCurry :: String -> Array String -> Array PhpExpr -> String\ngenNativeCurry name args stmts =`,
  `genNativeCurry :: Map String Int -> String -> Array String -> Array PhpExpr -> String\ngenNativeCurry allArities name args stmts =`
);

// fix calls to genNativeCurry
code = code.replace(
  `genNativeCurry (safeFuncName name) args stmts`,
  `genNativeCurry allArities (safeFuncName name) args stmts`
);

// replace printPhpFile signature
code = code.replace(
  `printPhpFile :: Boolean -> String -> PhpFile -> String\nprintPhpFile isBundle ffiString file =`,
  `printPhpFile :: Boolean -> String -> Map String Int -> PhpFile -> String\nprintPhpFile isBundle ffiString allArities file =`
);

// fix calls to printDecl in printPhpFile
code = code.replace(
  `map printDecl file.decls`,
  `map (printDecl allArities) file.decls`
);

// Now for the actual logic!
code = code.replace(
  `  PhpCall (PhpGlobalVar mbMod ident) args ->
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> ""
      idStr = safeName (modPrefix <> ident)
    in "($GLOBALS['" <> idStr <> "'])(" <> joinWith ", " (map (printExpr allArities) args) <> ")"`,
  `  PhpCall (PhpGlobalVar mbMod ident) args ->
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> ""
      funcName = modPrefix <> ident
      idStr = safeName funcName
    in case Map.lookup funcName allArities of
      Just arity | arity == length args -> "\\\\" <> __NAMESPACE__ <> "\\\\" <> safeFuncName funcName <> "(" <> joinWith ", " (map (printExpr allArities) args) <> ")"
      _ -> "($GLOBALS['" <> idStr <> "'])(" <> joinWith ", " (map (printExpr allArities) args) <> ")"`
);

// Wait, the __NAMESPACE__ literal is not available in PureScript!
// In pure script it would be " . __NAMESPACE__ . "\\\\" <> safeFuncName funcName <> "(" ...
// But it's easier to just output the static module name if it's imported!
// Actually, PHP allows calling unqualified names if they are in the current namespace, but for cross-namespace it needs full path.
// But we use namespaces!
// Wait! Our functions are NOT namespaced per module!
// They are ALL global!
// Wait, index.php has `namespace App;`? NO!
// Actually, look at `__NAMESPACE__ . '\\majTest_majRmajBmajTree_insert'`
// It uses `__NAMESPACE__`! So they ARE namespaced!
// But wait, what if we just call the function directly?
// Since it's in the SAME namespace (everything is in the same namespace if they are concatenated, or we just prefix it with `\\` if it's global?)
// Actually, `__NAMESPACE__` in PHP is evaluated at compile time.
// Since we don't know the namespace here easily, we can just use `\\some_namespace\\funcName`.
// Wait! `printPhpFile` generates `namespace ` <> ns <> `;` at the top!
// So it IS namespaced!
// If it calls a function from ANOTHER module, it MUST be fully qualified!
// But `funcName` doesn't have the namespace of the TARGET module, it is just `Test_RBTree_insert`.
// Wait... our PureScript code is compiled into multiple PHP files, each with its OWN namespace.
// `Test_RBTree_insert` is in `Test\RBTree` namespace!
// If `App.purs` calls `Test_RBTree_insert`, how does it call it?!
// Currently it calls `($GLOBALS['Test_RBTree_insert'])`.
// `GLOBALS` works because it's populated with `__NAMESPACE__ . '\\majTest_majRmajBmajTree_insert'`.
// If we want to call it directly, we need to know its namespace!
// Or we can just use the global namespace? No, it's inside `namespace Test\RBTree;`!
