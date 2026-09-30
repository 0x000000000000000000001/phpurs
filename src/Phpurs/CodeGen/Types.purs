-- | PHP signatures and runtime arity derived from typed optimizer expressions.
module Phpurs.CodeGen.Types
  ( FunctionType
  , exprTypeToPhpType
  , extractFuncType
  , getRetType
  , remainingArity
  , zipArgsWithTypes
  ) where

import Prelude

import Data.Array as Array
import Data.Array.NonEmpty (toArray)
import Data.Maybe (Maybe(..), fromMaybe)
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..))
import PureScript.Backend.Optimizer.CoreFn (ExprType(..))
import PureScript.Backend.Optimizer.Syntax as Syn

type FunctionType = { fArgs :: Array ExprType, fRet :: ExprType }

extractFuncType :: TcoExpr -> Maybe FunctionType
extractFuncType (TcoExpr _ (Syn.Typed ty inner)) = case ty of
  Func args ret -> Just (flattenFuncType args ret)
  _ -> extractFuncType inner
  where
  flattenFuncType args (Func moreArgs ret) = flattenFuncType (args <> moreArgs) ret
  flattenFuncType args ret = { fArgs: args, fRet: ret }
extractFuncType _ = Nothing

zipArgsWithTypes :: Array String -> Maybe FunctionType -> Array { name :: String, type_ :: String }
zipArgsWithTypes names = case _ of
  Just { fArgs } -> Array.mapWithIndex
    (\index name -> { name, type_: exprTypeToPhpType (fromMaybe Any (Array.index fArgs index)) })
    names
  Nothing -> map (\name -> { name, type_: "" }) names

getRetType :: Int -> Maybe FunctionType -> String
getRetType arity = case _ of
  Just { fArgs, fRet } ->
    if arity < Array.length fArgs then "" else exprTypeToPhpType fRet
  Nothing -> ""

-- | Only scalar types currently become PHP signature checks. An empty string
-- | leaves the value untyped at this boundary; it does not imply PHP `mixed`.
exprTypeToPhpType :: ExprType -> String
exprTypeToPhpType = case _ of
  Int -> "int"
  Number -> "float"
  String -> "string"
  Boolean -> "bool"
  _ -> ""

-- | A partial application can retain the callee's pre-application annotation,
-- | including an already applied dictionary. Both forwarding wrappers and the
-- | printer's arity table must describe the remaining runtime arguments.
remainingArity :: TcoExpr -> Int
remainingArity expr = max 0 (annotatedArity expr - appliedArgs expr)

annotatedArity :: TcoExpr -> Int
annotatedArity (TcoExpr _ syntax) = case syntax of
  Syn.Typed (Func args _) _ -> Array.length args
  Syn.Typed _ inner -> annotatedArity inner
  _ -> 0

appliedArgs :: TcoExpr -> Int
appliedArgs (TcoExpr _ syntax) = case syntax of
  Syn.Typed _ inner -> appliedArgs inner
  Syn.App fn args -> Array.length (toArray args) + appliedArgs fn
  Syn.UncurriedApp _ args -> Array.length args
  Syn.TypeApp inner _ -> appliedArgs inner
  _ -> 0
