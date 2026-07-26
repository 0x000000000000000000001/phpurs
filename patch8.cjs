const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  `  App fn arg ->
    let
      resFn = translateExprImpl modNameStr recVars moduleArities namedBound bound Nothing loopCtx false nextId fn
      resArg = translateExprImpl modNameStr recVars moduleArities namedBound bound Nothing [] false resFn.nextId arg
    in
      { stmts: resFn.stmts <> resArg.stmts, expr: PhpCall resFn.expr [ resArg.expr ], nextId: resArg.nextId }`,
  `  App fn arg ->
    let
      resFn = translateExprImpl modNameStr recVars moduleArities namedBound bound Nothing loopCtx false nextId fn
      resArg = translateExprImpl modNameStr recVars moduleArities namedBound bound Nothing [] false resFn.nextId arg
      callExpr = PhpCall resFn.expr [ resArg.expr ]
    in
      { stmts: resFn.stmts <> resArg.stmts, expr: callExpr, nextId: resArg.nextId }`
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
