import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    text = f.read()

# 1. Update LoopCtx
text = text.replace(
    "type LoopCtx = { ident :: String, params :: Array String, mutGroup :: Maybe (Array { ident :: String, params :: Array String }) }",
    "type LoopCtx = { ident :: String, params :: Array String, doneVar :: String, loopFuncVar :: String, resultVar :: String, varPrefix :: String }"
)

# 2. Signature
text = text.replace(
    "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Maybe LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }",
    "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Array LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }"
)

# 3. Replace Nothing with []
text = re.sub(r'(translateExprImpl [^ ]+ [^ ]+ [^ ]+ [^ ]+ )Nothing( (?:false|true|isTail) )', r'\1[]\2', text)
text = re.sub(r'(translateExprImpl [^ ]+ [^ ]+ [^ ]+ \(Just [^)]+\) )Nothing( (?:false|true|isTail) )', r'\1[]\2', text)
text = re.sub(r'(translateExprImpl [^ ]+ [^ ]+ [^ ]+ Nothing )Nothing( (?:false|true|isTail) )', r'\1[]\2', text)
# Top level occurrences
text = text.replace(" (Just (modPrefix <> name)) Nothing false ", " (Just (modPrefix <> name)) [] false ")

# 4. LetRec branch
letrec_old = """  LetRec lvl binds body ->
    let
      oldNewPairs = map
        ( \\(Tuple (Ident oldName) _) ->
            let oldId = localId (Just (Ident oldName)) lvl
            in { oldName: oldId, newName: oldId <> "_" <> show nextId }
        )
        (toArray binds)
      newBound = foldl (\\acc pair -> Map.insert pair.oldName pair.newName acc) bound oldNewPairs
      newRecVars = map _.newName oldNewPairs
      combinedRecVars = recVars <> newRecVars
      
      isLoop = (getTcoRole (tcoAnalysisOf tcoExpr)).isLoop
      mutRecBinds = if isLoop && Array.length (toArray binds) > 1 then
        traverse (\\(Tuple ident val) -> map (\\abs -> { ident: localId (Just ident) lvl, args: abs.args, body: abs.body, fvs: abs.fvs }) (extractUncurriedAbs val)) (toArray binds)
      else Nothing
    in case mutRecBinds of
      Just fns ->
        let
          mutGroupInfo = map (\\fn -> { ident: fromMaybe fn.ident (Map.lookup fn.ident newBound), params: fn.args }) fns
          
          allFvs = Set.filter (\\v -> not (Array.elem v newRecVars)) (foldMap _.fvs fns)
          useVarsLoop = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped combinedRecVars then "&" <> mapped else mapped) (Array.fromFoldable allFvs)
          
          stateVar = "$__tco_state"
          loopVar = "__tco_loop_" <> show nextId
          
          switchCases = map
            ( \\fn ->
                let
                  newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
                  loopCtxMut = Just { ident: newName, params: fn.args, mutGroup: Just mutGroupInfo }
                  resBodyMut = translateExprImpl combinedRecVars namedBound newBound Nothing loopCtxMut true nextId fn.body
                  
                  argAssigns = Array.mapWithIndex (\\i argName -> PhpAssign argName (PhpRaw (stateVar <> "['" <> newName <> "_" <> show i <> "']"))) fn.args
                in
                  { matchCases: [PhpString newName], stmts: argAssigns <> resBodyMut.stmts <> [ PhpRaw (stateVar <> "['done'] = true;"), PhpRaw (stateVar <> "['res'] = " <> printExpr resBodyMut.expr <> ";") ] }
            )
            fns
            
          loopBody = [ PhpWhile (PhpRaw ("!" <> stateVar <> "['done']")) [ PhpSwitch (PhpRaw (stateVar <> "['id']")) switchCases Nothing ] ]
          loopFunc = PhpAssign loopVar (PhpFunction (Array.cons ("&" <> stateVar) useVarsLoop) [] loopBody)
          
          wrapperAssigns = map
            ( \\fn ->
                let
                  newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
                  useVarsFn = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped combinedRecVars then "&" <> mapped else mapped) (Array.fromFoldable fn.fvs)
                  
                  setupStmts = [ PhpRaw (stateVar <> "['id'] = '" <> newName <> "';")
                               , PhpRaw (stateVar <> "['done'] = false;")
                               ] <> Array.mapWithIndex (\\i argName -> PhpRaw (stateVar <> "['" <> newName <> "_" <> show i <> "'] = $" <> argName <> ";")) fn.args
                  
                  callStmt = PhpRaw (loopVar <> "();")
                  retStmt = PhpReturn (PhpRaw (stateVar <> "['res']"))
                  
                in
                  PhpAssign newName (PhpFunction (Array.cons ("&" <> stateVar) (Array.cons ("&" <> loopVar) useVarsFn)) fn.args (setupStmts <> [callStmt, retStmt]))
            )
            fns
            
          initStmts = map (\\pair -> PhpAssign pair.newName (PhpRaw "null")) oldNewPairs
          
          resBodyOuter = translateExprImpl combinedRecVars namedBound newBound Nothing loopCtx isTail (nextId + 1) body
        in
          { stmts: initStmts <> [ PhpRaw (stateVar <> " = [];"), loopFunc ] <> wrapperAssigns <> resBodyOuter.stmts, expr: resBodyOuter.expr, nextId: resBodyOuter.nextId }
          
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
          { stmts: [ PhpRaw ("// DEBUG LetRec mutRecBinds=" <> (if isJust mutRecBinds then "Just" else "Nothing") <> " isLoop=" <> show isLoop <> " bindsLen=" <> show (Array.length (toArray binds))) ] <> accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }"""

letrec_new = """  LetRec lvl binds body ->
    let
      oldNewPairs = map
        ( \\(Tuple (Ident oldName) _) ->
            let oldId = localId (Just (Ident oldName)) lvl
            in { oldName: oldId, newName: oldId <> "_" <> show nextId }
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
          
          -- Create LoopCtx for each function
          loopCtxs = map (\\fn ->
            let newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
            in { ident: newName, params: fn.args, doneVar: "__tco_done_" <> newName, resultVar: "__tco_res_" <> newName, loopFuncVar: "__tco_loop_" <> newName, varPrefix: "__tco_var_" <> newName <> "_" }
          ) fns
          
          combinedLoopCtx = loopCtxs <> loopCtx
          
          fnWrapperStmts = map
            ( \\fn ->
                let
                  newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
                  ctx = fromMaybe { ident: "", params: [], doneVar: "", resultVar: "", loopFuncVar: "", varPrefix: "" } (Array.find (\\c -> c.ident == newName) loopCtxs)
                  
                  -- The loop variables
                  loopVars = map (\\p -> ctx.varPrefix <> p) fn.args
                  
                  -- Initial assignment: $__tco_var_f_x = $x;
                  initVarStmts = Array.mapWithIndex (\\i p -> PhpAssign (fromMaybe "" (Array.index loopVars i)) (PhpVar p)) fn.args
                  
                  doneAssign = PhpAssign ctx.doneVar (PhpBoolean false)
                  resAssign = PhpAssign ctx.resultVar (PhpRaw "null")
                  
                  -- Function body translation
                  resBodyMut = translateExprImpl combinedRecVars namedBound newBound Nothing combinedLoopCtx true nextId fn.body
                  
                  -- Inner function $__tco_loop_f
                  allFvs = Set.filter (\\v -> not (Array.elem v newRecVars)) fn.fvs
                  -- fvs that must be captured by reference
                  useVarsLoop = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped combinedRecVars then "&" <> mapped else mapped) (Array.fromFoldable allFvs)
                  
                  -- we need to capture ALL loop doneVars and varPrefix variables for ALL loops in loopCtxs!
                  -- because they can jump to each other!
                  mutVarsToCapture = foldMap (\\c -> Array.cons ("&" <> c.doneVar) (map (\\p -> "&" <> c.varPrefix <> p) c.params)) loopCtxs
                  
                  useVarsInner = mutVarsToCapture <> useVarsLoop
                  
                  -- Inner function start: $x = $__tco_var_f_x;
                  innerLoopInit = Array.mapWithIndex (\\i p -> PhpAssign p (PhpVar (fromMaybe "" (Array.index loopVars i)))) fn.args
                  
                  innerFunc = PhpAssign ctx.loopFuncVar (PhpFunction useVarsInner fn.args (innerLoopInit <> resBodyMut.stmts <> [ PhpReturn resBodyMut.expr ]))
                  
                  -- While loop
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
    print("Failed to find LetRec")
    sys.exit(1)
text = text.replace(letrec_old, letrec_new)

# 5. App branch
app_old = """  App fn args ->
    let
      resFn = translateExprImpl recVars namedBound bound Nothing [] false nextId fn
      argsArr = toArray args
      
      Tuple flatFn flatArgs = flattenApp tcoExpr
      
      isTailCallTo = case flatFn of
        TcoExpr _ (Local mbIdent (Level l)) ->
          let v = fromMaybe (localId mbIdent (Level l)) (Map.lookup (localId mbIdent (Level l)) bound)
          in Array.findIndex (\\ctx -> ctx.ident == v) (fromMaybe [] (map (\\c -> [c]) loopCtx))
        TcoExpr _ (Var (Qualified mbMod (Ident name))) ->
          let fullName = fromMaybe "" (map (\\(ModuleName m) -> String.joinWith "_" (String.split (Pattern ".") m) <> "_") mbMod) <> name
          in Array.findIndex (\\ctx -> ctx.ident == fullName) (fromMaybe [] (map (\\c -> [c]) loopCtx))
        _ -> Nothing

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
      Just index | isTail ->
        let
          ctx = fromMaybe { ident: "", params: [], mutGroup: Nothing } loopCtx
          
          -- Wait, if we are in mutGroup, the target might be in mutGroup!
          
          flatAccFinal = foldl
            ( \\acc arg@(TcoExpr _ _) ->
                let
                  argRes = translateExprImpl recVars namedBound bound Nothing [] false acc.nextId arg
                in
                  { stmts: acc.stmts <> argRes.stmts, exprs: Array.snoc acc.exprs argRes.expr, nextId: argRes.nextId }
            )
            { stmts: [], exprs: [], nextId: nextId }
            flatArgs
            
          mutGroupTarget = case flatFn of
            TcoExpr _ (Local mbIdent (Level l)) ->
              let v = fromMaybe (localId mbIdent (Level l)) (Map.lookup (localId mbIdent (Level l)) bound)
              in Array.findIndex (\\m -> m.ident == v) (fromMaybe [] ctx.mutGroup)
            TcoExpr _ (Var (Qualified mbMod (Ident name))) ->
              let fullName = fromMaybe "" (map (\\(ModuleName m) -> String.joinWith "_" (String.split (Pattern ".") m) <> "_") mbMod) <> name
              in Array.findIndex (\\m -> m.ident == fullName) (fromMaybe [] ctx.mutGroup)
            _ -> Nothing
            
        in case mutGroupTarget of
          Just mutIdx ->
            let
              mutFn = fromMaybe { ident: "", params: [] } (Array.index (fromMaybe [] ctx.mutGroup) mutIdx)
              tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (flatAccFinal.nextId + i)) e) flatAccFinal.exprs
              assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (fromMaybe "" (Array.index mutFn.params i)) (PhpVar ("__tco_" <> show (flatAccFinal.nextId + i)))) flatAccFinal.exprs
              
              stateVar = "$__tco_state"
              mutStmts = [ PhpRaw (stateVar <> "['id'] = '" <> mutFn.ident <> "';") ] <> assignStmts <> [ PhpContinue ]
            in { stmts: flatAccFinal.stmts <> tcoStmts <> mutStmts, expr: PhpRaw "null", nextId: flatAccFinal.nextId + Array.length flatArgs }
          Nothing ->
            let
              tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (flatAccFinal.nextId + i)) e) flatAccFinal.exprs
              assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (fromMaybe "" (Array.index ctx.params i)) (PhpVar ("__tco_" <> show (flatAccFinal.nextId + i)))) flatAccFinal.exprs
              finalStmts = flatAccFinal.stmts <> tcoStmts <> assignStmts <> [ PhpContinue ]
            in { stmts: finalStmts, expr: PhpRaw "null", nextId: flatAccFinal.nextId + Array.length flatArgs }
      _ -> 
        let curriedCall = foldl (\\acc e -> PhpCall acc [e]) resFn.expr accFinal.exprs
        in { stmts: accFinal.stmts, expr: curriedCall, nextId: accFinal.nextId }"""

app_new = """  App fn args ->
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
        in { stmts: accFinal.stmts, expr: curriedCall, nextId: accFinal.nextId }"""

if app_old not in text:
    print("Failed to find App")
    sys.exit(1)
text = text.replace(app_old, app_new)

# 6. UncurriedApp
uncurried_app_old = """  UncurriedApp fn args ->
    let
      resFn = translateExprImpl recVars namedBound bound Nothing [] false nextId fn
      
      isTailCallTo = case resFn.expr of
        PhpGlobalVar mbMod name ->
          let fullName = fromMaybe "" (map (\\m -> String.joinWith "_" m <> "_") mbMod) <> name
          in Array.findIndex (\\ctx -> ctx.ident == fullName) (fromMaybe [] (map (\\c -> [c]) loopCtx))
        PhpVar v ->
          Array.findIndex (\\ctx -> ctx.ident == v) (fromMaybe [] (map (\\c -> [c]) loopCtx))
        _ -> Nothing

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
      Just index | isTail ->
        let
          ctx = fromMaybe { ident: "", params: [], mutGroup: Nothing } loopCtx
          
          mutGroupTarget = case resFn.expr of
            PhpGlobalVar mbMod name ->
              let fullName = fromMaybe "" (map (\\m -> String.joinWith "_" m <> "_") mbMod) <> name
              in Array.findIndex (\\m -> m.ident == fullName) (fromMaybe [] ctx.mutGroup)
            PhpVar v ->
              Array.findIndex (\\m -> m.ident == v) (fromMaybe [] ctx.mutGroup)
            _ -> Nothing
            
        in case mutGroupTarget of
          Just mutIdx ->
            let
              mutFn = fromMaybe { ident: "", params: [] } (Array.index (fromMaybe [] ctx.mutGroup) mutIdx)
              tcoStmts = Array.mapWithIndex (\\i e -> PhpAssign ("__tco_" <> show (accArgs.nextId + i)) e) accArgs.exprs
              assignStmts = Array.mapWithIndex (\\i _ -> PhpAssign (fromMaybe "" (Array.index mutFn.params i)) (PhpVar ("__tco_" <> show (accArgs.nextId + i)))) accArgs.exprs
              
              stateVar = "$__tco_state"
              mutStmts = [ PhpRaw (stateVar <> "['id'] = '" <> mutFn.ident <> "';") ] <> assignStmts <> [ PhpContinue ]
            in { stmts: accArgs.stmts <> tcoStmts <> mutStmts, expr: PhpRaw "null", nextId: accArgs.nextId + Array.length args }
          Nothing ->
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
    print("Failed to find UncurriedApp")
    sys.exit(1)
text = text.replace(uncurried_app_old, uncurried_app_new)


# 7. Abs
abs_old = """  Abs args body ->
    let
      argsArray = map (\\(Tuple mbI lvl) -> localId mbI lvl) (toArray args)
      fvs = freeVars tcoExpr
      useVars = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      newLoopCtx = case currentBindingName of
        Just name | Array.elem name recVars -> Just { ident: name, params: argsArray, mutGroup: Nothing }
        _ -> Nothing
        
      resBody = translateExprImpl recVars namedBound bound Nothing newLoopCtx true nextId body
      
      debugStmt = PhpRaw ("// DEBUG Abs newLoopCtx=" <> (if isJust newLoopCtx then "Just" else "Nothing") <> " currentBindingName=" <> show currentBindingName <> " recVars=" <> String.joinWith "," recVars)
      
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
    print("Failed to find Abs")
    sys.exit(1)
text = text.replace(abs_old, abs_new)

# 8. UncurriedAbs
uncurried_abs_old = """  UncurriedAbs args body ->
    let
      argsArray = map (\\(Tuple mbI lvl) -> localId mbI lvl) args
      fvs = freeVars tcoExpr
      useVars = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      newLoopCtx = case currentBindingName of
        Just name | Array.elem name recVars -> Just { ident: name, params: argsArray, mutGroup: Nothing }
        _ -> Nothing

      resBody = translateExprImpl recVars namedBound bound Nothing newLoopCtx true nextId body
      
      bodyStmts = case newLoopCtx of
        Just _ -> [ PhpWhile (PhpBoolean true) (resBody.stmts <> [ PhpReturn resBody.expr ]) ]
        Nothing -> resBody.stmts <> [ PhpReturn resBody.expr ]
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
    print("Failed to find UncurriedAbs")
    sys.exit(1)
text = text.replace(uncurried_abs_old, uncurried_abs_new)


with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(text)

