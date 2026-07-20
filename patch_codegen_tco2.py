import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    content = f.read()

# Replace LoopCtx definition
content = content.replace(
    "type LoopCtx = { ident :: String, params :: Array String, mutGroup :: Maybe (Array { ident :: String, params :: Array String }) }",
    "type LoopCtx = { ident :: String, params :: Array String, doneVar :: String, resultVar :: String }"
)

# In translateExprImpl signature
content = content.replace(
    "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Maybe LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }",
    "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Array LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }"
)

# We will need to do this carefully. Let's just create a full replacement for CodeGen.purs since it's tricky to patch accurately.
