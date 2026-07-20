import re

with open("src/Phpurs/CodeGen_test.purs", "r") as f:
    text = f.read()

uncurried_effect_abs_start = text.find("  UncurriedEffectAbs args body ->")

new_abs = """  Abs args body ->
    let
      argsArray = map (\\(Tuple mbI lvl) -> localId mbI lvl) (toArray args)
      fvs = freeVars tcoExpr
      useVars = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      resBody = translateExprImpl recVars namedBound bound Nothing [] true nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }

  UncurriedAbs args body ->
    let
      argsArray = map (\\(Tuple mbI lvl) -> localId mbI lvl) args
      fvs = freeVars tcoExpr
      useVars = map (\\v -> let mapped = fromMaybe v (Map.lookup v bound) in if Array.elem mapped recVars then "&" <> mapped else mapped) (Array.fromFoldable fvs)
      
      resBody = translateExprImpl recVars namedBound bound Nothing [] true nextId body
    in
      { stmts: [], expr: PhpFunction useVars argsArray (resBody.stmts <> [ PhpReturn resBody.expr ]), nextId: resBody.nextId }

"""

text = text[:uncurried_effect_abs_start] + new_abs + text[uncurried_effect_abs_start:]

with open("src/Phpurs/CodeGen_test.purs", "w") as f:
    f.write(text)

