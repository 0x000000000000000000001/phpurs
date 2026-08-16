#!/bin/bash
sed -i '' 's/  _ -> Tuple tcoExpr \[\]/  Typed _ inner -> flattenApp inner\
  _ -> Tuple tcoExpr \[\]/g' src/Phpurs/CodeGen.purs
