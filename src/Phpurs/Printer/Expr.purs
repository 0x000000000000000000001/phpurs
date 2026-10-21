-- | Render PHP expressions, calls and currying forms.
module Phpurs.Printer.Expr
  ( PrinterContext
  , exactSuffix
  , genCurry
  , genNativeCurry
  , genNativeCurryWithRoot
  , printExpr
  , printExprWith
  , printReturnType
  , resolveContinues
  , safeFuncName
  , safeName
  ) where

import Prelude

import Data.Map (Map)
import Data.Map as Map
import Data.Set (Set)
import Data.Set as Set
import Data.String (joinWith, replaceAll, Pattern(..), Replacement(..), take, drop)
import Data.Maybe (Maybe(..))
import Data.Array (length, mapWithIndex, concatMap)
import Data.Array as Array
import Data.Tuple (Tuple(..))
import Data.Foldable (foldl)
import Phpurs.PhpAst (PhpExpr(..), foreignValueArity)
import Phpurs.PhpAst.Traversal (mapBlocks)

foreign import showInt32Impl :: Int -> String
flattenPhpCalls :: PhpExpr -> Tuple PhpExpr (Array PhpExpr)
flattenPhpCalls (PhpCall fn args) | length args == 1 =
  let Tuple innerFn innerArgs = flattenPhpCalls fn
  in Tuple innerFn (innerArgs <> args)
flattenPhpCalls other = Tuple other []


foreign import safeNameImpl :: String -> String
foreign import safeFuncNameImpl :: String -> String
foreign import escapePhpStringImpl :: String -> String

-- | Exact bodies are private implementation functions. The public wrapper keeps
-- | currying and overapplication; only calls with the proven runtime arity use
-- | the body directly.
type PrinterContext = { arities :: Map String Int, exacts :: Set String }

exactSuffix :: String
exactSuffix = "__phpurs_exact"

emptyContext :: Map String Int -> PrinterContext
emptyContext arities = { arities, exacts: Set.empty }

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

genNativeCurry :: PrinterContext -> String -> Array { name :: String, type_ :: String } -> String -> Array PhpExpr -> String
genNativeCurry = genNativeCurryWithRoot false

genNativeCurryWithRoot :: Boolean -> PrinterContext -> String -> Array { name :: String, type_ :: String } -> String -> Array PhpExpr -> String
genNativeCurryWithRoot compactRoot ctx name args retType stmts =
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
      "    return \\phpurs_curry_fallback($__fn, \\func_get_args(), " <> nStr <> ");\n" <>
      "  }\n" <>
      printCurryStatements ctx stmts <>
      "  __end:\n" <>
      (if compactRoot then "  if ($__res instanceof Phpurs_InternalCallable) { $__res = \\Closure::fromCallable($__res); }\n" else "") <>
      "  return " <> nStr <> " < $__num ? $__res(...\\array_slice(\\func_get_args(), " <> nStr <> ")) : $__res;\n"

  in
    "function " <> name <> "(" <> argStr <> ")" <> retStr <> " {\n" <> fnBody <> "}"

genCurry :: Map String Int -> Array { name :: String, type_ :: String } -> String -> Array String -> Array PhpExpr -> String
genCurry allArities = genCurryWith (emptyContext allArities)

genCurryWith :: PrinterContext -> Array { name :: String, type_ :: String } -> String -> Array String -> Array PhpExpr -> String
genCurryWith ctx args retType captures stmts =
  let
    useClause = printCaptures captures
    recursiveUseClause = printCaptures (captures <> [ "&__fn" ])
  in if length args == 0 then
    "function()" <> recursiveUseClause <> printReturnType retType <> " {\n" <> (joinWith ";\n" (map (printExprWith ctx) stmts) <> ";") <> "\n}"
  else
    let
      argStr = joinWith ", " (mapWithIndex (\i a -> 
        let t = if a.type_ == "mixed" then "" else if a.type_ /= "" && i == 0 then a.type_ <> " " else ""
        in t <> "$" <> safeName a.name <> (if i > 0 then " = null" else "")
      ) args)
      nArgs = length args
      fnBody = curryBody ctx nArgs stmts
    in 
      if nArgs == 1 then
        "function(" <> argStr <> ")" <> useClause <> " {\n" <> fnBody <> "}"
      else
        "(function()" <> useClause <> " {\n" <>
        "  $__fn = function(" <> argStr <> ")" <> recursiveUseClause <> " {\n" <> fnBody <> "  };\n" <>
        "  return $__fn;\n" <>
        "})()"


curryBody :: PrinterContext -> Int -> Array PhpExpr -> String
curryBody ctx nArgs stmts =
  let
    nStr = show nArgs
  in
    "  $__num = \\func_num_args();\n" <>
    (if nArgs == 1 then "" else
      "  if ($__num < " <> nStr <> ") {\n" <>
      "    return \\phpurs_curry_fallback($__fn, \\func_get_args(), " <> nStr <> ");\n  }\n") <>
    printCurryStatements ctx stmts <>
    "  __end:\n" <>
    "  return $__num > " <> nStr <> " ? $__res(...\\array_slice(\\func_get_args(), " <> nStr <> ")) : $__res;\n"

-- | Returns converge before the shared overapplication check. Rewriting stays
-- | inside the current function; nested callable bodies keep their own returns.
printCurryStatements :: PrinterContext -> Array PhpExpr -> String
printCurryStatements ctx stmts =
  let rewritten = replaceReturn stmts
  in if Array.null rewritten then ""
     else "  " <> joinWith ";\n  " (map (printExprWith ctx) rewritten) <> ";\n"

-- Capture slots are reloaded for every invocation, preserving PHP's by-value
-- use bindings. Only the analysis in CompactLoops can create this AST node.
genCompactFunction :: PrinterContext -> Array String -> Array { name :: String, type_ :: String } -> Array PhpExpr -> String
genCompactFunction ctx captures args stmts =
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
    joinWith "\n" reload <> "\n" <> curryBody ctx (length args) stmts <> "  }\n}"

globalIdentifier :: Maybe (Array String) -> String -> String
globalIdentifier moduleName ident = case moduleName of
  Just parts -> joinWith "_" parts <> "_" <> ident
  Nothing -> ident

nativeName :: Maybe (Array String) -> String -> String
nativeName moduleName ident =
  (case moduleName of
    Just parts -> "\\" <> joinWith "\\" parts <> "\\"
    Nothing -> "") <> safeFuncName (globalIdentifier moduleName ident)

printGlobal :: Map String Int -> Maybe (Array String) -> String -> String
printGlobal allArities moduleName ident =
  let
    key = safeName (globalIdentifier moduleName ident)
    value = "$GLOBALS['" <> key <> "']"
  in
    if Map.lookup key allArities == Just foreignValueArity then
      "(" <> value <> " ?? " <> nativeName moduleName ident <> "())"
    else value

printExpr :: Map String Int -> PhpExpr -> String
printExpr allArities = printExprWith (emptyContext allArities)

printExprWith :: PrinterContext -> PhpExpr -> String
printExprWith ctx expr = case expr of
  PhpCompactLoop _ _ _ _ -> "/* ERROR: PhpCompactLoop inside expression */"
  PhpCompactFunction captures args _ stmts -> genCompactFunction ctx captures args stmts
  PhpNativeFunction _ _ _ _ -> "/* ERROR: PhpNativeFunction inside expression */"
  PhpPrivateFunction _ _ _ _ -> "/* ERROR: PhpPrivateFunction inside expression */"
  PhpGlobalAssign _ _ -> "/* ERROR: PhpGlobalAssign inside expression */"
  PhpFunction captures args retType stmts ->
    genCurryWith ctx args retType captures stmts
  PhpVar ident -> "$" <> safeName ident
  PhpGlobalVar mbMod ident -> printGlobal ctx.arities mbMod ident
  PhpDirectCall name args ->
    let
      argsStr = joinWith ", " (map (printExprWith ctx) args)
    in printGlobal ctx.arities Nothing name <> "(" <> argsStr <> ")"
  PhpCall _ _ ->
    let
      Tuple flatFn flatArgs = flattenPhpCalls expr
      canUnbox = case flatFn of
        PhpGlobalVar mbMod ident ->
          let
            fullName = globalIdentifier mbMod ident
            idStr = safeName fullName
            funcName = nativeName mbMod ident
          in case Map.lookup idStr ctx.arities of
            Just arity | arity > 0 && length flatArgs >= arity -> Just { funcName, arity, idStr }
            _ -> Nothing
        _ -> Nothing
    in case canUnbox of
      Just { funcName, arity, idStr } ->
        let
          directArgs = Array.take arity flatArgs
          remainingArgs = Array.drop arity flatArgs
          callStr = funcName <> "(" <> joinWith ", " (map (printExprWith ctx) directArgs) <> ")"
        in
          if Set.member idStr ctx.exacts && length flatArgs == arity then
            funcName <> exactSuffix <> "(" <> joinWith ", " (map (printExprWith ctx) flatArgs) <> ")"
          else if length remainingArgs > 0 then
            foldl (\acc a -> "(" <> acc <> ")(" <> printExprWith ctx a <> ")") callStr remainingArgs
          else
            callStr
      Nothing ->
        case expr of
          PhpCall (PhpGlobalVar mbMod ident) args ->
            "(" <> printGlobal ctx.arities mbMod ident <> ")(" <> joinWith ", " (map (printExprWith ctx) args) <> ")"
          PhpCall (PhpRaw raw) args -> raw <> "(" <> joinWith ", " (map (printExprWith ctx) args) <> ")"
          PhpCall abs args -> "(" <> printExprWith ctx abs <> ")(" <> joinWith ", " (map (printExprWith ctx) args) <> ")"
          _ -> "/* ERROR: Impossible PhpCall match */"
  PhpInt i -> showInt32Impl i
  PhpNumber n -> case show n of
    "Infinity" -> "\\INF"
    "-Infinity" -> "-\\INF"
    "NaN" -> "\\NAN"
    s -> s
  PhpString s -> "\"" <> escapePhpStringImpl s <> "\""
  PhpBoolean b -> if b then "true" else "false"
  PhpArray arr -> "[" <> joinWith ", " (map (printExprWith ctx) arr) <> "]"
  PhpAssocArray arr -> "(object)[" <> joinWith ", " (map (\item -> "\"" <> safeName item.key <> "\" => " <> printExprWith ctx item.value) arr) <> "]"
  PhpPropertyAccess e prop -> "(" <> printExprWith ctx e <> ")->{'" <> safeName prop <> "'}"
  PhpRecordAccess e prop -> "(" <> printExprWith ctx e <> ")->{'" <> safeName prop <> "'}"
  PhpArrayIndex arr i -> "(" <> printExprWith ctx arr <> ")[" <> printExprWith ctx i <> "]"
  PhpClone obj -> "clone " <> printExprWith ctx obj
  PhpAssign ident v -> "$" <> safeName ident <> " = " <> printExprWith ctx v
  PhpAssignExpr left v -> printExprWith ctx left <> " = " <> printExprWith ctx v
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
          caseStmts = joinWith "\n" (map (\c -> "case " <> printExprWith ctx c.val <> ":\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExprWith ctx) c.body) <> ";") <> "\nbreak;") sw.cases)
          defaultStmt = "default:\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExprWith ctx) sw.defaultBody) <> ";") <> "\nbreak;"
        in
          "switch (" <> printExprWith ctx sw.subject <> ") {\n" <> caseStmts <> "\n" <> defaultStmt <> "\n}"
      Nothing ->
        let
          thenBody = joinWith ";\n" (map (printExprWith ctx) thenStmts) <> ";"
        in
          "if (" <> printExprWith ctx cond <> ") {\n" <> thenBody <> "\n}" <>
          (if length elseStmts > 0 then " else {\n" <> (joinWith ";\n" (map (printExprWith ctx) elseStmts) <> ";") <> "\n}" else "")

  PhpThrow v -> "throw new \\Exception(" <> printExprWith ctx v <> ")"
  PhpInstanceOf v cls -> printExprWith ctx v <> " instanceof " <> cls
  PhpMatch subj cases defExpr ->
    let
      printCase { val, body } = printExprWith ctx val <> " => " <> printExprWith ctx body
      casesStr = joinWith ", " (map printCase cases)
      defStr = "default => " <> printExprWith ctx defExpr
    in
      "match (" <> printExprWith ctx subj <> ") { " <> casesStr <> (if length cases > 0 then ", " else "") <> defStr <> " }"
  PhpTernary cond t e -> "(" <> printExprWith ctx cond <> " ? " <> printExprWith ctx t <> " : " <> printExprWith ctx e <> ")"
  PhpReturn v -> "return " <> printExprWith ctx v
  PhpBinOp op left right -> "(" <> printExprWith ctx left <> " " <> op <> " " <> printExprWith ctx right <> ")"
  PhpWhile cond stmts -> "while (" <> printExprWith ctx cond <> ") {\n" <> joinWith ";\n" (map (printExprWith ctx) stmts) <> ";\n}"
  PhpContinue -> "continue /*__LVL__*/"
  PhpRaw raw -> raw
  PhpNew cls args -> "new " <> cls <> "(" <> joinWith ", " (map (printExprWith ctx) args) <> ")"
  PhpGoto lbl -> "goto " <> safeName lbl <> ";"
  PhpLabel lbl -> safeName lbl <> ":"
  PhpSwitch subject cases defaultStmts ->
    let
      printCase c = joinWith "\n" (map (\m -> "case " <> printExprWith ctx m <> ":") c.matchCases) <> "\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExprWith ctx) c.stmts) <> ";") <> "\nbreak;"
      casesStr = joinWith "\n" (map printCase cases)
      defaultStr = case defaultStmts of
        Just stmts -> "default:\n" <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/") (joinWith ";\n" (map (printExprWith ctx) stmts) <> ";") <> "\nbreak;"
        Nothing -> ""
    in "switch (" <> printExprWith ctx subject <> ") {\n" <> casesStr <> "\n" <> defaultStr <> "\n}"

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
