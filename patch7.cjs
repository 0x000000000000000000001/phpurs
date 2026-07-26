const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  /Just \(PhpCall \(PhpGlobalVar Nothing \("DEBUG_FALLTHROUGH_LOCAL_" <> \(if isLocal then "TRUE" else "FALSE"\) <> "_FUNC_" <> \(if funcName == "Test_RBTree_insert" then funcName else "NOT_INSERT"\)\)\) \[\]\)/g,
  `if funcName == "Test_RBTree_insert" then Just (PhpCall (PhpGlobalVar Nothing ("DEBUG_FALLTHROUGH_LOCAL_" <> (if isLocal then "TRUE" else "FALSE") <> "_FUNC_" <> funcName)) []) else Nothing`
);

code = code.replace(
  /Just \(PhpCall \(PhpGlobalVar Nothing \("DEBUG_FALLTHROUGH_GLOBAL_FUNC_" <> funcName\)\)\) \[\]\)/g,
  `if funcName == "Test_RBTree_insert" then Just (PhpCall (PhpGlobalVar Nothing ("DEBUG_FALLTHROUGH_GLOBAL_FUNC_" <> funcName)) []) else Nothing`
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
