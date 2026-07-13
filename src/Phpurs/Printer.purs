module Phpurs.Printer where

import Prelude

import Data.String (joinWith, replaceAll, Pattern(..), Replacement(..), indexOf, take)
import Data.Maybe (fromMaybe, isNothing, Maybe(..))
import Data.Array (filter, length, mapWithIndex, index, concatMap)
import Data.Array as Array
import Phpurs.PhpAst (PhpExpr(..), PhpDecl, PhpFile)

foreign import safeNameImpl :: String -> String
foreign import safeFuncNameImpl :: String -> String

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

genNativeCurry :: String -> Array String -> Array PhpExpr -> String
genNativeCurry name args stmts =
  let
    argStr = joinWith ", " (mapWithIndex (\i a -> "$" <> safeName a <> (if i > 0 then " = null" else "")) args)
    argNames = joinWith ", " (map (\a -> "$" <> safeName a) args)
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
      (if length rewrittenStmts > 0 then "  " <> joinWith ";\n  " (map printExpr rewrittenStmts) <> ";\n" else "") <>
      "  __end:\n" <>
      "  return " <> nStr <> " < $__num ? $__res(...\\array_slice(\\func_get_args(), " <> nStr <> ")) : $__res;\n"

  in
    "function " <> name <> "(" <> argStr <> ") {\n" <> fnBody <> "}"

genCurry :: Array String -> Array String -> Array PhpExpr -> String
genCurry args captures stmts =
  let safeCaptures = map (\c -> "$" <> safeName c) captures
  in if length args == 0 then
    let useClause = if length safeCaptures > 0 then " use (" <> joinWith ", " safeCaptures <> ", &$__fn)" else " use (&$__fn)"
    in "function()" <> useClause <> " {\n" <> (joinWith ";\n" (map printExpr stmts) <> ";") <> "\n}"
  else
    let
      argStr = joinWith ", " (mapWithIndex (\i a -> "$" <> safeName a <> (if i > 0 then " = null" else "")) args)
      nStr = show (length args)
      
      nArgs = length args
      
      safeCaptures = map (\v -> "&$" <> safeName v) captures
      innerUseClause = if nArgs == 1 then
                    (if length safeCaptures > 0 then " use (" <> joinWith ", " safeCaptures <> ")" else "")
                  else
                    (if length safeCaptures > 0 then " use (" <> joinWith ", " safeCaptures <> ", &$__fn)" else " use (&$__fn)")
                    
      outerUseClause = if length safeCaptures > 0 then " use (" <> joinWith ", " safeCaptures <> ")" else ""
      
      rewrittenStmts = replaceReturn stmts

      fastPathStr = ""

      fnBody = 
        "  $__num = \\func_num_args();\n" <>
        (if nArgs == 1 then "" else
        "  if ($__num < " <> nStr <> ") {\n" <>
        fastPathStr <>
        "    return phpurs_curry_fallback($__fn, \\func_get_args(), " <> nStr <> ");\n" <>
        "  }\n") <>
        (if length rewrittenStmts > 0 then "  " <> joinWith ";\n  " (map printExpr rewrittenStmts) <> ";\n" else "") <>
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

printExpr :: PhpExpr -> String
printExpr expr = case expr of
  PhpNativeFunction name args stmts -> "/* ERROR: PhpNativeFunction inside expression */"
  PhpValueThunk name expr -> "/* ERROR: PhpValueThunk inside expression */"
  PhpFunction captures args stmts ->
    genCurry args captures stmts
  PhpVar ident -> "$" <> safeName ident
  PhpursThunkRef mbMod ident -> 
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> ""
      idStr = safeName (modPrefix <> ident)
    in "($GLOBALS['" <> idStr <> "'] ?? \\PhpursThunks::eval('" <> idStr <> "'))"
  PhpCall (PhpRaw raw) args -> raw <> "(" <> joinWith ", " (map printExpr args) <> ")"
  PhpCall abs args -> "(" <> printExpr abs <> ")(" <> joinWith ", " (map printExpr args) <> ")"
  PhpInt i -> show i
  PhpNumber n -> show n
  PhpString s -> 
    let
      s0 = replaceAll (Pattern "\\") (Replacement "\\\\") s
      s1 = replaceAll (Pattern "$") (Replacement "\\$") s0
      s2 = replaceAll (Pattern "\"") (Replacement "\\\"") s1
    in "\"" <> s2 <> "\""
  PhpBoolean b -> if b then "true" else "false"
  PhpArray arr -> "[" <> joinWith ", " (map printExpr arr) <> "]"
  PhpAssocArray arr -> "(object)[" <> joinWith ", " (map (\item -> "\"" <> safeName item.key <> "\" => " <> printExpr item.value) arr) <> "]"
  PhpPropertyAccess expr prop -> "(" <> printExpr expr <> ")->" <> safeName prop
  PhpArrayIndex expr idx -> "(" <> printExpr expr <> ")[" <> show idx <> "]"
  PhpClone obj -> "clone " <> printExpr obj
  PhpAssign ident expr -> "$" <> safeName ident <> " = " <> printExpr expr
  PhpAssignExpr left expr -> printExpr left <> " = " <> printExpr expr
  PhpIf cond thenStmts elseStmts ->
    let
      extractSwitch :: PhpExpr -> Maybe { subject :: PhpExpr, cases :: Array { val :: PhpExpr, body :: Array PhpExpr }, defaultBody :: Array PhpExpr }
      extractSwitch (PhpIf (PhpBinOp "===" subj litExpr) tBody [PhpIf (PhpBoolean true) tDefault eDefault]) | isLiteral litExpr =
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
          caseStmts = joinWith "\n" (map (\c -> "case " <> printExpr c.val <> ":\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map printExpr c.body) <> ";") <> "\nbreak;") sw.cases)
          defaultStmt = "default:\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map printExpr sw.defaultBody) <> ";") <> "\nbreak;"
        in
          "switch (" <> printExpr sw.subject <> ") {\n" <> caseStmts <> "\n" <> defaultStmt <> "\n}"
      Nothing ->
        let
          thenBody = joinWith ";\n" (map printExpr thenStmts) <> ";"
        in
          "if (" <> printExpr cond <> ") {\n" <> thenBody <> "\n}" <> 
          (if length elseStmts > 0 then " else {\n" <> (joinWith ";\n" (map printExpr elseStmts) <> ";") <> "\n}" else "")

  PhpThrow expr -> "throw new \\Exception(" <> printExpr expr <> ")"
  PhpTernary cond t e -> "(" <> printExpr cond <> " ? " <> printExpr t <> " : " <> printExpr e <> ")"
  PhpReturn expr -> "return " <> printExpr expr
  PhpBinOp op left right -> "(" <> printExpr left <> " " <> op <> " " <> printExpr right <> ")"
  PhpWhile cond stmts -> "while (" <> printExpr cond <> ") {\n" <> joinWith ";\n" (map printExpr stmts) <> ";\n}"
  PhpContinue -> "continue /*__LVL__*/"
  PhpRaw raw -> raw
  PhpNew cls args -> "new " <> cls <> "(" <> joinWith ", " (map printExpr args) <> ")"
  PhpGoto lbl -> "goto " <> safeName lbl <> ";"
  PhpLabel lbl -> safeName lbl <> ":"
  PhpSwitch subject cases defaultStmts ->
    let
      printCase c = joinWith "\n" (map (\m -> "case " <> printExpr m <> ":") c.matchCases) <> "\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map printExpr c.stmts) <> ";") <> "\nbreak;"
      casesStr = joinWith "\n" (map printCase cases)
      defaultStr = case defaultStmts of
        Just stmts -> "default:\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map printExpr stmts) <> ";") <> "\nbreak;"
        Nothing -> ""
    in "switch (" <> printExpr subject <> ") {\n" <> casesStr <> "\n" <> defaultStr <> "\n}"

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

printDecl :: PhpDecl -> String
printDecl decl = resolveContinues $ case decl.expression of
  PhpNativeFunction name args stmts ->
    "// " <> decl.identifier <> "\n" <>
    genNativeCurry (safeFuncName name) args stmts <> "\n" <>
    "$GLOBALS['" <> safeName decl.identifier <> "'] = __NAMESPACE__ . '\\\\" <> safeFuncName name <> "';\n"
  PhpValueThunk name expr -> ""
  expr ->
    "// " <> decl.identifier <> "\n$" <> safeName decl.identifier <> " = " <> printExpr expr <> ";\n"

printPhpFile :: Boolean -> String -> PhpFile -> String
printPhpFile isBundle ffiString file =
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
    decls = joinWith "\n" $ map printDecl file.decls
    thunks = filter (\d -> case d.expression of
      PhpValueThunk _ _ -> true
      _ -> false
    ) file.decls
    thunkAssignments = joinWith "\n" $ map (\d -> case d.expression of
      PhpValueThunk name expr -> "$GLOBALS['__phpurs_thunks']['" <> safeName name <> "'] = function() { $v = " <> resolveContinues (printExpr expr) <> "; return $v; };"
      _ -> ""
    ) thunks
    evalThunkStr = "if (!\\function_exists(__NAMESPACE__ . '\\\\phpurs_eval_thunk')) {\n" <>
      "  function phpurs_eval_thunk($id) {\n" <>
      "    static $cache = [];\n" <>
      "    if (isset($cache[$id]) || array_key_exists($id, $cache)) return $cache[$id];\n" <>
      "    if (isset($GLOBALS['__phpurs_thunks'][$id])) {\n" <>
      "      $cache[$id] = $GLOBALS['__phpurs_thunks'][$id]();\n" <>
      "      return $cache[$id];\n" <>
      "    }\n" <>
      "    if (\\function_exists('\\\\phpurs_eval_thunk') && __NAMESPACE__ !== '') return \\phpurs_eval_thunk($id);\n" <>
      "    throw new \\Exception(\"FATAL: Unknown thunk \" . $id);\n" <>
      "  }\n" <>
      "}\n"
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
    dataClasses = "if (!class_exists(__NAMESPACE__ . '\\\\Phpurs_Data0')) {\n  class Phpurs_Data0 { public $tag; public function __construct($t) { $this->tag = $t; } }\n  class Phpurs_Data1 { public $tag; public $value0; public function __construct($t, $value0) { $this->tag = $t; $this->value0 = $value0; } }\n  class Phpurs_Data2 { public $tag; public $value0, $value1; public function __construct($t, $value0, $value1) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; } }\n  class Phpurs_Data3 { public $tag; public $value0, $value1, $value2; public function __construct($t, $value0, $value1, $value2) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; } }\n  class Phpurs_Data4 { public $tag; public $value0, $value1, $value2, $value3; public function __construct($t, $value0, $value1, $value2, $value3) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; } }\n  class Phpurs_Data5 { public $tag; public $value0, $value1, $value2, $value3, $value4; public function __construct($t, $value0, $value1, $value2, $value3, $value4) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; } }\n  class Phpurs_Data6 { public $tag; public $value0, $value1, $value2, $value3, $value4, $value5; public function __construct($t, $value0, $value1, $value2, $value3, $value4, $value5) { $this->tag = $t; $this->value0 = $value0; $this->value1 = $value1; $this->value2 = $value2; $this->value3 = $value3; $this->value4 = $value4; $this->value5 = $value5; } }\n}\n"
    prefix = if isBundle then "namespace " else "<?php\n\nnamespace "
  in
    prefix <> ns <> ";\n\n" <> debugImps <> imps <> "\n\n" <> dataClasses <> fallback <> evalThunkStr <> thunkAssignments <> "\n$GLOBALS['" <> safeName "Prim_undefined" <> "'] = function() { throw new \\Exception(\"undefined\"); };\n" <> ffiString <> "\n\n" <> decls <> "\n"
