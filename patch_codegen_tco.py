import re
import sys

with open("src/Phpurs/CodeGen.purs", "r") as f:
    content = f.read()

# I will use a python script to refactor CodeGen.purs instead of doing it manually since it's large.
