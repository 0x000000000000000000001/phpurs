-- | Ordered module-pass table and per-module statistics.
module Phpurs.CodeGen.Pipeline
  ( Pass
  , PassResult
  , PassStats
  , aggregate
  , passes
  , refEq
  , run
  ) where

import Prelude

import Data.Array as Array
import Data.Foldable (foldl)
import Phpurs.ArrayCallbacks as ArrayCallbacks
import Phpurs.CallbackSpecialization as CallbackSpecialization
import Phpurs.EnumRegions as EnumRegions
import Phpurs.ForeignTraversals (Traversals)
import Phpurs.NativeCallbacks (Callbacks)
import Phpurs.PartialBindings as PartialBindings
import Phpurs.StateFusion as StateFusion
import Phpurs.ThunkFusion as ThunkFusion
import PureScript.Backend.Optimizer.Convert (BackendModule)

foreign import refEq :: forall a. a -> a -> Boolean

-- | `run` is pure; the descriptor records the contract checked by the pass.
type Pass =
  { name :: String
  , precondition :: String
  , budget :: String
  , fallback :: String
  , run :: BackendModule -> BackendModule
  }

type PassResult = { name :: String, changed :: Boolean }

type PassStats =
  { proved :: Int
  , refusedBudget :: Int
  , refusedProof :: Int
  , unchanged :: Int
  }

-- | The six typed-AST passes, in evaluation order. Later PHP passes consume
-- | the private layouts and names produced here.
passes :: Callbacks -> Traversals -> Array Pass
passes callbacks traversals =
  [ { name: "PartialBindings"
    , precondition: "smallModule"
    , budget: "moduleBindings, groupBindings, scanNodes"
    , fallback: "unchanged"
    , run: \mod -> (PartialBindings.optimize mod).module_
    }
  , { name: "ThunkFusion"
    , precondition: "smallModule"
    , budget: "moduleBindings, groupBindings, scanNodes"
    , fallback: "unchanged"
    , run: \mod -> (ThunkFusion.optimize mod).module_
    }
  , { name: "StateFusion"
    , precondition: "smallModule"
    , budget: "stateFuel, workerLimit"
    , fallback: "unchanged"
    , run: \mod -> (StateFusion.optimize mod).module_
    }
  , { name: "CallbackSpecialization"
    , precondition: "callbacks"
    , budget: "moduleBindings, groupBindings"
    , fallback: "unchanged"
    , run: \mod -> (CallbackSpecialization.optimize callbacks mod).module_
    }
  , { name: "ArrayCallbacks"
    , precondition: "traversals"
    , budget: "moduleBindings, groupBindings"
    , fallback: "unchanged"
    , run: \mod -> (ArrayCallbacks.optimize traversals callbacks mod).module_
    }
  , { name: "EnumRegions"
    , precondition: "smallModule, dataDecls"
    , budget: "regionNodes, workerLimit"
    , fallback: "unchanged"
    , run: \mod -> (EnumRegions.optimize mod).module_
    }
  ]

run
  :: Array Pass
  -> BackendModule
  -> { module_ :: BackendModule, results :: Array PassResult }
run ordered mod = foldl step { module_: mod, results: [] } ordered
  where
  step acc pass =
    let
      next = pass.run acc.module_
    in
      { module_: next
      , results: Array.snoc acc.results { name: pass.name, changed: not (refEq next acc.module_) }
      }

-- | Passes report only whether they returned a new module; refusal reasons are
-- | counted by their own proofs and stay zero here until a pass exposes them.
aggregate :: Array PassResult -> PassStats
aggregate results = foldl step { proved: 0, refusedBudget: 0, refusedProof: 0, unchanged: 0 }
  results
  where
  step acc result =
    if result.changed then acc { proved = acc.proved + 1 }
    else acc { unchanged = acc.unchanged + 1 }
