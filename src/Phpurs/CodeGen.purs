module Phpurs.CodeGen where

import Prelude

import Phpurs.CoreFn (Expr(..), Bind(..), Module(..), Literal(..), CaseAlternative(..), Binder(..), Decl)
import Phpurs.PhpAst (PhpExpr(..), PhpDecl, PhpFile)
import Data.Maybe (Maybe(..))
import Data.String (joinWith)
import Data.Array (nub, concatMap, filter, mapWithIndex, foldl, foldr, index, length, sortBy)
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
      Just _ -> []
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
getArgs other = { args: [], body: other }

formatFv :: Array String -> String -> String
formatFv recVars f = if f `elem` recVars || Printer.isUppercase f then "&$" <> Printer.sanitize f else "$" <> Printer.sanitize f

type CompileResult = { stmts :: Array PhpExpr, expr :: PhpExpr, nextId :: Int }

translateTailCall :: Array String -> String -> Array String -> Int -> Expr -> { stmts :: Array PhpExpr, nextId :: Int }
translateTailCall recVars ident args nextId expr = case expr of
  Let binds body ->
    let
      newRecVars = concatMap (\bind -> case bind of
        Rec rbs -> map (\rb -> rb.identifier) rbs
        _ -> []
      ) binds
      nextRecVars = recVars <> newRecVars
      
      processBind nextId' bind = case bind of
        NonRec id e -> 
          let res = translateExprImpl nextRecVars Nothing nextId' e
          in { stmts: res.stmts <> [PhpAssign id res.expr], nextId: res.nextId }
        Rec rbs -> 
          foldl (\acc rb -> 
            let extracted = getArgs rb.expression
                res = if length extracted.args > 0 then
                        translateExprImpl nextRecVars (Just { ident: rb.identifier, args: extracted.args }) acc.nextId rb.expression
                      else
                        translateExprImpl nextRecVars Nothing acc.nextId rb.expression
            in { stmts: acc.stmts <> res.stmts <> [PhpAssign rb.identifier res.expr], nextId: res.nextId }
          ) { stmts: [], nextId: nextId' } (sortRecBinds rbs)
          
      bindRes = foldl (\acc b -> 
        let br = processBind acc.nextId b
        in { stmts: acc.stmts <> br.stmts, nextId: br.nextId }
      ) { stmts: [], nextId } binds
      
      bodyRes = translateTailCall nextRecVars ident args bindRes.nextId body
    in { stmts: bindRes.stmts <> bodyRes.stmts, nextId: bodyRes.nextId }
    
  Case caseExprs alts ->
      let
        processCaseExpr acc e =
          let res = translateExprImpl recVars Nothing acc.nextId e
          in { assigns: acc.assigns <> res.stmts <> [PhpAssign ("__case_" <> show acc.i) res.expr]
             , vars: acc.vars <> [PhpVar ("__case_" <> show acc.i)]
             , nextId: res.nextId
             , i: acc.i + 1
             }
        caseRes = foldl processCaseExpr { assigns: [], vars: [], nextId: nextId, i: 0 } caseExprs
        
        processAlt (CaseAlternative alt) acc =
          let
            bindResults = mapWithIndex (\i b -> P.bindBinder (caseRes.vars `unsafeIndex` i) b) alt.binders
            combined = foldl P.combineResult { cond: PhpBoolean true, assigns: [] } bindResults
            altBodyRes = translateTailCall recVars ident args acc.nextId alt.expression
          in { stmts: [PhpIf combined.cond (combined.assigns <> altBodyRes.stmts) acc.stmts], nextId: altBodyRes.nextId }
             
        altRes = foldr processAlt { stmts: [PhpThrow "Pattern match failure"], nextId: caseRes.nextId } alts
      in { stmts: caseRes.assigns <> altRes.stmts, nextId: altRes.nextId }
    
  _ ->
    case isTailCall ident (length args) expr of
      Just callArgs ->
        let
          processArg acc argExpr =
            let res = translateExprImpl recVars Nothing acc.nextId argExpr
            in { assigns: acc.assigns <> res.stmts <> [PhpAssign ("__tco_tmp_" <> show acc.i) res.expr]
               , nextId: res.nextId
               , i: acc.i + 1
               }
          argRes = foldl processArg { assigns: [], nextId: nextId, i: 0 } callArgs
          reassigns = mapWithIndex (\i argName -> PhpAssign argName (PhpVar ("__tco_tmp_" <> show i))) args
        in { stmts: argRes.assigns <> reassigns <> [PhpContinue], nextId: argRes.nextId }
      Nothing ->
        let res = translateExprImpl recVars Nothing nextId expr
        in { stmts: res.stmts <> [PhpReturn res.expr], nextId: res.nextId }

isTailCall :: String -> Int -> Expr -> Maybe (Array Expr)
isTailCall ident expectedArgs expr =
  let
    collectCall (Call f x) =
      let c = collectCall f
      in { fn: c.fn, args: c.args <> [x] }
    collectCall other = { fn: other, args: [] }
    extracted = collectCall expr
  in case extracted.fn of
    Variable _ vName | vName == ident && length extracted.args == expectedArgs -> Just extracted.args
    _ -> Nothing

translateExprImpl :: Array String -> Maybe { ident :: String, args :: Array String } -> Int -> Expr -> CompileResult
translateExprImpl recVars tcoCtx nextId expr = case expr of
  Lambda _ _ -> 
    let
      extracted = getArgs expr
      fvs = nub (freeVars expr)
      formattedFvs = map (formatFv recVars) fvs
    in case tcoCtx of
      Just ctx -> 
        let bodyRes = translateExprImpl recVars tcoCtx nextId extracted.body
        in { stmts: [], expr: PhpFunction formattedFvs extracted.args (bodyRes.stmts <> [PhpReturn bodyRes.expr]), nextId: bodyRes.nextId }
      Nothing -> 
        let bodyRes = translateTailCall recVars "" [] nextId extracted.body -- Just use normal flatten for body
        in { stmts: [], expr: PhpFunction formattedFvs extracted.args bodyRes.stmts, nextId: bodyRes.nextId }
        
  _ -> case tcoCtx of
    Just ctx ->
      let
        loopBody = translateTailCall recVars ctx.ident ctx.args nextId expr
      in { stmts: [PhpWhile (PhpBoolean true) loopBody.stmts], expr: PhpRaw "null", nextId: loopBody.nextId }
    Nothing -> case expr of
      Variable mbMod ident ->
        case mbMod of
          Just mod -> { stmts: [], expr: PhpGlobalVar (joinWith "_" mod <> "_" <> ident), nextId }
          Nothing -> { stmts: [], expr: PhpVar ident, nextId }
      Call _ _ -> 
        let
          collectCall (Call f x) =
            let c = collectCall f
            in { fn: c.fn, args: c.args <> [x] }
          collectCall other = { fn: other, args: [] }
          extracted = collectCall expr
          
          fnRes = translateExprImpl recVars Nothing nextId extracted.fn
          
          processArg acc argExpr =
            let res = translateExprImpl recVars Nothing acc.nextId argExpr
            in { stmts: acc.stmts <> res.stmts, exprs: acc.exprs <> [res.expr], nextId: res.nextId }
            
          argsRes = foldl processArg { stmts: [], exprs: [], nextId: fnRes.nextId } extracted.args
          
        in { stmts: fnRes.stmts <> argsRes.stmts, expr: PhpCall fnRes.expr argsRes.exprs, nextId: argsRes.nextId }
      Literal lit -> translateLiteral lit nextId
      Case exprs alts -> 
        let
          processCaseExpr acc e =
            let res = translateExprImpl recVars Nothing acc.nextId e
            in { assigns: acc.assigns <> res.stmts <> [PhpAssign ("__case_" <> show acc.i) res.expr]
               , vars: acc.vars <> [PhpVar ("__case_" <> show acc.i)]
               , nextId: res.nextId
               , i: acc.i + 1
               }
          caseRes = foldl processCaseExpr { assigns: [], vars: [], nextId: nextId, i: 0 } exprs
          
          resultVar = "__case_res_" <> show caseRes.nextId
          nextIdAfterVar = caseRes.nextId + 1
          
          processAlt (CaseAlternative alt) acc =
            let
              bindResults = mapWithIndex (\i b -> P.bindBinder (caseRes.vars `unsafeIndex` i) b) alt.binders
              combined = foldl P.combineResult { cond: PhpBoolean true, assigns: [] } bindResults
              altBodyRes = translateExprImpl recVars Nothing acc.nextId alt.expression
            in { stmts: [PhpIf combined.cond (combined.assigns <> altBodyRes.stmts <> [PhpAssign resultVar altBodyRes.expr]) acc.stmts], nextId: altBodyRes.nextId }
               
          altRes = foldr processAlt { stmts: [PhpThrow "Pattern match failure"], nextId: nextIdAfterVar } alts
        in { stmts: caseRes.assigns <> [PhpAssign resultVar (PhpRaw "null")] <> altRes.stmts
           , expr: PhpVar resultVar
           , nextId: altRes.nextId
           }
      Let binds body ->
        let
          newRecVars = concatMap (\bind -> case bind of
            Rec rbs -> map (\rb -> rb.identifier) rbs
            _ -> []
          ) binds
          nextRecVars = recVars <> newRecVars
          
          processBind nextId' bind = case bind of
            NonRec ident e -> 
              let res = translateExprImpl nextRecVars Nothing nextId' e
              in { stmts: res.stmts <> [PhpAssign ident res.expr], nextId: res.nextId }
            Rec rbs -> 
              foldl (\acc rb -> 
                let extracted = getArgs rb.expression
                    res = if length extracted.args > 0 then
                            translateExprImpl nextRecVars (Just { ident: rb.identifier, args: extracted.args }) acc.nextId rb.expression
                          else
                            translateExprImpl nextRecVars Nothing acc.nextId rb.expression
                in { stmts: acc.stmts <> res.stmts <> [PhpAssign rb.identifier res.expr], nextId: res.nextId }
              ) { stmts: [], nextId: nextId' } (sortRecBinds rbs)
              
          bindRes = foldl (\acc b -> 
            let br = processBind acc.nextId b
            in { stmts: acc.stmts <> br.stmts, nextId: br.nextId }
          ) { stmts: [], nextId } binds
          
          bodyRes = translateExprImpl nextRecVars Nothing bindRes.nextId body
        in { stmts: bindRes.stmts <> bodyRes.stmts, expr: bodyRes.expr, nextId: bodyRes.nextId }
      Constructor typeName constructorName fieldNames ->
        let
          body = PhpAssocArray
            [ { key: "tag", value: PhpString constructorName }
            , { key: "values", value: PhpArray (map PhpVar fieldNames) }
            ]
        in if length fieldNames == 0 then { stmts: [], expr: body, nextId } else { stmts: [], expr: PhpFunction [] fieldNames [PhpReturn body], nextId }
      Accessor field e -> 
        let res = translateExprImpl recVars Nothing nextId e
        in { stmts: res.stmts, expr: PhpPropertyAccess res.expr field, nextId: res.nextId }
      ObjectUpdate e updates ->
        let
          resE = translateExprImpl recVars Nothing nextId e
          
          processUpdate acc u =
            let res = translateExprImpl recVars Nothing acc.nextId u.value
            in { stmts: acc.stmts <> res.stmts, updates: acc.updates <> [{ key: u.key, value: res.expr }], nextId: res.nextId }
            
          updatesRes = foldl processUpdate { stmts: [], updates: [], nextId: resE.nextId } updates
        in { stmts: resE.stmts <> updatesRes.stmts, expr: PhpObjectUpdate resE.expr updatesRes.updates, nextId: updatesRes.nextId }
      Unsupported t -> { stmts: [], expr: PhpRaw $ "\"/* Unsupported: " <> t <> " */\"", nextId }
      _ -> { stmts: [], expr: PhpRaw "/* Unknown */", nextId }

translateLiteral :: Literal Expr -> Int -> CompileResult
translateLiteral lit nextId = case lit of
  IntLiteral i -> { stmts: [], expr: PhpInt i, nextId }
  NumberLiteral n -> { stmts: [], expr: PhpNumber n, nextId }
  StringLiteral s -> { stmts: [], expr: PhpString s, nextId }
  CharLiteral c -> { stmts: [], expr: PhpString c, nextId }
  BooleanLiteral b -> { stmts: [], expr: PhpBoolean b, nextId }
  ArrayLiteral arr -> 
    let
      processItem acc item =
        let res = translateExprImpl [] Nothing acc.nextId item
        in { stmts: acc.stmts <> res.stmts, exprs: acc.exprs <> [res.expr], nextId: res.nextId }
      arrRes = foldl processItem { stmts: [], exprs: [], nextId } arr
    in { stmts: arrRes.stmts, expr: PhpArray arrRes.exprs, nextId: arrRes.nextId }
  ObjectLiteral arr -> 
    let
      processItem acc item =
        let res = translateExprImpl [] Nothing acc.nextId item.value
        in { stmts: acc.stmts <> res.stmts, exprs: acc.exprs <> [{ key: item.key, value: res.expr }], nextId: res.nextId }
      arrRes = foldl processItem { stmts: [], exprs: [], nextId } arr
    in { stmts: arrRes.stmts, expr: PhpAssocArray arrRes.exprs, nextId: arrRes.nextId }

translateDecl :: Array String -> Bind -> Array PhpDecl
translateDecl moduleName bind = case bind of
  NonRec ident expr ->
    let 
      prefix = joinWith "_" moduleName <> "_"
      res = translateExprImpl [] Nothing 0 expr
    in [{ identifier: prefix <> ident, expression: if length res.stmts > 0 then PhpCall (PhpFunction [] [] (res.stmts <> [PhpReturn res.expr])) [] else res.expr }]
  Rec rBinds ->
    map (\rb -> 
      let
        prefix = joinWith "_" moduleName <> "_"
        extracted = getArgs rb.expression
        res = if length extracted.args > 0 then
                translateExprImpl [] (Just { ident: rb.identifier, args: extracted.args }) 0 rb.expression
              else
                translateExprImpl [] Nothing 0 rb.expression
      in { identifier: prefix <> rb.identifier, expression: if length res.stmts > 0 then PhpCall (PhpFunction [] [] (res.stmts <> [PhpReturn res.expr])) [] else res.expr }
    ) (sortRecBinds rBinds)

translate :: Module -> PhpFile
translate (Module mod) =
  { namespace: mod.moduleName
  , decls: concatMap (translateDecl mod.moduleName) mod.decls
  , imports: mod.imports
  }
