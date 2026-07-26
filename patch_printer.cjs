const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

// Update genCurry signature and implementation
code = code.replace(
  'genCurry :: String -> Map String Int -> Array String -> Array String -> Array PhpExpr -> String',
  'genCurry :: String -> Map String Int -> Array { name :: String, type_ :: String } -> String -> Array String -> Array PhpExpr -> String'
);

code = code.replace(
  `genCurry currentModPrefix allArities args captures stmts =
  let safeCaptures = map (\\v -> if take 1 v == "&" then "&$" <> safeName (drop 1 v) else "$" <> safeName v) captures
  in if length args == 0 then
    let useClause = if length safeCaptures > 0 then " use (" <> joinWith ", " safeCaptures <> ", &$__fn)" else " use (&$__fn)"
    in "function()" <> useClause <> " {\\n" <> (joinWith ";\\n" (map (printExpr currentModPrefix allArities) stmts) <> ";") <> "\\n}"
  else
    let
      argStr = joinWith ", " (map (\\a -> "$" <> safeName a <> " = null") args)
      nStr = show (length args)
      nArgs = length args`,
  `genCurry currentModPrefix allArities args retType captures stmts =
  let safeCaptures = map (\\v -> if take 1 v == "&" then "&$" <> safeName (drop 1 v) else "$" <> safeName v) captures
      retStr = if retType == "mixed" || retType == "" then "" else ": " <> retType
  in if length args == 0 then
    let useClause = if length safeCaptures > 0 then " use (" <> joinWith ", " safeCaptures <> ", &$__fn)" else " use (&$__fn)"
    in "function()" <> useClause <> retStr <> " {\\n" <> (joinWith ";\\n" (map (printExpr currentModPrefix allArities) stmts) <> ";") <> "\\n}"
  else
    let
      argStr = joinWith ", " (mapWithIndex (\\i a -> 
        let t = if a.type_ == "mixed" then "" else if a.type_ /= "" then (if i > 0 then "?" <> a.type_ else a.type_) <> " " else ""
        in t <> "$" <> safeName a.name <> (if i > 0 then " = null" else "")
      ) args)
      nStr = show (length args)
      nArgs = length args`
);

// We need to also patch where args are used in genCurry!
// e.g. joinWith ", " (map (\a -> "$" <> safeName a) args) -> joinWith ", " (map (\a -> "$" <> safeName a.name) args)
// Let's replace any map over args in genCurry:
code = code.replace(
  `joinWith ", " (map (\\a -> "$" <> safeName a) args)`,
  `joinWith ", " (map (\\a -> "$" <> safeName a.name) args)`
);
code = code.replace(
  `joinWith ", " (map (\\a -> "$" <> safeName a) args)`,
  `joinWith ", " (map (\\a -> "$" <> safeName a.name) args)`
); // It appears multiple times, let's just do it twice.

// Update genNativeCurry signature and implementation
code = code.replace(
  'genNativeCurry :: String -> Map String Int -> String -> Array String -> Array PhpExpr -> String',
  'genNativeCurry :: String -> Map String Int -> String -> Array { name :: String, type_ :: String } -> String -> Array PhpExpr -> String'
);

code = code.replace(
  `genNativeCurry currentModPrefix allArities name args stmts =
  let
    argStr = joinWith ", " (mapWithIndex (\\i a -> "$" <> safeName a <> (if i > 0 then " = null" else "")) args)`,
  `genNativeCurry currentModPrefix allArities name args retType stmts =
  let
    argStr = joinWith ", " (mapWithIndex (\\i a -> 
      let t = if a.type_ == "mixed" then "" else if a.type_ /= "" then (if i > 0 then "?" <> a.type_ else a.type_) <> " " else ""
      in t <> "$" <> safeName a.name <> (if i > 0 then " = null" else "")
    ) args)
    retStr = if retType == "mixed" || retType == "" then "" else ": " <> retType`
);

code = code.replace(
  `fnBody = "function " <> name <> "(" <> argStr <> ") {\\n"`,
  `fnBody = "function " <> name <> "(" <> argStr <> ")" <> retStr <> " {\\n"`
);

// Also replace args usage in genNativeCurry
code = code.replace(
  `joinWith ", " (map (\\a -> "$" <> safeName a) args)`,
  `joinWith ", " (map (\\a -> "$" <> safeName a.name) args)`
);
code = code.replace(
  `joinWith ", " (map (\\a -> "$" <> safeName a) args)`,
  `joinWith ", " (map (\\a -> "$" <> safeName a.name) args)`
);

// Update printExpr
code = code.replace(
  `  PhpFunction captures args stmts ->
    genCurry currentModPrefix allArities args captures stmts`,
  `  PhpFunction captures args retType stmts ->
    genCurry currentModPrefix allArities args retType captures stmts`
);

code = code.replace(
  `  PhpNativeFunction name args stmts ->
    "// " <> decl.identifier <> "\\n" <>
    genNativeCurry currentModPrefix allArities (safeFuncName name) args stmts <> "\\n" <>`,
  `  PhpNativeFunction name args retType stmts ->
    "// " <> decl.identifier <> "\\n" <>
    genNativeCurry currentModPrefix allArities (safeFuncName name) args retType stmts <> "\\n" <>`
);

fs.writeFileSync('src/Phpurs/Printer.purs', code);
