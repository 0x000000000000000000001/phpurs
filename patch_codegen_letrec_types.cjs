const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

// Patch 1: Capture originalVal in local LetRec
code = code.replace(
  'traverse (\\(Tuple ident val) -> case extractUncurriedAbs val of\n            Just abs -> Just { ident: localId (Just ident) lvl, args: abs.args, body: abs.body, fvs: abs.fvs }\n            Nothing -> Nothing\n        ) (toArray binds)',
  'traverse (\\(Tuple ident val) -> case extractUncurriedAbs val of\n            Just abs -> Just { ident: localId (Just ident) lvl, args: abs.args, body: abs.body, fvs: abs.fvs, originalVal: val }\n            Nothing -> Nothing\n        ) (toArray binds)'
);

// Patch 2: Inject types in local LetRec
code = code.replace(
  'PhpAssign newName (PhpFunction useVarsOuter (map (\\n -> { name: n, type_: "" }) fn.args) "" (initVarStmts <> innerFuncBody))',
  'let\n                    types = extractFuncType fn.originalVal\n                    argsWithTypes = zipArgsWithTypes fn.args types\n                    retType = getRetType (Array.length fn.args) types\n                  in PhpAssign newName (PhpFunction useVarsOuter argsWithTypes retType (initVarStmts <> innerFuncBody))'
);

// Patch 3: Capture originalVal in global LetRec
code = code.replace(
  'mutRecBinds = traverse (\\(Tuple (Ident name) val) -> map (\\abs -> { ident: modPrefix <> name, args: abs.args, body: abs.body, fvs: abs.fvs }) (extractUncurriedAbs val)) group.bindings',
  'mutRecBinds = traverse (\\(Tuple (Ident name) val) -> map (\\abs -> { ident: modPrefix <> name, args: abs.args, body: abs.body, fvs: abs.fvs, originalVal: val }) (extractUncurriedAbs val)) group.bindings'
);

// Patch 4: Inject types in global LetRec
code = code.replace(
  '{ identifier: fn.ident, expression: PhpNativeFunction fn.ident (map (\\name -> { name: name, type_: "" }) fn.args) "" (initVarStmts <> innerFuncBody) }',
  'let\n                               types = extractFuncType fn.originalVal\n                               argsWithTypes = zipArgsWithTypes fn.args types\n                               retType = getRetType (Array.length fn.args) types\n                             in\n                             { identifier: fn.ident, expression: PhpNativeFunction fn.ident argsWithTypes retType (initVarStmts <> innerFuncBody) }'
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
