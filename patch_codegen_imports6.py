import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

# Fix the assignStmts lines in App and UncurriedApp
search1 = r"assignStmts = Array\.mapWithIndex \(\\i _ -> PhpRaw <<< \(\\val -> \"\$__tco_state\['\" <> targetFn\.ident <> \"_\" <> show i <> \"'\] = \" <> val <> \";\"\) <<< printExpr \(PhpVar \(\"__tco_\" <> show \(accFinal\.nextId \+ i\)\)\)\) accFinal\.exprs"
replace1 = "assignStmts = Array.mapWithIndex (\\i _ -> PhpRaw (\"$__tco_state['\" <> targetFn.ident <> \"_\" <> show i <> \"'] = $__tco_\" <> show (accFinal.nextId + i) <> \";\")) accFinal.exprs"
code = re.sub(search1, replace1, code)

search2 = r"assignStmts = Array\.mapWithIndex \(\\i _ -> PhpRaw <<< \(\\val -> \"\$__tco_state\['\" <> targetFn\.ident <> \"_\" <> show i <> \"'\] = \" <> val <> \";\"\) <<< printExpr \(PhpVar \(\"__tco_\" <> show \(accArgs\.nextId \+ i\)\)\)\) accArgs\.exprs"
replace2 = "assignStmts = Array.mapWithIndex (\\i _ -> PhpRaw (\"$__tco_state['\" <> targetFn.ident <> \"_\" <> show i <> \"'] = $__tco_\" <> show (accArgs.nextId + i) <> \";\")) accArgs.exprs"
code = re.sub(search2, replace2, code)

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
