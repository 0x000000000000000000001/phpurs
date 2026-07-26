const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

code = code.replace(
  `    moduleArities = foldl (\\acc group ->
        foldl (\\acc2 (Tuple (Ident name) val) ->
            case extractUncurriedAbs val of
                Just fn -> Map.insert (modPrefix <> name) (Array.length fn.args) acc2
                Nothing -> acc2
        ) acc group.bindings
      ) Map.empty tcoBindings

    rawDecls = Array.concatMap (\\decl ->`,
  `    rawDecls = Array.concatMap (\\decl ->`
);

code = code.replace(
  `      mod.bindings

    decls = Array.concatMap`,
  `      mod.bindings

    moduleArities = foldl (\\acc group ->
        foldl (\\acc2 (Tuple (Ident name) val) ->
            case extractUncurriedAbs val of
                Just fn -> Map.insert (modPrefix <> name) (Array.length fn.args) acc2
                Nothing -> acc2
        ) acc group.bindings
      ) Map.empty tcoBindings

    decls = Array.concatMap`
);

fs.writeFileSync('src/Phpurs/CodeGen.purs', code);
