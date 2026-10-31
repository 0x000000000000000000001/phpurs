-- | A single, bounded pass over native functions. Public entry points remain intact.
module Phpurs.TailInline (optimize, simplifyReturns, bodyBudget, callerBudget) where

import Prelude

import Data.Array as Array
import Data.Foldable (all, any, foldl, foldMap)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.String as String
import Data.Tuple (Tuple(..))
import Phpurs.PhpAst (PhpExpr(..), PhpFile)
import Phpurs.Budget as Budget
import Phpurs.TailInline.Proof (flattenCall, leafNode, localNames, nodes, readNames, terminates)
import Phpurs.TailInline.Rewrite (compactCopies, rename)
import Phpurs.TailInline.Rewrite as Rewrite

-- Count expression nodes, not just statements; cap both each copied body and
-- the total added body/argument nodes per caller. No iterative expansion.
simplifyReturns :: Array PhpExpr -> Array PhpExpr
simplifyReturns = Rewrite.simplifyReturns

bodyBudget :: Int
bodyBudget = Budget.inlineBody

callerBudget :: Int
callerBudget = Budget.inlineCaller

-- Candidates containing closures are excluded; callers' closures keep their
-- own locals and returns. Only the name scans below explicitly enter them.

optimize :: PhpFile -> PhpFile
optimize file = file { decls = map optimizeDecl file.decls }
  where
  modulePrefix = String.joinWith "_" file.namespace <> "_"
  noCheck t = t == "" || t == "mixed"
  candidates = Map.fromFoldable $ Array.mapMaybe
    ( \d -> case d.expression of
        -- genNativeCurry annotates only the first parameter; subsequent parameters
        -- stay untyped/optional for currying even when TAST supplies their types.
        PhpNativeFunction name args ret body
          | Just first <- Array.head args, noCheck first.type_ && noCheck ret ->
              let
                terminal = simplifyReturns body
                terminalNodes = Array.concatMap nodes terminal
                locals = map _.name args <> Array.mapMaybe
                  ( case _ of
                      PhpAssign v _ -> Just v
                      _ -> Nothing
                  )
                  terminalNodes
                isLeaf = all leafNode terminalNodes && terminates terminal
                  && all (flip Array.elem locals) (foldMap readNames terminal)
                compact = if isLeaf then compactCopies terminal else terminal
                ns = Array.concatMap nodes compact
              in
                if isLeaf && Array.length ns <= bodyBudget then Just
                  ( Tuple name
                      { args, body: compact, cost: Array.length ns + 2 * Array.length args }
                  )
                else Nothing
        _ -> Nothing
    )
    file.decls

  targetName = case _ of
    PhpGlobalVar (Just mod) ident | mod == file.namespace -> Just (modulePrefix <> ident)
    PhpGlobalVar Nothing ident -> Just ident
    _ -> Nothing

  optimizeDecl decl = case decl.expression of
    PhpNativeFunction name args ret body ->
      let
        names = map _.name args <> foldMap localNames body
        fresh n =
          let
            attempt = "__phpurs_inline_" <> show n <> "_"
          in
            if any (\v -> String.take (String.length attempt) v == attempt) names then fresh (n + 1)
            else attempt
        prefix = fresh 0
        -- Slots are shared between terminal sites of the same callee. No two
        -- such invocations can overlap within this PHP activation.
        indexed = Map.fromFoldable $ Array.mapWithIndex
          ( \i (Tuple key value) -> Tuple key
              { args: value.args
              , body: value.body
              , cost: value.cost
              , prefix: prefix <> show i <> "_"
              }
          )
          (Map.toUnfoldable candidates :: Array _)
        visit budget xs = foldl step { stmts: [], remaining: budget, changed: false } xs
          where
          step acc stmt =
            let
              res = one acc.remaining stmt
            in
              { stmts: acc.stmts <> res.stmts
              , remaining: res.remaining
              , changed: acc.changed || res.changed
              }
          one available stmt = case stmt of
            PhpReturn call ->
              let
                Tuple target actual = flattenCall call
              in
                case
                  targetName target >>=
                    (\key -> if key == name then Nothing else Map.lookup key indexed)
                  of
                  Just fn | Array.length actual == Array.length fn.args && fn.cost <= available ->
                    { stmts:
                        Array.zipWith (\arg value -> PhpAssign (fn.prefix <> arg.name) value)
                          fn.args
                          actual <> map (rename fn.prefix) fn.body
                    , remaining: available - fn.cost
                    , changed: true
                    }
                  _ -> unchanged available stmt
            PhpIf c t e ->
              let
                a = visit available t
                b = visit a.remaining e
              in
                { stmts: [ PhpIf c a.stmts b.stmts ]
                , remaining: b.remaining
                , changed: a.changed || b.changed
                }
            PhpWhile c b ->
              let
                r = visit available b
              in
                { stmts: [ PhpWhile c r.stmts ], remaining: r.remaining, changed: r.changed }
            PhpSwitch s cs d ->
              let
                cases = foldl
                  ( \acc c ->
                      let
                        r = visit acc.remaining c.stmts
                      in
                        { cases: Array.snoc acc.cases (c { stmts = r.stmts })
                        , remaining: r.remaining
                        , changed: acc.changed || r.changed
                        }
                  )
                  { cases: [], remaining: available, changed: false }
                  cs
                def = visit cases.remaining (fromMaybe [] d)
              in
                { stmts: [ PhpSwitch s cases.cases (map (const def.stmts) d) ]
                , remaining: def.remaining
                , changed: cases.changed || def.changed
                }
            _ -> unchanged available stmt
          unchanged available stmt = { stmts: [ stmt ], remaining: available, changed: false }
        result = visit callerBudget (simplifyReturns body)
      in
        if result.changed then decl { expression = PhpNativeFunction name args ret result.stmts }
        else decl
    _ -> decl
