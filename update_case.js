const fs = require('fs');

let content = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

// Replace Case alternative processing in translateExprImpl
const exprCaseRegex = /Case exprs alts ->[\s\S]*?(?=Let binds body ->)/;
const exprCaseReplacement = `Case exprs alts -> 
        let
          processCaseExpr acc e =
            let res = translateExprImpl env currentModStr moduleName recVars Nothing acc.nextId e
            in { assigns: acc.assigns <> res.stmts <> [PhpAssign ("__case_" <> show acc.i) res.expr]
               , vars: acc.vars <> [PhpVar ("__case_" <> show acc.i)]
               , nextId: res.nextId
               , i: acc.i + 1
               }
          caseRes = foldl processCaseExpr { assigns: [], vars: [], nextId: nextId, i: 0 } exprs
          
          resultVar = "__case_res_" <> show caseRes.nextId
          matchedVar = "__match_" <> show caseRes.nextId
          nextIdAfterVar = caseRes.nextId + 1
          
          processAlt acc (CaseAlternative alt) =
            let
              bindResults = mapWithIndex (\\i b -> P.bindBinder (caseRes.vars \`unsafeIndex\` i) b) alt.binders
              combined = foldl P.combineResult { cond: PhpBoolean true, assigns: [] } bindResults
              
              processGuard gAcc e =
                let
                   guardRes = translateExprImpl env currentModStr moduleName recVars Nothing gAcc.nextId e.guard
                   bodyRes = translateExprImpl env currentModStr moduleName recVars Nothing guardRes.nextId e.expression
                   
                   -- We use an if for the guard
                   guardIf = PhpIf guardRes.expr (bodyRes.stmts <> [PhpAssign resultVar bodyRes.expr, PhpAssign matchedVar (PhpBoolean true)]) []
                in { stmts: gAcc.stmts <> guardRes.stmts <> [guardIf], nextId: bodyRes.nextId }
                
              guardsRes = foldl processGuard { stmts: [], nextId: acc.nextId } alt.expressions
              
              -- The alternative block
              altBlock = PhpIf combined.cond (combined.assigns <> guardsRes.stmts) []
              
              -- We only run this alternative if we haven't matched yet
              finalAlt = PhpIf (PhpUnary "!" (PhpVar matchedVar)) [altBlock] []
              
            in { stmts: acc.stmts <> [finalAlt], nextId: guardsRes.nextId }
               
          altRes = foldl processAlt { stmts: [], nextId: nextIdAfterVar } alts
        in { stmts: caseRes.assigns <> [PhpAssign resultVar (PhpRaw "null"), PhpAssign matchedVar (PhpBoolean false)] <> altRes.stmts <> [PhpIf (PhpUnary "!" (PhpVar matchedVar)) [PhpThrow "Pattern match failure"] []]
           , expr: PhpVar resultVar
           , nextId: altRes.nextId
           }
      `;

content = content.replace(exprCaseRegex, exprCaseReplacement);

// Replace Case alternative processing in translateTailCall
const tailCaseRegex = /Case exprs alts ->[\s\S]*?(?=_ ->)/;
const tailCaseReplacement = `Case exprs alts -> 
      let
        processCaseExpr acc e =
          let res = translateExprImpl env currentModStr moduleName recVars Nothing acc.nextId e
          in { assigns: acc.assigns <> res.stmts <> [PhpAssign ("__case_" <> show acc.i) res.expr]
             , vars: acc.vars <> [PhpVar ("__case_" <> show acc.i)]
             , nextId: res.nextId
             , i: acc.i + 1
             }
        caseRes = foldl processCaseExpr { assigns: [], vars: [], nextId: nextId, i: 0 } exprs
        
        matchedVar = "__match_" <> show caseRes.nextId
        
        processAlt acc (CaseAlternative alt) =
          let
            bindResults = mapWithIndex (\\i b -> P.bindBinder (caseRes.vars \`unsafeIndex\` i) b) alt.binders
            combined = foldl P.combineResult { cond: PhpBoolean true, assigns: [] } bindResults
            
            processGuard gAcc e =
              let
                 guardRes = translateExprImpl env currentModStr moduleName recVars Nothing gAcc.nextId e.guard
                 bodyRes = translateTailCall env currentModStr moduleName recVars ident args guardRes.nextId e.expression
                 
                 guardIf = PhpIf guardRes.expr (bodyRes.stmts <> [PhpAssign matchedVar (PhpBoolean true)]) []
              in { stmts: gAcc.stmts <> guardRes.stmts <> [guardIf], nextId: bodyRes.nextId }
              
            guardsRes = foldl processGuard { stmts: [], nextId: acc.nextId } alt.expressions
            
            altBlock = PhpIf combined.cond (combined.assigns <> guardsRes.stmts) []
            finalAlt = PhpIf (PhpUnary "!" (PhpVar matchedVar)) [altBlock] []
            
          in { stmts: acc.stmts <> [finalAlt], nextId: guardsRes.nextId }
             
        altRes = foldl processAlt { stmts: [], nextId: caseRes.nextId + 1 } alts
      in { stmts: caseRes.assigns <> [PhpAssign matchedVar (PhpBoolean false)] <> altRes.stmts <> [PhpIf (PhpUnary "!" (PhpVar matchedVar)) [PhpThrow "Pattern match failure"] []]
         , nextId: altRes.nextId
         }
    `;

content = content.replace(tailCaseRegex, tailCaseReplacement);

fs.writeFileSync('src/Phpurs/CodeGen.purs', content);
