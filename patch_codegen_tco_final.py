import re
import sys

with open("src/Phpurs/CodeGen.purs", "r") as f:
    content = f.read()

# 1. Update LoopCtx
content = re.sub(r'type LoopCtx = \{ ident :: String, params :: Array String, mutGroup :: Maybe \(Array \{ ident :: String, params :: Array String \}\) \}',
                 r'type LoopCtx = { ident :: String, params :: Array String, doneVar :: String, loopFuncVar :: String, varPrefix :: String }', content)

# 2. Update translateExprImpl signature
content = content.replace(
    "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Maybe LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }",
    "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Array LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }"
)

# 3. Replace Nothing with [] for loopCtx in translateExprImpl calls
patterns = [
    r'(translateExprImpl combinedRecVars namedBound newBound \(Just newName\) )Nothing',
    r'(translateExprImpl combinedRecVars namedBound newBound Nothing )Nothing',
    r'(translateExprImpl recVars namedBound bound Nothing )Nothing',
    r'(translateExprImpl recVars namedBound bound \(Just varName\) )Nothing',
    r'(translateExprImpl recVars namedBound newBound Nothing )Nothing',
    r'(translateExprImpl \[\] Map\.empty Map\.empty Nothing )Nothing'
]
for p in patterns:
    content = re.sub(p, r'\1[]', content)

# 4. We will use a script to replace Abs and UncurriedAbs entirely using AST manipulation.
# Since python regex is fragile for Purescript, I'll use simple string splitting.
