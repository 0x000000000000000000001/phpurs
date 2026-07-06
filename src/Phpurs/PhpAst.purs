module Phpurs.PhpAst where

import Prelude
import Data.Maybe (Maybe(..))

-- Abstract Syntax Tree for PHP
data PhpExpr
  = PhpFunction (Array String) (Array String) (Array PhpExpr)
  | PhpNativeFunction String (Array String) (Array PhpExpr)
  | PhpValueThunk String PhpExpr
  | PhpVar String
  | PhpGlobalVar (Maybe (Array String)) String
  | PhpCall PhpExpr (Array PhpExpr)
  | PhpInt Int
  | PhpNumber Number
  | PhpString String
  | PhpBoolean Boolean
  | PhpArray (Array PhpExpr)
  | PhpAssocArray (Array { key :: String, value :: PhpExpr })
  | PhpPropertyAccess PhpExpr String
  | PhpArrayIndex PhpExpr Int
  | PhpAssign String PhpExpr
  | PhpIf PhpExpr (Array PhpExpr) (Array PhpExpr) -- cond, then, else
  | PhpThrow String
  | PhpTernary PhpExpr PhpExpr PhpExpr
  | PhpReturn PhpExpr
  | PhpBinOp String PhpExpr PhpExpr
  | PhpWhile PhpExpr (Array PhpExpr)
  | PhpContinue
  | PhpRaw String
  | PhpNew String (Array PhpExpr)
  | PhpClone PhpExpr
  | PhpSwitch PhpExpr (Array { matchCases :: Array PhpExpr, stmts :: Array PhpExpr }) (Maybe (Array PhpExpr))
  | PhpGoto String
  | PhpLabel String

derive instance eqPhpExpr :: Eq PhpExpr

type PhpDecl =
  { identifier :: String
  , expression :: PhpExpr
  }

type PhpFile =
  { namespace :: Array String
  , decls :: Array PhpDecl
  , imports :: Array (Array String)
  }
