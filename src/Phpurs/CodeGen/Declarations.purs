-- | Emit module-level constructor classes, native functions and initialized
-- | values after binding-group analysis. Expression lowering owns local scope.
module Phpurs.CodeGen.Declarations (translateBindingGroup, translateConstructors) where

import Prelude

import Data.Array as Array
import Data.Maybe (Maybe(..))
import Data.Set (Set)
import Data.Set as Set
import Data.String as String
import Data.String.Pattern (Pattern(..), Replacement(..))
import Data.Tuple (Tuple(..))
import Phpurs.CodeGen.Bindings (AnalyzedBindingGroup, extractUncurriedAbs)
import Phpurs.CodeGen.Context (TranslationContext, initialContext)
import Phpurs.CodeGen.Expr (translateExpr, wrapInStmts)
import Phpurs.CodeGen.Tco (containsNestedLoop, wrapLoopBody)
import Phpurs.CodeGen.Types
  ( exprTypeToPhpType
  , extractFuncType
  , getRetType
  , remainingArity
  , zipArgsWithTypes
  )
import Phpurs.CompactLoops as CompactLoops
import Phpurs.PhpAst (PhpDecl, PhpExpr(..))
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr)
import PureScript.Backend.Optimizer.CoreFn (DataDecl, Ident(..))

translateConstructors :: String -> Set String -> Array DataDecl -> Array String
translateConstructors modPrefix privateConstructors dataDecls =
  Array.concatMap
    ( \decl ->
        Array.concatMap
          ( \ctor ->
              let
                safeCtorName = String.replaceAll (Pattern "'") (Replacement "_prime_") ctor.name
                structName = modPrefix <> safeCtorName
                safeTagStr = String.replaceAll (Pattern "'") (Replacement "\\'") ctor.name
                isPrivate = Set.member ctor.name privateConstructors
                -- Internal field types are proven before rewriting. PHP promoted
                -- property checks add a cost to each allocation without adding a
                -- check at a public boundary; keep those checks on public classes.
                argsStr = Array.mapWithIndex
                  ( \i typ -> "public " <> (if isPrivate then "" else exprTypeToPhpType typ)
                      <> " $value"
                      <> show i
                  )
                  ctor.fields
                -- Generated pattern matches use `instanceof`, never a tag read, and
                -- private constructors never cross an FFI or public boundary. The
                -- public `$tag` is part of the visible representation only.
                tagDecl = if isPrivate then "" else "public $tag = '" <> safeTagStr <> "'; "
                structDecl = "final class " <> structName <> " { " <> tagDecl
                  <> "public function __construct("
                  <> String.joinWith ", " argsStr
                  <> ") {} }"
              in
                [ structDecl ]
          )
          decl.constructors
    )
    dataDecls

translateBindingGroup :: String -> Boolean -> AnalyzedBindingGroup -> Array PhpDecl
translateBindingGroup moduleName allowDirect group = case group.bindings of
  [ Tuple (Ident name) expr ] | group.recursive ->
    let
      identifier = moduleName <> "_" <> name
      ctx = (initialContext moduleName)
        { allowDirect = allowDirect, recursiveVars = [ identifier ] }
    in
      case extractUncurriedAbs expr of
        Just fn ->
          let
            loop =
              { ident: identifier
              , params: fn.args
              , varPrefix: "__tco_var_" <> identifier <> "_"
              , labelName: "tco_loop_" <> identifier
              , direct: allowDirect && not (containsNestedLoop fn.body)
              }
            result = translateExpr (ctx { loops = [ loop ], isTail = true }) 0 fn.body
            types = extractFuncType expr
            params = zipArgsWithTypes fn.args types
            returnType = getRetType (Array.length fn.args) types
          in
            [ { identifier
              , expression: CompactLoops.optimize loop.labelName types
                  (PhpNativeFunction identifier params returnType (wrapLoopBody loop result))
              }
            ]
        Nothing -> translateBinding ctx identifier expr
  _ -> Array.concatMap
    ( \(Tuple (Ident name) expr) -> translateBinding
        ((initialContext moduleName) { allowDirect = allowDirect })
        (moduleName <> "_" <> name)
        expr
    )
    group.bindings

-- | A lambda becomes a native function. A function-valued expression is first
-- | initialized once, then exposed through a forwarding wrapper of its remaining
-- | arity. Plain values need only the global initialization.
translateBinding :: TranslationContext -> String -> TcoExpr -> Array PhpDecl
translateBinding ctx identifier expr = case extractUncurriedAbs expr of
  Just fn ->
    let
      result = translateExpr (ctx { isTail = true }) 0 fn.body
      types = extractFuncType expr
      params = zipArgsWithTypes fn.args types
      returnType = getRetType (Array.length fn.args) types
    in
      [ { identifier
        , expression: PhpNativeFunction identifier params returnType
            (result.stmts <> [ PhpReturn result.expr ])
        }
      ]
  Nothing ->
    let
      result = translateExpr ctx 0 expr
      value = wrapInStmts [] result.stmts result.expr
      arity = remainingArity expr
    in
      if arity > 0 then
        let
          closureName = identifier <> "_closure"
          args = Array.mapWithIndex (\index _ -> "v_" <> show index) (Array.replicate arity unit)
          call = PhpCall (PhpGlobalVar Nothing closureName) (map PhpVar args)
          types = extractFuncType expr
          params = zipArgsWithTypes args types
          returnType = getRetType arity types
        in
          [ { identifier: closureName, expression: PhpGlobalAssign closureName value }
          , { identifier
            , expression: PhpNativeFunction identifier params returnType [ PhpReturn call ]
            }
          ]
      else
        [ { identifier, expression: PhpGlobalAssign identifier value } ]
