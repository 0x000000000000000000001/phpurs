module Phpurs.Printer where

import Prelude

import Data.String (joinWith, replaceAll, Pattern(..), Replacement(..), indexOf, take)
import Data.Maybe (fromMaybe, isNothing, Maybe(..))
import Data.Array (filter, length, mapWithIndex, index, concatMap)
import Data.Array as Array
import Phpurs.PhpAst (PhpExpr(..), PhpDecl, PhpFile)

sanitize :: String -> String
sanitize = replaceAll (Pattern "$") (Replacement "__dollar__")
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
    argStr = joinWith ", " (mapWithIndex (\i a -> "$" <> sanitize a <> (if i > 0 then " = null" else "")) args)
    argNames = joinWith ", " (map (\a -> "$" <> sanitize a) args)
    nStr = show (length args)
    
    rewrittenStmts = replaceReturn stmts

    fastPathStr = 
      let
        aNames = map sanitize args
        nArgs = length args
        arg idx = fromMaybe "" (index aNames idx)
      in
        if nArgs == 2 then
          "    if ($__num === 1) return function($" <> arg 1 <> ") use ($" <> arg 0 <> ", $__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> "); };\n"
        else if nArgs == 3 then
          "    if ($__num === 2) return function($" <> arg 2 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", $__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> "); };\n" <>
          "    if ($__num === 1) return function($" <> arg 1 <> ", $" <> arg 2 <> " = null) use ($" <> arg 0 <> ", $__fn) {\n" <>
          "      $__num2 = func_num_args();\n" <>
          "      if ($__num2 === 2) return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ");\n" <>
          "      if ($__num2 === 1) return function($" <> arg 2 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", $__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> "); };\n" <>
          "      return phpurs_curry_fallback($__fn, [$" <> arg 0 <> "], 3);\n" <>
          "    };\n"
        else if nArgs == 4 then
          "    if ($__num === 3) return function($" <> arg 3 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> "); };\n" <>
          "    if ($__num === 2) return function($" <> arg 2 <> ", $" <> arg 3 <> " = null) use ($" <> arg 0 <> ", $" <> arg 1 <> ", $__fn) {\n" <>
          "      $__num2 = func_num_args();\n" <>
          "      if ($__num2 === 2) return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> ");\n" <>
          "      if ($__num2 === 1) return function($" <> arg 3 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> "); };\n" <>
          "      return phpurs_curry_fallback($__fn, [$" <> arg 0 <> ", $" <> arg 1 <> "], 4);\n" <>
          "    };\n" <>
          "    if ($__num === 1) return function($" <> arg 1 <> ", $" <> arg 2 <> " = null, $" <> arg 3 <> " = null) use ($" <> arg 0 <> ", $__fn) {\n" <>
          "      $__num2 = func_num_args();\n" <>
          "      if ($__num2 === 3) return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> ");\n" <>
          "      if ($__num2 === 2) return function($" <> arg 3 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> "); };\n" <>
          "      if ($__num2 === 1) return function($" <> arg 2 <> ", $" <> arg 3 <> " = null) use ($" <> arg 0 <> ", $" <> arg 1 <> ", $__fn) {\n" <>
          "        $__num3 = func_num_args();\n" <>
          "        if ($__num3 === 2) return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> ");\n" <>
          "        if ($__num3 === 1) return function($" <> arg 3 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> "); };\n" <>
          "        return phpurs_curry_fallback($__fn, [$" <> arg 0 <> ", $" <> arg 1 <> "], 4);\n" <>
          "      };\n" <>
          "      return phpurs_curry_fallback($__fn, [$" <> arg 0 <> "], 4);\n" <>
          "    };\n"
        else ""

    fnBody = 
      "  $__num = func_num_args();\n" <>
      "  $__fn = __NAMESPACE__ . '\\\\' . '" <> name <> "';\n" <>
      "  if ($__num < " <> nStr <> ") {\n" <>
      fastPathStr <>
      "    return phpurs_curry_fallback($__fn, func_get_args(), " <> nStr <> ");\n" <>
      "  }\n" <>
      (if length rewrittenStmts > 0 then "  " <> joinWith ";\n  " (map printExpr rewrittenStmts) <> ";\n" else "") <>
      "  __end:\n" <>
      "  return " <> nStr <> " < $__num ? $__res(...array_slice(func_get_args(), " <> nStr <> ")) : $__res;\n"

  in
    "function " <> name <> "(" <> argStr <> ") {\n" <> fnBody <> "}"

genCurry :: Array String -> Array String -> Array PhpExpr -> String
genCurry args captures stmts =
  if length args == 0 then "function() use (&$__fn) {\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map printExpr stmts) <> ";") <> "\n}"
  else
    let
      argStr = joinWith ", " (mapWithIndex (\i a -> "$" <> sanitize a <> (if i > 0 then " = null" else "")) args)
      argNames = joinWith ", " (map (\a -> "$" <> sanitize a) args)
      nStr = show (length args)
      
      nArgs = length args
      
      useClause = if nArgs == 1 then
                    (if length captures > 0 then " use (" <> joinWith ", " captures <> ")" else "")
                  else
                    (if length captures > 0 then " use (" <> joinWith ", " captures <> ", &$__fn)" else " use (&$__fn)")
      
      rewrittenStmts = replaceReturn stmts

      fastPathStr = 
        let
          aNames = map sanitize args
          nArgs = length args
          arg idx = fromMaybe "" (index aNames idx)
        in
          if nArgs == 2 then
            "    if ($__num === 1) return function($" <> arg 1 <> ") use ($" <> arg 0 <> ", &$__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> "); };\n"
          else if nArgs == 3 then
            "    if ($__num === 2) return function($" <> arg 2 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", &$__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> "); };\n" <>
            "    if ($__num === 1) return function($" <> arg 1 <> ", $" <> arg 2 <> " = null) use ($" <> arg 0 <> ", &$__fn) {\n" <>
            "      $__num2 = func_num_args();\n" <>
            "      if ($__num2 === 2) return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ");\n" <>
            "      if ($__num2 === 1) return function($" <> arg 2 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", &$__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> "); };\n" <>
            "      return phpurs_curry_fallback($__fn, [$" <> arg 0 <> "], 3);\n" <>
            "    };\n"
          else if nArgs == 4 then
            "    if ($__num === 3) return function($" <> arg 3 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", &$__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> "); };\n" <>
            "    if ($__num === 2) return function($" <> arg 2 <> ", $" <> arg 3 <> " = null) use ($" <> arg 0 <> ", $" <> arg 1 <> ", &$__fn) {\n" <>
            "      $__num2 = func_num_args();\n" <>
            "      if ($__num2 === 2) return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> ");\n" <>
            "      if ($__num2 === 1) return function($" <> arg 3 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", &$__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> "); };\n" <>
            "      return phpurs_curry_fallback($__fn, [$" <> arg 0 <> ", $" <> arg 1 <> "], 4);\n" <>
            "    };\n" <>
            "    if ($__num === 1) return function($" <> arg 1 <> ", $" <> arg 2 <> " = null, $" <> arg 3 <> " = null) use ($" <> arg 0 <> ", &$__fn) {\n" <>
            "      $__num2 = func_num_args();\n" <>
            "      if ($__num2 === 3) return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> ");\n" <>
            "      if ($__num2 === 2) return function($" <> arg 3 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", &$__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> "); };\n" <>
            "      if ($__num2 === 1) return function($" <> arg 2 <> ", $" <> arg 3 <> " = null) use ($" <> arg 0 <> ", $" <> arg 1 <> ", &$__fn) {\n" <>
            "        $__num3 = func_num_args();\n" <>
            "        if ($__num3 === 2) return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> ");\n" <>
            "        if ($__num3 === 1) return function($" <> arg 3 <> ") use ($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", &$__fn) { return $__fn($" <> arg 0 <> ", $" <> arg 1 <> ", $" <> arg 2 <> ", $" <> arg 3 <> "); };\n" <>
            "        return phpurs_curry_fallback($__fn, [$" <> arg 0 <> ", $" <> arg 1 <> "], 4);\n" <>
            "      };\n" <>
            "      return phpurs_curry_fallback($__fn, [$" <> arg 0 <> "], 4);\n" <>
            "    };\n"
          else ""

      fnBody = 
        "  $__num = func_num_args();\n" <>
        (if nArgs == 1 then "" else
        "  if ($__num < " <> nStr <> ") {\n" <>
        fastPathStr <>
        "    return phpurs_curry_fallback($__fn, func_get_args(), " <> nStr <> ");\n" <>
        "  }\n") <>
        (if length rewrittenStmts > 0 then "  " <> joinWith ";\n  " (map printExpr rewrittenStmts) <> ";\n" else "") <>
        "  __end:\n" <>
        "  return $__num > " <> nStr <> " ? $__res(...array_slice(func_get_args(), " <> nStr <> ")) : $__res;\n"

    in 
      if nArgs == 1 then
        "function(" <> argStr <> ")" <> useClause <> " {\n" <> fnBody <> "}"
      else
        "(function()" <> useClause <> " {\n" <>
        "  $__fn = function(" <> argStr <> ")" <> useClause <> " {\n" <> fnBody <> "  };\n" <>
        "  return $__fn;\n" <>
        "})()"

printExpr :: PhpExpr -> String
printExpr expr = case expr of
  PhpNativeFunction name args stmts -> "/* ERROR: PhpNativeFunction inside expression */"
  PhpValueThunk name expr -> "/* ERROR: PhpValueThunk inside expression */"
  PhpFunction captures args stmts ->
    genCurry args captures stmts
  PhpVar ident -> "$" <> sanitize ident
  PhpGlobalVar mbMod ident -> 
    let
      modPrefix = case mbMod of
        Just mod -> joinWith "_" mod <> "_"
        Nothing -> ""
      idStr = sanitize (modPrefix <> ident)
      pathStr = case mbMod of
        Just mod -> "\\" <> joinWith "\\" mod <> "\\phpurs_eval_thunk('" <> idStr <> "')"
        Nothing -> "phpurs_eval_thunk('" <> idStr <> "')"
    in "($GLOBALS['" <> idStr <> "'] ?? " <> pathStr <> ")"
  PhpCall (PhpRaw raw) args -> raw <> "(" <> joinWith ", " (map printExpr args) <> ")"
  PhpCall abs args -> "(" <> printExpr abs <> ")(" <> joinWith ", " (map printExpr args) <> ")"
  PhpInt i -> show i
  PhpNumber n -> show n
  PhpString s -> "\"" <> replaceAll (Pattern "\"") (Replacement "\\\"") s <> "\""
  PhpBoolean b -> if b then "true" else "false"
  PhpArray arr -> "[" <> joinWith ", " (map printExpr arr) <> "]"
  PhpAssocArray arr -> "(object)[" <> joinWith ", " (map (\item -> "\"" <> item.key <> "\" => " <> printExpr item.value) arr) <> "]"
  PhpPropertyAccess expr prop -> "(" <> printExpr expr <> ")->" <> sanitize prop
  PhpClone obj -> "clone " <> printExpr obj
  PhpAssign ident expr -> "$" <> sanitize ident <> " = " <> printExpr expr
  PhpIf cond thenStmts elseStmts ->
    let
      thenBody = joinWith ";\n" (map printExpr thenStmts) <> ";"
      elseBody = joinWith ";\n" (map printExpr elseStmts) <> ";"
      
      extractSwitch :: PhpExpr -> Maybe { subject :: PhpExpr, cases :: Array { val :: String, body :: String }, defaultBody :: String }
      extractSwitch (PhpIf (PhpBinOp "===" subj litExpr) tBody [PhpIf (PhpBoolean true) tDefault eDefault]) | isLiteral litExpr =
        Just { subject: subj, cases: [{ val: printExpr litExpr, body: joinWith ";\n" (map printExpr tBody) <> ";" }], defaultBody: joinWith ";\n" (map printExpr tDefault) <> ";" }
      extractSwitch (PhpIf (PhpBinOp "===" subj litExpr) tBody [eBody@(PhpIf _ _ _)]) | isLiteral litExpr =
        case extractSwitch eBody of
          Just rest -> 
            if printExpr subj == printExpr rest.subject then
              Just { subject: subj, cases: [{ val: printExpr litExpr, body: joinWith ";\n" (map printExpr tBody) <> ";" }] <> rest.cases, defaultBody: rest.defaultBody }
            else Nothing
          Nothing -> Nothing
      extractSwitch (PhpIf (PhpBinOp "===" subj litExpr) tBody eBodyArray) | isLiteral litExpr =
        Just { subject: subj, cases: [{ val: printExpr litExpr, body: joinWith ";\n" (map printExpr tBody) <> ";" }], defaultBody: joinWith ";\n" (map printExpr eBodyArray) <> ";" }
      extractSwitch _ = Nothing
      
      isLiteral :: PhpExpr -> Boolean
      isLiteral (PhpString _) = true
      isLiteral (PhpInt _) = true
      isLiteral (PhpNumber _) = true
      isLiteral (PhpBoolean _) = true
      isLiteral _ = false
      
    in case extractSwitch (PhpIf cond thenStmts elseStmts) of
      Just sw ->
        let
          caseStmts = joinWith "\n" (map (\c -> "case " <> c.val <> ":\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") c.body <> "\nbreak;") sw.cases)
          defaultStmt = "default:\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") sw.defaultBody <> "\nbreak;"
        in
          "switch (" <> printExpr sw.subject <> ") {\n" <> caseStmts <> "\n" <> defaultStmt <> "\n}"
      Nothing ->
        "if (" <> printExpr cond <> ") {\n" <> thenBody <> "\n} else {\n" <> elseBody <> "\n}"


  PhpThrow msg -> "throw new \\Exception(\"" <> replaceAll (Pattern "\"") (Replacement "\\\"") msg <> "\")"
  PhpTernary cond t e -> "(" <> printExpr cond <> " ? " <> printExpr t <> " : " <> printExpr e <> ")"
  PhpReturn expr -> "return " <> printExpr expr
  PhpBinOp op left right -> "(" <> printExpr left <> " " <> op <> " " <> printExpr right <> ")"
  PhpWhile cond stmts -> "while (" <> printExpr cond <> ") {\n" <> joinWith ";\n" (map printExpr stmts) <> ";\n}"
  PhpContinue -> "continue /*__LVL__*/"
  PhpRaw raw -> raw
  PhpNew cls args -> "new " <> cls <> "(" <> joinWith ", " (map printExpr args) <> ")"
  PhpGoto lbl -> "goto " <> sanitize lbl <> ";"
  PhpLabel lbl -> sanitize lbl <> ":"
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
  in r5

printDecl :: PhpDecl -> String
printDecl decl = resolveContinues $ case decl.expression of
  PhpNativeFunction name args stmts ->
    "// " <> decl.identifier <> "\n" <>
    genNativeCurry (sanitize name) args stmts <> "\n" <>
    "$GLOBALS['" <> sanitize decl.identifier <> "'] = __NAMESPACE__ . '\\\\" <> sanitize name <> "';\n"
  PhpValueThunk name expr -> ""
  expr ->
    "// " <> decl.identifier <> "\n$" <> sanitize decl.identifier <> " = " <> printExpr expr <> ";\n"

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
    decls = joinWith "\n" $ map printDecl file.decls
    thunks = filter (\d -> case d.expression of
      PhpValueThunk _ _ -> true
      _ -> false
    ) file.decls
    thunkCases = joinWith "\n" $ map (\d -> case d.expression of
      PhpValueThunk name expr -> "      case '" <> sanitize name <> "': $v = " <> printExpr expr <> "; break;"
      _ -> ""
    ) thunks
    evalThunkStr = "if (!function_exists(__NAMESPACE__ . '\\\\phpurs_eval_thunk')) {\n" <>
      "  function phpurs_eval_thunk($id) {\n" <>
      "    static $cache = [];\n" <>
      "    if (array_key_exists($id, $cache)) return $cache[$id];\n" <>
      "    switch ($id) {\n" <>
      thunkCases <> "\n" <>
      "      default: throw new \\Exception(\"Unknown thunk \" . $id);\n" <>
      "    }\n" <>
      "    $GLOBALS[$id] = $v;\n" <>
      "    return $cache[$id] = $v;\n" <>
      "  }\n" <>
      "}\n"
    fallback = "if (!function_exists(__NAMESPACE__ . '\\\\phpurs_curry_fallback')) {\n" <>
      "  function phpurs_curry_fallback($fn, $args, $expected) {\n" <>
      "    $missing = $expected - count($args);\n" <>
      "    if ($missing === 1) {\n" <>
      "      return function($a) use ($fn, $args, $expected) {\n" <>
      "        $num = func_num_args();\n" <>
      "        if ($num > 1) {\n" <>
      "          $merged = array_merge($args, func_get_args());\n" <>
      "          $res = $fn(...array_slice($merged, 0, $expected));\n" <>
      "          return $res(...array_slice($merged, $expected));\n" <>
      "        }\n" <>
      "        $args[] = $a;\n" <>
      "        return $fn(...$args);\n" <>
      "      };\n" <>
      "    }\n" <>
      "    if ($missing === 2) {\n" <>
      "      return function($a, $b = null) use ($fn, $args, $expected) {\n" <>
      "        $num = func_num_args();\n" <>
      "        if ($num === 1) { $args[] = $a; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num > 2) {\n" <>
      "          $merged = array_merge($args, func_get_args());\n" <>
      "          $res = $fn(...array_slice($merged, 0, $expected));\n" <>
      "          return $res(...array_slice($merged, $expected));\n" <>
      "        }\n" <>
      "        $args[] = $a; $args[] = $b;\n" <>
      "        return $fn(...$args);\n" <>
      "      };\n" <>
      "    }\n" <>
      "    if ($missing === 3) {\n" <>
      "      return function($a, $b = null, $c = null) use ($fn, $args, $expected) {\n" <>
      "        $num = func_num_args();\n" <>
      "        if ($num === 1) { $args[] = $a; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num === 2) { $args[] = $a; $args[] = $b; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num > 3) {\n" <>
      "          $merged = array_merge($args, func_get_args());\n" <>
      "          $res = $fn(...array_slice($merged, 0, $expected));\n" <>
      "          return $res(...array_slice($merged, $expected));\n" <>
      "        }\n" <>
      "        $args[] = $a; $args[] = $b; $args[] = $c;\n" <>
      "        return $fn(...$args);\n" <>
      "      };\n" <>
      "    }\n" <>
      "    if ($missing === 4) {\n" <>
      "      return function($a, $b = null, $c = null, $d = null) use ($fn, $args, $expected) {\n" <>
      "        $num = func_num_args();\n" <>
      "        if ($num === 1) { $args[] = $a; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num === 2) { $args[] = $a; $args[] = $b; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num === 3) { $args[] = $a; $args[] = $b; $args[] = $c; return phpurs_curry_fallback($fn, $args, $expected); }\n" <>
      "        if ($num > 4) {\n" <>
      "          $merged = array_merge($args, func_get_args());\n" <>
      "          $res = $fn(...array_slice($merged, 0, $expected));\n" <>
      "          return $res(...array_slice($merged, $expected));\n" <>
      "        }\n" <>
      "        $args[] = $a; $args[] = $b; $args[] = $c; $args[] = $d;\n" <>
      "        return $fn(...$args);\n" <>
      "      };\n" <>
      "    }\n" <>
      "    return function(...$more) use ($fn, $args, $expected) {\n" <>
      "      $merged = array_merge($args, $more);\n" <>
      "      if (count($merged) >= $expected) {\n" <>
      "        $res = $fn(...array_slice($merged, 0, $expected));\n" <>
      "        return count($merged) > $expected ? $res(...array_slice($merged, $expected)) : $res;\n" <>
      "      }\n" <>
      "      return phpurs_curry_fallback($fn, $merged, $expected);\n" <>
      "    };\n" <>
      "  }\n" <>
      "}\n"
    dataClasses = "if (!class_exists(__NAMESPACE__ . '\\\\Phpurs_Data0')) {\n  class Phpurs_Data0 { public $tag; public function __construct($t) { $this->tag = $t; } }\n  class Phpurs_Data1 { public $tag; public $v0; public function __construct($t, $v0) { $this->tag = $t; $this->v0 = $v0; } }\n  class Phpurs_Data2 { public $tag; public $v0, $v1; public function __construct($t, $v0, $v1) { $this->tag = $t; $this->v0 = $v0; $this->v1 = $v1; } }\n  class Phpurs_Data3 { public $tag; public $v0, $v1, $v2; public function __construct($t, $v0, $v1, $v2) { $this->tag = $t; $this->v0 = $v0; $this->v1 = $v1; $this->v2 = $v2; } }\n  class Phpurs_Data4 { public $tag; public $v0, $v1, $v2, $v3; public function __construct($t, $v0, $v1, $v2, $v3) { $this->tag = $t; $this->v0 = $v0; $this->v1 = $v1; $this->v2 = $v2; $this->v3 = $v3; } }\n  class Phpurs_Data5 { public $tag; public $v0, $v1, $v2, $v3, $v4; public function __construct($t, $v0, $v1, $v2, $v3, $v4) { $this->tag = $t; $this->v0 = $v0; $this->v1 = $v1; $this->v2 = $v2; $this->v3 = $v3; $this->v4 = $v4; } }\n  class Phpurs_Data6 { public $tag; public $v0, $v1, $v2, $v3, $v4, $v5; public function __construct($t, $v0, $v1, $v2, $v3, $v4, $v5) { $this->tag = $t; $this->v0 = $v0; $this->v1 = $v1; $this->v2 = $v2; $this->v3 = $v3; $this->v4 = $v4; $this->v5 = $v5; } }\n}\n"
    prefix = if isBundle then "namespace " else "<?php\n\nnamespace "
  in
    prefix <> ns <> ";\n\n" <> imps <> "\n\n" <> dataClasses <> fallback <> evalThunkStr <> "$Prim_undefined = function() { throw new \\Exception(\"undefined\"); };\n" <> ffiString <> "\n\n" <> decls <> "\n"
