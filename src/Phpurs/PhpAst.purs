module Phpurs.PhpAst where

import Prelude

-- Abstract Syntax Tree for PHP
data PhpExpr
  = PhpFunction (Array String) (Array String) (Array PhpExpr)
  | PhpVar String
  | PhpCall PhpExpr (Array PhpExpr)
  | PhpInt Int
  | PhpNumber Number
  | PhpString String
  | PhpBoolean Boolean
  | PhpArray (Array PhpExpr)
  | PhpAssocArray (Array { key :: String, value :: PhpExpr })
  | PhpPropertyAccess PhpExpr String
  | PhpClosure (Array String) (Array PhpExpr) -- captures, statements
  | PhpAssign String PhpExpr
  | PhpIf PhpExpr (Array PhpExpr) (Array PhpExpr) -- cond, then, else
  | PhpThrow String
  | PhpTernary PhpExpr PhpExpr PhpExpr
  | PhpReturn PhpExpr
  | PhpBinOp String PhpExpr PhpExpr
  | PhpWhile PhpExpr (Array PhpExpr)
  | PhpContinue
  | PhpTcoLoop (Array String) (Array String) (Array PhpExpr)
  | PhpRaw String
  | PhpObjectUpdate PhpExpr (Array { key :: String, value :: PhpExpr })

type PhpDecl =
  { identifier :: String
  , expression :: PhpExpr
  }

type PhpFile =
  { namespace :: Array String
  , decls :: Array PhpDecl
  , imports :: Array (Array String)
  }
