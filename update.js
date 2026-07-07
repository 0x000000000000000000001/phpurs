const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

// Update translateTailCall
code = code.replace(
/            processGuard \{ guard, expression \} innerAcc =[\s\S]*?in \{ stmts: \[PhpIf combined\.cond \(combined\.assigns <> guardsRes\.stmts\) acc\.stmts\], nextId: guardsRes\.nextId \}/,
`            isUnguarded = case alt.expressions of
              [{ guard: Literal (BooleanLiteral true), expression: e }] -> Just e
              _ -> Nothing

          in case isUnguarded of
            Just e ->
              let bodyRes = translateTailCall env currentModStr moduleName recVars ident args acc.nextId e
              in { stmts: [PhpIf combined.cond (combined.assigns <> bodyRes.stmts) acc.stmts], nextId: bodyRes.nextId }
            Nothing ->
              let
                matchedVar = "__matched_" <> show acc.nextId
                nextIdAfterVar = acc.nextId + 1
                
                processGuard { guard, expression } innerAcc =
                  case guard of
                    Literal (BooleanLiteral true) ->
                      let bodyRes = translateTailCall env currentModStr moduleName recVars ident args innerAcc.nextId expression
                      in { stmts: innerAcc.stmts <> bodyRes.stmts <> [PhpAssign matchedVar (PhpBoolean true)], nextId: bodyRes.nextId }
                    _ ->
                      let
                        guardRes = translateExprImpl env currentModStr moduleName recVars Nothing innerAcc.nextId guard
                        bodyRes = translateTailCall env currentModStr moduleName recVars ident args guardRes.nextId expression
                      in { stmts: innerAcc.stmts <> guardRes.stmts <> [PhpIf guardRes.expr (bodyRes.stmts <> [PhpAssign matchedVar (PhpBoolean true)]) []], nextId: bodyRes.nextId }
                
                guardsRes = foldl processGuard { stmts: [], nextId: nextIdAfterVar } alt.expressions
              in { stmts: [PhpAssign matchedVar (PhpBoolean false), PhpIf combined.cond (combined.assigns <> guardsRes.stmts) [], PhpIf (PhpNot (PhpVar matchedVar)) acc.stmts []], nextId: guardsRes.nextId }`);

// Update translateExprImpl
code = code.replace(
/              processGuard \{ guard, expression \} innerAcc =[\s\S]*?in \{ stmts: \[PhpIf combined\.cond \(combined\.assigns <> guardsRes\.stmts\) acc\.stmts\], nextId: guardsRes\.nextId \}/,
`              isUnguarded = case alt.expressions of
                [{ guard: Literal (BooleanLiteral true), expression: e }] -> Just e
                _ -> Nothing

            in case isUnguarded of
              Just e ->
                let bodyRes = translateExprImpl env currentModStr moduleName recVars Nothing acc.nextId e
                in { stmts: [PhpIf combined.cond (combined.assigns <> bodyRes.stmts <> [PhpAssign resultVar bodyRes.expr]) acc.stmts], nextId: bodyRes.nextId }
              Nothing ->
                let
                  matchedVar = "__matched_" <> show acc.nextId
                  nextIdAfterVar = acc.nextId + 1
                  
                  processGuard { guard, expression } innerAcc =
                    case guard of
                      Literal (BooleanLiteral true) ->
                        let bodyRes = translateExprImpl env currentModStr moduleName recVars Nothing innerAcc.nextId expression
                        in { stmts: innerAcc.stmts <> bodyRes.stmts <> [PhpAssign resultVar bodyRes.expr, PhpAssign matchedVar (PhpBoolean true)], nextId: bodyRes.nextId }
                      _ ->
                        let
                          guardRes = translateExprImpl env currentModStr moduleName recVars Nothing innerAcc.nextId guard
                          bodyRes = translateExprImpl env currentModStr moduleName recVars Nothing guardRes.nextId expression
                        in { stmts: innerAcc.stmts <> guardRes.stmts <> [PhpIf guardRes.expr (bodyRes.stmts <> [PhpAssign resultVar bodyRes.expr, PhpAssign matchedVar (PhpBoolean true)]) []], nextId: bodyRes.nextId }
                  
                  guardsRes = foldl processGuard { stmts: [], nextId: nextIdAfterVar } alt.expressions
                in { stmts: [PhpAssign matchedVar (PhpBoolean false), PhpIf combined.cond (combined.assigns <> guardsRes.stmts) [], PhpIf (PhpNot (PhpVar matchedVar)) acc.stmts []], nextId: guardsRes.nextId }`);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
