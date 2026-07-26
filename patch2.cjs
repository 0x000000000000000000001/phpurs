const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  `  UncurriedEffectApp fn args ->
    let
      resFn = translateExprImpl modNameStr recVars moduleArities namedBound bound Nothing [] false nextId fn
      accArgs = foldl
        ( \\acc arg@(TcoExpr _ _) ->
            let
              argRes = translateExprImpl modNameStr recVars moduleArities namedBound bound Nothing [] false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
        )
        { stmts: [], exprs: [], nextId: resFn.nextId }
        args
    in
      { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }`,
  `  UncurriedEffectApp fn args ->
    let
      resFn = translateExprImpl modNameStr recVars moduleArities namedBound bound Nothing [] false nextId fn
      accArgs = foldl
        ( \\acc arg@(TcoExpr _ _) ->
            let
              argRes = translateExprImpl modNameStr recVars moduleArities namedBound bound Nothing [] false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
        )
        { stmts: [], exprs: [], nextId: resFn.nextId }
        args
    in
        let
          mbDirectCall = case resFn.expr of
            PhpGlobalVar (Just mbModArr) ident ->
              let 
                modJoined = String.joinWith "_" mbModArr
                modJoinedClean = String.replaceAll (Pattern ".") (Replacement "_") modJoined
                isLocal = modJoinedClean == modNameStr
                funcName = modJoinedClean <> "_" <> ident
                mbArity = if isLocal then Map.lookup funcName moduleArities else Nothing
              in
                case mbArity of
                  Just arity -> 
                    if arity == Array.length args then Just (PhpDirectCall funcName accArgs.exprs)
                    else Just (PhpCall (PhpGlobalVar Nothing ("DEBUG_ARITY_MISMATCH_ARITY_" <> show arity <> "_LEN_" <> show (Array.length args))) [])
                  _ -> Nothing
            PhpGlobalVar Nothing ident ->
              let 
                funcName = modNameStr <> "_" <> ident
                mbArity = Map.lookup funcName moduleArities
              in
                case mbArity of
                  Just arity -> 
                    if arity == Array.length args then Just (PhpDirectCall funcName accArgs.exprs)
                    else Just (PhpCall (PhpGlobalVar Nothing ("DEBUG_ARITY_MISMATCH_ARITY_" <> show arity <> "_LEN_" <> show (Array.length args))) [])
                  _ -> Nothing
            _ -> Nothing
        in
          case mbDirectCall of
            Just callExpr -> { stmts: resFn.stmts <> accArgs.stmts, expr: callExpr, nextId: accArgs.nextId }
            Nothing -> { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }`
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
