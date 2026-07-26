const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

// Fix 1: let res = ... let types = ...
code = code.replace(
  `                             let res = translateExprImpl modNameStr recVars Map.empty Map.empty (Just (modPrefix <> name)) [] true 0 fn.body
                             let
                                 types = extractFuncType expr`,
  `                             let res = translateExprImpl modNameStr recVars Map.empty Map.empty (Just (modPrefix <> name)) [] true 0 fn.body
                                 types = extractFuncType expr`
);

// Fix 2: let res = ... let types = ...
code = code.replace(
  `                           let res = translateExprImpl modNameStr [] Map.empty Map.empty (Just (modPrefix <> name)) [] false 0 fn.body
                           let
                              types = extractFuncType expr`,
  `                           let res = translateExprImpl modNameStr [] Map.empty Map.empty (Just (modPrefix <> name)) [] false 0 fn.body
                               types = extractFuncType expr`
);

// Fix 3: Let block for Abs, UncurriedAbs, UncurriedEffectAbs
code = code.replace(
  `      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] true nextId body
      types = extractFuncType tcoExpr`,
  `      resBody = translateExprImpl modNameStr recVars namedBound bound Nothing [] true nextId body
      types = extractFuncType tcoExpr` // Wait, this is in a let block? Yes. But did I break indentation? Let's check.
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
