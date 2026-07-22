const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

const targetBranch = `
  Branch pairs def ->
    let
      resDef = translateExprImpl recVars namedBound bound Nothing loopCtx isTail nextId def
      tmpVar = "__t" <> show resDef.nextId
      accPairs = foldl
        ( \\acc (Pair condExpr@(TcoExpr _ _cond) bodyExpr@(TcoExpr _ _body)) ->
            let
              resCond = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId condExpr
              resBody = translateExprImpl recVars namedBound bound Nothing loopCtx isTail resCond.nextId bodyExpr
            in
              { ifNodes: Array.snoc acc.ifNodes { cond: wrapInStmts (map (\\v -> fromMaybe v (Map.lookup v bound)) (Array.fromFoldable (freeVars condExpr))) resCond.stmts resCond.expr, body: resBody.stmts <> [ PhpAssign tmpVar resBody.expr ] }, nextId: resBody.nextId }
        )
        { ifNodes: [], nextId: resDef.nextId + 1 }
        (toArray pairs)

      finalElse = resDef.stmts <> [ PhpAssign tmpVar resDef.expr ]
      nestedIf = foldr (\\ifNode accElse -> [ PhpIf ifNode.cond ifNode.body accElse ]) finalElse accPairs.ifNodes
    in
      { stmts: nestedIf, expr: PhpVar tmpVar, nextId: accPairs.nextId }
`;

const replacementBranch = `
  Branch pairs def ->
    let
      resDef = translateExprImpl recVars namedBound bound Nothing loopCtx isTail nextId def
      tmpVar = "__t" <> show resDef.nextId
      labelName = "end_branch_" <> show resDef.nextId
      accPairs = foldl
        ( \\acc (Pair condExpr@(TcoExpr _ _cond) bodyExpr@(TcoExpr _ _body)) ->
            let
              resCond = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId condExpr
              resBody = translateExprImpl recVars namedBound bound Nothing loopCtx isTail resCond.nextId bodyExpr
              condWrapped = wrapInStmts (map (\\v -> fromMaybe v (Map.lookup v bound)) (Array.fromFoldable (freeVars condExpr))) resCond.stmts resCond.expr
              ifNode = PhpIf condWrapped (resBody.stmts <> [ PhpAssign tmpVar resBody.expr, PhpRaw ("goto " <> labelName <> ";") ]) []
            in
              { stmts: acc.stmts <> [ifNode], nextId: resBody.nextId }
        )
        { stmts: [], nextId: resDef.nextId + 1 }
        (toArray pairs)

      finalDef = resDef.stmts <> [ PhpAssign tmpVar resDef.expr, PhpRaw (labelName <> ":") ]
    in
      { stmts: [ PhpRaw ("$" <> tmpVar <> " = null;") ] <> accPairs.stmts <> finalDef, expr: PhpVar tmpVar, nextId: accPairs.nextId }
`;

code = code.replace(/  Branch pairs def ->[\s\S]*?(?=  Update e props ->)/m, replacementBranch.trim() + '\n\n');
fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
