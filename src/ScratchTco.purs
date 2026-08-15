module ScratchTco where
import Prelude
import PureScript.Backend.Optimizer.Codegen.Tco (TcoExpr(..))
import PureScript.Backend.Optimizer.Syntax (BackendSyntax(..))
import Data.Maybe (Maybe(..))
import Data.Foldable (foldl)
import Data.Tuple (Tuple(..))

countTcoNodes :: TcoExpr -> Int
countTcoNodes (TcoExpr _ expr) = 1 + case expr of
  App f args -> countTcoNodes f + foldl (+) 0 (map countTcoNodes args)
  Abs _ body -> countTcoNodes body
  UncurriedApp f args -> countTcoNodes f + foldl (+) 0 (map countTcoNodes args)
  UncurriedAbs _ body -> countTcoNodes body
  UncurriedEffectApp f args -> countTcoNodes f + foldl (+) 0 (map countTcoNodes args)
  UncurriedEffectAbs _ body -> countTcoNodes body
  Let _ _ val body -> countTcoNodes val + countTcoNodes body
  LetRec _ binds body -> foldl (+) 0 (map (\(Tuple _ v) -> countTcoNodes v) binds) + countTcoNodes body
  Accessor obj _ -> countTcoNodes obj
  Update obj _ -> countTcoNodes obj
  CtorSaturated _ _ _ _ args -> foldl (+) 0 (map (\(Tuple _ a) -> countTcoNodes a) args)
  EffectBind _ _ val body -> countTcoNodes val + countTcoNodes body
  EffectPure val -> countTcoNodes val
  EffectDefer val -> countTcoNodes val
  Branch _ _ -> 0
  _ -> 0
