const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  `                             let res = translateExprImpl modNameStr recVars Map.empty Map.empty (Just (modPrefix <> name)) [] true 0 fn.body
                                 types = extractFuncType expr
                                 argsWithTypes = zipArgsWithTypes fn.args types
                                 retType = getRetType (Array.length fn.args) types
                             in [ { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType (res.stmts <> [ PhpReturn res.expr ]) } ]`,
  `                             let res = translateExprImpl modNameStr recVars Map.empty Map.empty (Just (modPrefix <> name)) [] true 0 fn.body
                                 types = extractFuncType expr
                                 argsWithTypes = zipArgsWithTypes fn.args types
                                 retType = getRetType (Array.length fn.args) types
                             in [ { identifier: modPrefix <> name, expression: PhpNativeFunction (modPrefix <> name) argsWithTypes retType (res.stmts <> [ PhpReturn res.expr ]) } ]`
); // I'll just regex replace '                              argsWithTypes' with '                               argsWithTypes'

code = code.replace(/                              argsWithTypes/g, '                               argsWithTypes');
code = code.replace(/                              retType/g, '                               retType');

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
