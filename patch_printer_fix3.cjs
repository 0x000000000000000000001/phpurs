const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

code = code.replace(
  `printDecl :: PhpDecl -> String
printDecl decl = resolveContinues $ case decl.expression of`,
  `printDecl :: Map String Int -> PhpDecl -> String
printDecl allArities decl = resolveContinues $ case decl.expression of`
);

code = code.replace(
  /printExpr/g,
  `printExpr allArities`
);

// wait, printExpr is replaced everywhere? I already did that in patch_printer.cjs!
// But wait, if I did it already, let's just make sure `printDecl` uses `printExpr allArities` where needed.
// Wait! If `printExpr` takes `allArities`, then inside `printDecl`, any calls to `printExpr` need to pass `allArities`.
// Let me look at `printDecl`.
