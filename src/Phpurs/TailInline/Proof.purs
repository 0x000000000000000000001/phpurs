-- | Pure predicates and name scans used by tail inlining.
module Phpurs.TailInline.Proof
  ( captureName
  , capturedNames
  , flattenCall
  , leafNode
  , localNames
  , nodes
  , readNames
  , returnLabels
  , terminates
  ) where

import Prelude

import Data.Array as Array
import Data.Foldable (all, any, foldl, foldMap)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.String as String
import Data.Tuple (Tuple(..))
import Phpurs.PhpAst (PhpExpr(..))
import Phpurs.PhpAst.Traversal (localChildren)

nodes :: PhpExpr -> Array PhpExpr
nodes e = [ e ] <> Array.concatMap nodes (localChildren e)

returnLabels :: Array PhpExpr -> Map.Map String String
returnLabels xs =
  let
    here = Array.mapMaybe identity $ Array.zipWith pair xs (Array.drop 1 xs)
    pair (PhpLabel label) (PhpReturn (PhpVar name)) = Just (Tuple label name)
    pair _ _ = Nothing
    nested = foldl Map.union Map.empty $ map
      ( case _ of
          PhpIf _ t e -> Map.union (returnLabels t) (returnLabels e)
          PhpWhile _ b -> returnLabels b
          PhpSwitch _ cs d -> foldl Map.union (returnLabels (fromMaybe [] d))
            (map (returnLabels <<< _.stmts) cs)
          _ -> Map.empty
      )
      xs
  in
    Map.union (Map.fromFoldable here) nested

leafNode :: PhpExpr -> Boolean
leafNode = case _ of
  PhpVar _ -> true
  PhpGlobalVar _ _ -> true
  PhpInt _ -> true
  PhpNumber _ -> true
  PhpString _ -> true
  PhpBoolean _ -> true
  PhpArray _ -> true
  PhpAssocArray _ -> true
  PhpPropertyAccess _ _ -> true
  PhpRecordAccess _ _ -> true
  PhpArrayIndex _ _ -> true
  PhpAssign _ _ -> true
  PhpIf _ _ _ -> true
  PhpMatch _ _ _ -> true
  PhpThrow _ -> true
  PhpTernary _ _ _ -> true
  PhpReturn _ -> true
  PhpBinOp _ _ _ -> true
  PhpNull -> true
  PhpRaw "null" -> true
  PhpNew _ _ -> true
  PhpSwitch _ _ _ -> true
  PhpInstanceOf _ _ -> true
  _ -> false

flattenCall :: PhpExpr -> Tuple PhpExpr (Array PhpExpr)
flattenCall (PhpCall f args) | not (Array.null args) =
  let
    Tuple target previous = flattenCall f
  in
    Tuple target (previous <> args)
flattenCall e = Tuple e []

-- Include nested closures when choosing a fresh prefix, even though the pass
-- never rewrites their bodies. A caller may keep one in a local before the call.
localNames :: PhpExpr -> Array String
localNames = case _ of
  PhpVar v -> [ v ]
  PhpAssign v e -> [ v ] <> localNames e
  PhpFunction caps args _ body -> map captureName caps <> map _.name args <> foldMap localNames body
  e -> foldMap localNames (localChildren e)

captureName :: String -> String
captureName s = if String.take 1 s == "&" then String.drop 1 s else s

readNames :: PhpExpr -> Array String
readNames = case _ of
  PhpVar v -> [ v ]
  PhpFunction caps _ _ body -> map captureName caps <> foldMap readNames body
  e -> foldMap readNames (localChildren e)

-- Eliminating an assignment must not hide it from an escaping closure.
capturedNames :: PhpExpr -> Array String
capturedNames = case _ of
  PhpFunction caps _ _ body -> map captureName caps <> foldMap capturedNames body
  e -> foldMap capturedNames (localChildren e)

-- An inlined body must leave the caller just as the original call did. Reject
-- fallthrough bodies instead of relying on PHP's implicit null return.
terminates :: Array PhpExpr -> Boolean
terminates = any case _ of
  PhpReturn _ -> true
  PhpThrow _ -> true
  PhpIf _ t e -> terminates t && terminates e
  PhpSwitch _ cs (Just d) -> all (terminates <<< _.stmts) cs && terminates d
  _ -> false

