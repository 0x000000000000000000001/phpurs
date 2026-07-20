import re
import sys

with open("src/Phpurs/CodeGen.purs", "r") as f:
    content = f.read()

# 1. Update LoopCtx
content = re.sub(r'type LoopCtx = \{ ident :: String, params :: Array String, mutGroup :: Maybe \(Array \{ ident :: String, params :: Array String \}\) \}',
                 r'type LoopCtx = { ident :: String, params :: Array String, doneVar :: String, resultVar :: String, loopFuncVar :: String, varPrefix :: String }', content)

# 2. Update translateExprImpl signature
content = content.replace(
    "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Maybe LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }",
    "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Array LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }"
)

# 3. Update the initial translateExprImpl call in `translate` function
# Need to find `translateExprImpl ... Nothing Nothing false` and replace with `translateExprImpl ... Nothing [] false`
content = re.sub(r'(translateExprImpl combinedRecVars namedBound newBound \(Just newName\) )Nothing', r'\1[]', content)
content = re.sub(r'(translateExprImpl combinedRecVars namedBound newBound Nothing )Nothing', r'\1[]', content)
content = re.sub(r'(translateExprImpl recVars namedBound bound Nothing )Nothing', r'\1[]', content)
content = re.sub(r'(translateExprImpl recVars namedBound bound \(Just varName\) )Nothing', r'\1[]', content)
content = re.sub(r'(translateExprImpl recVars namedBound newBound Nothing )Nothing', r'\1[]', content)

# 4. We will replace the whole `LetRec` block to cleanly implement the `isLoop` logic!
# I will output the patch for LetRec separately and use patch tool.
