module Phpurs.CoreFn where

import Prelude

import Data.Argonaut.Decode (class DecodeJson, decodeJson, (.:), (.:?))
import Data.Maybe (Maybe(..))
import Data.Either (Either(..))
import Data.Argonaut.Core (Json)
import Data.Argonaut.Decode.Error (JsonDecodeError(..))
import Data.Traversable (traverse)
import Data.Array (concat)
import Data.Newtype (class Newtype)

data Literal a
  = IntLiteral Int
  | NumberLiteral Number
  | StringLiteral String
  | CharLiteral String
  | BooleanLiteral Boolean
  | ArrayLiteral (Array a)
  | ObjectLiteral (Array { key :: String, value :: a })

data Binder
  = NullBinder
  | LiteralBinder (Literal Binder)
  | VarBinder String
  | ConstructorBinder Boolean (Array String) String (Array Binder)
  | NamedBinder String Binder

data CaseAlternative = CaseAlternative
  { binders :: Array Binder
  , isGuarded :: Boolean
  , expression :: Expr
  }

data Bind
  = NonRec String Expr
  | Rec (Array { identifier :: String, expression :: Expr })

data Expr
  = Lambda String Expr
  | Variable (Maybe (Array String)) String
  | Call Expr Expr
  | Literal (Literal Expr)
  | Case (Array Expr) (Array CaseAlternative)
  | Let (Array Bind) Expr
  | Constructor String String (Array String)
  | Accessor String Expr
  | ObjectUpdate Expr (Array { key :: String, value :: Expr })
  | Unsupported String

instance decodeJsonExpr :: DecodeJson Expr where
  decodeJson json = do
    obj <- decodeJson json
    typ <- obj .: "type"
    case typ of
      "Abs" -> do
        arg <- obj .: "argument"
        body <- obj .: "body"
        pure $ Lambda arg body
      "Var" -> do
        value <- obj .: "value"
        ident <- value .: "identifier"
        moduleName <- value .:? "moduleName"
        pure $ Variable moduleName ident
      "App" -> do
        abs <- obj .: "abstraction"
        arg <- obj .: "argument"
        pure $ Call abs arg
      "Literal" -> do
        value <- obj .: "value"
        lit <- decodeJsonLiteral value
        pure $ Literal lit
      "Case" -> do
        caseExpressions <- obj .: "caseExpressions"
        caseAlternatives <- obj .: "caseAlternatives"
        pure $ Case caseExpressions caseAlternatives
      "Let" -> do
        bindsJson <- obj .: "binds"
        binds <- traverse decodeBind bindsJson
        expr <- obj .: "expression"
        pure $ Let (concat binds) expr
      "Constructor" -> do
        constructorName <- obj .: "constructorName"
        typeName <- obj .: "typeName"
        fieldNames <- obj .: "fieldNames"
        pure $ Constructor typeName constructorName fieldNames
      "Accessor" -> do
        fieldName <- obj .: "fieldName"
        expression <- obj .: "expression"
        pure $ Accessor fieldName expression
      "ObjectUpdate" -> do
        expression <- obj .: "expression"
        updatesArr <- obj .: "updates"
        updates <- traverse decodeUpdate updatesArr
        pure $ ObjectUpdate expression updates
      _ -> pure $ Unsupported typ
      
    where
      decodeUpdate j = do
        arr <- decodeJson j
        case arr of
          [kJson, vJson] -> do
            k <- decodeJson kJson
            v <- decodeJson vJson
            pure { key: k, value: v }
          _ -> Left $ TypeMismatch "Expected [key, value] array"

      decodeBind j = do
        o <- decodeJson j
        bindType <- o .: "bindType"
        case bindType of
          "NonRec" -> do
            ident <- o .: "identifier"
            expr <- o .: "expression"
            pure [ NonRec ident expr ]
          "Rec" -> do
            bindsArr <- o .: "binds"
            recBinds <- traverse decodeSingleBinding bindsArr
            pure [ Rec recBinds ]
          _ -> pure []
      
      decodeSingleBinding j = do
        o <- decodeJson j
        ident <- o .: "identifier"
        expr <- o .: "expression"
        pure { identifier: ident, expression: expr }

decodeJsonLiteral :: forall a. DecodeJson a => Json -> Either JsonDecodeError (Literal a)
decodeJsonLiteral json = do
  obj <- decodeJson json
  literalType <- obj .: "literalType"
  case literalType of
    "IntLiteral" -> IntLiteral <$> obj .: "value"
    "NumberLiteral" -> NumberLiteral <$> obj .: "value"
    "StringLiteral" -> StringLiteral <$> obj .: "value"
    "CharLiteral" -> CharLiteral <$> obj .: "value"
    "BooleanLiteral" -> BooleanLiteral <$> obj .: "value"
    "ArrayLiteral" -> ArrayLiteral <$> obj .: "value"
    "ObjectLiteral" -> do
      valArr <- obj .: "value"
      arr <- traverse decodeObjLit valArr
      pure $ ObjectLiteral arr
    _ -> pure $ StringLiteral "unsupported literal"
  where
    decodeObjLit j = do
      arr <- decodeJson j
      case arr of
        [kJson, vJson] -> do
          k <- decodeJson kJson
          v <- decodeJson vJson
          pure { key: k, value: v }
        _ -> Left $ TypeMismatch "Expected [key, value] array"

instance decodeJsonBinder :: DecodeJson Binder where
  decodeJson json = do
    obj <- decodeJson json
    binderType <- obj .: "binderType"
    case binderType of
      "NullBinder" -> pure NullBinder
      "LiteralBinder" -> do
        litObj <- obj .: "literal"
        lit <- decodeJsonLiteral litObj
        pure $ LiteralBinder lit
      "VarBinder" -> VarBinder <$> obj .: "identifier"
      "ConstructorBinder" -> do
        constructorNameObj <- obj .: "constructorName"
        ident <- constructorNameObj .: "identifier"
        modName <- constructorNameObj .:? "moduleName"
        let mod = case modName of
              Just m -> m
              Nothing -> []
        binders <- obj .: "binders"
        
        -- Check if it's a newtype
        ann <- obj .:? "annotation"
        let isNewtype = case ann of
              Just a -> case decodeJson a of
                Right aObj -> case aObj .:? "meta" of
                  Right (Just mObj) -> case mObj .:? "metaType" of
                    Right (Just mt) -> mt == "IsNewtype"
                    _ -> false
                  _ -> false
                _ -> false
              _ -> false
              
        pure $ ConstructorBinder isNewtype mod ident binders
      "NamedBinder" -> do
        ident <- obj .: "identifier"
        binder <- obj .: "binder"
        pure $ NamedBinder ident binder
      _ -> pure NullBinder

instance decodeJsonCaseAlternative :: DecodeJson CaseAlternative where
  decodeJson json = do
    obj <- decodeJson json
    binders <- obj .: "binders"
    isGuarded <- obj .: "isGuarded"
    expression <- if isGuarded 
                  then pure (Unsupported "Guards not supported")
                  else obj .: "expression"
    pure $ CaseAlternative { binders, isGuarded, expression }

type Decl =
  { identifier :: String
  , expression :: Expr
  }

newtype Module = Module
  { moduleName :: Array String
  , decls :: Array Bind
  , imports :: Array (Array String)
  , foreign :: Array String
  , modulePath :: Maybe String
  }

derive instance newtypeModule :: Newtype Module _

instance decodeJsonModule :: DecodeJson Module where
  decodeJson json = do
    obj <- decodeJson json
    name <- obj .: "moduleName"
    declsJson <- obj .: "decls"
    binds <- traverse decodeBind declsJson
    importsJson <- obj .: "imports"
    imports <- traverse decodeImport importsJson
    foreigns <- obj .: "foreign"
    modulePath <- obj .:? "modulePath"
    pure $ Module { moduleName: name, decls: concat binds, imports: imports, foreign: foreigns, modulePath: modulePath }
    where
      decodeImport j = do
        o <- decodeJson j
        o .: "moduleName"

      decodeBind j = do
        o <- decodeJson j
        bindType <- o .: "bindType"
        case bindType of
          "NonRec" -> do
            ident <- o .: "identifier"
            expr <- o .: "expression"
            pure [ NonRec ident expr ]
          "Rec" -> do
            bindsArr <- o .: "binds"
            recBinds <- traverse decodeSingleBinding bindsArr
            pure [ Rec recBinds ]
          _ -> pure []
      
      decodeSingleBinding j = do
        o <- decodeJson j
        ident <- o .: "identifier"
        expr <- o .: "expression"
        pure { identifier: ident, expression: expr }
