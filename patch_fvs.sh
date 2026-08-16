#!/bin/bash
sed -i '' 's/getFreeVars bound tcoExpr/Array.fromFoldable (freeVars tcoExpr)/g' src/Phpurs/CodeGen.purs
sed -i '' 's/getFreeVars bound condExpr/Array.fromFoldable (freeVars condExpr)/g' src/Phpurs/CodeGen.purs
sed -i '' 's/getFreeVars bound body/Array.fromFoldable (freeVars body)/g' src/Phpurs/CodeGen.purs
