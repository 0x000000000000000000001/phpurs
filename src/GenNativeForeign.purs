module Phpurs.GenNativeForeign where

import Prelude
import Data.Array as Array
import Data.String as String
import PureScript.Backend.Optimizer.CoreFn (ExprType(..))

flattenFuncType :: ExprType -> { args :: Array ExprType, ret :: ExprType }
flattenFuncType = case _ of
  Func args ret ->
    let inner = flattenFuncType ret
    in { args: args <> inner.args, ret: inner.ret }
  other -> { args: [], ret: other }

exprTypeToPhpType :: ExprType -> String
exprTypeToPhpType = case _ of
  Int -> "int"
  Number -> "float"
  String -> "string"
  Boolean -> "bool"
  _ -> ""

genNativeWrapper :: String -> String -> String -> String -> ExprType -> String
genNativeWrapper globalKey funcName ffiBaseVar ffiVar exprType =
  let
    flat = flattenFuncType exprType
    arity = Array.length flat.args
  in
    if arity <= 0 then
      "$GLOBALS['" <> globalKey <> "'] = " <> ffiVar <> ";"
    else
      let
        argsWithTypes = Array.mapWithIndex (\i ty -> 
          let phpTy = exprTypeToPhpType ty 
          in (if phpTy == "" || i > 0 then "" else phpTy <> " ") <> "$v" <> show i <> (if i > 0 then " = null" else "")
        ) flat.args
        callArgs = map (\i -> "$v" <> show i) (Array.range 0 (arity - 1))
        
        retPhpTy = exprTypeToPhpType flat.ret
        retTypeSig = if retPhpTy == "" then "" else ": " <> retPhpTy <> "|\\Closure"
        
        globalDecl = if ffiBaseVar == "null" then "" else "  global " <> ffiBaseVar <> ";\n"
        
        fallbackStr = 
          "  $__num = \\func_num_args();\n" <>
          "  $__fn = __NAMESPACE__ . '\\\\" <> funcName <> "';\n" <>
          "  if ($__num < " <> show arity <> ") {\n" <>
          "    return phpurs_curry_fallback($__fn, \\func_get_args(), " <> show arity <> ");\n" <>
          "  }\n"
      in
        "function " <> funcName <> "(" <> String.joinWith ", " argsWithTypes <> ")" <> retTypeSig <> " {\n" <>
        fallbackStr <>
        globalDecl <>
        "  $f = " <> ffiVar <> ";\n" <>
        "  return $f(" <> String.joinWith ", " callArgs <> ");\n" <>
        "}\n" <>
        "$GLOBALS['" <> globalKey <> "'] = __NAMESPACE__ . '\\\\" <> funcName <> "';\n"
