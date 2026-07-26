-- | The Code Printer for the `phpurs` backend.
-- | Converts the `PhpAst` into actual PHP strings.
-- | 
-- | Handles:
-- | - Printing PHP expressions (closures, arrays, binary operations).
-- | - Inlining the `\PhpursThunks` class for lazy value evaluation.
-- | - Generating the Curry Fallback function (`phpurs_curry_fallback`) to support 
-- |   partial application dynamically at runtime if arguments are missing.
-- | - Generating ADT data classes (`Phpurs_Data0`, `Phpurs_Data1`, etc.).
module Phpurs.Printer where

import Prelude

import Data.Map (Map)
import Data.Map as Map
import Data.String (joinWith, replaceAll, Pattern(..), Replacement(..), indexOf, take, drop)
import Data.Maybe (isNothing, Maybe(..))
import Data.Array (filter, length, mapWithIndex, concatMap)
import Phpurs.PhpAst (PhpExpr(..), PhpDecl, PhpFile)

foreign import safeNameImpl :: String -> String
foreign import safeFuncNameImpl :: String -> String
foreign import escapePhpStringImpl :: String -> String

safeName :: String -> String
safeName = safeNameImpl
  <<< replaceAll (Pattern "'") (Replacement "__prime__")

safeFuncName :: String -> String
safeFuncName = safeFuncNameImpl
  <<< replaceAll (Pattern "'") (Replacement "__prime__")

isUppercase :: String -> Boolean
isUppercase s =
  let
    c = take 1 s
  in
    c >= "A" && c <= "Z"

replaceReturn :: Array PhpExpr -> Array PhpExpr
replaceReturn = concatMap replaceExpr
  where
    replaceExpr :: PhpExpr -> Array PhpExpr
    replaceExpr (PhpReturn e) = [PhpAssign "__res" e, PhpGoto "__end"]
    replaceExpr (PhpIf cond t e) = [PhpIf cond (replaceReturn t) (replaceReturn e)]
    replaceExpr (PhpWhile cond body) = [PhpWhile cond (replaceReturn body)]
    replaceExpr (PhpSwitch cond cases def) = 
      [PhpSwitch cond (map (\c -> c { stmts = replaceReturn c.stmts }) cases) (map replaceReturn def)]
    replaceExpr other = [other]

genNativeCurry :: String -> Map String Int -> String -> Array { name :: String, type_ :: String } -> String -> Array PhpExpr -> String
genNativeCurry currentModPrefix allArities name args retType stmts =
  let
    argStr = joinWith ", " (mapWithIndex (\i a -> 
      let t = if a.type_ == "mixed" then "" else if a.type_ /= "" && i == 0 then a.type_ <> " " else ""
      in t <> "$" <> safeName a.name <> (if i > 0 then " = null" else "")
    ) args)
    retStr = if retType == "mixed" || retType == "" then "" else if retType == "\\Closure" then ": \\Closure" else ": " <> retType <> "|\\Closure"

    nStr = show (length args)
    
    rewrittenStmts = replaceReturn stmts

    fastPathStr = ""

    fnBody = 
      "  $__num = \\func_num_args();\n" <>
      "  $__fn = __NAMESPACE__ . '\\\\' . '" <> name <> "';\n" <>
      "  if ($__num < " <> nStr <> ") {\n" <>
      fastPathStr <>
      "    return phpurs_curry_fallback($__fn, \\func_get_args(), " <> nStr <> ");\n" <>
      "  }\n" <>
      (if length rewrittenStmts > 0 then "  " <> joinWith ";\n  " (map (printExpr currentModPrefix allArities) rewrittenStmts) <> ";\n" else "") <>
      "  __end:\n" <>
      "  return " <> nStr <> " < $__num ? $__res(...\\array_slice(\\func_get_args(), " <> nStr <> ")) : $__res;\n"

  in
    "function " <> name <> "(" <> argStr <> ")" <> retStr <> " {\n" <> fnBody <> "}"

genCurry :: String -> Map String Int -> Array { name :: String, type_ :: String } -> String -> Array String -> Array PhpExpr -> String
genCurry currentModPrefix allArities args retType captures stmts =
  let safeCaptures = map (\v -> if take 1 v == "&" then "&$" <> safeName (drop 1 v) else "$" <> safeName v) captures
      retStr = if retType == "mixed" || retType == "" then "" else if retType == "\\Closure" then ": \\Closure" else ": " <> retType <> "|\\Closure"
  in if length args == 0 then
    let useClause = if length safeCaptures > 0 then " use (" <> joinWith ", " safeCaptures <> ", &$__fn)" else " use (&$__fn)"
    in "function()" <> useClause <> retStr <> " {\n" <> (joinWith ";\n" (map (printExpr currentModPrefix allArities) stmts) <> ";") <> "\n}"
  else
    let
      argStr = joinWith ", " (mapWithIndex (\i a -> 
        let t = if a.type_ == "mixed" then "" else if a.type_ /= "" && i == 0 then a.type_ <> " " else ""
        in t <> "$" <> safeName a.name <> (if i > 0 then " = null" else "")
      ) args)
      nStr = show (length args)
      nArgs = length args
      safeCaps = map (\v -> if take 1 v == "&" then "&$" <> safeName (drop 1 v) else "$" <> safeName v) captures
      outerUseClause = if length safeCaps > 0 then " use (" <> joinWith ", " safeCaps <> ")" else ""
      innerUseClause = if nArgs == 1 then
                    (if length safeCaps > 0 then " use (" <> joinWith ", " safeCaps <> ")" else "")
                  else
                    (if length safeCaps > 0 then " use (" <> joinWith ", " safeCaps <> ", &$__fn)" else " use (&$__fn)")
      rewrittenStmts = replaceReturn stmts
      fastPathStr = ""
      fnBody = 
        "  $__num = \\func_num_args();\n" <>
        (if nArgs == 1 then "" else
        "  if ($__num < " <> nStr <> ") {\n" <>
        fastPathStr <>
        "    return phpurs_curry_fallback($__fn, \\func_get_args(), " <> nStr <> ");\n" <>
        "  }\n") <>
        (if length rewrittenStmts > 0 then "  " <> joinWith ";\n  " (map (printExpr currentModPrefix allArities) rewrittenStmts) <> ";\n" else "") <>
        "  __end:\n" <>
        "  return $__num > " <> nStr <> " ? $__res(...\\array_slice(\\func_get_args(), " <> nStr <> ")) : $__res;\n"
    in 
      if nArgs == 1 then
        "function(" <> argStr <> ")" <> innerUseClause <> " {\n" <> fnBody <> "}"
      else
        "(function()" <> outerUseClause <> " {\n" <>
        "  $__fn = function(" <> argStr <> ")" <> innerUseClause <> " {\n" <> fnBody <> "  };\n" <>
        "  return $__fn;\n" <>
        "})()"

printExpr :: String -> Map String Int -> PhpExpr -> String
printExpr currentModPrefix allArities expr = case expr of
  PhpNativeFunction _ _ _ _ -> "/* ERROR: PhpNativeFunction inside expression */"
  PhpGlobalAssign _ _ -> "/* ERROR: PhpGlobalAssign inside expression */"
  PhpFunction captures args retType stmts ->
    genCurry currentModPrefix allArities args retType captures stmts
  PhpVar ident -> "$" <> safeName ident
  PhpGlobalVar mbMod ident -> 
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> ""
      idStr = safeName (modPrefix <> ident)
    in "$GLOBALS['" <> idStr <> "']"
  PhpDirectCall name args ->
    let
      argsStr = joinWith ", " (map (printExpr currentModPrefix allArities) args)
    in "$GLOBALS['" <> safeName name <> "'](" <> argsStr <> ")"
  PhpCall (PhpGlobalVar mbMod ident) args ->
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> ""
      idStr = safeName (modPrefix <> ident)
    in "($GLOBALS['" <> idStr <> "'])(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"
  PhpCall (PhpCall inner args1) args2 ->
    printExpr currentModPrefix allArities (PhpCall inner (args1 <> args2))
  PhpCall (PhpRaw raw) args -> raw <> "(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"
  PhpCall abs args -> "(" <> printExpr currentModPrefix allArities abs <> ")(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"
  PhpInt i -> show i
  PhpNumber n -> show n
  PhpString s -> "\"" <> escapePhpStringImpl s <> "\""
  PhpBoolean b -> if b then "true" else "false"
  PhpArray arr -> "[" <> joinWith ", " (map (printExpr currentModPrefix allArities) arr) <> "]"
  PhpAssocArray arr -> "[" <> joinWith ", " (map (\item -> "\"" <> safeName item.key <> "\" => " <> printExpr currentModPrefix allArities item.value) arr) <> "]"
  PhpPropertyAccess e prop -> "(" <> printExpr currentModPrefix allArities e <> ")->{'" <> safeName prop <> "'}"
  PhpRecordAccess e prop -> "(" <> printExpr currentModPrefix allArities e <> ")['" <> safeName prop <> "']"
  PhpArrayIndex arr i -> "(" <> printExpr currentModPrefix allArities arr <> ")[" <> show i <> "]"
  PhpClone obj -> "clone " <> printExpr currentModPrefix allArities obj
  PhpAssign ident v -> "$" <> safeName ident <> " = " <> printExpr currentModPrefix allArities v
  PhpAssignExpr left v -> printExpr currentModPrefix allArities left <> " = " <> printExpr currentModPrefix allArities v
  PhpIf cond thenStmts elseStmts ->
    let
      extractSwitch :: PhpExpr -> Maybe { subject :: PhpExpr, cases :: Array { val :: PhpExpr, body :: Array PhpExpr }, defaultBody :: Array PhpExpr }
      extractSwitch (PhpIf (PhpBinOp "===" subj litExpr) tBody [PhpIf (PhpBoolean true) tDefault _]) | isLiteral litExpr =
        Just { subject: subj, cases: [{ val: litExpr, body: tBody }], defaultBody: tDefault }
      extractSwitch (PhpIf (PhpBinOp "===" subj litExpr) tBody [eBody@(PhpIf _ _ _)]) | isLiteral litExpr =
        case extractSwitch eBody of
          Just rest -> 
            if subj == rest.subject then
              Just { subject: subj, cases: [{ val: litExpr, body: tBody }] <> rest.cases, defaultBody: rest.defaultBody }
            else Nothing
          Nothing -> Nothing
      extractSwitch (PhpIf (PhpBinOp "===" subj litExpr) tBody eBodyArray) | isLiteral litExpr =
        Just { subject: subj, cases: [{ val: litExpr, body: tBody }], defaultBody: eBodyArray }
      extractSwitch _ = Nothing
      
      isLiteral :: PhpExpr -> Boolean
      isLiteral (PhpString _) = true
      isLiteral (PhpInt _) = true
      isLiteral (PhpNumber _) = true
      isLiteral _ = false
      
    in case extractSwitch (PhpIf cond thenStmts elseStmts) of
      Just sw ->
        let
          caseStmts = joinWith "\n" (map (\c -> "case " <> printExpr currentModPrefix allArities c.val <> ":\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExpr currentModPrefix allArities) c.body) <> ";") <> "\nbreak;") sw.cases)
          defaultStmt = "default:\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExpr currentModPrefix allArities) sw.defaultBody) <> ";") <> "\nbreak;"
        in
          "switch (" <> printExpr currentModPrefix allArities sw.subject <> ") {\n" <> caseStmts <> "\n" <> defaultStmt <> "\n}"
      Nothing ->
        let
          thenBody = joinWith ";\n" (map (printExpr currentModPrefix allArities) thenStmts) <> ";"
        in
          "if (" <> printExpr currentModPrefix allArities cond <> ") {\n" <> thenBody <> "\n}" <> 
          (if length elseStmts > 0 then " else {\n" <> (joinWith ";\n" (map (printExpr currentModPrefix allArities) elseStmts) <> ";") <> "\n}" else "")

  PhpThrow v -> "throw new \\Exception(" <> printExpr currentModPrefix allArities v <> ")"
  PhpInstanceOf v cls -> printExpr currentModPrefix allArities v <> " instanceof " <> cls
  PhpMatch subj cases defExpr ->
    let
      printCase { val, body } = printExpr currentModPrefix allArities val <> " => " <> printExpr currentModPrefix allArities body
      casesStr = joinWith ", " (map printCase cases)
      defStr = "default => " <> printExpr currentModPrefix allArities defExpr
    in
      "match (" <> printExpr currentModPrefix allArities subj <> ") { " <> casesStr <> (if length cases > 0 then ", " else "") <> defStr <> " }"
  PhpTernary cond t e -> "(" <> printExpr currentModPrefix allArities cond <> " ? " <> printExpr currentModPrefix allArities t <> " : " <> printExpr currentModPrefix allArities e <> ")"
  PhpReturn v -> "return " <> printExpr currentModPrefix allArities v
  PhpBinOp op left right -> "(" <> printExpr currentModPrefix allArities left <> " " <> op <> " " <> printExpr currentModPrefix allArities right <> ")"
  PhpWhile cond stmts -> "while (" <> printExpr currentModPrefix allArities cond <> ") {\n" <> joinWith ";\n" (map (printExpr currentModPrefix allArities) stmts) <> ";\n}"
  PhpContinue -> "continue /*__LVL__*/"
  PhpRaw raw -> raw
  PhpNew cls args -> "new " <> cls <> "(" <> joinWith ", " (map (printExpr currentModPrefix allArities) args) <> ")"
  PhpGoto lbl -> "goto " <> safeName lbl <> ";"
  PhpLabel lbl -> safeName lbl <> ":"
  PhpSwitch subject cases defaultStmts ->
    let
      printCase c = joinWith "\n" (map (\m -> "case " <> printExpr currentModPrefix allArities m <> ":") c.matchCases) <> "\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExpr currentModPrefix allArities) c.stmts) <> ";") <> "\nbreak;"
      casesStr = joinWith "\n" (map printCase cases)
      defaultStr = case defaultStmts of
        Just stmts -> "default:\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExpr currentModPrefix allArities) stmts) <> ";") <> "\nbreak;"
        Nothing -> ""
    in "switch (" <> printExpr currentModPrefix allArities subject <> ") {\n" <> casesStr <> "\n" <> defaultStr <> "\n}"

resolveContinues :: String -> String
resolveContinues str =
  let
    r0 = replaceAll (Pattern "/*__LVL__*/") (Replacement "") str
    r1 = replaceAll (Pattern "continue I;") (Replacement "continue 2;") r0
    r2 = replaceAll (Pattern "continue II;") (Replacement "continue 3;") r1
    r3 = replaceAll (Pattern "continue III;") (Replacement "continue 4;") r2
    r4 = replaceAll (Pattern "continue IIII;") (Replacement "continue 5;") r3
    r5 = replaceAll (Pattern "continue IIIII;") (Replacement "continue 6;") r4
    r6 = replaceAll (Pattern "continue IIIIII;") (Replacement "continue 7;") r5
    r7 = replaceAll (Pattern "continue IIIIIII;") (Replacement "continue 8;") r6
    r8 = replaceAll (Pattern "continue IIIIIIII;") (Replacement "continue 9;") r7
    r9 = replaceAll (Pattern "continue IIIIIIIII;") (Replacement "continue 10;") r8
    r10 = replaceAll (Pattern "continue IIIIIIIIII;") (Replacement "continue 11;") r9
    r11 = replaceAll (Pattern "continue IIIIIIIIIII;") (Replacement "continue 12;") r10
    r12 = replaceAll (Pattern "continue IIIIIIIIIIII;") (Replacement "continue 13;") r11
    r13 = replaceAll (Pattern "continue IIIIIIIIIIIII;") (Replacement "continue 14;") r12
    r14 = replaceAll (Pattern "continue IIIIIIIIIIIIII;") (Replacement "continue 15;") r13
    r15 = replaceAll (Pattern "continue IIIIIIIIIIIIIII;") (Replacement "continue 16;") r14
  in r15

printDecl :: String -> Map String Int -> PhpDecl -> String
printDecl currentModPrefix allArities decl = resolveContinues $ case decl.expression of
  PhpNativeFunction name args retType stmts ->
    "// " <> decl.identifier <> "\n" <>
    genNativeCurry currentModPrefix allArities (safeFuncName name) args retType stmts <> "\n" <>
    "$GLOBALS['" <> safeName decl.identifier <> "'] = __NAMESPACE__ . '\\\\" <> safeFuncName name <> "';\n"
  PhpGlobalAssign name expr ->
    "// " <> decl.identifier <> "\n$GLOBALS['" <> safeName name <> "'] = " <> printExpr currentModPrefix allArities expr <> ";\n"
  expr ->
    "// " <> decl.identifier <> "\n$" <> safeName decl.identifier <> " = " <> printExpr currentModPrefix allArities expr <> ";\n"

-- | Main printing function that assembles a complete PHP file.
-- | Generates the namespace, require statements (if not bundled), standard library
-- | helpers (ADT classes, curry fallback), the thunk definitions, and finally the declarations.
printPhpFile :: Boolean -> String -> Map String Int -> PhpFile -> String
printPhpFile isBundle ffiString allArities file =
  let
    ns = joinWith "\\" file.namespace
    importsToRequire = filter
      ( \i ->
          let
            m = joinWith "." i
          in
            m /= "Prim" && isNothing (indexOf (Pattern "Prim.") m)
      )
      file.imports
    imps = if isBundle then "" else joinWith "\n" $ map (\i -> "require_once __DIR__ . '/../" <> joinWith "." i <> "/index.php';") importsToRequire
    debugImps = "// ALL IMPORTS: " <> joinWith ", " (map (\i -> joinWith "." i) file.imports) <> "\n" <> "// TO REQUIRE: " <> joinWith ", " (map (\i -> joinWith "." i) importsToRequire) <> "\n"
    currentModPrefix = if length file.namespace > 0 then joinWith "_" file.namespace <> "_" else ""
    rawDeclsStr = joinWith "\n" file.rawDecls
    decls = joinWith "\n" $ map (printDecl currentModPrefix allArities) file.decls
    fallback = "if (!\\function_exists(__NAMESPACE__ . '\\\\phpurs_curry_fallback')) {\n" <>
      "  function phpurs_curry_fallback($fn, $args, $expected) {\n" <>
      "    $missing = $expected - \\count($args);\n" <>
      "    if ($missing === 1) {\n" <>
      "      return function($a) use ($fn, $args, $expected) {\n" <>
      "        $num = \\func_num_args();\n" <>
      "        if ($num > 1) {\n" <>
      "          $merged = \\array_merge($args, \\func_get_args());\n" <>
      "          $res = $fn(...\\array_slice($merged, 0, $expected));\n" <>
      "          return $res(...\\array_slice($merged, $expected));\n" <>
      "        }\n" <>
      "        $args[] = $a;\n" <>
      "        return $fn(...$args);\n" <>
      "      };\n" <>
      "    }\n" <>
      "    if ($missing === 2) {\n" <>
      "      return function($a, $b = null) use ($fn, $args, $expected) {\n" <>
      "        $num = \\func_num_args();\n" <>
      "        if ($num === 1) { $args[] = $a; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num > 2) {\n" <>
      "          $merged = \\array_merge($args, \\func_get_args());\n" <>
      "          $res = $fn(...\\array_slice($merged, 0, $expected));\n" <>
      "          return $res(...\\array_slice($merged, $expected));\n" <>
      "        }\n" <>
      "        $args[] = $a; $args[] = $b;\n" <>
      "        return $fn(...$args);\n" <>
      "      };\n" <>
      "    }\n" <>
      "    if ($missing === 3) {\n" <>
      "      return function($a, $b = null, $c = null) use ($fn, $args, $expected) {\n" <>
      "        $num = \\func_num_args();\n" <>
      "        if ($num === 1) { $args[] = $a; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num === 2) { $args[] = $a; $args[] = $b; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num > 3) {\n" <>
      "          $merged = \\array_merge($args, \\func_get_args());\n" <>
      "          $res = $fn(...\\array_slice($merged, 0, $expected));\n" <>
      "          return $res(...\\array_slice($merged, $expected));\n" <>
      "        }\n" <>
      "        $args[] = $a; $args[] = $b; $args[] = $c;\n" <>
      "        return $fn(...$args);\n" <>
      "      };\n" <>
      "    }\n" <>
      "    if ($missing === 4) {\n" <>
      "      return function($a, $b = null, $c = null, $d = null) use ($fn, $args, $expected) {\n" <>
      "        $num = \\func_num_args();\n" <>
      "        if ($num === 1) { $args[] = $a; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num === 2) { $args[] = $a; $args[] = $b; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num === 3) { $args[] = $a; $args[] = $b; $args[] = $c; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num > 4) {\n" <>
      "          $merged = \\array_merge($args, \\func_get_args());\n" <>
      "          $res = $fn(...\\array_slice($merged, 0, $expected));\n" <>
      "          return $res(...\\array_slice($merged, $expected));\n" <>
      "        }\n" <>
      "        $args[] = $a; $args[] = $b; $args[] = $c; $args[] = $d;\n" <>
      "        return $fn(...$args);\n" <>
      "      };\n" <>
      "    }\n" <>
      "    return function(...$more) use ($fn, $args, $expected) {\n" <>
      "      $merged = \\array_merge($args, $more);\n" <>
      "      if (\\count($merged) >= $expected) {\n" <>
      "        $res = $fn(...\\array_slice($merged, 0, $expected));\n" <>
      "        if (\\count($merged) > $expected) {\n" <>
      "          return $res(...\\array_slice($merged, $expected));\n" <>
      "        }\n" <>
      "        return $res;\n" <>
      "      }\n" <>
      "      return phpurs_curry_fallback($fn, $merged, $expected);\n" <>
      "    };\n" <>
      "  }\n" <>
      "}\n"
    dataClasses = "if (!class_exists(__NAMESPACE__ . '\\\\Phpurs_Data0')) {\n" <>
      "  class Phpurs_Data0 { public $tag; public function __construct($t) { $this->tag = $t; } }\n" <>
      "  class Phpurs_Data1 { public $tag; public $value0; public function __construct($t, $value0) { $this->tag = $t; $this->value0 = $value0; } }\n" <>
      "  class Phpurs_Data2 { public $tag; public $value0, $value1; public function __construct($t, $value0, $value1) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; } }\n" <>
      "  class Phpurs_Data3 { public $tag; public $value0, $value1, $value2; public function __construct($t, $value0, $value1, $value2) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; } }\n" <>
      "  class Phpurs_Data4 { public $tag; public $value0, $value1, $value2, $value3; public function __construct($t, $value0, $value1, $value2, $value3) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; } }\n" <>
      "  class Phpurs_Data5 { public $tag; public $value0, $value1, $value2, $value3, $value4; public function __construct($t, $value0, $value1, $value2, $value3, $value4) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; } }\n" <>
      "  class Phpurs_Data6 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; } }\n" <>
      "  class Phpurs_Data7 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; } }\n" <>
      "  class Phpurs_Data8 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; $this->value7 = $value7; } }\n" <>
      "  class Phpurs_Data9 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; $this->value7 = $value7; $this->value8 = $value8; } }\n" <>
      "  class Phpurs_Data10 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; $this->value7 = $value7; $this->value8 = $value8; $this->value9 = $value9; } }\n" <>
      "  class Phpurs_Data11 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9, $value10; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9, $value10) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; $this->value7 = $value7; $this->value8 = $value8; $this->value9 = $value9; $this->value10 = $value10; } }\n" <>
      "  class Phpurs_Data12 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5, $value6, $value7, $value8, $value9, $value10, $value11) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; $this->value6 = $value6; $this->value7 = $value7; $this->value8 = $value8; $this->value9 = $value9; $this->value10 = $value10; $this->value11 = $value11; } }\n" <>
      "}\n"
    prefix = if isBundle then "namespace " <> ns <> " {\n" else "<?php\n\nnamespace " <> ns <> ";\n\n"
    suffix = if isBundle then "\n}\n" else "\n"
  in
    prefix <> debugImps <> imps <> "\n\n" <> dataClasses <> fallback <> "\n$GLOBALS['" <> safeName "Prim_undefined" <> "'] = function() { throw new \\Exception(\"undefined\"); };\n" <> ffiString <> "\n\n" <> rawDeclsStr <> "\n\n" <> decls <> suffix
