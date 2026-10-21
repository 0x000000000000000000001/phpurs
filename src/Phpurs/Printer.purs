-- | Public PHP printer facade.
module Phpurs.Printer
  ( genCurry
  , printExpr
  , printPhpFile
  , safeFuncName
  , safeName
  ) where

import Data.Map (Map)
import Phpurs.PhpAst (PhpExpr, PhpFile)
import Phpurs.Printer.Decl as Decl
import Phpurs.Printer.Expr as Expr

genCurry :: Map String Int -> Array { name :: String, type_ :: String } -> String -> Array String -> Array PhpExpr -> String
genCurry = Expr.genCurry

printExpr :: Map String Int -> PhpExpr -> String
printExpr = Expr.printExpr

printPhpFile :: Boolean -> String -> Map String Int -> PhpFile -> String
printPhpFile = Decl.printPhpFile

safeFuncName :: String -> String
safeFuncName = Expr.safeFuncName

safeName :: String -> String
safeName = Expr.safeName
