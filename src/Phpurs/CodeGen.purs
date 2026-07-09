module Phpurs.CodeGen where

import Prelude

import Phpurs.CoreFn (Expr(..), Bind(..), Module(..), Literal(..), CaseAlternative(..), Binder(..), Decl)
import Phpurs.PhpAst (PhpExpr(..), PhpDecl, PhpFile)
import Data.Maybe (Maybe(..))
import Data.String (joinWith)
import Data.Array as Array
import Data.Foldable (find)
import Foreign.Object (Object)
import Foreign.Object as Object
import Data.Tuple (Tuple(..))
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

type GlobalRef = { mod :: Array String, ident :: String }

collectGlobals :: Expr -> Array GlobalRef
collectGlobals = case _ of
  Lambda _ body -> collectGlobals body
  Variable (Just mod) ident -> [{ mod, ident }]
  Call abs arg -> nub (collectGlobals abs <> collectGlobals arg)
  Literal lit -> case lit of
    ArrayLiteral arr -> nub (concatMap collectGlobals arr)
    ObjectLiteral arr -> nub (concatMap (\item -> collectGlobals item.value) arr)
    _ -> []
  Case exprs alts -> 
    let 
      exprVars = concatMap collectGlobals exprs
      altVars = concatMap collectGlobalsAlt alts
    in nub (exprVars <> altVars)
  Let binds expr ->
    let
      bodyVars = concatMap (\b -> case b of
        NonRec _ e -> collectGlobals e
        Rec rBinds -> concatMap (\rb -> collectGlobals rb.expression) rBinds
      ) binds <> collectGlobals expr
    in nub bodyVars
  Constructor _ _ _ -> []
  Accessor _ expr -> collectGlobals expr
  ObjectUpdate expr updates -> nub (collectGlobals expr <> concatMap (\u -> collectGlobals u.value) updates)
  Unsupported _ -> []
  _ -> []

collectGlobalsAlt :: CaseAlternative -> Array GlobalRef
collectGlobalsAlt (CaseAlternative { expressions }) = concatMap (\e -> collectGlobals e.guard <> collectGlobals e.expression) expressions

replaceGlobals :: Array GlobalRef -> Expr -> Expr
replaceGlobals globals = case _ of
  Lambda arg body -> Lambda arg (replaceGlobals globals body)
  Variable (Just mod) ident -> 
    case Array.find (\g -> g.mod == mod && g.ident == ident) globals of
      Just _ -> Variable Nothing ("__global_" <> joinWith "_" mod <> "_" <> ident)
      Nothing -> Variable (Just mod) ident
  Variable Nothing ident -> Variable Nothing ident
  Call abs arg -> Call (replaceGlobals globals abs) (replaceGlobals globals arg)
  Literal lit -> case lit of
    ArrayLiteral arr -> Literal (ArrayLiteral (map (replaceGlobals globals) arr))
    ObjectLiteral arr -> Literal (ObjectLiteral (map (\item -> item { value = replaceGlobals globals item.value }) arr))
    _ -> Literal lit
  Case exprs alts -> Case (map (replaceGlobals globals) exprs) (map (\(CaseAlternative ca) -> CaseAlternative (ca { expressions = map (\e -> { guard: replaceGlobals globals e.guard, expression: replaceGlobals globals e.expression }) ca.expressions })) alts)
  Let binds expr -> Let (map (\b -> case b of
    NonRec i e -> NonRec i (replaceGlobals globals e)
    Rec rBinds -> Rec (map (\rb -> rb { expression = replaceGlobals globals rb.expression }) rBinds)
  ) binds) (replaceGlobals globals expr)
  Constructor t c n -> Constructor t c n
  Accessor prop expr -> Accessor prop (replaceGlobals globals expr)
  ObjectUpdate expr updates -> ObjectUpdate (replaceGlobals globals expr) (map (\u -> u { value = replaceGlobals globals u.value }) updates)
  Unsupported s -> Unsupported s

freeVarsAlt :: CaseAlternative -> Array String
freeVarsAlt (CaseAlternative { binders, expressions }) =
  let
    bVars = concatMap binderVars binders
    eVars = concatMap (\e -> freeVars e.guard <> freeVars e.expression) expressions
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
getArgs (Let binds body) =
  let
    next = getArgs body
  in { args: next.args, body: Let binds next.body }
getArgs other = { args: [], body: other }

extractLambda :: Expr -> Maybe Expr
extractLambda e@(Lambda _ _) = Just e
extractLambda (Let _ body) = extractLambda body
extractLambda _ = Nothing

formatFv :: Array String -> String -> String
formatFv recVars f = if f `elem` recVars || Printer.isUppercase f then "&$" <> Printer.safeName f else "$" <> Printer.safeName f

type CompileResult = { stmts :: Array PhpExpr, expr :: PhpExpr, nextId :: Int }

translateTailCall :: GlobalEnv -> String -> Array String -> Array String -> String -> Array String -> Int -> Expr -> { stmts :: Array PhpExpr, nextId :: Int }
translateTailCall env currentModStr moduleName recVars ident args nextId expr = case expr of
  Let binds body ->
    let
      newRecVars = concatMap (\bind -> case bind of
        Rec rbs -> map (\rb -> rb.identifier) rbs
        _ -> []
      ) binds
      nextRecVars = recVars <> newRecVars
      
      processBind nextId' bind = case bind of
        NonRec id e -> 
          let res = translateExprImpl env currentModStr moduleName nextRecVars Nothing nextId' e
          in { stmts: res.stmts <> [PhpAssign id res.expr], nextId: res.nextId }
        Rec rbs -> 
          foldl (\acc rb -> 
            let extracted = getArgs rb.expression
                res = if length extracted.args > 0 then
                        translateExprImpl env currentModStr moduleName nextRecVars (Just { ident: rb.identifier, args: extracted.args }) acc.nextId rb.expression
                      else
                        translateExprImpl env currentModStr moduleName nextRecVars Nothing acc.nextId rb.expression
            in { stmts: acc.stmts <> res.stmts <> [PhpAssign rb.identifier res.expr], nextId: res.nextId }
          ) { stmts: [], nextId: nextId' } (sortRecBinds rbs)
          
      bindRes = foldl (\acc b -> 
        let br = processBind acc.nextId b
        in { stmts: acc.stmts <> br.stmts, nextId: br.nextId }
      ) { stmts: [], nextId } binds
      
      bodyRes = translateTailCall env currentModStr moduleName nextRecVars ident args bindRes.nextId body
    in { stmts: bindRes.stmts <> bodyRes.stmts, nextId: bodyRes.nextId }
    
  Case caseExprs alts -> 
      let
        processCaseExpr acc e =
          let res = translateExprImpl env currentModStr moduleName recVars Nothing acc.nextId e
          in { assigns: acc.assigns <> res.stmts <> [PhpAssign ("__case_" <> show acc.i) res.expr]
             , vars: acc.vars <> [PhpVar ("__case_" <> show acc.i)]
             , nextId: res.nextId
             , i: acc.i + 1
             }
        caseRes = foldl processCaseExpr { assigns: [], vars: [], nextId: nextId, i: 0 } caseExprs
        
        matchedVar = "__match_" <> show caseRes.nextId
        
        processAlt acc (CaseAlternative alt) =
          let
            bindResults = mapWithIndex (\i b -> P.bindBinder (caseRes.vars `unsafeIndex` i) b) alt.binders
            combined = foldl P.combineResult { cond: PhpBoolean true, assigns: [] } bindResults
            
            processGuard gAcc e =
              let
                 guardRes = translateExprImpl env currentModStr moduleName recVars Nothing gAcc.nextId e.guard
                 bodyRes = translateTailCall env currentModStr moduleName recVars ident args guardRes.nextId e.expression
                 
                 guardIf = PhpIf (PhpBinOp "===" (PhpVar matchedVar) (PhpBoolean false)) [ PhpIf guardRes.expr (bodyRes.stmts <> [PhpAssign matchedVar (PhpBoolean true)]) [] ] []
              in { stmts: gAcc.stmts <> guardRes.stmts <> [guardIf], nextId: bodyRes.nextId }
              
            guardsRes = foldl processGuard { stmts: [], nextId: acc.nextId } alt.expressions
            
            altBlock = PhpIf combined.cond (combined.assigns <> guardsRes.stmts) []
            finalAlt = PhpIf (PhpBinOp "===" (PhpVar matchedVar) (PhpBoolean false)) [altBlock] []
            
          in { stmts: acc.stmts <> [finalAlt], nextId: guardsRes.nextId }
             
        altRes = foldl processAlt { stmts: [], nextId: caseRes.nextId + 1 } alts
      in { stmts: caseRes.assigns <> [PhpAssign matchedVar (PhpBoolean false)] <> altRes.stmts <> [PhpIf (PhpBinOp "===" (PhpVar matchedVar) (PhpBoolean false)) [PhpThrow "Pattern match failure"] []]
         , nextId: altRes.nextId
         }
    
  _ ->
    case isTailCall ident (length args) expr of
      Just callArgs ->
        let
          processArg acc argExpr =
            let res = translateExprImpl env currentModStr moduleName recVars Nothing acc.nextId argExpr
            in { assigns: acc.assigns <> res.stmts <> [PhpAssign ("__tco_tmp_" <> show acc.i) res.expr]
               , nextId: res.nextId
               , i: acc.i + 1
               }
          argRes = foldl processArg { assigns: [], nextId: nextId, i: 0 } callArgs
          reassigns = mapWithIndex (\i argName -> PhpAssign argName (PhpVar ("__tco_tmp_" <> show i))) args
        in { stmts: argRes.assigns <> reassigns <> [PhpContinue], nextId: argRes.nextId }
      Nothing ->
        let res = translateExprImpl env currentModStr moduleName recVars Nothing nextId expr
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

matchIntrinsicOperator :: Expr -> Int -> Maybe String
matchIntrinsicOperator (Variable (Just mod) ident) argCount = case mod, ident, argCount of
  ["Data", "Semiring"], "intAdd", 2 -> Just "+"
  ["Data", "Semiring"], "numAdd", 2 -> Just "+"
  ["Data", "Semiring"], "intMul", 2 -> Just "*"
  ["Data", "Semiring"], "numMul", 2 -> Just "*"
  ["Data", "Ring"], "intSub", 2 -> Just "-"
  ["Data", "Ring"], "numSub", 2 -> Just "-"
  ["Data", "EuclideanRing"], "intDiv", 2 -> Just "/"
  ["Data", "EuclideanRing"], "numDiv", 2 -> Just "/"
  ["Data", "Eq"], "eqIntImpl", 2 -> Just "==="
  ["Data", "Eq"], "eqNumberImpl", 2 -> Just "==="
  ["Data", "Eq"], "eqStringImpl", 2 -> Just "==="
  ["Data", "Eq"], "eqCharImpl", 2 -> Just "==="
  ["Data", "Eq"], "eqBooleanImpl", 2 -> Just "==="
  ["Data", "HeytingAlgebra"], "boolAnd", 2 -> Just "&&"
  ["Data", "HeytingAlgebra"], "boolOr", 2 -> Just "||"
  ["Data", "HeytingAlgebra"], "boolConj", 2 -> Just "&&"
  ["Data", "HeytingAlgebra"], "boolDisj", 2 -> Just "||"
  ["Data", "Semigroup"], "concatString", 2 -> Just "."
  _, _, _ -> Nothing
matchIntrinsicOperator _ _ = Nothing

data InlineFunction
  = InlineIdentity Int
  | InlineConst
  | InlineApply
  | InlineFlip

matchInlineFunction :: Expr -> Int -> Maybe InlineFunction
matchInlineFunction (Variable (Just mod) ident) argCount = case mod, ident, argCount of
  ["Control", "Category"], "identity", _ -> Just (InlineIdentity 1)
  ["Data", "Function"], "const", 2 -> Just InlineConst
  ["Data", "Function"], "apply", 2 -> Just InlineApply
  ["Data", "Function"], "flip", 3 -> Just InlineFlip
  ["Data", "Newtype"], "unwrap", _ -> Just (InlineIdentity 1)
  ["Data", "Newtype"], "wrap", _ -> Just (InlineIdentity 1)
  ["Safe", "Coerce"], "coerce", _ -> Just (InlineIdentity 1)
  _, _, _ -> Nothing
matchInlineFunction _ _ = Nothing

translateExprImpl :: GlobalEnv -> String -> Array String -> Array String -> Maybe { ident :: String, args :: Array String } -> Int -> Expr -> CompileResult
translateExprImpl env currentModStr moduleName recVars tcoCtx nextId expr = case expr of
  Lambda _ _ -> 
    let
      extracted = getArgs expr
      fvs = nub (freeVars expr)
      formattedFvs = map (formatFv recVars) fvs
    in case tcoCtx of
      Just ctx -> 
        let
          -- Optimization: Hoist global thunks outside the TCO while(true) loop
          isIntrinsic h = case h.mod, h.ident of
            ["Data", "Semiring"], "intAdd" -> true
            ["Data", "Semiring"], "numAdd" -> true
            ["Data", "Semiring"], "intMul" -> true
            ["Data", "Semiring"], "numMul" -> true
            ["Data", "Ring"], "intSub" -> true
            ["Data", "Ring"], "numSub" -> true
            ["Data", "EuclideanRing"], "intDiv" -> true
            ["Data", "EuclideanRing"], "numDiv" -> true
            ["Data", "Eq"], "eqIntImpl" -> true
            ["Data", "Eq"], "eqNumberImpl" -> true
            ["Data", "Eq"], "eqStringImpl" -> true
            ["Data", "Eq"], "eqBooleanImpl" -> true
            ["Data", "Eq"], "eqCharImpl" -> true
            ["Data", "HeytingAlgebra"], "boolAnd" -> true
            ["Data", "HeytingAlgebra"], "boolOr" -> true
            ["Data", "Semigroup"], "concatString" -> true
            _, _ -> false
          isRec h = h.ident == ctx.ident || h.ident `elem` recVars || isIntrinsic h
          hoisted = filter (not <<< isRec) (collectGlobals extracted.body)
          hoistStmts = map (\h -> PhpAssign ("__global_" <> joinWith "_" h.mod <> "_" <> h.ident) (PhpGlobalVar (Just h.mod) h.ident)) hoisted
          rewrittenBody = replaceGlobals hoisted extracted.body
          
          bodyRes = translateExprImpl env currentModStr moduleName recVars tcoCtx nextId rewrittenBody
        in { stmts: [], expr: PhpFunction formattedFvs extracted.args (hoistStmts <> bodyRes.stmts <> [PhpReturn bodyRes.expr]), nextId: bodyRes.nextId }
      Nothing -> 
        let
          isIntrinsic h = case h.mod, h.ident of
            ["Data", "Semiring"], "intAdd" -> true
            ["Data", "Semiring"], "numAdd" -> true
            ["Data", "Semiring"], "intMul" -> true
            ["Data", "Semiring"], "numMul" -> true
            ["Data", "Ring"], "intSub" -> true
            ["Data", "Ring"], "numSub" -> true
            ["Data", "EuclideanRing"], "intDiv" -> true
            ["Data", "EuclideanRing"], "numDiv" -> true
            ["Data", "Eq"], "eqIntImpl" -> true
            ["Data", "Eq"], "eqNumberImpl" -> true
            ["Data", "Eq"], "eqStringImpl" -> true
            ["Data", "Eq"], "eqBooleanImpl" -> true
            ["Data", "Eq"], "eqCharImpl" -> true
            ["Data", "HeytingAlgebra"], "boolAnd" -> true
            ["Data", "HeytingAlgebra"], "boolOr" -> true
            ["Data", "Semigroup"], "concatString" -> true
            _, _ -> false
          isRec h = h.ident `elem` recVars || isIntrinsic h
          hoisted = filter (not <<< isRec) (collectGlobals extracted.body)
          hoistStmts = map (\h -> PhpAssign ("__global_" <> joinWith "_" h.mod <> "_" <> h.ident) (PhpGlobalVar (Just h.mod) h.ident)) hoisted
          rewrittenBody = replaceGlobals hoisted extracted.body
          
          bodyRes = translateTailCall env currentModStr moduleName recVars "" [] nextId rewrittenBody -- Just use normal flatten for body
        in { stmts: [], expr: PhpFunction formattedFvs extracted.args (hoistStmts <> bodyRes.stmts), nextId: bodyRes.nextId }
        
  _ -> case tcoCtx of
    Just ctx ->
      let
        loopBody = translateTailCall env currentModStr moduleName recVars ctx.ident ctx.args nextId expr
      in { stmts: [PhpWhile (PhpBoolean true) loopBody.stmts], expr: PhpRaw "null", nextId: loopBody.nextId }
    Nothing -> case expr of
      Variable mbMod ident ->
        let
          modPrefix = case mbMod of
            Just m -> joinWith "." m
            Nothing -> currentModStr
          globalKey = modPrefix <> "." <> ident
        in case Object.lookup globalKey env of
          Just (Lambda _ _) ->
            let
              targetMod = case mbMod of 
                Just m -> m
                Nothing -> moduleName
              fqn = "\\" <> joinWith "\\" targetMod <> "\\" <> Printer.safeFuncName (joinWith "_" targetMod <> "_" <> ident)
            in { stmts: [], expr: PhpString fqn, nextId }
          _ -> case mbMod of
            Just mod -> { stmts: [], expr: PhpGlobalVar (Just mod) ident, nextId }
            Nothing -> { stmts: [], expr: PhpVar ident, nextId }
      Call _ _ -> 
        let
          collectCall (Call f x) =
            let c = collectCall f
            in { fn: c.fn, args: c.args <> [x] }
          collectCall other = { fn: other, args: [] }
          extracted = collectCall expr
          
        in case matchInlineFunction extracted.fn (length extracted.args) of
          Just (InlineIdentity dictCount) ->
            if length extracted.args > dictCount then
              translateExprImpl env currentModStr moduleName recVars Nothing nextId (extracted.args `unsafeIndex` dictCount)
            else
              { stmts: [], expr: PhpFunction [] ["__x"] [PhpReturn (PhpVar "__x")], nextId }
          Just InlineConst ->
            translateExprImpl env currentModStr moduleName recVars Nothing nextId (extracted.args `unsafeIndex` 0)
          Just InlineApply ->
            let 
              fnRes = translateExprImpl env currentModStr moduleName recVars Nothing nextId (extracted.args `unsafeIndex` 0)
              argRes = translateExprImpl env currentModStr moduleName recVars Nothing fnRes.nextId (extracted.args `unsafeIndex` 1)
            in { stmts: fnRes.stmts <> argRes.stmts, expr: PhpCall fnRes.expr [argRes.expr], nextId: argRes.nextId }
          Just InlineFlip ->
            let
              fnRes = translateExprImpl env currentModStr moduleName recVars Nothing nextId (extracted.args `unsafeIndex` 0)
              argYRes = translateExprImpl env currentModStr moduleName recVars Nothing fnRes.nextId (extracted.args `unsafeIndex` 2)
              argXRes = translateExprImpl env currentModStr moduleName recVars Nothing argYRes.nextId (extracted.args `unsafeIndex` 1)
            in { stmts: fnRes.stmts <> argYRes.stmts <> argXRes.stmts, expr: PhpCall fnRes.expr [argYRes.expr, argXRes.expr], nextId: argXRes.nextId }
          Nothing ->
            case extracted.fn of
              Constructor _ constructorName fieldNames | length fieldNames == length extracted.args ->
                let
                  processArg acc argExpr =
                    let res = translateExprImpl env currentModStr moduleName recVars Nothing acc.nextId argExpr
                    in { stmts: acc.stmts <> res.stmts, exprs: acc.exprs <> [res.expr], nextId: res.nextId }
                  argsRes = foldl processArg { stmts: [], exprs: [], nextId: nextId } extracted.args
                in { stmts: argsRes.stmts
                   , expr: PhpNew ("Phpurs_Data" <> show (length fieldNames)) ([PhpString constructorName] <> argsRes.exprs)
                   , nextId: argsRes.nextId
                   }
              _ ->
                case matchIntrinsicOperator extracted.fn (length extracted.args) of
                  Just op -> 
                    let
                      processArg acc argExpr =
                        let res = translateExprImpl env currentModStr moduleName recVars Nothing acc.nextId argExpr
                        in { stmts: acc.stmts <> res.stmts, exprs: acc.exprs <> [res.expr], nextId: res.nextId }
                      argsRes = foldl processArg { stmts: [], exprs: [], nextId: nextId } extracted.args
                    in { stmts: argsRes.stmts, expr: PhpBinOp op (argsRes.exprs `unsafeIndex` 0) (argsRes.exprs `unsafeIndex` 1), nextId: argsRes.nextId }
                  Nothing ->
                    let
                      processArg acc argExpr =
                        let res = translateExprImpl env currentModStr moduleName recVars Nothing acc.nextId argExpr
                        in { stmts: acc.stmts <> res.stmts, exprs: acc.exprs <> [res.expr], nextId: res.nextId }
                    in case extracted.fn of
                      Variable fnMbMod fnIdent ->
                        let
                          fnModPrefix = case fnMbMod of
                            Just m -> joinWith "." m
                            Nothing -> currentModStr
                          fnGlobalKey = fnModPrefix <> "." <> fnIdent
                        in case Object.lookup fnGlobalKey env >>= extractLambda of
                          Just lambdaExpr ->
                            let
                              targetMod = case fnMbMod of 
                                Just m -> m
                                Nothing -> moduleName
                              fqn = "\\" <> joinWith "\\" targetMod <> "\\" <> Printer.safeFuncName (joinWith "_" targetMod <> "_" <> fnIdent)
                              argsRes = foldl processArg { stmts: [], exprs: [], nextId: nextId } extracted.args
                              expectedArity = length (getArgs lambdaExpr).args
                            in if length argsRes.exprs > expectedArity then
                                 let
                                   firstArgs = Array.take expectedArity argsRes.exprs
                                   restArgs = Array.drop expectedArity argsRes.exprs
                                 in { stmts: argsRes.stmts
                                    , expr: foldl (\acc a -> PhpCall acc [a]) (PhpCall (PhpRaw fqn) firstArgs) restArgs
                                    , nextId: argsRes.nextId 
                                    }
                               else
                                 { stmts: argsRes.stmts, expr: PhpCall (PhpRaw fqn) argsRes.exprs, nextId: argsRes.nextId }
                          _ -> 
                            let
                              fnRes = translateExprImpl env currentModStr moduleName recVars Nothing nextId extracted.fn
                              argsRes = foldl processArg { stmts: [], exprs: [], nextId: fnRes.nextId } extracted.args
                              finalCall = if length argsRes.exprs > 0 then foldl (\acc a -> PhpCall acc [a]) (PhpCall fnRes.expr [argsRes.exprs `unsafeIndex` 0]) (Array.drop 1 argsRes.exprs) else PhpCall fnRes.expr []
                            in { stmts: fnRes.stmts <> argsRes.stmts, expr: finalCall, nextId: argsRes.nextId }
                      _ ->
                        let
                          fnRes = translateExprImpl env currentModStr moduleName recVars Nothing nextId extracted.fn
                          argsRes = foldl processArg { stmts: [], exprs: [], nextId: fnRes.nextId } extracted.args
                          finalCall = if length argsRes.exprs > 0 then foldl (\acc a -> PhpCall acc [a]) (PhpCall fnRes.expr [argsRes.exprs `unsafeIndex` 0]) (Array.drop 1 argsRes.exprs) else PhpCall fnRes.expr []
                        in { stmts: fnRes.stmts <> argsRes.stmts, expr: finalCall, nextId: argsRes.nextId }
      Literal lit -> translateLiteral env currentModStr moduleName lit nextId
      Case exprs alts -> 
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
              bindResults = mapWithIndex (\i b -> P.bindBinder (caseRes.vars `unsafeIndex` i) b) alt.binders
              combined = foldl P.combineResult { cond: PhpBoolean true, assigns: [] } bindResults
              
              processGuard gAcc e =
                let
                   guardRes = translateExprImpl env currentModStr moduleName recVars Nothing gAcc.nextId e.guard
                   bodyRes = translateExprImpl env currentModStr moduleName recVars Nothing guardRes.nextId e.expression
                   
                   guardIf = PhpIf (PhpBinOp "===" (PhpVar matchedVar) (PhpBoolean false)) [ PhpIf guardRes.expr (bodyRes.stmts <> [PhpAssign resultVar bodyRes.expr, PhpAssign matchedVar (PhpBoolean true)]) [] ] []
                in { stmts: gAcc.stmts <> guardRes.stmts <> [guardIf], nextId: bodyRes.nextId }
                
              guardsRes = foldl processGuard { stmts: [], nextId: acc.nextId } alt.expressions
              
              altBlock = PhpIf combined.cond (combined.assigns <> guardsRes.stmts) []
              finalAlt = PhpIf (PhpBinOp "===" (PhpVar matchedVar) (PhpBoolean false)) [altBlock] []
              
            in { stmts: acc.stmts <> [finalAlt], nextId: guardsRes.nextId }
               
          altRes = foldl processAlt { stmts: [], nextId: nextIdAfterVar } alts
        in { stmts: caseRes.assigns <> [PhpAssign resultVar (PhpRaw "null"), PhpAssign matchedVar (PhpBoolean false)] <> altRes.stmts <> [PhpIf (PhpBinOp "===" (PhpVar matchedVar) (PhpBoolean false)) [PhpThrow "Pattern match failure"] []]
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
              let res = translateExprImpl env currentModStr moduleName nextRecVars Nothing nextId' e
              in { stmts: res.stmts <> [PhpAssign ident res.expr], nextId: res.nextId }
            Rec rbs -> 
              foldl (\acc rb -> 
                let extracted = getArgs rb.expression
                    res = if length extracted.args > 0 then
                            translateExprImpl env currentModStr moduleName nextRecVars (Just { ident: rb.identifier, args: extracted.args }) acc.nextId rb.expression
                          else
                            translateExprImpl env currentModStr moduleName nextRecVars Nothing acc.nextId rb.expression
                in { stmts: acc.stmts <> res.stmts <> [PhpAssign rb.identifier res.expr], nextId: res.nextId }
              ) { stmts: [], nextId: nextId' } (sortRecBinds rbs)
              
          bindRes = foldl (\acc b -> 
            let br = processBind acc.nextId b
            in { stmts: acc.stmts <> br.stmts, nextId: br.nextId }
          ) { stmts: [], nextId } binds
          
          bodyRes = translateExprImpl env currentModStr moduleName nextRecVars Nothing bindRes.nextId body
        in { stmts: bindRes.stmts <> bodyRes.stmts, expr: bodyRes.expr, nextId: bodyRes.nextId }
      Constructor _ constructorName fieldNames ->
        let
          numFields = length fieldNames
          body = PhpNew ("Phpurs_Data" <> show numFields) ([PhpString constructorName] <> map PhpVar fieldNames)
          singletonBody = PhpBinOp "??=" (PhpRaw ("$GLOBALS['__phpurs_data0_" <> Printer.safeName constructorName <> "']")) body
        in if numFields == 0 then { stmts: [], expr: singletonBody, nextId } else { stmts: [], expr: PhpFunction [] fieldNames [PhpReturn body], nextId }
      Accessor field e -> 
        let res = translateExprImpl env currentModStr moduleName recVars Nothing nextId e
        in { stmts: res.stmts, expr: PhpPropertyAccess res.expr field, nextId: res.nextId }
      ObjectUpdate e updates ->
        let
          resE = translateExprImpl env currentModStr moduleName recVars Nothing nextId e
          
          processUpdate acc u =
            let res = translateExprImpl env currentModStr moduleName recVars Nothing acc.nextId u.value
            in { stmts: acc.stmts <> res.stmts, updates: acc.updates <> [{ key: u.key, value: res.expr }], nextId: res.nextId }
            
          updatesRes = foldl processUpdate { stmts: [], updates: [], nextId: resE.nextId } updates
          
          tmpVar = "__update_tmp_" <> show updatesRes.nextId
          cloneStmt = PhpAssign tmpVar (PhpClone resE.expr)
          updateStmts = map (\u -> PhpAssign (tmpVar <> "->" <> u.key) u.value) updatesRes.updates
          
        in { stmts: resE.stmts <> updatesRes.stmts <> [cloneStmt] <> updateStmts, expr: PhpVar tmpVar, nextId: updatesRes.nextId + 1 }
      Unsupported t -> { stmts: [], expr: PhpRaw $ "\"/* Unsupported: " <> t <> " */\"", nextId }
      _ -> { stmts: [], expr: PhpRaw "/* Unknown */", nextId }

translateLiteral :: GlobalEnv -> String -> Array String -> Literal Expr -> Int -> CompileResult
translateLiteral env currentModStr moduleName lit nextId = case lit of
  IntLiteral i -> { stmts: [], expr: PhpInt i, nextId }
  NumberLiteral n -> { stmts: [], expr: PhpNumber n, nextId }
  StringLiteral s -> { stmts: [], expr: PhpString s, nextId }
  CharLiteral c -> { stmts: [], expr: PhpString c, nextId }
  BooleanLiteral b -> { stmts: [], expr: PhpBoolean b, nextId }
  ArrayLiteral arr -> 
    let
      processItem acc item =
        let res = translateExprImpl env currentModStr moduleName [] Nothing acc.nextId item
        in { stmts: acc.stmts <> res.stmts, exprs: acc.exprs <> [res.expr], nextId: res.nextId }
      arrRes = foldl processItem { stmts: [], exprs: [], nextId } arr
    in { stmts: arrRes.stmts, expr: PhpArray arrRes.exprs, nextId: arrRes.nextId }
  ObjectLiteral arr -> 
    let
      processItem acc item =
        let res = translateExprImpl env currentModStr moduleName [] Nothing acc.nextId item.value
        in { stmts: acc.stmts <> res.stmts, exprs: acc.exprs <> [{ key: item.key, value: res.expr }], nextId: res.nextId }
      arrRes = foldl processItem { stmts: [], exprs: [], nextId } arr
    in { stmts: arrRes.stmts, expr: PhpAssocArray arrRes.exprs, nextId: arrRes.nextId }

translateDecl :: GlobalEnv -> String -> Array String -> Bind -> Array PhpDecl
translateDecl env currentModStr moduleName bind = case bind of
  NonRec ident expr ->
    let 
      prefix = joinWith "_" moduleName <> "_"
      res = translateExprImpl env currentModStr moduleName [] Nothing 0 (simplify env currentModStr expr)
    in [{ identifier: prefix <> ident
        , expression: case res.expr of
                        PhpFunction _ args stmts | length res.stmts == 0 -> PhpNativeFunction (prefix <> ident) args stmts
                        _ -> PhpValueThunk (prefix <> ident) (if length res.stmts > 0 then PhpCall (PhpFunction [] [] (res.stmts <> [PhpReturn res.expr])) [] else res.expr)
        }]
  Rec rBinds ->
    map (\rb -> 
      let
        prefix = joinWith "_" moduleName <> "_"
        extracted = getArgs rb.expression
        res = if length extracted.args > 0 then
                translateExprImpl env currentModStr moduleName [] (Just { ident: rb.identifier, args: extracted.args }) 0 (simplify env currentModStr rb.expression)
              else
                translateExprImpl env currentModStr moduleName [] Nothing 0 (simplify env currentModStr rb.expression)
      in { identifier: prefix <> rb.identifier
         , expression: case res.expr of
                         PhpFunction _ args stmts | length res.stmts == 0 -> PhpNativeFunction (prefix <> rb.identifier) args stmts
                         _ -> PhpValueThunk (prefix <> rb.identifier) (if length res.stmts > 0 then PhpCall (PhpFunction [] [] (res.stmts <> [PhpReturn res.expr])) [] else res.expr)
                    }
    ) (sortRecBinds rBinds)


type GlobalEnv = Object Expr

buildGlobalEnv :: Array Module -> GlobalEnv
buildGlobalEnv mods = Object.fromFoldable $ concatMap processMod mods
  where
    processMod (Module m) =
      let modNameStr = joinWith "." m.moduleName
      in concatMap (processDecl modNameStr) m.decls

    processDecl modNameStr = case _ of
      NonRec ident expr -> [Tuple (modNameStr <> "." <> ident) expr]
      Rec binds -> map (\b -> Tuple (modNameStr <> "." <> b.identifier) b.expression) binds

simplify :: GlobalEnv -> String -> Expr -> Expr
simplify env currentMod expr = simplify' [] expr
  where
  simplify' visited e = case e of
    Call f arg ->
      let
        f' = simplify' visited f
        arg' = simplify' visited arg
      in case f' of
        Lambda param (Variable Nothing v) | param == v -> arg'
        Call (Variable (Just mod) ident) (Literal litA) ->
          case arg' of
            Literal litB ->
              case mod, ident, litA, litB of
                ["Data", "Semiring"], "intAdd", IntLiteral a, IntLiteral b -> Literal (IntLiteral (a + b))
                ["Data", "Semiring"], "intMul", IntLiteral a, IntLiteral b -> Literal (IntLiteral (a * b))
                ["Data", "Ring"], "intSub", IntLiteral a, IntLiteral b -> Literal (IntLiteral (a - b))
                ["Data", "EuclideanRing"], "intDiv", IntLiteral a, IntLiteral b -> if b /= 0 then Literal (IntLiteral (a / b)) else Call f' arg'
                ["Data", "Semiring"], "numAdd", NumberLiteral a, NumberLiteral b -> Literal (NumberLiteral (a + b))
                ["Data", "Semiring"], "numMul", NumberLiteral a, NumberLiteral b -> Literal (NumberLiteral (a * b))
                ["Data", "Ring"], "numSub", NumberLiteral a, NumberLiteral b -> Literal (NumberLiteral (a - b))
                ["Data", "EuclideanRing"], "numDiv", NumberLiteral a, NumberLiteral b -> if b /= 0.0 then Literal (NumberLiteral (a / b)) else Call f' arg'
                ["Data", "Eq"], "eqIntImpl", IntLiteral a, IntLiteral b -> Literal (BooleanLiteral (a == b))
                ["Data", "Eq"], "eqNumberImpl", NumberLiteral a, NumberLiteral b -> Literal (BooleanLiteral (a == b))
                ["Data", "Eq"], "eqStringImpl", StringLiteral a, StringLiteral b -> Literal (BooleanLiteral (a == b))
                ["Data", "Eq"], "eqCharImpl", CharLiteral a, CharLiteral b -> Literal (BooleanLiteral (a == b))
                ["Data", "Semigroup"], "concatString", StringLiteral a, StringLiteral b -> Literal (StringLiteral (a <> b))
                ["Data", "HeytingAlgebra"], "boolConj", BooleanLiteral a, BooleanLiteral b -> Literal (BooleanLiteral (a && b))
                ["Data", "HeytingAlgebra"], "boolDisj", BooleanLiteral a, BooleanLiteral b -> Literal (BooleanLiteral (a || b))
                ["Data", "HeytingAlgebra"], "boolAnd", BooleanLiteral a, BooleanLiteral b -> Literal (BooleanLiteral (a && b))
                ["Data", "HeytingAlgebra"], "boolOr", BooleanLiteral a, BooleanLiteral b -> Literal (BooleanLiteral (a || b))
                ["Data", "Eq"], "eqBooleanImpl", BooleanLiteral a, BooleanLiteral b -> Literal (BooleanLiteral (a == b))
                _, _, _, _ -> Call f' arg'
            _ -> Call f' arg'
        Variable mbMod ident ->
          let
            modPrefix = case mbMod of
              Just m -> joinWith "." m
              Nothing -> currentMod
            globalKey = modPrefix <> "." <> ident
          in case Object.lookup globalKey env of
            Just def ->
              let
                tryInlineDictionaryExtractor fnExpr argExpr = case fnExpr of
                  Lambda param (Variable Nothing v) | param == v -> Just argExpr
                  Lambda param (Case [Variable Nothing p2] [CaseAlternative ca]) | param == p2 ->
                    case ca.binders of
                      [ConstructorBinder _ _ _ [VarBinder v]] ->
                        case ca.expressions of
                          [{ guard: Literal (BooleanLiteral true), expression: Accessor prop (Variable Nothing p3) }] | v == p3 -> Just (Accessor prop argExpr)
                          _ -> Nothing
                      _ -> Nothing
                  Lambda param (Accessor prop (Variable Nothing v)) | param == v -> Just (Accessor prop argExpr)
                  _ -> Nothing
              in case tryInlineDictionaryExtractor def arg' of
                Just inlined -> simplify' visited inlined
                Nothing -> Call f' arg'
            Nothing -> Call f' arg'
        _ -> Call f' arg'
        
    Accessor prop arg ->
      let
        arg' = simplify' visited arg
        isSafeToInline exprInline = case exprInline of
          Variable _ _ -> true
          Literal (IntLiteral _) -> true
          Literal (NumberLiteral _) -> true
          Literal (StringLiteral _) -> true
          Literal (CharLiteral _) -> true
          Literal (BooleanLiteral _) -> true
          Accessor _ _ -> true
          _ -> false
        extractSafe item = if isSafeToInline item.value then item.value else Accessor prop arg'
      in case arg' of
        Variable argMbMod argIdent ->
          let
            argModPrefix = case argMbMod of
              Just m -> joinWith "." m
              Nothing -> currentMod
            argGlobalKey = argModPrefix <> "." <> argIdent
          in case Object.lookup argGlobalKey env of
            Just (Literal (ObjectLiteral fields)) ->
              case find (\item -> item.key == prop) fields of
                Just item -> extractSafe item
                Nothing -> Accessor prop arg'
            Just (Call (Variable _ _) (Literal (ObjectLiteral fields))) ->
              case find (\item -> item.key == prop) fields of
                Just item -> extractSafe item
                Nothing -> Accessor prop arg'
            _ -> Accessor prop arg'
        Literal (ObjectLiteral fields) ->
          case find (\item -> item.key == prop) fields of
            Just item -> extractSafe item
            Nothing -> Accessor prop arg'
        Call (Variable _ _) (Literal (ObjectLiteral fields)) ->
          case find (\item -> item.key == prop) fields of
            Just item -> extractSafe item
            Nothing -> Accessor prop arg'
        _ -> Accessor prop arg'
        
    Let binds body -> Let (map (\b -> case b of
      NonRec ident val -> NonRec ident (simplify' visited val)
      Rec arr -> Rec (map (\a -> a { expression = simplify' visited a.expression }) arr)
      ) binds) (simplify' visited body)
      
    Lambda arg body -> Lambda arg (simplify' visited body)
    Case binders alts -> Case (map (simplify' visited) binders) (map (\(CaseAlternative ca) -> CaseAlternative (ca { expressions = map (\e -> { guard: simplify' visited e.guard, expression: simplify' visited e.expression }) ca.expressions })) alts)
    Constructor t c n -> Constructor t c n
    Literal lit -> case lit of
      ArrayLiteral arr -> Literal (ArrayLiteral (map (simplify' visited) arr))
      ObjectLiteral arr -> Literal (ObjectLiteral (map (\item -> item { value = simplify' visited item.value }) arr))
      _ -> Literal lit
    ObjectUpdate obj updates -> ObjectUpdate (simplify' visited obj) (map (\u -> u { value = simplify' visited u.value }) updates)
    
    Variable mbMod ident -> 
      let
        modPrefix = case mbMod of
          Just m -> joinWith "." m
          Nothing -> currentMod
        globalKey = modPrefix <> "." <> ident
      in if Array.elem globalKey visited then
           Variable mbMod ident
         else case Object.lookup globalKey env of
           Just exprToInline ->
             case exprToInline of
               Constructor typeName constructorName fieldNames -> Constructor typeName constructorName fieldNames
               Variable _ _ -> simplify' (visited <> [globalKey]) exprToInline
               Literal l -> Literal l
               _ -> Variable mbMod ident
           Nothing -> Variable mbMod ident
           
    Unsupported s -> Unsupported s

translate :: GlobalEnv -> Module -> PhpFile
translate env (Module mod) =
  { namespace: mod.moduleName
  , decls: concatMap (translateDecl env (joinWith "." mod.moduleName) mod.moduleName) mod.decls
  , imports: mod.imports
  }
