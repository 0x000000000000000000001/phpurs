const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  `                  _ -> Nothing
            PhpGlobalVar Nothing ident ->`,
  `                  _ -> Just (PhpCall (PhpGlobalVar Nothing ("DEBUG_FALLTHROUGH_LOCAL_" <> (if isLocal then "TRUE" else "FALSE") <> "_FUNC_" <> funcName)) [])
            PhpGlobalVar Nothing ident ->`
);

code = code.replace(
  `                  _ -> Nothing
            _ -> Nothing`,
  `                  _ -> Just (PhpCall (PhpGlobalVar Nothing ("DEBUG_FALLTHROUGH_GLOBAL_FUNC_" <> funcName)) [])
            _ -> Nothing`
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
