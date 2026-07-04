module Phpurs.Printer where

import Prelude

import Data.String (joinWith, replaceAll, Pattern(..), Replacement(..), indexOf, take)
import Data.Maybe (fromMaybe, isNothing, Maybe(..))
import Data.Array (filter, length, mapWithIndex, index)
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

genCurry :: Array String -> Array String -> Array PhpExpr -> String
genCurry args captures stmts =
  if length args == 0 then joinWith ";\n" (map printExpr stmts) <> ";"
  else
    let
      argStr = joinWith ", " (mapWithIndex (\i a -> "$" <> sanitize a <> (if i > 0 then " = null" else "")) args)
      argNames = joinWith ", " (map (\a -> "$" <> sanitize a) args)
      nStr = show (length args)
      
      useClause = if length captures > 0 then " use (" <> joinWith ", " captures <> ")" else ""
      
      initStmts = Array.init stmts
      lastStmt = Array.last stmts
      
      isSimple = case lastStmt of
        Just (PhpReturn _) -> true
        _ -> false

      parts = if isSimple then
        let
          exprStr = case lastStmt of
            Just (PhpReturn e) -> printExpr e
            _ -> "null"
          initStr = case initStmts of
            Just is -> if length is > 0 then joinWith ";\n" (map printExpr is) <> ";\n" else ""
            Nothing -> ""
        in
          { setupStr: ""
          , returnStr: 
              initStr <>
              "    $__res = " <> exprStr <> ";\n"
          , usesBody: false
          }
      else
        let
          bodyCaps = captures
          bodyUse = if length bodyCaps > 0 then " use (" <> joinWith ", " bodyCaps <> ")" else ""
        in
          { setupStr:
              "  $__body = function(" <> argNames <> ")" <> bodyUse <> " {\n" <>
              "    " <> joinWith ";\n    " (map printExpr stmts) <> ";\n" <>
              "  };\n"
          , returnStr:
              "    $__res = $__body(" <> argNames <> ");\n"
          , usesBody: true
          }

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
        "  if ($__num < " <> nStr <> ") {\n" <>
        fastPathStr <>
        "    return phpurs_curry_fallback($__fn, func_get_args(), " <> nStr <> ");\n" <>
        "  }\n" <>
        parts.returnStr <>
        "  return $__num > " <> nStr <> " ? $__res(...array_slice(func_get_args(), " <> nStr <> ")) : $__res;\n"

      innerCaps = captures <> (if parts.usesBody then ["$__body", "&$__fn"] else ["&$__fn"])
      innerUseClause = " use (" <> joinWith ", " innerCaps <> ")"
    in 
      "(function()" <> useClause <> " {\n" <>
      parts.setupStr <>
      "  $__fn = function(" <> argStr <> ")" <> innerUseClause <> " {\n" <> fnBody <> "  };\n" <>
      "  return $__fn;\n" <>
      "})()"

printExpr :: PhpExpr -> String
printExpr = case _ of
  PhpFunction captures args stmts ->
    genCurry args captures stmts
  PhpVar ident -> "$" <> sanitize ident
  PhpGlobalVar ident -> "$GLOBALS['" <> sanitize ident <> "']"
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
      extractSwitch (PhpIf (PhpBinOp "===" subj (PhpString val)) tBody [eBody@(PhpIf _ _ _)]) =
        case extractSwitch eBody of
          Just rest -> 
            if printExpr subj == printExpr rest.subject then
              Just { subject: subj, cases: [{ val: val, body: joinWith ";\n" (map printExpr tBody) <> ";" }] <> rest.cases, defaultBody: rest.defaultBody }
            else Nothing
          Nothing -> Nothing
      extractSwitch (PhpIf (PhpBinOp "===" subj (PhpString val)) tBody eBodyArray) =
        Just { subject: subj, cases: [{ val: val, body: joinWith ";\n" (map printExpr tBody) <> ";" }], defaultBody: joinWith ";\n" (map printExpr eBodyArray) <> ";" }
      extractSwitch _ = Nothing
      
    in case extractSwitch (PhpIf cond thenStmts elseStmts) of
      Just sw ->
        let
          caseStmts = joinWith "\n" (map (\c -> "case \"" <> c.val <> "\":\n" <> replaceAll (Pattern "continue;") (Replacement "continue 2;") c.body <> "\nbreak;") sw.cases)
          defaultStmt = "default:\n" <> replaceAll (Pattern "continue;") (Replacement "continue 2;") sw.defaultBody <> "\nbreak;"
        in
          "switch (" <> printExpr sw.subject <> ") {\n" <> caseStmts <> "\n" <> defaultStmt <> "\n}"
      Nothing ->
        "if (" <> printExpr cond <> ") {\n" <> thenBody <> "\n} else {\n" <> elseBody <> "\n}"


  PhpThrow msg -> "throw new \\Exception(\"" <> replaceAll (Pattern "\"") (Replacement "\\\"") msg <> "\")"
  PhpTernary cond t e -> "(" <> printExpr cond <> " ? " <> printExpr t <> " : " <> printExpr e <> ")"
  PhpReturn expr -> "return " <> printExpr expr
  PhpBinOp op left right -> "(" <> printExpr left <> " " <> op <> " " <> printExpr right <> ")"
  PhpWhile cond stmts -> "while (" <> printExpr cond <> ") {\n" <> joinWith ";\n" (map printExpr stmts) <> ";\n}"
  PhpContinue -> "continue"
  PhpRaw raw -> raw
  PhpNew cls args -> "new " <> cls <> "(" <> joinWith ", " (map printExpr args) <> ")"

printDecl :: PhpDecl -> String
printDecl decl =
  "// " <> decl.identifier <> "\n$" <> sanitize decl.identifier <> " = " <> printExpr decl.expression <> ";\n"

printPhpFile :: String -> PhpFile -> String
printPhpFile ffiString file =
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
    imps = joinWith "\n" $ map (\i -> "require_once __DIR__ . '/../" <> joinWith "." i <> "/index.php';") importsToRequire
    decls = joinWith "\n" $ map printDecl file.decls
    fallback = "if (!function_exists(__NAMESPACE__ . '\\\\phpurs_curry_fallback')) {\n  function phpurs_curry_fallback($fn, $args, $expected) {\n    return function(...$more) use ($fn, $args, $expected) {\n      $merged = array_merge($args, $more);\n      if (count($merged) >= $expected) {\n        $res = $fn(...$merged);\n        return count($merged) > $expected ? $res(...array_slice($merged, $expected)) : $res;\n      }\n      return phpurs_curry_fallback($fn, $merged, $expected);\n    };\n  }\n}\n"
    dataClasses = "if (!class_exists(__NAMESPACE__ . '\\\\Phpurs_Data0')) {\n  class Phpurs_Data0 { public $tag; public function __construct($t) { $this->tag = $t; } }\n  class Phpurs_Data1 { public $tag; public $v0; public function __construct($t, $v0) { $this->tag = $t; $this->v0 = $v0; } }\n  class Phpurs_Data2 { public $tag; public $v0, $v1; public function __construct($t, $v0, $v1) { $this->tag = $t; $this->v0 = $v0; $this->v1 = $v1; } }\n  class Phpurs_Data3 { public $tag; public $v0, $v1, $v2; public function __construct($t, $v0, $v1, $v2) { $this->tag = $t; $this->v0 = $v0; $this->v1 = $v1; $this->v2 = $v2; } }\n  class Phpurs_Data4 { public $tag; public $v0, $v1, $v2, $v3; public function __construct($t, $v0, $v1, $v2, $v3) { $this->tag = $t; $this->v0 = $v0; $this->v1 = $v1; $this->v2 = $v2; $this->v3 = $v3; } }\n  class Phpurs_Data5 { public $tag; public $v0, $v1, $v2, $v3, $v4; public function __construct($t, $v0, $v1, $v2, $v3, $v4) { $this->tag = $t; $this->v0 = $v0; $this->v1 = $v1; $this->v2 = $v2; $this->v3 = $v3; $this->v4 = $v4; } }\n  class Phpurs_Data6 { public $tag; public $v0, $v1, $v2, $v3, $v4, $v5; public function __construct($t, $v0, $v1, $v2, $v3, $v4, $v5) { $this->tag = $t; $this->v0 = $v0; $this->v1 = $v1; $this->v2 = $v2; $this->v3 = $v3; $this->v4 = $v4; $this->v5 = $v5; } }\n}\n"
  in
    "<?php\n\nnamespace " <> ns <> ";\n\n" <> imps <> "\n\n" <> dataClasses <> fallback <> "$Prim_undefined = function() { throw new \\Exception(\"undefined\"); };\n" <> ffiString <> "\n\n" <> decls <> "\n"
