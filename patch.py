import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    text = f.read()

text = text.replace(
    "type LoopCtx = { ident :: String, params :: Array String, mutGroup :: Maybe (Array { ident :: String, params :: Array String }) }",
    "type LoopCtx = { ident :: String, params :: Array String, doneVar :: String, loopFuncVar :: String, resultVar :: String, varPrefix :: String }"
)

text = text.replace(
    "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Maybe LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }",
    "translateExprImpl :: Array String -> Map String String -> Map String String -> Maybe String -> Array LoopCtx -> Boolean -> Int -> TcoExpr -> { stmts :: Array PhpStmt, expr :: PhpExpr, nextId :: Int }"
)

# Replace all occurrences of ` Nothing ` that correspond to loopCtx
text = re.sub(r'(translateExprImpl [^ ]+ [^ ]+ [^ ]+ [^ ]+ )Nothing( (?:false|true|isTail) )', r'\1[]\2', text)
# And the ones with (Just something)
text = re.sub(r'(translateExprImpl [^ ]+ [^ ]+ [^ ]+ \(Just [^)]+\) )Nothing( (?:false|true|isTail) )', r'\1[]\2', text)
text = re.sub(r'(translateExprImpl [^ ]+ [^ ]+ [^ ]+ Nothing )Nothing( (?:false|true|isTail) )', r'\1[]\2', text)

with open("src/Phpurs/CodeGen_test.purs", "w") as f:
    f.write(text)

