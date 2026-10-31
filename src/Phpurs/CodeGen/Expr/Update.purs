-- | Record-update fusion and the typed re-entry that selects it.
module Phpurs.CodeGen.Expr.Update
  ( translateTyped
  , translateUpdate
  ) where

import Prelude

import Data.Array as Array
import Data.Foldable (foldl)
import Data.Map as Map
import Data.Maybe (Maybe(..), fromMaybe)
import Data.Tuple (Tuple(..))
import Phpurs.CodeGen.Context (TranslationContext, TranslationResult)
import Phpurs.CodeGen.Expr.Support
  ( Translate
  , UpdatePath
  , nestedUpdate
  , recordFields
  , sameRoot
  , translateValue
  , updatePath
  )
import Phpurs.PhpAst (PhpExpr(..))
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..))
import PureScript.Backend.Optimizer.CoreFn (ExprType, Prop(..))
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..))

tryFusedUpdate
  :: Translate
  -> TranslationContext
  -> Int
  -> UpdatePath
  -> Array (Tuple String ExprType)
  -> Array (Prop TcoExpr)
  -> Maybe TranslationResult
tryFusedUpdate translate ctx nextId path fields props =
  let
    labels = map (\(Tuple label _) -> label) fields
    keys = map (\(Prop key _) -> key) props
    validFields =
      not (Array.null labels)
        && not (Array.null keys)
        && Array.length keys == Array.length labels
        && Array.sort keys == Array.sort labels
    fieldTypes = Map.fromFoldable fields
    expectedPath key = path.path <> [ key ]
    step Nothing _ = Nothing
    step (Just acc) (Prop key value) = case Map.lookup key fieldTypes of
      Nothing -> Nothing
      Just fieldType -> case nestedUpdate fieldType value of
        Just nested -> case updatePath nested.base of
          Just nestedPath
            | sameRoot path.root nestedPath.root && nestedPath.path == expectedPath key ->
                case recordFields fieldType of
                  Just nestedFields ->
                    case
                      tryFusedUpdate translate ctx acc.nextId nestedPath nestedFields nested.props
                      of
                      Just result ->
                        Just
                          { stmts: acc.stmts <> result.stmts
                          , values: Map.insert key result.expr acc.values
                          , nextId: result.nextId
                          }
                      Nothing -> plainValue acc key value
                  Nothing -> plainValue acc key value
          _ -> plainValue acc key value
        Nothing -> plainValue acc key value
    plainValue acc key value =
      let
        result = translateValue translate ctx acc.nextId value
      in
        Just
          { stmts: acc.stmts <> result.stmts
          , values: Map.insert key result.expr acc.values
          , nextId: result.nextId
          }
    initial = { stmts: [], values: Map.empty, nextId }
  in
    if not validFields then Nothing
    else case Array.foldl step (Just initial) props of
      Nothing -> Nothing
      Just built ->
        Just
          { stmts: built.stmts
          , expr: PhpAssocArray
              ( map
                  ( \(Tuple label _) ->
                      { key: label
                      , value: fromMaybe (PhpNull) (Map.lookup label built.values)
                      }
                  )
                  fields
              )
          , nextId: built.nextId
          }

translateTyped :: Translate -> TranslationContext -> Int -> ExprType -> TcoExpr -> TranslationResult
translateTyped translate ctx nextId ty tcoExpr@(TcoExpr _ syntax) = case syntax of
  Typed innerTy inner ->
    let
      selected = case recordFields innerTy of
        Just _ -> innerTy
        Nothing -> ty
    in
      translateTyped translate ctx nextId selected inner
  Update base props -> case recordFields ty of
    Just fields -> case updatePath base of
      Just path -> case tryFusedUpdate translate ctx nextId path fields props of
        Just result -> result
        Nothing -> translate ctx nextId tcoExpr
      Nothing -> translate ctx nextId tcoExpr
    Nothing -> translate ctx nextId tcoExpr
  _ -> translate ctx nextId tcoExpr

translateUpdate
  :: Translate
  -> TranslationContext
  -> Int
  -> TcoExpr
  -> Array (Prop TcoExpr)
  -> TranslationResult
translateUpdate translate ctx nextId e props =
  let
    resE = translateValue translate ctx nextId e
    tmpVar = "__obj" <> show resE.nextId
    accProps = foldl
      ( \acc (Prop key val@(TcoExpr _ _)) ->
          let
            resVal = translateValue translate ctx acc.nextId val
          in
            { stmts: acc.stmts <> resVal.stmts <>
                [ PhpAssignExpr (PhpRecordAccess (PhpVar tmpVar) key) resVal.expr ]
            , nextId: resVal.nextId
            }
      )
      { stmts: [], nextId: resE.nextId + 1 }
      props
  in
    { stmts: resE.stmts <> [ PhpAssign tmpVar (PhpClone resE.expr) ] <> accProps.stmts
    , expr: PhpVar tmpVar
    , nextId: accProps.nextId
    }
