-- | Pure helpers and the shared operand walker for expression translation.
module Phpurs.CodeGen.Expr.Support
  ( Translate
  , captureVariable
  , closureCaptures
  , flattenApp
  , isSimpleBindingValue
  , nestedUpdate
  , recordFields
  , renameLocal
  , sameRoot
  , stripRoot
  , totalUsagesOf
  , translateValue
  , translateValues
  , updatePath
  , UpdatePath
  , wrapInStmts
  ) where

import Prelude

import Control.Monad.ST as ST
import Control.Monad.ST.Ref as STRef
import Data.Array as Array
import Data.Array.NonEmpty (toArray)
import Data.Array.ST as STArray
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Tuple (Tuple(..))
import Phpurs.CodeGen.Context
  ( TranslatedValues
  , TranslationContext
  , TranslationResult
  , valueContext
  )
import Phpurs.FreeVars (freeVars)
import Phpurs.PhpAst (PhpExpr(..))
import PureScript.Backend.Optimizer.Codegen.Tco
  ( TcoAnalysis(..)
  , TcoExpr(..)
  , TcoRef
  , TcoUsage(..)
  )
import PureScript.Backend.Optimizer.CoreFn (ExprType(..), Literal(..), Prop, Qualified(..))
import PureScript.Backend.Optimizer.FreeVars (localId)
import PureScript.Backend.Optimizer.Syntax
  ( BackendAccessor(..)
  , BackendSyntax(..)
  )
import PureScript.Backend.Optimizer.Syntax as Syn

type Translate = TranslationContext -> Int -> TcoExpr -> TranslationResult

translateValue :: Translate -> TranslationContext -> Int -> TcoExpr -> TranslationResult
translateValue translate ctx = translate (valueContext ctx)

translateValues :: Translate -> TranslationContext -> Int -> Array TcoExpr -> TranslatedValues
translateValues translate ctx nextId values = ST.run do
  stmts <- STArray.new
  exprs <- STArray.new
  counter <- STRef.new nextId
  ST.foreach values \expr -> do
    current <- STRef.read counter
    let result = translate (valueContext ctx) current expr
    -- Append only the new statements, without recopying the accumulated prefix
    -- or spreading a potentially large statement array into call arguments.
    ST.foreach result.stmts \stmt -> void $ STArray.push stmt stmts
    void $ STArray.push result.expr exprs
    void $ STRef.write result.nextId counter
  -- Both arrays are fresh and remain private until construction has finished.
  finalStmts <- STArray.unsafeFreeze stmts
  finalExprs <- STArray.unsafeFreeze exprs
  finalId <- STRef.read counter
  pure { stmts: finalStmts, exprs: finalExprs, nextId: finalId }

renameLocal :: TranslationContext -> String -> String
renameLocal ctx name = fromMaybe name (Map.lookup name ctx.boundVars)

-- | Recursive closures capture the renamed binding by reference so they see
-- | its eventual initialization. Ordinary captures keep PHP's value semantics.
captureVariable :: TranslationContext -> String -> String
captureVariable ctx name = if Array.elem name ctx.recursiveVars then "&" <> name else name

closureCaptures :: TranslationContext -> TcoExpr -> Array String
closureCaptures ctx expr = map (captureVariable ctx <<< renameLocal ctx)
  (Array.fromFoldable (freeVars expr))

-- Terminal alternatives may share these slots: evaluating the binding itself
-- cannot introduce statements or capture a reference to the slot being written.
isSimpleBindingValue :: TcoExpr -> Boolean
isSimpleBindingValue (TcoExpr _ expr) = case expr of
  Local _ _ -> true
  Accessor value _ -> isSimpleBindingValue value
  Lit (LitInt _) -> true
  Lit (LitNumber _) -> true
  Lit (LitString _) -> true
  Lit (LitChar _) -> true
  Lit (LitBoolean _) -> true
  Typed _ value -> isSimpleBindingValue value
  Syn.TypeApp value _ -> isSimpleBindingValue value
  _ -> false

wrapInStmts :: Array String -> Array PhpExpr -> PhpExpr -> PhpExpr
wrapInStmts _ [] expr = expr
wrapInStmts captures stmts expr = PhpCall (PhpFunction captures [] "" (stmts <> [ PhpReturn expr ]))
  []

flattenApp :: TcoExpr -> Tuple TcoExpr (Array TcoExpr)
flattenApp tcoExpr@(TcoExpr _ syntax) = case syntax of
  App fn args ->
    let
      Tuple innerFn innerArgs = flattenApp fn
    in
      Tuple innerFn (innerArgs <> toArray args)
  Typed _ inner -> flattenApp inner
  Syn.TypeApp inner _ -> flattenApp inner
  _ -> Tuple tcoExpr []

recordFields :: ExprType -> Maybe (Array (Tuple String ExprType))
recordFields = case _ of
  Record (Row fields Nothing) -> Just fields
  _ -> Nothing

stripRoot :: TcoExpr -> TcoExpr
stripRoot tcoExpr@(TcoExpr _ syntax) = case syntax of
  Typed _ inner -> stripRoot inner
  Syn.TypeApp inner _ -> stripRoot inner
  _ -> tcoExpr

sameRoot :: TcoExpr -> TcoExpr -> Boolean
sameRoot left right =
  let
    TcoExpr _ a = stripRoot left
    TcoExpr _ b = stripRoot right
  in
    case a, b of
      Local ident level, Local ident2 level2 -> localId ident level == localId ident2 level2
      Var (Qualified moduleName ident), Var (Qualified moduleName2 ident2) ->
        moduleName == moduleName2 && ident == ident2
      _, _ -> false

type UpdatePath = { root :: TcoExpr, path :: Array String }

updatePath :: TcoExpr -> Maybe UpdatePath
updatePath tcoExpr@(TcoExpr _ syntax) = case syntax of
  Typed _ inner -> updatePath inner
  Syn.TypeApp inner _ -> updatePath inner
  -- A global read may be a demand-time FFI getter and must still be evaluated
  -- on the original path. Only local roots are proven side-effect free.
  Local _ _ -> Just { root: tcoExpr, path: [] }
  Var _ -> Nothing
  Accessor inner (GetProp key) ->
    (\found -> found { path = found.path <> [ key ] }) <$> updatePath inner
  _ -> Nothing

nestedUpdate :: ExprType -> TcoExpr -> Maybe { base :: TcoExpr, props :: Array (Prop TcoExpr) }
nestedUpdate fieldType (TcoExpr _ syntax) = case syntax of
  Typed _ inner -> nestedUpdate fieldType inner
  Syn.TypeApp inner _ -> nestedUpdate fieldType inner
  Update base props -> case recordFields fieldType of
    Just _ -> Just { base, props }
    Nothing -> Nothing
  _ -> Nothing

totalUsagesOf :: TcoRef -> TcoAnalysis -> Int
totalUsagesOf ref (TcoAnalysis { usages }) = case Map.lookup ref usages of
  Just (TcoUsage { total }) -> total
  _ -> 0
