module ScratchTco where
import Prelude
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..))
import Data.Maybe (Maybe(..))
import Data.Map as Map
import Data.Foldable (foldl)
import Data.Tuple (Tuple(..))

countTcoNodes :: TcoExpr -> Int
countTcoNodes (TcoExpr _ expr) = 1 + case expr of
  _ -> 0
