const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  `translateExprImpl :: String -> Array String -> Map String String -> Map String String -> Maybe String -> Array LoopCtx -> Boolean -> Int -> TcoExpr -> TranslationRes
translateExprImpl modNameStr recVars namedBound bound currentLoop loopCtx isTail nextId tcoExpr@(TcoExpr _ syntax) =`,
  `translateExprImpl :: String -> Array String -> Map String Int -> Map String String -> Map String String -> Maybe String -> Array LoopCtx -> Boolean -> Int -> TcoExpr -> TranslationRes
translateExprImpl modNameStr recVars moduleArities namedBound bound currentLoop loopCtx isTail nextId tcoExpr@(TcoExpr _ syntax) =`
);

code = code.replace(
  `            let
              argRes = translateExprImpl modNameStr recVars namedBound bound Nothing [] false acc.nextId arg`,
  `            let
              argRes = translateExprImpl modNameStr recVars moduleArities namedBound bound Nothing [] false acc.nextId arg`
);

code = code.replace(
  `      Nothing ->
        { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }`,
  `      Nothing ->
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

// Do the same for UncurriedEffectApp and all other calls to translateExprImpl
code = code.replace(/translateExprImpl modNameStr (\[\]|recVars) namedBound bound/g, "translateExprImpl modNameStr $1 moduleArities namedBound bound");
code = code.replace(/translateExprImpl modNameStr (\[\]|recVars) Map.empty Map.empty/g, "translateExprImpl modNameStr $1 moduleArities Map.empty Map.empty");

// Now update translate function
code = code.replace(
  `    rawDecls = Array.concatMap (\\decl ->`,
  `    moduleArities = foldl (\\acc group ->
        foldl (\\acc2 (Tuple (Ident name) val) ->
            case extractUncurriedAbs val of
                Just fn -> Map.insert (modPrefix <> name) (Array.length fn.args) acc2
                Nothing -> acc2
        ) acc group.bindings
      ) Map.empty tcoBindings

    rawDecls = Array.concatMap (\\decl ->`
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
