const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

// 1. Add extractFuncType and getExprType to CodeGen.purs
const helpers = `
extractFuncType :: TcoExpr -> Maybe { fArgs :: Array ExprType, fRet :: ExprType }
extractFuncType (TcoExpr _ (Typed ty inner)) =
  let
    flattenFuncType acc (Func args ret) = flattenFuncType (acc <> args) ret
    flattenFuncType acc ret = { fArgs: acc, fRet: ret }
    
    getFunc (Func a r) = Just (flattenFuncType a r)
    getFunc _ = extractFuncType inner
  in getFunc ty
extractFuncType _ = Nothing

getExprType :: TcoExpr -> ExprType
getExprType (TcoExpr _ syn) = case syn of
  Typed ty _ -> ty
  _ -> Any

zipArgsWithTypes :: Array String -> Maybe { fArgs :: Array ExprType, fRet :: ExprType } -> Array { name :: String, type_ :: String }
zipArgsWithTypes names mbTypes =
  case mbTypes of
    Just { fArgs } ->
      Array.mapWithIndex (\\i name ->
        let t = fromMaybe Any (Array.index fArgs i)
        in { name: name, type_: exprTypeToPhpType t }
      ) names
    Nothing ->
      map (\\name -> { name: name, type_: "" }) names

getRetType :: Int -> Maybe { fArgs :: Array ExprType, fRet :: ExprType } -> String
getRetType arity mbTypes =
  case mbTypes of
    Just { fArgs, fRet } ->
      if arity < Array.length fArgs then "" else exprTypeToPhpType fRet
    Nothing -> ""
`;

code = code.replace(
  'exprTypeToPhpType :: ExprType -> String',
  helpers + '\nexprTypeToPhpType :: ExprType -> String'
);

// 2. Modify exprTypeToPhpType to return "" for Any/unknown
code = code.replace(
  '  _ -> "mixed"',
  '  _ -> ""'
);

// 3. Update PhpNativeFunction calls
// There are multiple places. I'll use regex to replace them all safely.
// We need to parse where extractUncurriedAbs is called.
// It's returned as `Just fn ->` or `Just abs ->` where `fn.args` or `abs.args` are `Array String`.
// However, extractUncurriedAbs returns `{ args, body, fvs }`. It doesn't know about `expr` types!
// So we need to call `extractFuncType val` or `extractFuncType expr`.

// Wait, doing this via JS regex is risky. Let's do it via node script and AST walking or carefully replace line by line.

fs.writeFileSync('src/Phpurs/CodeGen_patch_helpers.purs', code);
