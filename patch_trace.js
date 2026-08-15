const fs = require('fs');
const file = '/Users/0x1/Documents/htdocs/phpurs/phpurs/src/Phpurs/CodeGen.purs';
let content = fs.readFileSync(file, 'utf-8');

const target = `translateExprImpl modNameStr recVars namedBound bound mbNamedVar loopCtx isTail nextId tcoExpr@(TcoExpr _ syntax) = case syntax of`;
const replacement = `translateExprImpl modNameStr recVars namedBound bound mbNamedVar loopCtx isTail nextId tcoExpr@(TcoExpr _ syntax) = 
  let
    _ = if modNameStr == "Phpurs.PhpAst" then
          unsafePerformEffect $ Effect.Console.log ("Translating: " <> case syntax of
            Var _ -> "Var"
            Local _ _ -> "Local"
            Lit _ -> "Lit"
            App _ _ -> "App"
            Abs _ _ -> "Abs"
            UncurriedApp _ _ -> "UncurriedApp"
            UncurriedAbs _ _ -> "UncurriedAbs"
            UncurriedEffectApp _ _ -> "UncurriedEffectApp"
            UncurriedEffectAbs _ _ -> "UncurriedEffectAbs"
            Accessor _ _ -> "Accessor"
            Update _ _ -> "Update"
            CtorDef _ _ _ _ -> "CtorDef"
            CtorSaturated _ _ _ _ _ -> "CtorSaturated"
            Let _ _ _ _ -> "Let"
            LetRec _ _ _ -> "LetRec"
            Branch _ _ -> "Branch"
            EffectBind _ _ _ _ -> "EffectBind"
            EffectPure _ -> "EffectPure"
            EffectDefer _ -> "EffectDefer"
            PrimOp _ -> "PrimOp"
            PrimEffect _ -> "PrimEffect"
            PrimUndefined -> "PrimUndefined"
            Typed _ _ -> "Typed"
          )
        else unit
  in case syntax of`;

content = content.replace(target, replacement);

const targetImport = `import Effect.Console as Console`;
const replacementImport = `import Effect.Console as Console\nimport Effect.Console (log) as Effect.Console`;

if (!content.includes('import Effect.Console (log)')) {
  content = content.replace(targetImport, replacementImport);
}

fs.writeFileSync(file, content);
