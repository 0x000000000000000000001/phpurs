-- | Safety valves shared by the local passes. They bound the work a pass may
-- | attempt on one module; they are not performance tuning knobs. Change one
-- | together with a boundary test and an entry in `docs/decisions/`.
module Phpurs.Budget
  ( attempts
  , boundedDepth
  , compactBodyNodes
  , compactCaptures
  , boundedWidth
  , compositionNodes
  , constructorWidth
  , dataDecls
  , copyDepth
  , copyNodes
  , copyWidth
  , groupBindings
  , identityDepth
  , identityNodes
  , identityWidth
  , inferDepth
  , inlineBody
  , inlineCaller
  , inputNodes
  , levelBound
  , moduleBindings
  , proofNodes
  , regionDataDecls
  , regionNodes
  , scanNodes
  , sizeDepth
  , sizeWidth
  , stateFuel
  , successorNodes
  , workerLimit
  ) where

-- | Binding groups a pass may inspect in a module.
moduleBindings :: Int
moduleBindings = 256

-- | Bindings allowed in a single group.
groupBindings :: Int
groupBindings = 64

-- | Expressions above this size are left untouched.
scanNodes :: Int
scanNodes = 8192

-- | Rewrite steps a pass may take per module.
stateFuel :: Int
stateFuel = 32768

-- | Private copies or workers a pass may create.
workerLimit :: Int
workerLimit = 32

-- | Nodes inspected while proving a builder, fold or worker shape.
proofNodes :: Int
proofNodes = 1024

-- | Data declarations admitted by the callback passes.
dataDecls :: Int
dataDecls = 128

-- | Data declarations admitted by enum-region analysis.
regionDataDecls :: Int
regionDataDecls = 64

-- | Nodes admitted by enum-region analysis (its own counter).
regionNodes :: Int
regionNodes = 8192

-- | Constructor and field width admitted by enum-region analysis.
constructorWidth :: Int
constructorWidth = 64

-- | Inference depth inside an enum region.
inferDepth :: Int
inferDepth = 128

-- | Layout attempts per module in enum-region analysis.
attempts :: Int
attempts = 64

-- | Nodes admitted when inspecting a producer input.
inputNodes :: Int
inputNodes = 128

-- | Nodes admitted when proving a successor chain.
successorNodes :: Int
successorNodes = 512

-- | Nodes admitted when proving a composition.
compositionNodes :: Int
compositionNodes = 512

-- | Statements inspected while compacting a loop body.
compactBodyNodes :: Int
compactBodyNodes = 4096

-- | Captures tracked while compacting a loop body.
compactCaptures :: Int
compactCaptures = 64

-- | Nodes, depth and width admitted by the copy-cleanup scan.
copyNodes :: Int
copyNodes = 8192

copyDepth :: Int
copyDepth = 128

copyWidth :: Int
copyWidth = 512

-- | Nodes, depth and constructor width admitted by nullable layout analysis.
identityNodes :: Int
identityNodes = 131072

identityDepth :: Int
identityDepth = 256

identityWidth :: Int
identityWidth = 8192

-- | Bodies and callers admitted by terminal inlining.
inlineBody :: Int
inlineBody = 1536

inlineCaller :: Int
inlineCaller = 3072

-- | Depth and immediate width admitted by the shared `bounded` check.
boundedDepth :: Int
boundedDepth = 96

boundedWidth :: Int
boundedWidth = 64

-- | Depth and immediate width admitted by the enum-region size counter.
sizeDepth :: Int
sizeDepth = 128

sizeWidth :: Int
sizeWidth = 64

-- | Level bound used by the partial-binding encoding checks.
levelBound :: Int
levelBound = 8192
