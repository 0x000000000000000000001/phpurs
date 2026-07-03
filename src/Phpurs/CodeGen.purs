module Phpurs.CodeGen where

import Prelude

import Phpurs.CoreFn (Expr(..), Bind(..), Module(..), Literal(..), CaseAlternative(..), Binder(..), Decl)
import Phpurs.PhpAst (PhpExpr(..), PhpDecl, PhpFile)
import Data.Maybe (Maybe(..))
import Data.String (joinWith)
import Data.Array (nub, concatMap, filter, mapWithIndex, foldl, index, length, uncons, sortBy)
import Data.Foldable (elem)
import Phpurs.Pattern as P
import Phpurs.Printer as Printer

weightExpr :: Expr -> Int
weightExpr = case _ of
  Lambda _ _ -> 0
  Variable _ _ -> 0
  Literal (IntLiteral _) -> 0
  Literal (NumberLiteral _) -> 0
  Literal (StringLiteral _) -> 0
  Literal (CharLiteral _) -> 0
  Literal (BooleanLiteral _) -> 0
  Literal (ArrayLiteral xs) -> foldl (+) 0 (map weightExpr xs)
  Literal (ObjectLiteral fields) -> foldl (+) 0 (map (\f -> weightExpr f.value) fields)
  Call f x -> 1 + weightExpr f + weightExpr x
  Case _ _ -> 10
  Let _ e -> weightExpr e
  Constructor _ _ _ -> 0
  Accessor _ e -> weightExpr e
  ObjectUpdate e updates -> weightExpr e + foldl (+) 0 (map (\u -> weightExpr u.value) updates)
  Unsupported _ -> 0

sortRecBinds :: forall r. Array { identifier :: String, expression :: Expr | r } -> Array { identifier :: String, expression :: Expr | r }
sortRecBinds = sortBy (\a b -> compare (weightExpr a.expression) (weightExpr b.expression))

unsafeIndex :: forall a. Array a -> Int -> a
unsafeIndex arr i = case index arr i of
  Just x -> x
  Nothing -> unsafeIndex arr i

freeVars :: Expr -> Array String
freeVars = case _ of
  Lambda arg body -> filter (_ /= arg) (freeVars body)
  Variable mbMod ident ->
    case mbMod of
      Just mod -> []
      Nothing -> [ident]
  Call abs arg -> nub (freeVars abs <> freeVars arg)
  Literal lit -> case lit of
    ArrayLiteral arr -> nub (concatMap freeVars arr)
    ObjectLiteral arr -> nub (concatMap (\item -> freeVars item.value) arr)
    _ -> []
  Case exprs alts -> 
    let 
      exprVars = concatMap freeVars exprs
      altVars = concatMap freeVarsAlt alts
    in nub (exprVars <> altVars)
  Let binds expr ->
    let
      bindVars = concatMap (\b -> case b of
        NonRec ident _ -> [ident]
        Rec rBinds -> map _.identifier rBinds
      ) binds
      bodyVars = concatMap (\b -> case b of
        NonRec _ e -> freeVars e
        Rec rBinds -> concatMap (\rb -> freeVars rb.expression) rBinds
      ) binds <> freeVars expr
    in filter (\v -> not (v `elem` bindVars)) bodyVars
  Constructor _ _ _ -> []
  Accessor _ expr -> freeVars expr
  ObjectUpdate expr updates -> nub (freeVars expr <> concatMap (\u -> freeVars u.value) updates)
  Unsupported _ -> []

freeVarsAlt :: CaseAlternative -> Array String
freeVarsAlt (CaseAlternative { binders, expression }) =
  let
    bVars = concatMap binderVars binders
    eVars = freeVars expression
  in filter (\v -> not (v `elem` bVars)) eVars

binderVars :: Binder -> Array String
binderVars = case _ of
  NullBinder -> []
  LiteralBinder lit -> case lit of
    ArrayLiteral arr -> concatMap binderVars arr
    ObjectLiteral arr -> concatMap (\item -> binderVars item.value) arr
    _ -> []
  VarBinder ident -> [ident]
  ConstructorBinder _ _ _ bs -> concatMap binderVars bs
  NamedBinder ident b -> [ident] <> binderVars b

getArgs :: Expr -> { args :: Array String, body :: Expr }
getArgs (Lambda arg body) = 
  let 
    next = getArgs body
    rename a = if length (filter (\x -> x == a) next.args) > 0 then rename (a <> "_") else a
    arg' = rename arg
  in { args: [arg'] <> next.args, body: next.body }
getArgs e = { args: [], body: e }

isTailCall :: String -> Int -> Expr -> Maybe (Array Expr)
isTailCall ident arity expr =
  let
    collectArgs (Call f arg) = 
      case collectArgs f of
        Just args -> Just (args <> [arg])
        Nothing -> Nothing
    collectArgs (Variable _ name) | name == ident = Just []
    collectArgs _ = Nothing
  in case collectArgs expr of
    Just args | length args == arity -> Just args
    _ -> Nothing

translateTailCall :: Array String -> String -> Array String -> Expr -> Array PhpExpr
translateTailCall recVars ident args expr = case expr of
  Let binds body ->
    let
      stmts = concatMap (\b -> case b of
        NonRec id e -> [PhpAssign id (translateExprImpl recVars Nothing e)]
        Rec rBinds -> map (\rb -> 
          let extracted = getArgs rb.expression
          in if length extracted.args > 0 then
               PhpAssign rb.identifier (translateExprImpl recVars (Just { ident: rb.identifier, args: extracted.args }) rb.expression)
             else
               PhpAssign rb.identifier (translateExprImpl recVars Nothing rb.expression)
        ) (sortRecBinds rBinds)
      ) binds
    in stmts <> translateTailCall recVars ident args body
  Case caseExprs alts ->
    let
      subjVars = mapWithIndex (\i _ -> PhpVar ("__case_" <> show i)) caseExprs
      exprAssigns = mapWithIndex (\i e -> PhpAssign ("__case_" <> show i) (translateExprImpl recVars Nothing e)) caseExprs
      
      altStmts = map (\(CaseAlternative alt) ->
        let
          bindResults = mapWithIndex (\i b -> P.bindBinder (subjVars `unsafeIndex` i) b) alt.binders
          combined = foldl P.combineResult { cond: PhpBoolean true, assigns: [] } bindResults
        in PhpIf combined.cond (combined.assigns <> translateTailCall recVars ident args alt.expression) []
      ) alts
    in exprAssigns <> altStmts <> [PhpThrow "Pattern match failure"]
  _ ->
    case isTailCall ident (length args) expr of
      Just callArgs ->
        let
          tempAssigns = mapWithIndex (\i argExpr -> PhpAssign ("__tco_tmp_" <> show i) (translateExprImpl recVars Nothing argExpr)) callArgs
          reassigns = mapWithIndex (\i argName -> PhpAssign argName (PhpVar ("__tco_tmp_" <> show i))) args
        in tempAssigns <> reassigns <> [PhpContinue]
      Nothing ->
        [PhpReturn (translateExprImpl recVars Nothing expr)]

translateExpr :: Expr -> PhpExpr
translateExpr = translateExprImpl [] Nothing

formatFv :: Array String -> String -> String
formatFv recVars f = if f `elem` recVars || Printer.isUppercase f then "&$" <> Printer.sanitize f else "$" <> Printer.sanitize f

translateExprToStmts :: Array String -> Expr -> Array PhpExpr
translateExprToStmts recVars expr = case expr of
  Let binds body ->
    let
      newRecVars = concatMap (\bind -> case bind of
        Rec rbs -> map (\rb -> rb.identifier) rbs
        _ -> []
      ) binds
      nextRecVars = recVars <> newRecVars
      stmts = concatMap (\bind -> case bind of
        NonRec ident e -> [PhpAssign ident (translateExprImpl nextRecVars Nothing e)]
        Rec rbs -> map (\rb -> 
          let extracted = getArgs rb.expression
          in if length extracted.args > 0 then
               PhpAssign rb.identifier (translateExprImpl nextRecVars (Just { ident: rb.identifier, args: extracted.args }) rb.expression)
             else
               PhpAssign rb.identifier (translateExprImpl nextRecVars Nothing rb.expression)
        ) (sortRecBinds rbs)
      ) binds
    in stmts <> translateExprToStmts nextRecVars body
  Case caseExprs alts ->
    let
      exprAssigns = mapWithIndex (\i e -> PhpAssign ("__case_" <> show i) (translateExprImpl recVars Nothing e)) caseExprs
      subjVars = mapWithIndex (\i _ -> PhpVar ("__case_" <> show i)) caseExprs
      altStmts = map (\(CaseAlternative alt) ->
        let
          bindResults = mapWithIndex (\i b -> P.bindBinder (subjVars `unsafeIndex` i) b) alt.binders
          combined = foldl P.combineResult { cond: PhpBoolean true, assigns: [] } bindResults
        in PhpIf combined.cond (combined.assigns <> translateExprToStmts recVars alt.expression) []
      ) alts
    in exprAssigns <> altStmts <> [PhpThrow "Pattern match failure"]
  _ ->
    [PhpReturn (translateExprImpl recVars Nothing expr)]

translateExprImpl :: Array String -> Maybe { ident :: String, args :: Array String } -> Expr -> PhpExpr
translateExprImpl recVars tcoCtx expr = case expr of
  Lambda _ _ -> 
    let
      extracted = getArgs expr
      fvs = nub (freeVars expr)
      formattedFvs = map (formatFv recVars) fvs
    in PhpFunction formattedFvs extracted.args (
      case tcoCtx of
        Just _ -> [PhpReturn (translateExprImpl recVars tcoCtx extracted.body)]
        Nothing -> translateExprToStmts recVars extracted.body
    )
  _ -> case tcoCtx of
    Just ctx ->
      let
        loopBody = translateTailCall recVars ctx.ident ctx.args expr
        fvs = filter (\v -> not (v `elem` ctx.args)) (nub (freeVars expr))
        formattedFvs = map (formatFv recVars) fvs
      in PhpTcoLoop formattedFvs ctx.args loopBody
    Nothing -> case expr of
      Variable mbMod ident ->
        case mbMod of
          Just mod -> PhpGlobalVar (joinWith "_" mod <> "_" <> ident)
          Nothing -> PhpVar ident
      Call _ _ -> 
        let
          collectCall (Call f x) =
            let c = collectCall f
            in { fn: c.fn, args: c.args <> [x] }
          collectCall other = { fn: other, args: [] }
          extracted = collectCall expr
          translatedFn = translateExprImpl recVars Nothing extracted.fn
          translatedArgs = map (translateExprImpl recVars Nothing) extracted.args
        in PhpCall translatedFn translatedArgs
      Literal lit -> translateLiteral lit
      Case exprs alts -> 
        let
          fvs = nub (freeVars (Case exprs alts))
          exprAssigns = mapWithIndex (\i e -> PhpAssign ("__case_" <> show i) (translateExprImpl recVars Nothing e)) exprs
          subjVars = mapWithIndex (\i _ -> PhpVar ("__case_" <> show i)) exprs
          
          altStmts = map (\(CaseAlternative alt) ->
            let
              bindResults = mapWithIndex (\i b -> P.bindBinder (subjVars `unsafeIndex` i) b) alt.binders
              combined = foldl P.combineResult { cond: PhpBoolean true, assigns: [] } bindResults
            in PhpIf combined.cond (combined.assigns <> [PhpReturn (translateExprImpl recVars Nothing alt.expression)]) []
          ) alts
        in PhpClosure (map (formatFv recVars) fvs) (exprAssigns <> altStmts <> [PhpThrow "Pattern match failure"])
      Let binds body ->
        let
          fvs = nub (freeVars (Let binds body))
          newRecVars = concatMap (\bind -> case bind of
            Rec rbs -> map (\rb -> rb.identifier) rbs
            _ -> []
          ) binds
          nextRecVars = recVars <> newRecVars
          stmts = concatMap (\bind -> case bind of
            NonRec ident e -> [PhpAssign ident (translateExprImpl nextRecVars Nothing e)]
            Rec rbs -> map (\rb -> 
              let extracted = getArgs rb.expression
              in if length extracted.args > 0 then
                   PhpAssign rb.identifier (translateExprImpl nextRecVars (Just { ident: rb.identifier, args: extracted.args }) rb.expression)
                 else
                   PhpAssign rb.identifier (translateExprImpl nextRecVars Nothing rb.expression)
            ) (sortRecBinds rbs)
          ) binds
          returnStmt = PhpReturn (translateExprImpl nextRecVars Nothing body)
        in PhpClosure (map (formatFv recVars) fvs) (stmts <> [returnStmt])
      Constructor typeName constructorName fieldNames ->
        let
          body = PhpAssocArray
            [ { key: "tag", value: PhpString constructorName }
            , { key: "values", value: PhpArray (map PhpVar fieldNames) }
            ]
        in if length fieldNames == 0 then body else PhpFunction [] fieldNames [PhpReturn body]
      Accessor field e -> PhpPropertyAccess (translateExprImpl recVars Nothing e) field
      ObjectUpdate e updates ->
        let
          updates' = map (\u -> { key: u.key, value: translateExprImpl recVars Nothing u.value }) updates
        in PhpObjectUpdate (translateExprImpl recVars Nothing e) updates'
      Unsupported t -> PhpRaw $ "\"/* Unsupported: " <> t <> " */\""
      _ -> PhpRaw "/* Unknown */"

translateLiteral :: Literal Expr -> PhpExpr
translateLiteral = case _ of
  IntLiteral i -> PhpInt i
  NumberLiteral n -> PhpNumber n
  StringLiteral s -> PhpString s
  CharLiteral c -> PhpString c
  BooleanLiteral b -> PhpBoolean b
  ArrayLiteral arr -> PhpArray (map (translateExprImpl [] Nothing) arr)
  ObjectLiteral arr -> PhpAssocArray (map (\item -> { key: item.key, value: translateExprImpl [] Nothing item.value }) arr)

translateDecl :: Array String -> Bind -> Array PhpDecl
translateDecl moduleName bind = case bind of
  NonRec ident expr ->
    let prefix = joinWith "_" moduleName <> "_"
    in [{ identifier: prefix <> ident, expression: translateExpr expr }]
  Rec rBinds ->
    map (\rb -> 
      let
        prefix = joinWith "_" moduleName <> "_"
        extracted = getArgs rb.expression
      in if length extracted.args > 0 then
           { identifier: prefix <> rb.identifier, expression: translateExprImpl [] (Just { ident: rb.identifier, args: extracted.args }) rb.expression }
         else
           { identifier: prefix <> rb.identifier, expression: translateExpr rb.expression }
    ) (sortRecBinds rBinds)

translate :: Module -> PhpFile
translate (Module mod) =
  let
    moduleNameStr = joinWith "." mod.moduleName
  in { namespace: mod.moduleName
  , decls: concatMap (translateDecl mod.moduleName) mod.decls
  , imports: mod.imports
  }
