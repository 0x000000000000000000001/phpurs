-- | Pure printer helpers: return annotations, captures, curry bodies and
-- | switch recognition. Nothing here prints a child expression.
module Phpurs.Printer.Support
  ( extractSwitch
  , flattenUnaryCalls
  , printCall
  , printCaptures
  , printReturnType
  , replaceReturn
  , resolveContinues
  ) where

import Prelude

import Data.Array (concatMap, length)
import Data.Array as Array
import Data.Map as Map
import Data.Set as Set
import Phpurs.Printer.Context (PrinterContext, exactSuffix)
import Data.Foldable (foldl)
import Data.Maybe (Maybe(..))
import Data.String (joinWith, replaceAll, Pattern(..), Replacement(..), take, drop)
import Data.Tuple (Tuple(..))
import Phpurs.PhpAst (PhpExpr(..))
import Phpurs.PhpAst.Traversal (mapBlocks)
import Phpurs.Printer.Names (globalIdentifier, nativeName, printGlobal, safeName)

flattenUnaryCalls :: PhpExpr -> Tuple PhpExpr (Array PhpExpr)
flattenUnaryCalls (PhpCall fn args) | length args == 1 =
  let
    Tuple innerFn innerArgs = flattenUnaryCalls fn
  in
    Tuple innerFn (innerArgs <> args)
flattenUnaryCalls other = Tuple other []

printReturnType :: String -> String
printReturnType retType =
  if retType == "mixed" || retType == "" then ""
  else if retType == "\\Closure" then ": \\Closure"
  else ": " <> retType <> "|\\Closure"

-- | A loop can contribute a by-reference capture for the same variable that
-- | ordinary free-variable collection contributes by value. PHP rejects
-- | `use (&$x, $x)`, so keep one entry per base name and prefer the reference.
dedupeCaptures :: Array String -> Array String
dedupeCaptures = foldl step []
  where
  step acc name =
    let
      isRef = take 1 name == "&"
      base = captureBase name
    in
      case Array.findIndex (\existing -> captureBase existing == base) acc of
        Nothing -> Array.snoc acc name
        Just index ->
          if isRef then
            Array.mapWithIndex
              (\i existing -> if i == index && take 1 existing /= "&" then name else existing)
              acc
          else acc

  captureBase name = if take 1 name == "&" then drop 1 name else name

printCaptures :: Array String -> String
printCaptures [] = ""
printCaptures captures = " use (" <> joinWith ", " (map printCapture (dedupeCaptures captures)) <>
  ")"
  where
  printCapture name =
    if take 1 name == "&" then "&$" <> safeName (drop 1 name) else "$" <> safeName name

replaceReturn :: Array PhpExpr -> Array PhpExpr
replaceReturn = concatMap replaceExpr
  where
  replaceExpr :: PhpExpr -> Array PhpExpr
  replaceExpr (PhpReturn e) = [ PhpAssign "__res" e, PhpGoto "__end" ]
  replaceExpr other = [ mapBlocks replaceReturn other ]

-- | Recognize one subject tested against literal alternatives. Normalize it
-- | to PhpSwitch at the call site so explicit and inferred switches share their
-- | rendering and continue-level handling.
extractSwitch
  :: PhpExpr
  -> Maybe
       { subject :: PhpExpr
       , cases :: Array { val :: PhpExpr, body :: Array PhpExpr }
       , defaultBody :: Array PhpExpr
       }
extractSwitch (PhpIf (PhpBinOp "===" subj litExpr) tBody [ PhpIf (PhpBoolean true) tDefault _ ])
  | isLiteral litExpr =
      Just { subject: subj, cases: [ { val: litExpr, body: tBody } ], defaultBody: tDefault }
extractSwitch (PhpIf (PhpBinOp "===" subj litExpr) tBody [ eBody@(PhpIf _ _ _) ])
  | isLiteral litExpr =
      case extractSwitch eBody of
        Just rest ->
          if subj == rest.subject then
            Just
              { subject: subj
              , cases: [ { val: litExpr, body: tBody } ] <> rest.cases
              , defaultBody: rest.defaultBody
              }
          else Nothing
        Nothing -> Nothing
extractSwitch (PhpIf (PhpBinOp "===" subj litExpr) tBody eBodyArray) | isLiteral litExpr =
  Just { subject: subj, cases: [ { val: litExpr, body: tBody } ], defaultBody: eBodyArray }
extractSwitch _ = Nothing

isLiteral :: PhpExpr -> Boolean
isLiteral (PhpString _) = true
isLiteral (PhpInt _) = true
isLiteral (PhpNumber _) = true
isLiteral _ = false

resolveContinues :: String -> String
resolveContinues str = foldl replaceLevel withoutMarkers (Array.range 1 15)
  where
  withoutMarkers = replaceAll (Pattern "/*__LVL__*/") (Replacement "") str
  replaceLevel rendered depth = replaceAll
    (Pattern ("continue " <> joinWith "" (Array.replicate depth "I") <> ";"))
    (Replacement ("continue " <> show (depth + 1) <> ";"))
    rendered

printCall :: PrinterContext -> (PhpExpr -> String) -> PhpExpr -> String
printCall ctx render expr =
  let
    Tuple flatFn flatArgs = flattenUnaryCalls expr
    nativeTarget = case flatFn of
      PhpGlobalVar mbMod ident ->
        let
          fullName = globalIdentifier mbMod ident
          idStr = safeName fullName
          funcName = nativeName mbMod ident
        in
          case Map.lookup idStr ctx.arities of
            Just arity | arity > 0 && length flatArgs >= arity -> Just { funcName, arity, idStr }
            _ -> Nothing
      _ -> Nothing
  in
    case nativeTarget of
      Just { funcName, arity, idStr } ->
        let
          directArgs = Array.take arity flatArgs
          remainingArgs = Array.drop arity flatArgs
          callStr = funcName <> "(" <> joinWith ", " (map (render) directArgs) <> ")"
        in
          if Set.member idStr ctx.exacts && length flatArgs == arity then
            funcName <> exactSuffix <> "(" <> joinWith ", " (map (render) flatArgs) <>
              ")"
          else if length remainingArgs > 0 then
            foldl (\acc a -> "(" <> acc <> ")(" <> render a <> ")") callStr remainingArgs
          else
            callStr
      Nothing ->
        case expr of
          PhpCall (PhpGlobalVar mbMod ident) args ->
            "(" <> printGlobal ctx.arities mbMod ident <> ")("
              <> joinWith ", " (map (render) args)
              <> ")"
          PhpCall (PhpRaw raw) args -> raw <> "(" <> joinWith ", " (map (render) args)
            <> ")"
          PhpCall abs args -> "(" <> render abs <> ")("
            <> joinWith ", " (map (render) args)
            <> ")"
          _ -> "/* ERROR: Impossible PhpCall match */"
