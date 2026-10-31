-- | Render PHP expressions, calls and currying forms.
module Phpurs.Printer.Expr
  ( genCurry
  , genNativeCurry
  , genNativeCurryWithRoot
  , printExpr
  , printExprWith
  ) where

import Prelude

import Data.Map (Map)
import Data.String (joinWith, replaceAll, Pattern(..), Replacement(..))
import Data.Maybe (Maybe(..))
import Data.Array (length, mapWithIndex)
import Data.Array as Array
import Phpurs.PhpAst (PhpExpr(..))
import Phpurs.RuntimeNames as RuntimeNames
import Phpurs.Printer.Context (PrinterContext, emptyContext)
import Phpurs.Printer.Names (printGlobal, safeName)
import Phpurs.Printer.Support
  ( extractSwitch
  , printCall
  , printCaptures
  , printReturnType
  , replaceReturn
  )

foreign import showInt32Impl :: Int -> String

foreign import escapePhpStringImpl :: String -> String

genNativeCurry
  :: PrinterContext
  -> String
  -> Array { name :: String, type_ :: String }
  -> String
  -> Array PhpExpr
  -> String
genNativeCurry = genNativeCurryWithRoot false

genNativeCurryWithRoot
  :: Boolean
  -> PrinterContext
  -> String
  -> Array { name :: String, type_ :: String }
  -> String
  -> Array PhpExpr
  -> String
genNativeCurryWithRoot compactRoot ctx name args retType stmts =
  let
    argStr = joinWith ", "
      ( mapWithIndex
          ( \i a ->
              let
                t =
                  if a.type_ == "&" then "&"
                  else if a.type_ == "mixed" then ""
                  else if a.type_ /= "" && i == 0 then a.type_ <> " "
                  else ""
              in
                t <> "$" <> safeName a.name <> (if i > 0 then " = null" else "")
          )
          args
      )
    retStr = printReturnType retType

    nStr = show (length args)

    fnBody =
      "  $__num = \\func_num_args();\n"
        <> "  $__fn = __NAMESPACE__ . '\\\\' . '"
        <> name
        <> "';\n"
        <> "  if ($__num < "
        <> nStr
        <> ") {\n"
        <> "    return \\"
        <> RuntimeNames.curryFallback
        <> "($__fn, \\func_get_args(), "
        <> nStr
        <> ");\n"
        <> "  }\n"
        <> printCurryStatements ctx stmts
        <> "  __end:\n"
        <>
          ( if compactRoot then
              "  if ($__res instanceof Phpurs_InternalCallable) { $__res = \\Closure::fromCallable($__res); }\n"
            else ""
          )
        <> "  return "
        <> nStr
        <> " < $__num ? $__res(...\\array_slice(\\func_get_args(), "
        <> nStr
        <> ")) : $__res;\n"

  in
    "function " <> name <> "(" <> argStr <> ")" <> retStr <> " {\n" <> fnBody <> "}"

genCurry
  :: Map String Int
  -> Array { name :: String, type_ :: String }
  -> String
  -> Array String
  -> Array PhpExpr
  -> String
genCurry allArities = genCurryWith (emptyContext allArities)

genCurryWith
  :: PrinterContext
  -> Array { name :: String, type_ :: String }
  -> String
  -> Array String
  -> Array PhpExpr
  -> String
genCurryWith ctx args retType captures stmts =
  let
    useClause = printCaptures captures
    recursiveUseClause = printCaptures (captures <> [ "&__fn" ])
  in
    if length args == 0 then
      "function()" <> recursiveUseClause <> printReturnType retType <> " {\n"
        <> (joinWith ";\n" (map (printExprWith ctx) stmts) <> ";")
        <> "\n}"
    else
      let
        argStr = joinWith ", "
          ( mapWithIndex
              ( \i a ->
                  let
                    t =
                      if a.type_ == "mixed" then ""
                      else if a.type_ /= "" && i == 0 then a.type_ <> " "
                      else ""
                  in
                    t <> "$" <> safeName a.name <> (if i > 0 then " = null" else "")
              )
              args
          )
        nArgs = length args
        fnBody = curryBody ctx nArgs stmts
      in
        if nArgs == 1 then
          "function(" <> argStr <> ")" <> useClause <> " {\n" <> fnBody <> "}"
        else
          "(function()" <> useClause <> " {\n"
            <> "  $__fn = function("
            <> argStr
            <> ")"
            <> recursiveUseClause
            <> " {\n"
            <> fnBody
            <> "  };\n"
            <> "  return $__fn;\n"
            <>
              "})()"

curryBody :: PrinterContext -> Int -> Array PhpExpr -> String
curryBody ctx nArgs stmts =
  let
    nStr = show nArgs
  in
    "  $__num = \\func_num_args();\n"
      <>
        ( if nArgs == 1 then ""
          else
            "  if ($__num < " <> nStr <> ") {\n"
              <> "    return \\"
              <> RuntimeNames.curryFallback
              <> "($__fn, \\func_get_args(), "
              <> nStr
              <> ");\n  }\n"
        )
      <> printCurryStatements ctx stmts
      <> "  __end:\n"
      <> "  return $__num > "
      <> nStr
      <> " ? $__res(...\\array_slice(\\func_get_args(), "
      <> nStr
      <> ")) : $__res;\n"

-- | Returns converge before the shared overapplication check. Rewriting stays
-- | inside the current function; nested callable bodies keep their own returns.
printCurryStatements :: PrinterContext -> Array PhpExpr -> String
printCurryStatements ctx stmts =
  let
    rewritten = replaceReturn stmts
  in
    if Array.null rewritten then ""
    else "  " <> joinWith ";\n  " (map (printExprWith ctx) rewritten) <> ";\n"

-- Capture slots are reloaded for every invocation, preserving PHP's by-value
-- use bindings. Only the analysis in CompactLoops can create this AST node.
genCompactFunction
  :: PrinterContext
  -> Array String
  -> Array { name :: String, type_ :: String }
  -> Array PhpExpr
  -> String
genCompactFunction ctx captures args stmts =
  let
    names = map (\v -> "$" <> safeName v) captures
    field i = "__capture" <> show i
    declarations = mapWithIndex (\i _ -> "  private $" <> field i <> ";") captures
    initialize = mapWithIndex (\i v -> "$this->" <> field i <> " = " <> v <> ";") names
    reload = mapWithIndex (\i v -> v <> " = $this->" <> field i <> ";") names
    params = map
      ( \a -> (if a.type_ == "mixed" || a.type_ == "" then "" else a.type_ <> " ") <> "$" <>
          safeName a.name
      )
      args
  in
    "new class(" <> joinWith ", " names <> ") implements Phpurs_InternalCallable {\n"
      <> joinWith "\n" declarations
      <> "\n"
      <> "  public function __construct("
      <> joinWith ", " names
      <> ") { "
      <> joinWith " " initialize
      <> " }\n"
      <> "  public function __invoke("
      <> joinWith ", " params
      <> ") {\n"
      <> joinWith "\n" reload
      <> "\n"
      <> curryBody ctx (length args) stmts
      <> "  }\n}"

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
  PhpGlobalSlot key -> "$GLOBALS['" <> key <> "']"
  PhpDirectCall name args ->
    let
      argsStr = joinWith ", " (map (printExprWith ctx) args)
    in
      printGlobal ctx.arities Nothing name <> "(" <> argsStr <> ")"
  PhpNamedCall name args -> name <> "(" <> joinWith ", " (map (printExprWith ctx) args) <> ")"
  PhpCall _ _ -> printCall ctx (printExprWith ctx) expr
  PhpInt i -> showInt32Impl i
  PhpNumber n -> case show n of
    "Infinity" -> "\\INF"
    "-Infinity" -> "-\\INF"
    "NaN" -> "\\NAN"
    s -> s
  PhpString s -> "\"" <> escapePhpStringImpl s <> "\""
  PhpBoolean b -> if b then "true" else "false"
  PhpNull -> "null"
  PhpArray arr -> "[" <> joinWith ", " (map (printExprWith ctx) arr) <> "]"
  PhpAssocArray arr -> "(object)["
    <> joinWith ", "
      (map (\item -> "\"" <> safeName item.key <> "\" => " <> printExprWith ctx item.value) arr)
    <> "]"
  PhpPropertyAccess e prop -> "(" <> printExprWith ctx e <> ")->{'" <> safeName prop <> "'}"
  PhpRecordAccess e prop -> "(" <> printExprWith ctx e <> ")->{'" <> safeName prop <> "'}"
  PhpArrayIndex arr i -> "(" <> printExprWith ctx arr <> ")[" <> printExprWith ctx i <> "]"
  PhpClone obj -> "clone " <> printExprWith ctx obj
  PhpAssign ident v -> "$" <> safeName ident <> " = " <> printExprWith ctx v
  PhpAssignExpr left v -> printExprWith ctx left <> " = " <> printExprWith ctx v
  PhpIf cond thenStmts elseStmts ->
    case extractSwitch expr of
      Just sw -> printExprWith ctx
        ( PhpSwitch sw.subject
            (map (\branch -> { matchCases: [ branch.val ], stmts: branch.body }) sw.cases)
            (Just sw.defaultBody)
        )
      Nothing ->
        let
          thenBody = joinWith ";\n" (map (printExprWith ctx) thenStmts) <> ";"
        in
          "if (" <> printExprWith ctx cond <> ") {\n" <> thenBody <> "\n}" <>
            ( if length elseStmts > 0 then " else {\n"
                <> (joinWith ";\n" (map (printExprWith ctx) elseStmts) <> ";")
                <> "\n}"
              else ""
            )

  PhpThrow v -> "throw new \\Exception(" <> printExprWith ctx v <> ")"
  PhpInstanceOf v cls -> printExprWith ctx v <> " instanceof " <> cls
  PhpMatch subj cases defExpr -> printMatch ctx subj cases defExpr
  PhpTernary cond t e -> "(" <> printExprWith ctx cond <> " ? " <> printExprWith ctx t <> " : "
    <> printExprWith ctx e
    <> ")"
  PhpReturn v -> "return " <> printExprWith ctx v
  PhpBinOp op left right -> "(" <> printExprWith ctx left <> " " <> op <> " "
    <> printExprWith ctx right
    <> ")"
  -- Preserve the historical spacing of the former empty-left `PhpBinOp` form.
  PhpUnaryOp op value -> "(" <> " " <> op <> " " <> printExprWith ctx value <> ")"
  PhpWhile cond stmts -> "while (" <> printExprWith ctx cond <> ") {\n"
    <> joinWith ";\n" (map (printExprWith ctx) stmts)
    <> ";\n}"
  PhpContinue -> "continue /*__LVL__*/"
  PhpRaw raw -> raw
  PhpUnset name -> "unset($" <> name <> ")"
  PhpNew cls args -> "new " <> cls <> "(" <> joinWith ", " (map (printExprWith ctx) args) <> ")"
  PhpGoto lbl -> "goto " <> safeName lbl <> ";"
  PhpLabel lbl -> safeName lbl <> ":"
  PhpSwitch subject cases defaultStmts -> printSwitch ctx subject cases defaultStmts

-- | Preserve the printer's existing support for up to fifteen nested switches.
-- | A switch adds one I marker to continue; the enclosing loop adds one level.

printSwitch
  :: PrinterContext
  -> PhpExpr
  -> Array { matchCases :: Array PhpExpr, stmts :: Array PhpExpr }
  -> Maybe (Array PhpExpr)
  -> String
printSwitch ctx subject cases defaultStmts =
  let
    printCase c = joinWith "\n" (map (\m -> "case " <> printExprWith ctx m <> ":") c.matchCases)
      <> "\n"
      <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/")
        (joinWith ";\n" (map (printExprWith ctx) c.stmts) <> ";")
      <> "\nbreak;"
    casesStr = joinWith "\n" (map printCase cases)
    defaultStr = case defaultStmts of
      Just stmts -> "default:\n"
        <> replaceAll (Pattern "/*__LVL__*/") (Replacement "I/*__LVL__*/")
          (joinWith ";\n" (map (printExprWith ctx) stmts) <> ";")
        <> "\nbreak;"
      Nothing -> ""
  in
    "switch (" <> printExprWith ctx subject <> ") {\n" <> casesStr <> "\n" <> defaultStr <> "\n}"

printMatch
  :: PrinterContext
  -> PhpExpr
  -> Array { val :: PhpExpr, body :: PhpExpr }
  -> PhpExpr
  -> String
printMatch ctx subj cases defExpr =
  let
    printCase { val, body } = printExprWith ctx val <> " => " <> printExprWith ctx body
    casesStr = joinWith ", " (map printCase cases)
    defStr = "default => " <> printExprWith ctx defExpr
  in
    "match (" <> printExprWith ctx subj <> ") { " <> casesStr
      <> (if length cases > 0 then ", " else "")
      <> defStr
      <> " }"
