import re

with open("src/Phpurs/CodeGen_test.purs", "r") as f:
    text = f.read()

app_start = text.find("  App fn args ->")
uncurried_app_start = text.find("  UncurriedApp fn args ->")
uncurried_effect_abs_start = text.find("  UncurriedEffectAbs args body ->")

new_app = """  App fn args ->
    let
      resFn = translateExprImpl recVars namedBound bound Nothing [] false nextId fn
      argsArr = toArray args
      
      Tuple flatFn flatArgs = flattenApp tcoExpr
      
      isTailCallTo = if isTail then case flatFn of
        TcoExpr _ (Local mbIdent (Level l)) ->
          let v = fromMaybe (localId mbIdent (Level l)) (Map.lookup (localId mbIdent (Level l)) bound)
          in Array.findIndex (\\ctx -> ctx.ident == v) loopCtx
        TcoExpr _ (Var (Qualified mbMod (Ident name))) ->
          let fullName = fromMaybe "" (map (\\(ModuleName m) -> String.joinWith "_" (String.split (Pattern ".") m) <> "_") mbMod) <> name
          in Array.findIndex (\\ctx -> ctx.ident == fullName) loopCtx
        _ -> Nothing
      else Nothing

      accFinal = foldl
        ( \\acc arg@(TcoExpr _ _) ->
            let
              argRes = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
        )
        { stmts: resFn.stmts, exprs: [], nextId: resFn.nextId }
        argsArr

    in case isTailCallTo of
      Just index ->
        let
          targetCtx = fromMaybe { ident: "", params: [], doneVar: "", resultVar: "", loopFuncVar: "", varPrefix: "" } (Array.index loopCtx index)
          
          flatAccFinal = foldl
            ( \\acc arg@(TcoExpr _ _) ->
                let
                  argRes = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId arg
                in
                  { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
            )
            { stmts: [], exprs: [], nextId: nextId }
            flatArgs
            
          tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (flatAccFinal.nextId + i)) e) flatAccFinal.exprs
          assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (targetCtx.varPrefix <> (fromMaybe "" (Array.index targetCtx.params i))) (PhpVar ("__tco_" <> show (flatAccFinal.nextId + i)))) flatAccFinal.exprs
          
          popStmts = map (\\ctx -> PhpAssign ctx.doneVar (PhpBoolean true)) (Array.take index loopCtx)
          
          finalStmts = flatAccFinal.stmts <> tcoStmts <> assignStmts <> popStmts <> [ PhpReturn (PhpRaw "null") ]
        in { stmts: finalStmts, expr: PhpRaw "null", nextId: flatAccFinal.nextId + Array.length flatArgs }
      Nothing -> 
        let curriedCall = foldl (\\acc e -> PhpCall acc [e]) resFn.expr accFinal.exprs
        in { stmts: accFinal.stmts, expr: curriedCall, nextId: accFinal.nextId }

  UncurriedApp fn args ->
    let
      resFn = translateExprImpl recVars namedBound bound Nothing [] false nextId fn
      
      isTailCallTo = if isTail then case resFn.expr of
        PhpGlobalVar mbMod name ->
          let fullName = fromMaybe "" (map (\\m -> String.joinWith "_" m <> "_") mbMod) <> name
          in Array.findIndex (\\ctx -> ctx.ident == fullName) loopCtx
        PhpVar v ->
          Array.findIndex (\\ctx -> ctx.ident == v) loopCtx
        _ -> Nothing
      else Nothing

      accArgs = foldl
        ( \\acc arg@(TcoExpr _ _) ->
            let
              argRes = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: acc.nextId }
        )
        { stmts: [], exprs: [], nextId: resFn.nextId }
        args
        
    in case isTailCallTo of
      Just index ->
        let
          targetCtx = fromMaybe { ident: "", params: [], doneVar: "", resultVar: "", loopFuncVar: "", varPrefix: "" } (Array.index loopCtx index)
          tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (accArgs.nextId + i)) e) accArgs.exprs
          assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (targetCtx.varPrefix <> (fromMaybe "" (Array.index targetCtx.params i))) (PhpVar ("__tco_" <> show (accArgs.nextId + i)))) accArgs.exprs
          
          popStmts = map (\\ctx -> PhpAssign ctx.doneVar (PhpBoolean true)) (Array.take index loopCtx)
          
          finalStmts = accArgs.stmts <> tcoStmts <> assignStmts <> popStmts <> [ PhpReturn (PhpRaw "null") ]
        in { stmts: finalStmts, expr: PhpRaw "null", nextId: accArgs.nextId + Array.length args }
      Nothing ->
        { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }

"""

text = text[:app_start] + new_app + text[uncurried_effect_abs_start:]

with open("src/Phpurs/CodeGen_test.purs", "w") as f:
    f.write(text)

