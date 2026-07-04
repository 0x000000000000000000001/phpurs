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
collectGlobalsAlt (CaseAlternative { expression }) = collectGlobals expression

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
  Case exprs alts -> Case (map (replaceGlobals globals) exprs) (map (\(CaseAlternative ca) -> CaseAlternative (ca { expression = replaceGlobals globals ca.expression })) alts)
  Let binds expr -> Let (map (\b -> case b of
    NonRec i e -> NonRec i (replaceGlobals globals e)
    Rec rBinds -> Rec (map (\rb -> rb { expression = replaceGlobals globals rb.expression }) rBinds)
  ) binds) (replaceGlobals globals expr)
  Constructor t c n -> Constructor t c n
  Accessor prop expr -> Accessor prop (replaceGlobals globals expr)
  ObjectUpdate expr updates -> ObjectUpdate (replaceGlobals globals expr) (map (\u -> u { value = replaceGlobals globals u.value }) updates)
  Unsupported s -> Unsupported s

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
  ["Data", "Eq"], "eqBooleanImpl", 2 -> Just "==="
  ["Data", "Eq"], "eqCharImpl", 2 -> Just "==="
  ["Data", "HeytingAlgebra"], "boolAnd", 2 -> Just "&&"
  ["Data", "HeytingAlgebra"], "boolOr", 2 -> Just "||"
  ["Data", "Semigroup"], "concatString", 2 -> Just "."
  _, _, _ -> Nothing
matchIntrinsicOperator _ _ = Nothing

data InlineFunction
  = InlineIdentity
  | InlineConst
  | InlineApply
  | InlineFlip

matchInlineFunction :: Expr -> Int -> Maybe InlineFunction
matchInlineFunction (Variable (Just mod) ident) argCount = case mod, ident, argCount of
  ["Control", "Category"], "identity", 2 -> Just InlineIdentity
  ["Data", "Function"], "const", 2 -> Just InlineConst
  ["Data", "Function"], "apply", 2 -> Just InlineApply
  ["Data", "Function"], "flip", 3 -> Just InlineFlip
  _, _, _ -> Nothing
matchInlineFunction _ _ = Nothing

translateExprImpl :: Array String -> Maybe { ident :: String, args :: Array String } -> Int -> Expr -> CompileResult
translateExprImpl recVars tcoCtx nextId expr = case expr of
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
          
          bodyRes = translateExprImpl recVars tcoCtx nextId rewrittenBody
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
          
          bodyRes = translateTailCall recVars "" [] nextId rewrittenBody -- Just use normal flatten for body
        in { stmts: [], expr: PhpFunction formattedFvs extracted.args (hoistStmts <> bodyRes.stmts), nextId: bodyRes.nextId }
        
  _ -> case tcoCtx of
    Just ctx ->
      let
        loopBody = translateTailCall recVars ctx.ident ctx.args nextId expr
      in { stmts: [PhpWhile (PhpBoolean true) loopBody.stmts], expr: PhpRaw "null", nextId: loopBody.nextId }
    Nothing -> case expr of
      Variable mbMod ident ->
        case mbMod of
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
          Just InlineIdentity ->
            translateExprImpl recVars Nothing nextId (extracted.args `unsafeIndex` 1)
          Just InlineConst ->
            translateExprImpl recVars Nothing nextId (extracted.args `unsafeIndex` 0)
          Just InlineApply ->
            let 
              fnRes = translateExprImpl recVars Nothing nextId (extracted.args `unsafeIndex` 0)
              argRes = translateExprImpl recVars Nothing fnRes.nextId (extracted.args `unsafeIndex` 1)
            in { stmts: fnRes.stmts <> argRes.stmts, expr: PhpCall fnRes.expr [argRes.expr], nextId: argRes.nextId }
          Just InlineFlip ->
            let
              fnRes = translateExprImpl recVars Nothing nextId (extracted.args `unsafeIndex` 0)
              argYRes = translateExprImpl recVars Nothing fnRes.nextId (extracted.args `unsafeIndex` 2)
              argXRes = translateExprImpl recVars Nothing argYRes.nextId (extracted.args `unsafeIndex` 1)
            in { stmts: fnRes.stmts <> argYRes.stmts <> argXRes.stmts, expr: PhpCall fnRes.expr [argYRes.expr, argXRes.expr], nextId: argXRes.nextId }
          Nothing ->
            case matchIntrinsicOperator extracted.fn (length extracted.args) of
              Just op -> 
                let
                  processArg acc argExpr =
                    let res = translateExprImpl recVars Nothing acc.nextId argExpr
                    in { stmts: acc.stmts <> res.stmts, exprs: acc.exprs <> [res.expr], nextId: res.nextId }
                  argsRes = foldl processArg { stmts: [], exprs: [], nextId: nextId } extracted.args
                in { stmts: argsRes.stmts, expr: PhpBinOp op (argsRes.exprs `unsafeIndex` 0) (argsRes.exprs `unsafeIndex` 1), nextId: argsRes.nextId }
              Nothing ->
                let
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
      Constructor _ constructorName fieldNames ->
        let
          numFields = length fieldNames
          body = PhpNew ("Phpurs_Data" <> show numFields) ([PhpString constructorName] <> map PhpVar fieldNames)
          singletonBody = PhpBinOp "??=" (PhpRaw ("$GLOBALS['__phpurs_data0_" <> constructorName <> "']")) body
        in if numFields == 0 then { stmts: [], expr: singletonBody, nextId } else { stmts: [], expr: PhpFunction [] fieldNames [PhpReturn body], nextId }
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
          
          tmpVar = "__update_tmp_" <> show updatesRes.nextId
          cloneStmt = PhpAssign tmpVar (PhpClone resE.expr)
          updateStmts = map (\u -> PhpAssign (tmpVar <> "->" <> u.key) u.value) updatesRes.updates
          
        in { stmts: resE.stmts <> updatesRes.stmts <> [cloneStmt] <> updateStmts, expr: PhpVar tmpVar, nextId: updatesRes.nextId + 1 }
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

translateDecl :: GlobalEnv -> String -> Array String -> Bind -> Array PhpDecl
translateDecl env currentModStr moduleName bind = case bind of
  NonRec ident expr ->
    let 
      prefix = joinWith "_" moduleName <> "_"
      res = translateExprImpl [] Nothing 0 (simplify env currentModStr expr)
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
                translateExprImpl [] (Just { ident: rb.identifier, args: extracted.args }) 0 (simplify env currentModStr rb.expression)
              else
                translateExprImpl [] Nothing 0 (simplify env currentModStr rb.expression)
      in { identifier: prefix <> rb.identifier
         , expression: case res.expr of
                         PhpFunction _ args stmts | length res.stmts == 0 -> PhpNativeFunction (prefix <> rb.identifier) args stmts
                         _ -> PhpValueThunk (prefix <> rb.identifier) (if length res.stmts > 0 then PhpCall (PhpFunction [] [] (res.stmts <> [PhpReturn res.expr])) [] else res.expr)
         }
    ) (sortRecBinds rBinds)


type GlobalEnv = Object Expr

buildGlobalEnv :: Array Module -> GlobalEnv
buildGlobalEnv mods =
  let
    processDecl env modName decl = case decl of
      NonRec ident expr -> Object.insert (modName <> "." <> ident) expr env
      Rec binds -> foldl (\acc b -> Object.insert (modName <> "." <> b.identifier) b.expression acc) env binds
      
    processMod env (Module m) =
      let modNameStr = joinWith "." m.moduleName
      in foldl (\acc d -> processDecl acc modNameStr d) env m.decls
  in foldl processMod Object.empty mods

simplify :: GlobalEnv -> String -> Expr -> Expr
simplify env currentMod expr = simplify' [] expr
  where
  simplify' visited e = case e of
    Call f arg ->
      let
        f' = simplify' visited f
        arg' = simplify' visited arg
      in case f' of
        Variable mbMod ident ->
          let
            modPrefix = case mbMod of
              Just m -> joinWith "." m
              Nothing -> currentMod
            globalKey = modPrefix <> "." <> ident
          in case Object.lookup globalKey env of
            Just (Lambda param (Case [Variable Nothing p2] [CaseAlternative ca])) | param == p2 ->
              case ca.binders of
                [ConstructorBinder _ _ _ [VarBinder v]] ->
                  case ca.expression of
                    Accessor prop (Variable Nothing p3) | v == p3 ->
                      case arg' of
                        Variable argMbMod argIdent ->
                          let
                            argModPrefix = case argMbMod of
                              Just m -> joinWith "." m
                              Nothing -> currentMod
                            argGlobalKey = argModPrefix <> "." <> argIdent
                          in case Object.lookup argGlobalKey env of
                            Just (Literal (ObjectLiteral fields)) ->
                              case find (\item -> item.key == prop) fields of
                                Just item -> item.value
                                Nothing -> Accessor prop arg'
                            Just (Call (Variable _ _) (Literal (ObjectLiteral fields))) ->
                              case find (\item -> item.key == prop) fields of
                                Just item -> item.value
                                Nothing -> Accessor prop arg'
                            _ -> Accessor prop arg'
                        Literal (ObjectLiteral fields) ->
                          case find (\item -> item.key == prop) fields of
                            Just item -> item.value
                            Nothing -> Accessor prop arg'
                        Call (Variable _ _) (Literal (ObjectLiteral fields)) ->
                          case find (\item -> item.key == prop) fields of
                            Just item -> item.value
                            Nothing -> Accessor prop arg'
                        _ -> Accessor prop arg'
                    _ -> Call f' arg'
                _ -> Call f' arg'
            _ -> Call f' arg'
        _ -> Call f' arg'
        
    Accessor prop arg ->
      let arg' = simplify' visited arg
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
                Just item -> item.value
                Nothing -> Accessor prop arg'
            Just (Call (Variable _ _) (Literal (ObjectLiteral fields))) ->
              case find (\item -> item.key == prop) fields of
                Just item -> item.value
                Nothing -> Accessor prop arg'
            _ -> Accessor prop arg'
        Literal (ObjectLiteral fields) ->
          case find (\item -> item.key == prop) fields of
            Just item -> item.value
            Nothing -> Accessor prop arg'
        Call (Variable _ _) (Literal (ObjectLiteral fields)) ->
          case find (\item -> item.key == prop) fields of
            Just item -> item.value
            Nothing -> Accessor prop arg'
        _ -> Accessor prop arg'
        
    Let binds body -> Let (map (\b -> case b of
      NonRec ident val -> NonRec ident (simplify' visited val)
      Rec arr -> Rec (map (\a -> a { expression = simplify' visited a.expression }) arr)
      ) binds) (simplify' visited body)
      
    Lambda arg body -> Lambda arg (simplify' visited body)
    Case binders alts -> Case (map (simplify' visited) binders) (map (\(CaseAlternative ca) -> CaseAlternative (ca { expression = simplify' visited ca.expression })) alts)
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
             let simplified = simplify' (visited <> [globalKey]) exprToInline
             in case simplified of
               Variable _ _ -> simplified
               _ -> Variable mbMod ident
           Nothing -> Variable mbMod ident
           
    Unsupported s -> Unsupported s

translate :: GlobalEnv -> Module -> PhpFile
translate env (Module mod) =
  { namespace: mod.moduleName
  , decls: concatMap (translateDecl env (joinWith "." mod.moduleName) mod.moduleName) mod.decls
  , imports: mod.imports
  }
