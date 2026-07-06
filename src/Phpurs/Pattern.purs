module Phpurs.Pattern where

import Prelude

import Phpurs.PhpAst (PhpExpr(..))
import Phpurs.CoreFn (Binder(..), CaseAlternative(..), Literal(..))
import Data.Array (mapWithIndex, length, foldl, index)
import Data.String (joinWith)
import Data.Maybe (Maybe(..))

type BinderResult =
  { cond :: PhpExpr
  , assigns :: Array PhpExpr
  }

bindBinder :: PhpExpr -> Binder -> BinderResult
bindBinder subj = case _ of
  NullBinder -> { cond: PhpBoolean true, assigns: [] }
  VarBinder ident -> { cond: PhpBoolean true, assigns: [PhpAssign ident subj] }
  NamedBinder ident b ->
    let
      res = bindBinder subj b
    in { cond: res.cond, assigns: [PhpAssign ident subj] <> res.assigns }
  LiteralBinder lit -> case lit of
    ObjectLiteral arr ->
      let
        isObj = PhpCall (PhpRaw "is_object") [subj]
        bindResults = map (\item -> bindBinder (PhpPropertyAccess subj item.key) item.value) arr
        combined = foldl combineResult { cond: isObj, assigns: [] } bindResults
      in combined
    ArrayLiteral arr ->
      let
        isArray = PhpCall (PhpRaw "is_array") [subj]
        lenCond = PhpBinOp "===" (PhpCall (PhpRaw "count") [subj]) (PhpInt (length arr))
        bindResults = mapWithIndex (\i b -> bindBinder (PhpArrayIndex subj i) b) arr
        combined = foldl combineResult { cond: PhpBinOp "&&" isArray lenCond, assigns: [] } bindResults
      in combined
    _ -> { cond: PhpBinOp "===" subj (translateLiteral lit), assigns: [] }
  ConstructorBinder isNewtype _ ctorName bs ->
    if isNewtype then
      -- If it's a newtype, the constructor is removed at runtime. The inner value IS the subject!
      if length bs == 1 then
        bindBinder subj (unsafeIndex bs 0)
      else
        { cond: PhpBoolean true, assigns: [] } -- Should not happen for newtypes
    else
      let
        tagCond = PhpBinOp "===" (PhpPropertyAccess subj "tag") (PhpString ctorName)
        bindResults = mapWithIndex (\i b -> bindBinder (PhpPropertyAccess subj ("v" <> show i)) b) bs
        combined = foldl combineResult { cond: tagCond, assigns: [] } bindResults
      in combined

combineResult :: BinderResult -> BinderResult -> BinderResult
combineResult a b =
  let
    newCond = case a.cond, b.cond of
      PhpBoolean true, c -> c
      c, PhpBoolean true -> c
      c1, c2 -> PhpBinOp "&&" c1 c2
  in { cond: newCond, assigns: a.assigns <> b.assigns }

translateLiteral :: Literal Binder -> PhpExpr
translateLiteral = case _ of
  IntLiteral i -> PhpInt i
  NumberLiteral n -> PhpNumber n
  StringLiteral s -> PhpString s
  CharLiteral c -> PhpString c
  BooleanLiteral b -> PhpBoolean b
  ArrayLiteral _ -> PhpRaw "/* ArrayLiteral */"
  ObjectLiteral _ -> PhpRaw "/* ObjectLiteral */"

unsafeIndex :: forall a. Array a -> Int -> a
unsafeIndex arr i = case index arr i of
  Just x -> x
  Nothing -> unsafeIndex arr i
