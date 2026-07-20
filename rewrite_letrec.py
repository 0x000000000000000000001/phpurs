import re

with open("src/Phpurs/CodeGen_test.purs", "r") as f:
    text = f.read()

# We want to replace the LetRec branch:
#   LetRec lvl binds body ->
#     let
#       ...
#     in case mutRecBinds of
#       ...
#       Nothing ->
#         ...
#
#   EffectBind (Just (Ident i)) (Level l) val body ->

letrec_start = text.find("  LetRec lvl binds body ->")
effectbind_start = text.find("  EffectBind (Just (Ident i)) (Level l) val body ->")

if letrec_start == -1 or effectbind_start == -1:
    print("Could not find LetRec or EffectBind")
    exit(1)

new_letrec = """  LetRec lvl binds body ->
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
          { stmts: accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }

"""

text = text[:letrec_start] + new_letrec + text[effectbind_start:]

with open("src/Phpurs/CodeGen_test.purs", "w") as f:
    f.write(text)

