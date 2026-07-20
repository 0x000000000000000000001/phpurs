import sys

with open("src/Phpurs/CodeGen.purs", "r") as f:
    text = f.read()

loopctx_old = "type LoopCtx = { ident :: String, params :: Array String }"
if loopctx_old not in text:
    print("LoopCtx not found")
    sys.exit(1)
text = text.replace(loopctx_old, "type LoopCtx = { ident :: String, params :: Array String, doneVar :: String, loopFuncVar :: String, resultVar :: String, varPrefix :: String }")

sig_old = "Maybe LoopCtx -> Boolean -> Int -> TcoExpr -> TranslationRes"
sig_new = "Array LoopCtx -> Boolean -> Int -> TcoExpr -> TranslationRes"
if sig_old not in text:
    print("Signature not found")
    sys.exit(1)
text = text.replace(sig_old, sig_new)

# Replace all "Nothing" passed to loopCtx
text = text.replace(" (Just newName) Nothing false ", " (Just newName) [] false ")
text = text.replace(" Nothing Nothing false ", " Nothing [] false ")
text = text.replace(" (Just varName) Nothing false ", " (Just varName) [] false ")
text = text.replace(" (Just (modPrefix <> name)) Nothing false ", " (Just (modPrefix <> name)) [] false ")

letrec_old = """  LetRec lvl binds body ->
    let
      oldNewPairs = map
        ( \\(Tuple ident _) ->
            let
              oldName = localId (Just ident) lvl
            in
              { oldName, newName: oldName <> "_" <> show nextId }
        )
        (toArray binds)
      newBound = foldl (\\acc pair -> Map.insert pair.oldName pair.newName acc) bound oldNewPairs
      newRecVars = map _.newName oldNewPairs
      combinedRecVars = recVars <> newRecVars
      initStmts = map (\\pair -> PhpAssign pair.newName (PhpRaw "null")) oldNewPairs
      accBinds = foldl
        ( \\acc (Tuple (Ident ident) val@(TcoExpr _ _)) ->
            let
              oldName = localId (Just (Ident ident)) lvl
              newName = fromMaybe oldName (Map.lookup oldName newBound)
              res = translateExprImpl combinedRecVars namedBound newBound (Just newName) Nothing false acc.nextId val
            in
              { stmts: acc.stmts <> res.stmts <> [ PhpAssign newName res.expr ], nextId: res.nextId }
        )
        { stmts: initStmts, nextId: nextId + 1 }
        (toArray binds)
      resBody = translateExprImpl combinedRecVars namedBound newBound Nothing loopCtx isTail accBinds.nextId body
    in
      { stmts: accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }"""

letrec_new = """  LetRec lvl binds body ->
    let
      oldNewPairs = map
        ( \\(Tuple ident _) ->
            let
              oldName = localId (Just ident) lvl
            in
              { oldName, newName: oldName <> "_" <> show nextId }
        )
        (toArray binds)
      newBound = foldl (\\acc pair -> Map.insert pair.oldName pair.newName acc) bound oldNewPairs
      newRecVars = map _.newName oldNewPairs
      combinedRecVars = recVars <> newRecVars
      
      isLoop = (getTcoRole (tcoAnalysisOf tcoExpr)).isLoop
      mutRecBinds = if isLoop then
        traverse (\\(Tuple ident val) -> map (\\abs -> { ident: localId (Just ident) lvl, args: abs.args, body: abs.body, fvs: abs.fvs }) (extractUncurriedAbs val)) (toArray binds)
      else Nothing
    in case mutRecBinds of
      Just fns ->
        let
          initStmts = map (\\pair -> PhpAssign pair.newName (PhpRaw "null")) oldNewPairs
          
          loopCtxs = map (\\fn ->
            let newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
            in { ident: newName, params: fn.args, doneVar: "__tco_done_" <> newName <> "_" <> show nextId, resultVar: "__tco_res_" <> newName <> "_" <> show nextId, loopFuncVar: "__tco_loop_" <> newName <> "_" <> show nextId, varPrefix: "__tco_var_" <> newName <> "_" <> show nextId <> "_" }
          ) fns
          
          combinedLoopCtx = loopCtxs <> loopCtx
          
          fnWrapperStmts = map
            ( \\fn ->
                let
                  newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
                  ctx = fromMaybe { ident: "", params: [], doneVar: "", resultVar: "", loopFuncVar: "", varPrefix: "" } (Array.find (\\c -> c.ident == newName) loopCtxs)
                  
                  loopVars = map (\\p -> ctx.varPrefix <> p) fn.args
                  
                  initVarStmts = Array.mapWithIndex (\\i p -> PhpAssign (fromMaybe "" (Array.index loopVars i)) (PhpVar p)) fn.args
                  
                  doneAssign = PhpAssign ctx.doneVar (PhpBoolean false)
                  resAssign = PhpAssign ctx.resultVar (PhpRaw "null")
                  
                  resBodyMut = translateExprImpl combinedRecVars namedBound newBound Nothing combinedLoopCtx true nextId fn.body
                  
                  allFvs = Set.filter (\\v -> not (Array.elem v newRecVars)) fn.fvs
                  useVarsLoop = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped combinedRecVars then "&" <> mapped else mapped) (Array.fromFoldable allFvs)
                  
                  mutVarsToCapture = foldMap (\\c -> Array.cons ("&" <> c.doneVar) (map (\\p -> "&" <> c.varPrefix <> p) c.params)) loopCtxs
                  
                  useVarsInner = mutVarsToCapture <> useVarsLoop
                  
                  innerLoopInit = Array.mapWithIndex (\\i p -> PhpAssign p (PhpVar (fromMaybe "" (Array.index loopVars i)))) fn.args
                  
                  innerFunc = PhpAssign ctx.loopFuncVar (PhpFunction useVarsInner fn.args (innerLoopInit <> resBodyMut.stmts <> [ PhpReturn resBodyMut.expr ]))
                  
                  whileLoop = PhpWhile (PhpRaw ("!$" <> ctx.doneVar))
                    [ PhpAssign ctx.resultVar (PhpCall (PhpVar ctx.loopFuncVar) (map PhpVar loopVars)) ]
                    
                in
                  PhpAssign newName (PhpFunction useVarsLoop fn.args (initVarStmts <> [doneAssign, resAssign, innerFunc, whileLoop, PhpReturn (PhpVar ctx.resultVar)]))
            )
            fns
            
          resBodyOuter = translateExprImpl combinedRecVars namedBound newBound Nothing loopCtx isTail (nextId + 1) body
        in
          { stmts: initStmts <> fnWrapperStmts <> resBodyOuter.stmts, expr: resBodyOuter.expr, nextId: resBodyOuter.nextId }
          
      Nothing ->
        let
          initStmts = map (\\pair -> PhpAssign pair.newName (PhpRaw "null")) oldNewPairs
          accBinds = foldl
            ( \\acc (Tuple (Ident ident) val@(TcoExpr _ _)) ->
                let
                  oldName = localId (Just (Ident ident)) lvl
                  newName = fromMaybe oldName (Map.lookup oldName newBound)
                  res = translateExprImpl combinedRecVars namedBound newBound (Just newName) [] false acc.nextId val
                in
                  { stmts: acc.stmts <> res.stmts <> [ PhpAssign newName res.expr ], nextId: res.nextId }
            )
            { stmts: initStmts, nextId: nextId + 1 }
            (toArray binds)
          resBody = translateExprImpl combinedRecVars namedBound newBound Nothing loopCtx isTail accBinds.nextId body
        in
          { stmts: accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }"""

if letrec_old not in text:
    print("LetRec not found")
    sys.exit(1)
text = text.replace(letrec_old, letrec_new)

app_old = """  App fn args ->
    let
      resFn = translateExprImpl recVars namedBound bound Nothing Nothing false nextId fn
      accFinal = foldl
        ( \\acc arg@(TcoExpr _ _) ->
            let
              argRes = translateExprImpl recVars namedBound bound Nothing Nothing false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
        )
        { stmts: resFn.stmts, exprs: [], nextId: resFn.nextId }
        (toArray args)
      
      isTailCallTo = case fn of
        TcoExpr _ (Local mbIdent (Level l)) ->
          let v = fromMaybe (localId mbIdent (Level l)) (Map.lookup (localId mbIdent (Level l)) bound)
          in if isJust loopCtx && (fromMaybe { ident: "", params: [] } loopCtx).ident == v then loopCtx else Nothing
        TcoExpr _ (Var (Qualified mbMod (Ident name))) ->
          let fullName = fromMaybe "" (map (\\(ModuleName m) -> String.joinWith "_" (String.split (Pattern ".") m) <> "_") mbMod) <> name
          in if isJust loopCtx && (fromMaybe { ident: "", params: [] } loopCtx).ident == fullName then loopCtx else Nothing
        _ -> Nothing

    in case isTailCallTo of
      Just ctx | isTail ->
        let
          tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (accFinal.nextId + i)) e) accFinal.exprs
          assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (fromMaybe "" (Array.index ctx.params i)) (PhpVar ("__tco_" <> show (accFinal.nextId + i)))) accFinal.exprs
          finalStmts = accFinal.stmts <> tcoStmts <> assignStmts <> [ PhpContinue ]
        in { stmts: finalStmts, expr: PhpRaw "null", nextId: accFinal.nextId + Array.length (toArray args) }
      _ -> 
        let curriedCall = foldl (\\acc e -> PhpCall acc [e]) resFn.expr accFinal.exprs
        in { stmts: accFinal.stmts, expr: curriedCall, nextId: accFinal.nextId }"""

app_new = """  App fn args ->
    let
      resFn = translateExprImpl recVars namedBound bound Nothing [] false nextId fn
      argsArr = toArray args
      
      isTailCallTo = if isTail then case fn of
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
            argsArr
            
          tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (flatAccFinal.nextId + i)) e) flatAccFinal.exprs
          assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (targetCtx.varPrefix <> (fromMaybe "" (Array.index targetCtx.params i))) (PhpVar ("__tco_" <> show (flatAccFinal.nextId + i)))) flatAccFinal.exprs
          
          popStmts = map (\\ctx -> PhpAssign ctx.doneVar (PhpBoolean true)) (Array.take (index) loopCtx)
          
          finalStmts = flatAccFinal.stmts <> tcoStmts <> assignStmts <> popStmts <> [ PhpReturn (PhpRaw "null") ]
        in { stmts: finalStmts, expr: PhpRaw "null", nextId: flatAccFinal.nextId + Array.length argsArr }
      Nothing -> 
        let curriedCall = foldl (\\acc e -> PhpCall acc [e]) resFn.expr accFinal.exprs
        in { stmts: accFinal.stmts, expr: curriedCall, nextId: accFinal.nextId }"""

if app_old not in text:
    print("App not found")
    sys.exit(1)
text = text.replace(app_old, app_new)

uncurried_app_old = """  UncurriedApp fn args ->
    let
      resFn = translateExprImpl recVars namedBound bound Nothing Nothing false nextId fn
      accArgs = foldl
        ( \\acc arg@(TcoExpr _ _) ->
            let
              argRes = translateExprImpl recVars namedBound bound Nothing Nothing false acc.nextId arg
            in
              { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: acc.nextId }
        )
        { stmts: [], exprs: [], nextId: resFn.nextId }
        args
        
      isTailCallTo = case resFn.expr of
        PhpGlobalVar mbMod name ->
          let fullName = fromMaybe "" (map (\\m -> String.joinWith "_" m <> "_") mbMod) <> name
          in if isJust loopCtx && (fromMaybe { ident: "", params: [] } loopCtx).ident == fullName then loopCtx else Nothing
        PhpVar v ->
          if isJust loopCtx && (fromMaybe { ident: "", params: [] } loopCtx).ident == v then loopCtx else Nothing
        _ -> Nothing

    in case isTailCallTo of
      Just ctx | isTail ->
        let
          tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (accArgs.nextId + i)) e) accArgs.exprs
          assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (fromMaybe "" (Array.index ctx.params i)) (PhpVar ("__tco_" <> show (accArgs.nextId + i)))) accArgs.exprs
          finalStmts = accArgs.stmts <> tcoStmts <> assignStmts <> [ PhpContinue ]
        in { stmts: finalStmts, expr: PhpRaw "null", nextId: accArgs.nextId + Array.length args }
      _ ->
        { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }"""

uncurried_app_new = """  UncurriedApp fn args ->
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
        { stmts: resFn.stmts <> accArgs.stmts, expr: PhpCall resFn.expr accArgs.exprs, nextId: accArgs.nextId }"""

if uncurried_app_old not in text:
    print("UncurriedApp not found")
    sys.exit(1)
text = text.replace(uncurried_app_old, uncurried_app_new)

abs_old = """  Abs args body ->
    let
      argsArray = map (\\(Tuple mbI lvl) -> localId mbI lvl) (toArray args)
      fvs = freeVars tcoExpr
      useVars = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      newLoopCtx = case currentBindingName of
        Just name | Array.elem name recVars -> Just { ident: name, params: argsArray }
        _ -> Nothing
        
      resBody = translateExprImpl recVars namedBound bound Nothing newLoopCtx true nextId body
      
      debugStmt = PhpRaw ("// DEBUG UncurriedAbs: currentBindingName=" <> show currentBindingName <> ", recVars=" <> show recVars)
      bodyStmts = case newLoopCtx of
        Just _ -> [ debugStmt, PhpWhile (PhpBoolean true) (resBody.stmts <> [ PhpReturn resBody.expr ]) ]
        Nothing -> [ debugStmt ] <> resBody.stmts <> [ PhpReturn resBody.expr ]
    in
      { stmts: [], expr: PhpFunction useVars argsArray bodyStmts, nextId: resBody.nextId }"""

abs_new = """  Abs args body ->
    let
      argsArray = map (\\(Tuple mbI lvl) -> localId mbI lvl) (toArray args)
      fvs = freeVars tcoExpr
      useVars = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      resBody = translateExprImpl recVars namedBound bound Nothing [] true nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }"""

if abs_old not in text:
    print("Abs not found")
    sys.exit(1)
text = text.replace(abs_old, abs_new)

uncurried_abs_old = """  UncurriedAbs args body ->
    let
      argsArray = map (\\(Tuple mbI lvl) -> localId mbI lvl) args
      fvs = freeVars tcoExpr
      useVars = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      newLoopCtx = case currentBindingName of
        Just name | Array.elem name recVars -> Just { ident: name, params: argsArray }
        _ -> Nothing

      resBody = translateExprImpl recVars namedBound bound Nothing newLoopCtx true nextId body
      
      debugStmt = PhpRaw ("// DEBUG UncurriedAbs: currentBindingName=" <> show currentBindingName <> ", recVars=" <> show recVars)
      bodyStmts = case newLoopCtx of
        Just _ -> [ debugStmt, PhpWhile (PhpBoolean true) (resBody.stmts <> [ PhpReturn resBody.expr ]) ]
        Nothing -> [ debugStmt ] <> resBody.stmts <> [ PhpReturn resBody.expr ]
    in
      { stmts: [], expr: PhpFunction useVars argsArray bodyStmts, nextId: resBody.nextId }"""

uncurried_abs_new = """  UncurriedAbs args body ->
    let
      argsArray = map (\\(Tuple mbI lvl) -> localId mbI lvl) args
      fvs = freeVars tcoExpr
      useVars = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      resBody = translateExprImpl recVars namedBound bound Nothing [] true nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }"""

if uncurried_abs_old not in text:
    print("UncurriedAbs not found")
    sys.exit(1)
text = text.replace(uncurried_abs_old, uncurried_abs_new)


with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(text)

