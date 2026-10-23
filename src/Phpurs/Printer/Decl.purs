-- | Render declarations, exact wrappers and complete PHP modules.
module Phpurs.Printer.Decl
  ( printDecl
  , printPhpFile
  ) where

import Prelude

import Data.Map (Map)
import Data.Map as Map
import Data.Set (Set)
import Data.Set as Set
import Data.String (joinWith, Pattern(..), indexOf)
import Data.Maybe (Maybe(..), isNothing)
import Data.Array (filter, length, mapWithIndex)
import Data.Array as Array
import Phpurs.PhpAst (PhpDecl, PhpExpr(..), PhpFile)
import Phpurs.PhpAst.Traversal (children)
import Phpurs.RuntimeNames as RuntimeNames
import Phpurs.Printer.Expr
  ( PrinterContext
  , exactSuffix
  , genNativeCurry
  , genNativeCurryWithRoot
  , printExprWith
  , printReturnType
  , resolveContinues
  , safeFuncName
  , safeName
  )
import Phpurs.Printer.Runtime as Runtime

-- | The exact body has the same first-parameter check as the public wrapper,
-- | but requires every argument and returns without the Closure union.
exactArgStr :: Array { name :: String, type_ :: String } -> String
exactArgStr args = joinWith ", "
  ( mapWithIndex
      ( \i a ->
          let
            prefix =
              if a.type_ == "&" then "&"
              else if a.type_ == "mixed" || a.type_ == "" || i > 0 then ""
              else a.type_ <> " "
          in
            prefix <> "$" <> safeName a.name
      )
      args
  )

exactReturnType :: String -> String
exactReturnType retType =
  if retType == "" || retType == "mixed" || retType == "\\Closure" then ""
  else ": " <> retType

-- | A body containing a closure may return it through a value alias the type
-- | annotation does not describe (effect thunks are the common case). Keep the
-- | public union there and use the precise scalar check only for closure-free
-- | bodies.
containsClosure :: Array PhpExpr -> Boolean
containsClosure = Array.any go
  where
  go expr = case expr of
    PhpFunction _ _ _ _ -> true
    PhpCompactFunction _ _ _ _ -> true
    _ -> Array.any go (children expr)

genExactFunction
  :: PrinterContext
  -> String
  -> Array { name :: String, type_ :: String }
  -> String
  -> Array PhpExpr
  -> String
genExactFunction ctx name args retType stmts =
  let
    statements =
      if Array.null stmts then ""
      else "  " <> joinWith ";\n  " (map (printExprWith ctx) stmts) <> ";\n"
    resultType = if containsClosure stmts then printReturnType retType else exactReturnType retType
  in
    "function " <> safeFuncName name <> exactSuffix <> "(" <> exactArgStr args <> ")" <> resultType
      <> " {\n"
      <> statements
      <>
        "}"

genExactWrapper :: String -> Array { name :: String, type_ :: String } -> String -> String
genExactWrapper name args retType =
  let
    argStr = joinWith ", "
      ( mapWithIndex
          ( \i a ->
              let
                prefix =
                  if a.type_ == "&" then "&"
                  else if a.type_ == "mixed" || a.type_ == "" || i > 0 then ""
                  else a.type_ <> " "
              in
                prefix <> "$" <> safeName a.name <> (if i > 0 then " = null" else "")
          )
          args
      )
    nStr = show (length args)
    callArgs = joinWith ", " (map (\a -> "$" <> safeName a.name) args)
  in
    "function " <> safeFuncName name <> "(" <> argStr <> ")" <> printReturnType retType <> " {\n"
      <> "  $__num = \\func_num_args();\n"
      <> "  $__fn = __NAMESPACE__ . '\\\\' . '"
      <> safeFuncName name
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
      <> "  $__res = "
      <> safeFuncName name
      <> exactSuffix
      <> "("
      <> callArgs
      <> ");\n"
      <> "  return "
      <> nStr
      <> " < $__num ? $__res(...\\array_slice(\\func_get_args(), "
      <> nStr
      <> ")) : $__res;\n"
      <>
        "}"

declaredFunctionNames :: PhpFile -> Array String
declaredFunctionNames file = Array.mapMaybe
  ( \decl -> case decl.expression of
      PhpNativeFunction name _ _ _ -> Just (safeFuncName name)
      PhpPrivateFunction name _ _ _ -> Just (safeFuncName name)
      PhpCompactLoop name _ _ _ -> Just (safeFuncName name)
      _ -> Nothing
  )
  file.decls

-- | Only global native forms get an exact body. Compact loops keep their root
-- | conversion, and a suffix collision leaves the declaration unchanged.
exactBodies :: Map String Int -> PhpFile -> Set String
exactBodies allArities file =
  let
    declared = declaredFunctionNames file
    candidate decl = case decl.expression of
      PhpNativeFunction name args retType _ -> make decl.identifier name args retType
      PhpPrivateFunction name args retType _ -> make decl.identifier name args retType
      _ -> Nothing
    scalarType ty = ty == "int" || ty == "float" || ty == "string" || ty == "bool"
    make identifier name args retType
      | Array.null args = Nothing
      | not (Array.all (\a -> scalarType a.type_) args) = Nothing
      | not (scalarType retType) = Nothing
      | Map.lookup (safeName identifier) allArities /= Just (Array.length args) = Nothing
      | Array.elem (safeFuncName name <> exactSuffix) declared = Nothing
      | otherwise = Just (safeName identifier)
  in
    Set.fromFoldable (Array.mapMaybe candidate file.decls)

printDecl :: PrinterContext -> PhpDecl -> String
printDecl ctx decl = resolveContinues $ case decl.expression of
  PhpPrivateFunction name args retType stmts ->
    "// " <> decl.identifier <> "\n"
      <>
        ( if Set.member decl.identifier ctx.exacts then genExactFunction ctx name args retType stmts
            <> "\n"
            <> genExactWrapper name args retType
          else genNativeCurry ctx (safeFuncName name) args retType stmts
        )
      <> "\n"
  PhpCompactLoop name args retType stmts ->
    "// " <> decl.identifier <> "\n"
      <> genNativeCurryWithRoot true ctx (safeFuncName name) args retType stmts
      <> "\n"
      <> "$GLOBALS['"
      <> safeName decl.identifier
      <> "'] = __NAMESPACE__ . '\\\\"
      <> safeFuncName name
      <> "';\n"
  PhpNativeFunction name args retType stmts ->
    "// " <> decl.identifier <> "\n"
      <>
        ( if Set.member decl.identifier ctx.exacts then genExactFunction ctx name args retType stmts
            <> "\n"
            <> genExactWrapper name args retType
          else genNativeCurry ctx (safeFuncName name) args retType stmts
        )
      <> "\n"
      <> "$GLOBALS['"
      <> safeName decl.identifier
      <> "'] = __NAMESPACE__ . '\\\\"
      <> safeFuncName name
      <> "';\n"
  PhpGlobalAssign name expr ->
    "// " <> decl.identifier <> "\n$GLOBALS['" <> safeName name <> "'] = " <> printExprWith ctx expr
      <> ";\n"
  expr ->
    "// " <> decl.identifier <> "\n$" <> safeName decl.identifier <> " = " <> printExprWith ctx expr
      <> ";\n"

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
    imps =
      if isBundle then ""
      else joinWith "\n" $ map
        (\i -> "require_once __DIR__ . '/../" <> joinWith "." i <> "/index.php';")
        importsToRequire
    debugImps = "// ALL IMPORTS: " <> joinWith ", " (map (\i -> joinWith "." i) file.imports)
      <> "\n"
      <> "// TO REQUIRE: "
      <> joinWith ", " (map (\i -> joinWith "." i) importsToRequire)
      <> "\n"
    compactRuntime =
      if
        Array.any
          ( \d -> case d.expression of
              PhpCompactLoop _ _ _ _ -> true
              _ -> false
          )
          file.decls then "interface Phpurs_InternalCallable {}\n"
      else ""
    rawDeclsStr = compactRuntime <> joinWith "\n" file.rawDecls
    ctx = { arities: allArities, exacts: exactBodies allArities file }
    decls = joinWith "\n" $ map (printDecl ctx) file.decls
    body = ffiString <> "\n\n" <> rawDeclsStr <> "\n\n" <> decls
    mentions token = not (isNothing (indexOf (Pattern token) body))
    usesHelpers =
      mentions RuntimeNames.curryFallback
        || mentions RuntimeNames.executeEffect
        || mentions RuntimeNames.refNew
        || mentions RuntimeNames.refRead
        || mentions RuntimeNames.refWrite
        || mentions RuntimeNames.value
        || mentions RuntimeNames.record
        || mentions RuntimeNames.promise
        || mentions RuntimeNames.promiseMap
        ||
          mentions RuntimeNames.effectMap
    runtimeRequire =
      if isBundle || not usesHelpers then ""
      else "if (!\\function_exists('" <> RuntimeNames.curryFallback <> "') || !\\function_exists('"
        <> RuntimeNames.executeEffect
        <> "') || !\\function_exists('"
        <> RuntimeNames.refNew
        <> "') || !\\function_exists('"
        <> RuntimeNames.value
        <> "') || !\\function_exists('"
        <> RuntimeNames.record
        <> "') || !\\function_exists('"
        <> RuntimeNames.promise
        <> "')) require_once __DIR__ . '/../runtime.php';\n"
    dataPreamble = if mentions "Phpurs_Data" then Runtime.dataClasses else ""
    prefix =
      if isBundle then "namespace " <> ns <> " {\n" else "<?php\n\nnamespace " <> ns <> ";\n\n"
    suffix = if isBundle then "\n}\n" else "\n"
  in
    prefix <> debugImps <> runtimeRequire <> imps <> "\n\n" <> dataPreamble <> "\n$GLOBALS['"
      <> safeName "Prim_undefined"
      <> "'] = function() { throw new \\Exception(\"undefined\"); };\n"
      <> body
      <> suffix
