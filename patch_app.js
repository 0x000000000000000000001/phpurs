const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  /isTailCallTo = if isTail then case flatFn of/,
  `isTailCallTo = if isTail then case flatFn of`
);
// wait, let me just replace the 'Nothing ->' branch of 'case isTailCallTo of' in App:
code = code.replace(
  /      Nothing ->\n        let curriedCall = foldl \\\(\\acc e -> PhpCall acc \[e\]\) resFn.expr accFinal.exprs\n        in { stmts: accFinal.stmts, expr: curriedCall, nextId: accFinal.nextId }/,
  `      Nothing ->\n        let curriedCall = foldl (\\acc e -> PhpCall acc [e]) resFn.expr accFinal.exprs\n        in { stmts: accFinal.stmts, expr: PhpRaw ("/* flatFn: " <> show flatFn <> " */ " <> printExpr "" [] curriedCall), nextId: accFinal.nextId }`
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
