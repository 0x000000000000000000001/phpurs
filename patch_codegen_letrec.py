import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    code = f.read()

letrec_search = """  LetRec lvl binds body ->
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

letrec_replace = """  LetRec lvl binds body ->
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
      mutRecBinds = if isLoop && Array.length (toArray binds) > 1 then
        traverse (\\(Tuple ident val) -> map (\\abs -> { ident: localId (Just ident) lvl, args: abs.args, body: abs.body, fvs: abs.fvs }) (extractUncurriedAbs val)) (toArray binds)
      else Nothing
    in case mutRecBinds of
      Just fns ->
        let
          mutGroupInfo = map (\\fn -> { ident: fromMaybe fn.ident (Map.lookup fn.ident newBound), params: fn.args }) (toArray fns)
          
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
                  { val: PhpString newName, body: argAssigns <> resBodyMut.stmts <> [ PhpAssign (PhpRaw (stateVar <> "['done']")) (PhpBoolean true), PhpAssign (PhpRaw (stateVar <> "['res']")) resBodyMut.expr ] }
            )
            (toArray fns)
            
          loopBody = [ PhpWhile (PhpRaw ("!" <> stateVar <> "['done']")) [ PhpSwitch (PhpRaw (stateVar <> "['id']")) switchCases Nothing ] ]
          loopFunc = PhpAssign loopVar (PhpFunction ("&" <> stateVar : useVarsLoop) [] loopBody)
          
          wrapperAssigns = map
            ( \\fn ->
                let
                  newName = fromMaybe fn.ident (Map.lookup fn.ident newBound)
                  useVarsFn = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped combinedRecVars then "&" <> mapped else mapped) (Array.fromFoldable fn.fvs)
                  
                  setupStmts = [ PhpAssign (PhpRaw (stateVar <> "['id']")) (PhpString newName)
                               , PhpAssign (PhpRaw (stateVar <> "['done']")) (PhpBoolean false)
                               ] <> Array.mapWithIndex (\\i argName -> PhpAssign (PhpRaw (stateVar <> "['" <> newName <> "_" <> show i <> "']")) (PhpVar argName)) fn.args
                  
                  callStmt = PhpRaw (loopVar <> "();")
                  retStmt = PhpReturn (PhpRaw (stateVar <> "['res']"))
                  
                in
                  PhpAssign newName (PhpFunction ("&" <> stateVar : "&" <> loopVar : useVarsFn) fn.args (setupStmts <> [callStmt, retStmt]))
            )
            (toArray fns)
            
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
                  res = translateExprImpl combinedRecVars namedBound newBound (Just newName) Nothing false acc.nextId val
                in
                  { stmts: acc.stmts <> res.stmts <> [ PhpAssign newName res.expr ], nextId: res.nextId }
            )
            { stmts: initStmts, nextId: nextId + 1 }
            (toArray binds)
          resBody = translateExprImpl combinedRecVars namedBound newBound Nothing loopCtx isTail accBinds.nextId body
        in
          { stmts: accBinds.stmts <> resBody.stmts, expr: resBody.expr, nextId: resBody.nextId }"""

code = code.replace(letrec_search, letrec_replace)

with open("src/Phpurs/CodeGen.purs", "w") as f:
    f.write(code)
