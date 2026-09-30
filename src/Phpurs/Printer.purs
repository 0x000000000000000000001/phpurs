-- | Render PHP expressions, calling conventions and module files. The shared
-- | runtime preamble is assembled by Phpurs.Printer.Runtime.
module Phpurs.Printer
  ( genCurry
  , printExpr
  , printPhpFile
  , safeFuncName
  , safeName
  ) where

import Prelude

import Data.Map (Map)
import Data.Map as Map
import Data.String (joinWith, replaceAll, Pattern(..), Replacement(..), indexOf, take, drop)
import Data.Maybe (isNothing, Maybe(..))
import Data.Array (filter, length, mapWithIndex, concatMap)
import Data.Array as Array
import Data.Tuple (Tuple(..))
import Data.Foldable (foldl)
import Phpurs.PhpAst (PhpExpr(..), PhpDecl, PhpFile)
import Phpurs.PhpAst.Traversal (mapBlocks)
import Phpurs.Printer.Runtime as Runtime

foreign import showInt32Impl :: Int -> String
flattenPhpCalls :: PhpExpr -> Tuple PhpExpr (Array PhpExpr)
flattenPhpCalls (PhpCall fn args) | length args == 1 =
  let Tuple innerFn innerArgs = flattenPhpCalls fn
  in Tuple innerFn (innerArgs <> args)
flattenPhpCalls other = Tuple other []


foreign import safeNameImpl :: String -> String
foreign import safeFuncNameImpl :: String -> String
foreign import escapePhpStringImpl :: String -> String

safeName :: String -> String
safeName = safeNameImpl
  <<< replaceAll (Pattern "'") (Replacement "__prime__")

safeFuncName :: String -> String
safeFuncName = safeFuncNameImpl
  <<< replaceAll (Pattern "'") (Replacement "__prime__")

printReturnType :: String -> String
printReturnType retType =
  if retType == "mixed" || retType == "" then ""
  else if retType == "\\Closure" then ": \\Closure"
  else ": " <> retType <> "|\\Closure"

printCaptures :: Array String -> String
printCaptures [] = ""
printCaptures captures = " use (" <> joinWith ", " (map printCapture captures) <> ")"
  where
  printCapture name = if take 1 name == "&" then "&$" <> safeName (drop 1 name) else "$" <> safeName name

replaceReturn :: Array PhpExpr -> Array PhpExpr
replaceReturn = concatMap replaceExpr
  where
    replaceExpr :: PhpExpr -> Array PhpExpr
    replaceExpr (PhpReturn e) = [PhpAssign "__res" e, PhpGoto "__end"]
    replaceExpr other = [mapBlocks replaceReturn other]

genNativeCurry :: Map String Int -> String -> Array { name :: String, type_ :: String } -> String -> Array PhpExpr -> String
genNativeCurry = genNativeCurryWithRoot false

genNativeCurryWithRoot :: Boolean -> Map String Int -> String -> Array { name :: String, type_ :: String } -> String -> Array PhpExpr -> String
genNativeCurryWithRoot compactRoot allArities name args retType stmts =
  let
    argStr = joinWith ", " (mapWithIndex (\i a -> 
      let t = if a.type_ == "&" then "&" else if a.type_ == "mixed" then "" else if a.type_ /= "" && i == 0 then a.type_ <> " " else ""
      in t <> "$" <> safeName a.name <> (if i > 0 then " = null" else "")
    ) args)
    retStr = printReturnType retType

    nStr = show (length args)

    fnBody = 
      "  $__num = \\func_num_args();\n" <>
      "  $__fn = __NAMESPACE__ . '\\\\' . '" <> name <> "';\n" <>
      "  if ($__num < " <> nStr <> ") {\n" <>
      "    return phpurs_curry_fallback($__fn, \\func_get_args(), " <> nStr <> ");\n" <>
      "  }\n" <>
      printCurryStatements allArities stmts <>
      "  __end:\n" <>
      (if compactRoot then "  if ($__res instanceof Phpurs_InternalCallable) { $__res = \\Closure::fromCallable($__res); }\n" else "") <>
      "  return " <> nStr <> " < $__num ? $__res(...\\array_slice(\\func_get_args(), " <> nStr <> ")) : $__res;\n"

  in
    "function " <> name <> "(" <> argStr <> ")" <> retStr <> " {\n" <> fnBody <> "}"

genCurry :: Map String Int -> Array { name :: String, type_ :: String } -> String -> Array String -> Array PhpExpr -> String
genCurry allArities args retType captures stmts =
  let
    useClause = printCaptures captures
    recursiveUseClause = printCaptures (captures <> [ "&__fn" ])
  in if length args == 0 then
    "function()" <> recursiveUseClause <> printReturnType retType <> " {\n" <> (joinWith ";\n" (map (printExpr allArities) stmts) <> ";") <> "\n}"
  else
    let
      argStr = joinWith ", " (mapWithIndex (\i a -> 
        let t = if a.type_ == "mixed" then "" else if a.type_ /= "" && i == 0 then a.type_ <> " " else ""
        in t <> "$" <> safeName a.name <> (if i > 0 then " = null" else "")
      ) args)
      nArgs = length args
      fnBody = curryBody allArities nArgs stmts
    in 
      if nArgs == 1 then
        "function(" <> argStr <> ")" <> useClause <> " {\n" <> fnBody <> "}"
      else
        "(function()" <> useClause <> " {\n" <>
        "  $__fn = function(" <> argStr <> ")" <> recursiveUseClause <> " {\n" <> fnBody <> "  };\n" <>
        "  return $__fn;\n" <>
        "})()"


curryBody :: Map String Int -> Int -> Array PhpExpr -> String
curryBody allArities nArgs stmts =
  let
    nStr = show nArgs
  in
    "  $__num = \\func_num_args();\n" <>
    (if nArgs == 1 then "" else
      "  if ($__num < " <> nStr <> ") {\n" <>
      "    return phpurs_curry_fallback($__fn, \\func_get_args(), " <> nStr <> ");\n  }\n") <>
    printCurryStatements allArities stmts <>
    "  __end:\n" <>
    "  return $__num > " <> nStr <> " ? $__res(...\\array_slice(\\func_get_args(), " <> nStr <> ")) : $__res;\n"

-- | Returns converge before the shared overapplication check. Rewriting stays
-- | inside the current function; nested callable bodies keep their own returns.
printCurryStatements :: Map String Int -> Array PhpExpr -> String
printCurryStatements allArities stmts =
  let rewritten = replaceReturn stmts
  in if Array.null rewritten then ""
     else "  " <> joinWith ";\n  " (map (printExpr allArities) rewritten) <> ";\n"

-- Capture slots are reloaded for every invocation, preserving PHP's by-value
-- use bindings. Only the analysis in CompactLoops can create this AST node.
genCompactFunction :: Map String Int -> Array String -> Array { name :: String, type_ :: String } -> Array PhpExpr -> String
genCompactFunction allArities captures args stmts =
  let
    names = map (\v -> "$" <> safeName v) captures
    field i = "__capture" <> show i
    declarations = mapWithIndex (\i _ -> "  private $" <> field i <> ";") captures
    initialize = mapWithIndex (\i v -> "$this->" <> field i <> " = " <> v <> ";") names
    reload = mapWithIndex (\i v -> v <> " = $this->" <> field i <> ";") names
    params = map (\a -> (if a.type_ == "mixed" || a.type_ == "" then "" else a.type_ <> " ") <> "$" <> safeName a.name) args
  in
    "new class(" <> joinWith ", " names <> ") implements Phpurs_InternalCallable {\n" <>
    joinWith "\n" declarations <> "\n" <>
    "  public function __construct(" <> joinWith ", " names <> ") { " <> joinWith " " initialize <> " }\n" <>
    "  public function __invoke(" <> joinWith ", " params <> ") {\n" <>
    joinWith "\n" reload <> "\n" <> curryBody allArities (length args) stmts <> "  }\n}"

globalIdentifier :: Maybe (Array String) -> String -> String
globalIdentifier moduleName ident = case moduleName of
  Just parts -> joinWith "_" parts <> "_" <> ident
  Nothing -> ident

printGlobal :: Maybe (Array String) -> String -> String
printGlobal moduleName ident = "$GLOBALS['" <> safeName (globalIdentifier moduleName ident) <> "']"

printExpr :: Map String Int -> PhpExpr -> String
printExpr allArities expr = case expr of
  PhpCompactLoop _ _ _ _ -> "/* ERROR: PhpCompactLoop inside expression */"
  PhpCompactFunction captures args _ stmts -> genCompactFunction allArities captures args stmts
  PhpNativeFunction _ _ _ _ -> "/* ERROR: PhpNativeFunction inside expression */"
  PhpPrivateFunction _ _ _ _ -> "/* ERROR: PhpPrivateFunction inside expression */"
  PhpGlobalAssign _ _ -> "/* ERROR: PhpGlobalAssign inside expression */"
  PhpFunction captures args retType stmts ->
    genCurry allArities args retType captures stmts
  PhpVar ident -> "$" <> safeName ident
  PhpGlobalVar mbMod ident -> printGlobal mbMod ident
  PhpDirectCall name args ->
    let
      argsStr = joinWith ", " (map (printExpr allArities) args)
    in printGlobal Nothing name <> "(" <> argsStr <> ")"
  PhpCall _ _ ->
    let
      Tuple flatFn flatArgs = flattenPhpCalls expr
      canUnbox = case flatFn of
        PhpGlobalVar mbMod ident ->
          let
            fullName = globalIdentifier mbMod ident
            idStr = safeName fullName
            funcName = safeFuncName fullName
          in case Map.lookup idStr allArities of
            Just arity | arity > 0 && length flatArgs >= arity -> Just { funcName, arity, mbMod }
            _ -> Nothing
        _ -> Nothing
    in case canUnbox of
      Just { funcName, arity, mbMod } ->
        let
          nsPrefix = case mbMod of
            Just mod -> "\\" <> joinWith "\\" mod <> "\\"
            Nothing -> ""
          directArgs = Array.take arity flatArgs
          remainingArgs = Array.drop arity flatArgs
          callStr = nsPrefix <> funcName <> "(" <> joinWith ", " (map (printExpr allArities) directArgs) <> ")"
        in
          if length remainingArgs > 0 then
            foldl (\acc a -> "(" <> acc <> ")(" <> printExpr allArities a <> ")") callStr remainingArgs
          else
            callStr
      Nothing ->
        case expr of
          PhpCall (PhpGlobalVar mbMod ident) args ->
            "(" <> printGlobal mbMod ident <> ")(" <> joinWith ", " (map (printExpr allArities) args) <> ")"
          PhpCall (PhpRaw raw) args -> raw <> "(" <> joinWith ", " (map (printExpr allArities) args) <> ")"
          PhpCall abs args -> "(" <> printExpr allArities abs <> ")(" <> joinWith ", " (map (printExpr allArities) args) <> ")"
          _ -> "/* ERROR: Impossible PhpCall match */"
  PhpInt i -> showInt32Impl i
  PhpNumber n -> case show n of
    "Infinity" -> "\\INF"
    "-Infinity" -> "-\\INF"
    "NaN" -> "\\NAN"
    s -> s
  PhpString s -> "\"" <> escapePhpStringImpl s <> "\""
  PhpBoolean b -> if b then "true" else "false"
  PhpArray arr -> "[" <> joinWith ", " (map (printExpr allArities) arr) <> "]"
  PhpAssocArray arr -> "(object)[" <> joinWith ", " (map (\item -> "\"" <> safeName item.key <> "\" => " <> printExpr allArities item.value) arr) <> "]"
  PhpPropertyAccess e prop -> "(" <> printExpr allArities e <> ")->{'" <> safeName prop <> "'}"
  PhpRecordAccess e prop -> "(" <> printExpr allArities e <> ")->{'" <> safeName prop <> "'}"
  PhpArrayIndex arr i -> "(" <> printExpr allArities arr <> ")[" <> printExpr allArities i <> "]"
  PhpClone obj -> "clone " <> printExpr allArities obj
  PhpAssign ident v -> "$" <> safeName ident <> " = " <> printExpr allArities v
  PhpAssignExpr left v -> printExpr allArities left <> " = " <> printExpr allArities v
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
          caseStmts = joinWith "\n" (map (\c -> "case " <> printExpr allArities c.val <> ":\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExpr allArities) c.body) <> ";") <> "\nbreak;") sw.cases)
          defaultStmt = "default:\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExpr allArities) sw.defaultBody) <> ";") <> "\nbreak;"
        in
          "switch (" <> printExpr allArities sw.subject <> ") {\n" <> caseStmts <> "\n" <> defaultStmt <> "\n}"
      Nothing ->
        let
          thenBody = joinWith ";\n" (map (printExpr allArities) thenStmts) <> ";"
        in
          "if (" <> printExpr allArities cond <> ") {\n" <> thenBody <> "\n}" <>
          (if length elseStmts > 0 then " else {\n" <> (joinWith ";\n" (map (printExpr allArities) elseStmts) <> ";") <> "\n}" else "")

  PhpThrow v -> "throw new \\Exception(" <> printExpr allArities v <> ")"
  PhpInstanceOf v cls -> printExpr allArities v <> " instanceof " <> cls
  PhpMatch subj cases defExpr ->
    let
      printCase { val, body } = printExpr allArities val <> " => " <> printExpr allArities body
      casesStr = joinWith ", " (map printCase cases)
      defStr = "default => " <> printExpr allArities defExpr
    in
      "match (" <> printExpr allArities subj <> ") { " <> casesStr <> (if length cases > 0 then ", " else "") <> defStr <> " }"
  PhpTernary cond t e -> "(" <> printExpr allArities cond <> " ? " <> printExpr allArities t <> " : " <> printExpr allArities e <> ")"
  PhpReturn v -> "return " <> printExpr allArities v
  PhpBinOp op left right -> "(" <> printExpr allArities left <> " " <> op <> " " <> printExpr allArities right <> ")"
  PhpWhile cond stmts -> "while (" <> printExpr allArities cond <> ") {\n" <> joinWith ";\n" (map (printExpr allArities) stmts) <> ";\n}"
  PhpContinue -> "continue /*__LVL__*/"
  PhpRaw raw -> raw
  PhpNew cls args -> "new " <> cls <> "(" <> joinWith ", " (map (printExpr allArities) args) <> ")"
  PhpGoto lbl -> "goto " <> safeName lbl <> ";"
  PhpLabel lbl -> safeName lbl <> ":"
  PhpSwitch subject cases defaultStmts ->
    let
      printCase c = joinWith "\n" (map (\m -> "case " <> printExpr allArities m <> ":") c.matchCases) <> "\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExpr allArities) c.stmts) <> ";") <> "\nbreak;"
      casesStr = joinWith "\n" (map printCase cases)
      defaultStr = case defaultStmts of
        Just stmts -> "default:\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExpr allArities) stmts) <> ";") <> "\nbreak;"
        Nothing -> ""
    in "switch (" <> printExpr allArities subject <> ") {\n" <> casesStr <> "\n" <> defaultStr <> "\n}"

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

printDecl :: Map String Int -> PhpDecl -> String
printDecl allArities decl = resolveContinues $ case decl.expression of
  PhpPrivateFunction name args retType stmts ->
    "// " <> decl.identifier <> "\n" <>
    genNativeCurry allArities (safeFuncName name) args retType stmts <> "\n"
  PhpCompactLoop name args retType stmts ->
    "// " <> decl.identifier <> "\n" <>
    genNativeCurryWithRoot true allArities (safeFuncName name) args retType stmts <> "\n" <>
    "$GLOBALS['" <> safeName decl.identifier <> "'] = __NAMESPACE__ . '\\\\" <> safeFuncName name <> "';\n"
  PhpNativeFunction name args retType stmts ->
    "// " <> decl.identifier <> "\n" <>
    genNativeCurry allArities (safeFuncName name) args retType stmts <> "\n" <>
    "$GLOBALS['" <> safeName decl.identifier <> "'] = __NAMESPACE__ . '\\\\" <> safeFuncName name <> "';\n"
  PhpGlobalAssign name expr ->
    "// " <> decl.identifier <> "\n$GLOBALS['" <> safeName name <> "'] = " <> printExpr allArities expr <> ";\n"
  expr ->
    "// " <> decl.identifier <> "\n$" <> safeName decl.identifier <> " = " <> printExpr allArities expr <> ";\n"

-- | Assemble a complete PHP module: namespace/imports, runtime, FFI, data
-- | declarations and bindings. Bundle mode uses a bracketed namespace.
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
    compactRuntime = if Array.any (\d -> case d.expression of
      PhpCompactLoop _ _ _ _ -> true
      _ -> false) file.decls then "interface Phpurs_InternalCallable {}\n" else ""
    rawDeclsStr = compactRuntime <> joinWith "\n" file.rawDecls
    decls = joinWith "\n" $ map (printDecl allArities) file.decls
    prefix = if isBundle then "namespace " <> ns <> " {\n" else "<?php\n\nnamespace " <> ns <> ";\n\n"
    suffix = if isBundle then "\n}\n" else "\n"
  in
    prefix <> debugImps <> imps <> "\n\n" <> Runtime.preamble <> "\n$GLOBALS['" <> safeName "Prim_undefined" <> "'] = function() { throw new \\Exception(\"undefined\"); };\n" <> ffiString <> "\n\n" <> rawDeclsStr <> "\n\n" <> decls <> suffix
