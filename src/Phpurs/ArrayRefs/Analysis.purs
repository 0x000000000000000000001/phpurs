-- | Aggregate entry points for call-site discovery, decisions and the fixpoint.
module Phpurs.ArrayRefs.Analysis
  ( callsIn
  , fixpoint
  , functionParts
  , hasSharedConstruction
  , paramIndex
  , setFunction
  , tcoCarrier
  ) where

import Data.Map (Map)
import Data.Maybe (Maybe)
import Data.Set (Set)
import Phpurs.ArrayRefs.CallSites as CallSites
import Phpurs.ArrayRefs.Decision as Decision
import Phpurs.ArrayRefs.Fixpoint as Fixpoint
import Phpurs.PhpAst (PhpDecl, PhpExpr)

callsIn :: PhpExpr -> Array { target :: String, args :: Array PhpExpr }
callsIn = CallSites.callsIn

paramIndex :: Array String -> String -> Maybe Int
paramIndex = CallSites.paramIndex

tcoCarrier :: Array PhpExpr -> Int -> String -> Maybe String
tcoCarrier = Decision.tcoCarrier

fixpoint :: Map String (Set Int) -> Set String -> Array PhpDecl -> Map String (Set Int)
fixpoint = Fixpoint.fixpoint

functionParts
  :: PhpExpr -> Maybe { args :: Array { name :: String, type_ :: String }, body :: Array PhpExpr }
functionParts = Fixpoint.functionParts

hasSharedConstruction :: Set String -> Array PhpDecl -> Boolean
hasSharedConstruction = Fixpoint.hasSharedConstruction

setFunction
  :: PhpExpr
  -> Array { name :: String, type_ :: String }
  -> Array PhpExpr
  -> PhpExpr
setFunction = Fixpoint.setFunction
