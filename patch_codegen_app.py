import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

# Replace App tail call detection
app_tail_search = """      isFlatTailCall = isTail && case loopCtx, flatFn of
        Just ctx, TcoExpr _ (Var (Qualified mbMod (Ident name))) ->
          let fullName = fromMaybe "" (map (\\(ModuleName m) -> String.joinWith "_" (String.split (Pattern ".") m) <> "_") mbMod) <> name
          in fullName == ctx.ident && Array.length flatArgs == Array.length ctx.params
        Just ctx, TcoExpr _ (Local mbIdent (Level l)) ->
          let v = fromMaybe (localId mbIdent (Level l)) (Map.lookup (localId mbIdent (Level l)) bound)
          in v == ctx.ident && Array.length flatArgs == Array.length ctx.params
        _, _ -> false"""

app_tail_replace = """      isFlatTailCall = isTail && case loopCtx, flatFn of
        Just ctx, TcoExpr _ (Var (Qualified mbMod (Ident name))) ->
          let fullName = fromMaybe "" (map (\\(ModuleName m) -> String.joinWith "_" (String.split (Pattern ".") m) <> "_") mbMod) <> name
          in case ctx.mutGroup of
            Just group -> isJust (find (\\g -> g.ident == fullName && Array.length flatArgs == Array.length g.params) group)
            Nothing -> fullName == ctx.ident && Array.length flatArgs == Array.length ctx.params
        Just ctx, TcoExpr _ (Local mbIdent (Level l)) ->
          let v = fromMaybe (localId mbIdent (Level l)) (Map.lookup (localId mbIdent (Level l)) bound)
          in case ctx.mutGroup of
            Just group -> isJust (find (\\g -> g.ident == v && Array.length flatArgs == Array.length g.params) group)
            Nothing -> v == ctx.ident && Array.length flatArgs == Array.length ctx.params
        _, _ -> false"""

code = code.replace(app_tail_search, app_tail_replace)

app_tail_gen_search = """      finalRes = if isFlatTailCall then
        case loopCtx of
          Just ctx ->
            let
              tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (accFinal.nextId + i)) e) accFinal.exprs
              assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (fromMaybe "" (Array.index ctx.params i)) (PhpVar ("__tco_" <> show (accFinal.nextId + i)))) accFinal.exprs
              finalStmts = resFn.stmts <> accFinal.stmts <> tcoStmts <> assignStmts <> [ PhpContinue ]
            in { stmts: finalStmts, expr: PhpRaw "null", nextId: accFinal.nextId + Array.length flatArgs }
          Nothing -> { stmts: resFn.stmts <> accFinal.stmts, expr: PhpCall resFn.expr accFinal.exprs, nextId: accFinal.nextId }
      else"""

app_tail_gen_replace = """      finalRes = if isFlatTailCall then
        case loopCtx of
          Just ctx ->
            case ctx.mutGroup of
              Just group ->
                let
                  targetName = case flatFn of
                    TcoExpr _ (Var (Qualified mbMod (Ident name))) -> fromMaybe "" (map (\\(ModuleName m) -> String.joinWith "_" (String.split (Pattern ".") m) <> "_") mbMod) <> name
                    TcoExpr _ (Local mbIdent (Level l)) -> fromMaybe (localId mbIdent (Level l)) (Map.lookup (localId mbIdent (Level l)) bound)
                    _ -> ""
                  targetFn = fromMaybe {ident: "", params: []} (find (\\f -> f.ident == targetName) group)
                  tcoStmts = [ PhpAssign (PhpRaw "$__tco_state['id']") (PhpString targetFn.ident) ]
                  assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (PhpRaw ("$__tco_state['" <> targetFn.ident <> "_" <> show i <> "']")) (PhpVar ("__tco_" <> show (accFinal.nextId + i)))) accFinal.exprs
                  tcoVars = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (accFinal.nextId + i)) e) accFinal.exprs
                  finalStmts = resFn.stmts <> accFinal.stmts <> tcoVars <> tcoStmts <> assignStmts <> [ PhpRaw "continue 2;" ]
                in { stmts: finalStmts, expr: PhpRaw "null", nextId: accFinal.nextId + Array.length flatArgs }
              Nothing ->
                let
                  tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (accFinal.nextId + i)) e) accFinal.exprs
                  assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (fromMaybe "" (Array.index ctx.params i)) (PhpVar ("__tco_" <> show (accFinal.nextId + i)))) accFinal.exprs
                  finalStmts = resFn.stmts <> accFinal.stmts <> tcoStmts <> assignStmts <> [ PhpContinue ]
                in { stmts: finalStmts, expr: PhpRaw "null", nextId: accFinal.nextId + Array.length flatArgs }
          Nothing -> { stmts: resFn.stmts <> accFinal.stmts, expr: PhpCall resFn.expr accFinal.exprs, nextId: accFinal.nextId }
      else"""

code = code.replace(app_tail_gen_search, app_tail_gen_replace)

# Replace UncurriedApp tail call detection
unc_app_search = """      isTailCall = isTail && case loopCtx, resFn.expr of
        Just ctx, PhpGlobalVar mbMod name ->
          let fullName = fromMaybe "" (map (\\m -> String.joinWith "_" m <> "_") mbMod) <> name
          in fullName == ctx.ident && Array.length args == Array.length ctx.params
        Just ctx, PhpVar v -> v == ctx.ident && Array.length args == Array.length ctx.params
        _, _ -> false"""

unc_app_replace = """      isTailCall = isTail && case loopCtx, resFn.expr of
        Just ctx, PhpGlobalVar mbMod name ->
          let fullName = fromMaybe "" (map (\\m -> String.joinWith "_" m <> "_") mbMod) <> name
          in case ctx.mutGroup of
            Just group -> isJust (find (\\g -> g.ident == fullName && Array.length args == Array.length g.params) group)
            Nothing -> fullName == ctx.ident && Array.length args == Array.length ctx.params
        Just ctx, PhpVar v ->
          case ctx.mutGroup of
            Just group -> isJust (find (\\g -> g.ident == v && Array.length args == Array.length g.params) group)
            Nothing -> v == ctx.ident && Array.length args == Array.length ctx.params
        _, _ -> false"""

code = code.replace(unc_app_search, unc_app_replace)

unc_app_gen_search = """      finalRes = if isTailCall then
        case loopCtx of
          Just ctx ->
            let
              tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (accArgs.nextId + i)) e) accArgs.exprs
              assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (fromMaybe "" (Array.index ctx.params i)) (PhpVar ("__tco_" <> show (accArgs.nextId + i)))) accArgs.exprs
              finalStmts = resFn.stmts <> accArgs.stmts <> tcoStmts <> assignStmts <> [ PhpContinue ]
            in { stmts: finalStmts, expr: PhpRaw "null", nextId: accArgs.nextId + Array.length args }
          Nothing -> { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }
      else"""

unc_app_gen_replace = """      finalRes = if isTailCall then
        case loopCtx of
          Just ctx ->
            case ctx.mutGroup of
              Just group ->
                let
                  targetName = case resFn.expr of
                    PhpGlobalVar mbMod name -> fromMaybe "" (map (\\m -> String.joinWith "_" m <> "_") mbMod) <> name
                    PhpVar v -> v
                    _ -> ""
                  targetFn = fromMaybe {ident: "", params: []} (find (\\f -> f.ident == targetName) group)
                  tcoStmts = [ PhpAssign (PhpRaw "$__tco_state['id']") (PhpString targetFn.ident) ]
                  assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (PhpRaw ("$__tco_state['" <> targetFn.ident <> "_" <> show i <> "']")) (PhpVar ("__tco_" <> show (accArgs.nextId + i)))) accArgs.exprs
                  tcoVars = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (accArgs.nextId + i)) e) accArgs.exprs
                  finalStmts = resFn.stmts <> accArgs.stmts <> tcoVars <> tcoStmts <> assignStmts <> [ PhpRaw "continue 2;" ]
                in { stmts: finalStmts, expr: PhpRaw "null", nextId: accArgs.nextId + Array.length args }
              Nothing ->
                let
                  tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (accArgs.nextId + i)) e) accArgs.exprs
                  assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (fromMaybe "" (Array.index ctx.params i)) (PhpVar ("__tco_" <> show (accArgs.nextId + i)))) accArgs.exprs
                  finalStmts = resFn.stmts <> accArgs.stmts <> tcoStmts <> assignStmts <> [ PhpContinue ]
                in { stmts: finalStmts, expr: PhpRaw "null", nextId: accArgs.nextId + Array.length args }
          Nothing -> { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }
      else"""

code = code.replace(unc_app_gen_search, unc_app_gen_replace)

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
