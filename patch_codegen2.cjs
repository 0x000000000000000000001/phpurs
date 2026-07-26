const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen_patch_helpers.purs', 'utf8');

// Replace lines exactly
code = code.replace(
  '{ identifier: fn.ident, expression: PhpNativeFunction fn.ident fn.args (initVarStmts <> innerFuncBody) }',
  `{ identifier: fn.ident, expression: PhpNativeFunction fn.ident (map (\\name -> { name: name, type_: "" }) fn.args) "" (initVarStmts <> innerFuncBody) }`
);

code = code.replace(
  'in [ { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) fn.args (res.stmts <> [ PhpReturn res.expr ]) } ]',
  `let
                                 types = extractFuncType expr
                                 argsWithTypes = zipArgsWithTypes fn.args types
                                 retType = getRetType (Array.length fn.args) types
                             in [ { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType (res.stmts <> [ PhpReturn res.expr ]) } ]`
);

code = code.replace(
  'in [ { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) fn.args (res.stmts <> [ PhpReturn res.expr ]) } ]',
  `let
                              types = extractFuncType expr
                              argsWithTypes = zipArgsWithTypes fn.args types
                              retType = getRetType (Array.length fn.args) types
                           in [ { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType (res.stmts <> [ PhpReturn res.expr ]) } ]`
);

code = code.replace(
  'nativeFunc = { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) args [ PhpReturn callExpr ] }',
  `types = extractFuncType expr
                                 argsWithTypes = zipArgsWithTypes args types
                                 retType = getRetType arity types
                                 nativeFunc = { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType [ PhpReturn callExpr ] }`
);

// We also need to patch PhpFunction.
code = code.replace(
  'PhpFunction captures args stmts ->',
  'PhpFunction captures args retType stmts ->'
);
// Where is PhpFunction used?
// Usually from `translateExprImpl` for `Abs`?
// Let's check where PhpFunction is used in CodeGen!

fs.writeFileSync('src/Phpurs/CodeGen_patch_helpers.purs', code);
