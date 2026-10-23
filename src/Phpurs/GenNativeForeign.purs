-- | Prepare a module's foreign exports and their public PHP calling wrappers.
module Phpurs.GenNativeForeign
  ( ForeignModule
  , genForeignModule
  ) where

import Prelude

import Data.Array as Array
import Data.Map (Map)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.Tuple (Tuple(..))
import Phpurs.CodeGen.Types (exprTypeToPhpType)
import Phpurs.RuntimeNames as RuntimeNames
import Phpurs.PhpAst (PhpExpr(..), foreignValueArity)
import Phpurs.Printer (printExpr, safeFuncName, safeName)
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Ident(..), ModuleName(..))

type ForeignModule = { arities :: Map String Int, code :: String }

type ForeignSignature = { args :: Array ExprType, ret :: ExprType }

-- | The export key and native function name have different PHP escaping rules.
-- | A missing FFI base means the wrapper has no export table to capture.
type ForeignBinding =
  { globalKey :: String
  , funcName :: String
  , ffiBaseVar :: Maybe String
  , exportKey :: String
  , missingForeign :: String
  , signature :: ForeignSignature
  }

-- | Keep arities and wrappers together so both use the same flattened foreign
-- | signature. Source is the original FFI file, including its optional PHP tag.
genForeignModule
  :: { moduleName :: ModuleName, bindings :: Map Ident (Maybe ExprType), source :: String }
  -> ForeignModule
genForeignModule { moduleName: ModuleName name, bindings, source } =
  let
    phpModName = String.replaceAll (Pattern ".") (Replacement "_") name
    ffiBase = "$ffi_" <> phpModName
    ffiCode = String.trim
      ( String.replace (Pattern "<?php\n") (Replacement "")
          (String.replace (Pattern "<?php") (Replacement "") source)
      )
    hasSource = ffiCode /= ""
    prepareBinding (Tuple (Ident ident) type_) =
      let
        fullName = phpModName <> "_" <> ident
      in
        { globalKey: safeName fullName
        , funcName: safeFuncName fullName
        , ffiBaseVar: if hasSource then Just ffiBase else Nothing
        , exportKey: printExpr Map.empty (PhpString ident)
        , missingForeign: "throw new \\RuntimeException("
            <> printExpr Map.empty (PhpString ("Missing PHP FFI export: " <> name <> "." <> ident))
            <> ")"
        , signature: flattenFuncType (fromMaybe Any type_)
        }
    prepared = map prepareBinding (Map.toUnfoldable bindings :: Array _)
    mappings = String.joinWith "\n" (map genNativeWrapper prepared)
    code =
      if hasSource then
        ffiBase <> " = \\call_user_func(function() {\n  $exports = [];\n"
          <> ffiCode
          <> "\n  return $exports;\n});\n"
          <> mappings
          <> "\n"
      else mappings <> (if mappings /= "" then "\n" else "")
  in
    { arities: Map.fromFoldable
        ( map
            ( \binding -> Tuple binding.globalKey
                ( if Array.null binding.signature.args then foreignValueArity
                  else Array.length binding.signature.args
                )
            )
            prepared
        )
    , code
    }

-- | Boundary adaptation derived from the foreign import type. `Any` and
-- | records need a structural conversion because PureScript records are
-- | stdClass values; `Foreign` and `Object` are accepted as plain arrays by
-- | the runtime, so they stay untouched.
data Conversion
  = ConvertDirect String
  | ConvertEffect Conversion
  | ConvertPromise Conversion

conversionFor :: ExprType -> Maybe Conversion
conversionFor ty = case stripForAll ty of
  Any -> Just (ConvertDirect RuntimeNames.value)
  Record _ -> Just (ConvertDirect RuntimeNames.record)
  Array inner -> case conversionFor inner of
    Just (ConvertDirect _) -> Just (ConvertDirect RuntimeNames.value)
    _ -> Nothing
  ADT _ fqn [ inner ]
    | fqn == [ "Effect", "Effect" ] -> ConvertEffect <$> conversionFor inner
    | fqn == [ "Promise", "Internal", "Promise" ] -> ConvertPromise <$> conversionFor inner
  _ -> Nothing

applyConversion :: Conversion -> String -> String
applyConversion conversion expr = case conversion of
  ConvertDirect helper -> "\\" <> helper <> "(" <> expr <> ")"
  ConvertEffect inner -> "\\" <> RuntimeNames.effectMap <> "(" <> expr <> ", "
    <> conversionCallable inner
    <> ")"
  ConvertPromise inner -> "\\" <> RuntimeNames.promiseMap <> "(" <> expr <> ", "
    <> conversionCallable inner
    <> ")"

conversionCallable :: Conversion -> String
conversionCallable conversion = case conversion of
  ConvertDirect helper -> "'" <> helper <> "'"
  _ -> "function($__value) { return " <> applyConversion conversion "$__value" <> "; }"

adaptReturn :: ExprType -> String -> String
adaptReturn ty expr = case conversionFor ty of
  Just conversion -> applyConversion conversion expr
  Nothing -> expr

stripForAll :: ExprType -> ExprType
stripForAll (ForAll _ t) = stripForAll t
stripForAll t = t

flattenFuncType :: ExprType -> ForeignSignature
flattenFuncType ty = case stripForAll ty of
  Func args ret ->
    let
      inner = flattenFuncType ret
    in
      { args: args <> inner.args, ret: inner.ret }
  other -> { args: [], ret: other }

genNativeWrapper :: ForeignBinding -> String
genNativeWrapper { globalKey, funcName, ffiBaseVar, exportKey, missingForeign, signature: flat } =
  let
    arity = Array.length flat.args
    globalValue = "$GLOBALS['" <> globalKey <> "']"
    hasExport base = "\\array_key_exists(" <> exportKey <> ", " <> base <> ")"
    exportValue base = base <> "[" <> exportKey <> "]"
    ffiValue = case ffiBaseVar of
      Just base -> "(" <> hasExport base <> " ? " <> exportValue base <> " : (" <> missingForeign <>
        "))"
      Nothing -> "(" <> missingForeign <> ")"
  in
    if arity <= 0 then
      -- Values are adapted once when the export is captured. An absent export
      -- has no global slot. The printer uses ?? to call this getter only for
      -- absent or null slots; array_key_exists preserves an exported null.
      "function " <> funcName <> "() {\n"
        <> "  return \\array_key_exists('"
        <> globalKey
        <> "', $GLOBALS) ? "
        <> globalValue
        <> " : ("
        <> missingForeign
        <> ");\n"
        <> "}\n"
        <> case ffiBaseVar of
          Just base -> "if (" <> hasExport base <> ") {\n  " <> globalValue <> " = "
            <> adaptReturn flat.ret (exportValue base)
            <> ";\n} else {\n  unset("
            <> globalValue
            <> ");\n}\n"
          Nothing -> "unset(" <> globalValue <> ");\n"
    else
      let
        argsWithTypes = Array.mapWithIndex
          ( \i ty ->
              let
                phpTy = exprTypeToPhpType ty
              in
                (if phpTy == "" || i > 0 then "" else phpTy <> " ") <> "$v" <> show i <>
                  (if i > 0 then " = null" else "")
          )
          flat.args
        callArgs = map (\i -> "$v" <> show i) (Array.range 0 (arity - 1))

        retPhpTy = exprTypeToPhpType flat.ret
        retTypeSig = if retPhpTy == "" then "" else ": " <> retPhpTy <> "|\\Closure"

        globalDecl = case ffiBaseVar of
          Just name -> "  global " <> name <> ";\n"
          Nothing -> ""

        fallbackStr =
          "  $__num = \\func_num_args();\n"
            <> "  $__fn = __NAMESPACE__ . '\\\\"
            <> funcName
            <> "';\n"
            <> "  if ($__num < "
            <> show arity
            <> ") {\n"
            <> "    return \\"
            <> RuntimeNames.curryFallback
            <> "($__fn, \\func_get_args(), "
            <> show arity
            <> ");\n"
            <>
              "  }\n"
      in
        "function " <> funcName <> "(" <> String.joinWith ", " argsWithTypes <> ")" <> retTypeSig
          <> " {\n"
          <> fallbackStr
          <> globalDecl
          <> "  $f = "
          <> ffiValue
          <> ";\n"
          <> "  $__ret = $f("
          <> String.joinWith ", " callArgs
          <> ");\n"
          <> "  return "
          <> adaptReturn flat.ret "$__ret"
          <> ";\n"
          <> "}\n"
          <> "$GLOBALS['"
          <> globalKey
          <> "'] = __NAMESPACE__ . '\\\\"
          <> funcName
          <> "';\n"
