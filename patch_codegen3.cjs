const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen_patch_helpers.purs', 'utf8');

// Patch wrapInStmts
code = code.replace(
  'wrapInStmts captures stmts expr = PhpCall (PhpFunction captures [] (stmts <> [ PhpReturn expr ])) []',
  'wrapInStmts captures stmts expr = PhpCall (PhpFunction captures [] "" (stmts <> [ PhpReturn expr ])) []'
);

// Patch Abs
code = code.replace(
  `      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] true nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }`,
  `      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] true nextId body
      types = extractFuncType tcoExpr
      argsWithTypes = zipArgsWithTypes argsArray types
      retType = getRetType (Array.length argsArray) types
    in
      { stmts: [], expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }`
);

// Patch UncurriedAbs
code = code.replace(
  `      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] true nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }`, // Wait, will it replace both Abs and UncurriedAbs? The regex matches both exactly! Let's check!
  `      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] true nextId body
      types = extractFuncType tcoExpr
      argsWithTypes = zipArgsWithTypes argsArray types
      retType = getRetType (Array.length argsArray) types
    in
      { stmts: [], expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }`
); // Let's just do it with a global replace (though replace without /g only replaces the first)

// Because Abs and UncurriedAbs are identical, I'll replace it twice.
code = code.replace(
  `      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] true nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }`,
  `      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] true nextId body
      types = extractFuncType tcoExpr
      argsWithTypes = zipArgsWithTypes argsArray types
      retType = getRetType (Array.length argsArray) types
    in
      { stmts: [], expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }`
);

// Patch UncurriedEffectAbs
code = code.replace(
  `      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }`,
  `      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] false nextId body
      types = extractFuncType tcoExpr
      argsWithTypes = zipArgsWithTypes argsArray types
      retType = getRetType (Array.length argsArray) types
    in
      { stmts: [], expr: PhpFunction useVars argsWithTypes retType (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }`
);

// Patch LetRec
code = code.replace(
  'PhpAssign newName (PhpFunction useVarsOuter fn.args (initVarStmts <> innerFuncBody))',
  'PhpAssign newName (PhpFunction useVarsOuter (map (\\n -> { name: n, type_: "" }) fn.args) "" (initVarStmts <> innerFuncBody))'
);

// Patch EffectAbs
code = code.replace(
  `      useVars = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
    in
      { stmts: [], expr: PhpFunction useVars [] (res.stmts <> [ PhpReturn res.expr ]), nextId: res.nextId }`,
  `      useVars = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
    in
      { stmts: [], expr: PhpFunction useVars [] "" (res.stmts <> [ PhpReturn res.expr ]), nextId: res.nextId }`
);

// Patch DataCtor
code = code.replace(
  'if numFields == 0 then { stmts: [], expr: singletonBody, nextId } else { stmts: [], expr: PhpFunction [] fields [ PhpReturn body ], nextId }',
  'if numFields == 0 then { stmts: [], expr: singletonBody, nextId } else { stmts: [], expr: PhpFunction [] (map (\\n -> { name: n, type_: "" }) fields) "" [ PhpReturn body ], nextId }'
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
