const fs = require('fs');
const file = '/Users/0x1/Documents/htdocs/phpurs/phpurs/src/Phpurs/CodeGen.purs';
let content = fs.readFileSync(file, 'utf-8');

const target = `            tcoBinds = map (\\(Tuple k v) -> Tuple k (Tco.analyze env' v)) group.bindings`;
const replacement = `            tcoBinds = map (\\(Tuple k v) -> 
              let
                _ = if modNameStr == "Phpurs_PhpAst" then unsafePerformEffect (Console.log ("Tco.analyze START for " <> unwrap k)) else unit
                res = Tco.analyze env' v
                _2 = if modNameStr == "Phpurs_PhpAst" then unsafePerformEffect (Console.log ("Tco.analyze END for " <> unwrap k)) else unit
              in Tuple k res
            ) group.bindings`;

content = content.replace(target, replacement);

const targetDecls = `    decls = Array.concatMap`;
const replacementDecls = `    _declsLog = if modNameStr == "Phpurs_PhpAst" then unsafePerformEffect (Console.log "Tco.analyze finished all bindings") else unit
    decls = Array.concatMap`;

content = content.replace(targetDecls, replacementDecls);

fs.writeFileSync(file, content);
