const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  /DEBUG_FALLTHROUGH_LOCAL_" <> \(if isLocal then "TRUE" else "FALSE"\) <> "_FUNC_" <> funcName/g,
  `DEBUG_FALLTHROUGH_LOCAL_" <> (if isLocal then "TRUE" else "FALSE") <> "_FUNC_" <> (if funcName == "Test_RBTree_insert" then funcName else "NOT_INSERT")`
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
