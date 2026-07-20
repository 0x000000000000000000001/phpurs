import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

search1 = "assignStmts = Array.mapWithIndex (\\i _ -> PhpRaw <<< (\\val -> \"$__tco_state['\" <> targetFn.ident <> \"_\" <> show i <> \"'] = \" <> val <> \";\") <<< printExpr (PhpVar (\"__tco_\" <> show (accFinal.nextId + i)))) accFinal.exprs"
replace1 = "assignStmts = Array.mapWithIndex (\\i _ -> PhpRaw (\"$__tco_state['\" <> targetFn.ident <> \"_\" <> show i <> \"'] = $__tco_\" <> show (accFinal.nextId + i) <> \";\")) accFinal.exprs"
code = code.replace(search1, replace1)

search2 = "assignStmts = Array.mapWithIndex (\\i _ -> PhpRaw <<< (\\val -> \"$__tco_state['\" <> targetFn.ident <> \"_\" <> show i <> \"'] = \" <> val <> \";\") <<< printExpr (PhpVar (\"__tco_\" <> show (accArgs.nextId + i)))) accArgs.exprs"
replace2 = "assignStmts = Array.mapWithIndex (\\i _ -> PhpRaw (\"$__tco_state['\" <> targetFn.ident <> \"_\" <> show i <> \"'] = $__tco_\" <> show (accArgs.nextId + i) <> \";\")) accArgs.exprs"
code = code.replace(search2, replace2)

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
