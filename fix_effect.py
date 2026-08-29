import re

with open("src/Phpurs/CodeGen.purs", "r") as f:
    content = f.read()

# Let's just modify the `App` translation to ALWAYS log what it translates
# Actually, I don't need to do that, I can just look at `translateExprImpl_`
