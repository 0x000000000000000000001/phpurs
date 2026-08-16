#!/bin/bash
sed -i '' 's/      fvs = freeVars tcoExpr/      fvs = Debug.trace ("freeVars returned: " <> show (Array.fromFoldable (freeVars tcoExpr))) \\_ -> freeVars tcoExpr/g' src/Phpurs/CodeGen.purs
