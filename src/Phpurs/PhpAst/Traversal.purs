-- | Structural PHP AST traversal. Optimization eligibility and resource budgets
-- | belong to the passes; choose explicitly whether to enter nested functions.
module Phpurs.PhpAst.Traversal
  ( children
  , localChildren
  , mapChildren
  , mapBranches
  , mapBlocks
  ) where

import Prelude

import Data.Array as Array
import Data.Maybe (fromMaybe)
import Phpurs.PhpAst (PhpExpr(..))

data ChildScope = AllScopes | LocalScope

-- | Direct children across all scopes, including function bodies and globals.
-- | Enumeration is structural: a match's fallback precedes its arms. This is
-- | not an evaluation-order contract.
children :: PhpExpr -> Array PhpExpr
children = childrenWithScope AllScopes

-- | Direct children inside a function body. Nested function declarations,
-- | closures and module-level assignments are opaque to this traversal.
localChildren :: PhpExpr -> Array PhpExpr
localChildren = childrenWithScope LocalScope

-- Keep this match exhaustive so new AST forms require a traversal decision.
childrenWithScope :: ChildScope -> PhpExpr -> Array PhpExpr
childrenWithScope scope = case _ of
  PhpFunction _ _ _ body -> scoped body
  PhpCompactFunction _ _ _ body -> scoped body
  PhpCompactLoop _ _ _ body -> scoped body
  PhpNativeFunction _ _ _ body -> scoped body
  PhpPrivateFunction _ _ _ body -> scoped body
  PhpGlobalAssign _ e -> scoped [ e ]
  PhpVar _ -> []
  PhpGlobalVar _ _ -> []
  PhpDirectCall _ args -> args
  PhpCall fn args -> [ fn ] <> args
  PhpInt _ -> []
  PhpNumber _ -> []
  PhpString _ -> []
  PhpBoolean _ -> []
  PhpArray xs -> xs
  PhpAssocArray xs -> map _.value xs
  PhpPropertyAccess e _ -> [ e ]
  PhpRecordAccess e _ -> [ e ]
  PhpArrayIndex e index -> [ e, index ]
  PhpAssign _ e -> [ e ]
  PhpAssignExpr a b -> [ a, b ]
  PhpIf condition yes no -> [ condition ] <> yes <> no
  PhpMatch e cases fallback -> [ e, fallback ] <> Array.concatMap (\c -> [ c.val, c.body ]) cases
  PhpThrow e -> [ e ]
  PhpTernary condition yes no -> [ condition, yes, no ]
  PhpReturn e -> [ e ]
  PhpBinOp _ a b -> [ a, b ]
  PhpWhile condition body -> [ condition ] <> body
  PhpContinue -> []
  PhpRaw _ -> []
  PhpNew _ args -> args
  PhpClone e -> [ e ]
  PhpSwitch e cases fallback -> [ e ] <> Array.concatMap (\c -> c.matchCases <> c.stmts) cases <> fromMaybe [] fallback
  PhpGoto _ -> []
  PhpLabel _ -> []
  PhpInstanceOf e _ -> [ e ]
  where
  scoped body = case scope of
    AllScopes -> body
    LocalScope -> []

-- | Map every direct child, including function bodies. The caller controls
-- | recursion; binding names, captures and signatures are retained verbatim.
mapChildren :: (PhpExpr -> PhpExpr) -> PhpExpr -> PhpExpr
mapChildren f = case _ of
  PhpFunction caps args ret body -> PhpFunction caps args ret (map f body)
  PhpCompactFunction caps args ret body -> PhpCompactFunction caps args ret (map f body)
  PhpCompactLoop name args ret body -> PhpCompactLoop name args ret (map f body)
  PhpNativeFunction name args ret body -> PhpNativeFunction name args ret (map f body)
  PhpPrivateFunction name args ret body -> PhpPrivateFunction name args ret (map f body)
  PhpGlobalAssign name e -> PhpGlobalAssign name (f e)
  PhpVar name -> PhpVar name
  PhpGlobalVar ns name -> PhpGlobalVar ns name
  PhpDirectCall name args -> PhpDirectCall name (map f args)
  PhpCall e args -> PhpCall (f e) (map f args)
  PhpInt n -> PhpInt n
  PhpNumber n -> PhpNumber n
  PhpString s -> PhpString s
  PhpBoolean b -> PhpBoolean b
  PhpArray xs -> PhpArray (map f xs)
  PhpAssocArray xs -> PhpAssocArray (map (\x -> x { value = f x.value }) xs)
  PhpPropertyAccess e key -> PhpPropertyAccess (f e) key
  PhpRecordAccess e key -> PhpRecordAccess (f e) key
  PhpArrayIndex e index -> PhpArrayIndex (f e) (f index)
  PhpAssign name e -> PhpAssign name (f e)
  PhpAssignExpr a b -> PhpAssignExpr (f a) (f b)
  PhpIf condition yes no -> PhpIf (f condition) (map f yes) (map f no)
  PhpMatch e cases fallback -> PhpMatch (f e) (map (\c -> { val: f c.val, body: f c.body }) cases) (f fallback)
  PhpThrow e -> PhpThrow (f e)
  PhpTernary condition yes no -> PhpTernary (f condition) (f yes) (f no)
  PhpReturn e -> PhpReturn (f e)
  PhpBinOp op a b -> PhpBinOp op (f a) (f b)
  PhpWhile condition body -> PhpWhile (f condition) (map f body)
  PhpContinue -> PhpContinue
  PhpRaw s -> PhpRaw s
  PhpNew name args -> PhpNew name (map f args)
  PhpClone e -> PhpClone (f e)
  PhpSwitch e cases fallback -> PhpSwitch (f e)
    (map (\c -> { matchCases: map f c.matchCases, stmts: map f c.stmts }) cases) (map (map f) fallback)
  PhpGoto name -> PhpGoto name
  PhpLabel name -> PhpLabel name
  PhpInstanceOf e name -> PhpInstanceOf (f e) name

-- | Rewrite branch statement lists in the current scope, retaining conditions
-- | and switch labels. Loop bodies are handled separately by mapBlocks.
mapBranches :: (Array PhpExpr -> Array PhpExpr) -> PhpExpr -> PhpExpr
mapBranches f = case _ of
  PhpIf condition yes no -> PhpIf condition (f yes) (f no)
  PhpSwitch e cases fallback -> PhpSwitch e (map (\c -> c { stmts = f c.stmts }) cases) (map f fallback)
  e -> e

-- | Rewrite branch and loop statement lists, without entering nested functions.
mapBlocks :: (Array PhpExpr -> Array PhpExpr) -> PhpExpr -> PhpExpr
mapBlocks f = case _ of
  PhpWhile condition body -> PhpWhile condition (f body)
  e -> mapBranches f e
