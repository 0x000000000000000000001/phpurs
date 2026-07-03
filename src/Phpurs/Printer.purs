module Phpurs.Printer where

import Prelude

import Data.String (joinWith, replaceAll, Pattern(..), Replacement(..), indexOf, take)
import Data.Maybe (isNothing, Maybe(..))
import Data.Array (filter, length, mapWithIndex)
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
              "    $__res = " <> exprStr <> ";\n" <>
              "    if ($__num > " <> nStr <> ") {\n" <>
              "      $__args = func_get_args();\n" <>
              "      return $__res(...array_slice($__args, " <> nStr <> "));\n" <>
              "    }\n" <>
              "    return $__res;\n"
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
              "    if ($__num > " <> nStr <> ") {\n" <>
              "      $__res = $__body(" <> argNames <> ");\n" <>
              "      $__args = func_get_args();\n" <>
              "      return $__res(...array_slice($__args, " <> nStr <> "));\n" <>
              "    }\n" <>
              "    return $__body(" <> argNames <> ");\n"
          , usesBody: true
          }

      fnBody = 
        "  $__num = func_num_args();\n" <>
        "  if ($__num < " <> nStr <> ") {\n" <>
        "    $__args = func_get_args();\n" <>
        "    return function(...$__more) use ($__args, &$__fn) {\n" <>
        "      return $__fn(...array_merge($__args, $__more));\n" <>
        "    };\n" <>
        "  }\n" <>
        parts.returnStr

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
  PhpCall abs args -> "(" <> printExpr abs <> ")(" <> joinWith ", " (map printExpr args) <> ")"
  PhpInt i -> show i
  PhpNumber n -> show n
  PhpString s -> "\"" <> replaceAll (Pattern "\"") (Replacement "\\\"") s <> "\""
  PhpBoolean b -> if b then "true" else "false"
  PhpArray arr -> "[" <> joinWith ", " (map printExpr arr) <> "]"
  PhpAssocArray arr -> "(object)[" <> joinWith ", " (map (\item -> "\"" <> item.key <> "\" => " <> printExpr item.value) arr) <> "]"
  PhpPropertyAccess expr prop -> "(" <> printExpr expr <> ")->" <> sanitize prop
  PhpObjectUpdate expr updates ->
    let
      mapped = mapWithIndex (\i u -> { i: i, u: u }) updates
      updateParams = joinWith "" (map (\x -> ", $__v" <> show x.i) mapped)
      updateStmts = joinWith " " (map (\x -> "$__obj->" <> sanitize x.u.key <> " = $__v" <> show x.i <> ";") mapped)
      evalParams = joinWith "" (map (\x -> ", " <> printExpr x.u.value) mapped)
    in
      "(function($__e" <> updateParams <> ") { $__obj = clone $__e; " <> updateStmts <> " return $__obj; })(" <> printExpr expr <> evalParams <> ")"
  PhpClosure captures stmts ->
    let
      useClause = if length captures > 0 then " use (" <> joinWith ", " captures <> ")" else ""
      body = joinWith ";\n" (map printExpr stmts) <> ";"
    in
      "(function()" <> useClause <> " {\n" <> body <> "\n})()"
  PhpAssign ident expr -> "$" <> sanitize ident <> " = " <> printExpr expr
  PhpIf cond thenStmts elseStmts ->
    let
      thenBody = joinWith ";\n" (map printExpr thenStmts) <> ";"
      elseBody = joinWith ";\n" (map printExpr elseStmts) <> ";"
    in
      "if (" <> printExpr cond <> ") {\n" <> thenBody <> "\n} else {\n" <> elseBody <> "\n}"
  PhpThrow msg -> "throw new \\Exception(\"" <> replaceAll (Pattern "\"") (Replacement "\\\"") msg <> "\")"
  PhpTernary cond t e -> "(" <> printExpr cond <> " ? " <> printExpr t <> " : " <> printExpr e <> ")"
  PhpReturn expr -> "return " <> printExpr expr
  PhpBinOp op left right -> "(" <> printExpr left <> " " <> op <> " " <> printExpr right <> ")"
  PhpWhile cond stmts -> "while (" <> printExpr cond <> ") {\n" <> joinWith ";\n" (map printExpr stmts) <> ";\n}"
  PhpContinue -> "continue"
  PhpTcoLoop fvs args stmts ->
    let
      useClause = if length fvs > 0 then " use (" <> joinWith ", " fvs <> ")" else ""
      params = joinWith ", " (map (\a -> "$" <> sanitize a) args)
      body = "while (true) {\n" <> joinWith ";\n" (map printExpr stmts) <> ";\n}"
    in
      "(function(" <> params <> ")" <> useClause <> " {\n" <> body <> "\n})(" <> params <> ")"
  PhpRaw raw -> raw

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
  in
    "<?php\n\nnamespace " <> ns <> ";\n\n" <> imps <> "\n\n$Prim_undefined = function() { throw new \\Exception(\"undefined\"); };\n" <> ffiString <> "\n\n" <> decls <> "\n"
