import sys

with open("src/Phpurs/CodeGen.purs", "r") as f:
    text = f.read()

# 1. Update LoopCtx
loopctx_old = "type LoopCtx = { ident :: String, params :: Array String, mutGroup :: Maybe (Array { ident :: String, params :: Array String }) }"
if loopctx_old not in text:
    print("LoopCtx not found")
    sys.exit(1)
text = text.replace(loopctx_old, "type LoopCtx = { ident :: String, params :: Array String, doneVar :: String, loopFuncVar :: String, resultVar :: String, varPrefix :: String }")

# 2. Update translateExprImpl signature
sig_old = "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Maybe LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }"
sig_new = "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Array LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }"
if sig_old not in text:
    print("Signature not found")
    sys.exit(1)
text = text.replace(sig_old, sig_new)

# 3. Replace all "Nothing" passed to loopCtx
text = text.replace(" (Just newName) Nothing false ", " (Just newName) [] false ")
text = text.replace(" Nothing Nothing false ", " Nothing [] false ")
text = text.replace(" (Just varName) Nothing false ", " (Just varName) [] false ")
text = text.replace(" (Just (modPrefix <> name)) Nothing false ", " (Just (modPrefix <> name)) [] false ")

# 4. Replace LetRec block
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
    print("LetRec not found")
    sys.exit(1)
text = text.replace(letrec_old, letrec_new)

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(text)

