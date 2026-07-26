const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/Printer.purs', 'utf8');

// Replace all occurrences of `printExpr ` with `(printExpr currentModPrefix allArities) ` if used as a function pointer
code = code.replace(/map printExpr /g, 'map (printExpr currentModPrefix allArities) ');
code = code.replace(/printExpr \(/g, 'printExpr currentModPrefix allArities (');
code = code.replace(/printExpr c/g, 'printExpr currentModPrefix allArities c');
code = code.replace(/printExpr e/g, 'printExpr currentModPrefix allArities e');
code = code.replace(/printExpr t/g, 'printExpr currentModPrefix allArities t');
code = code.replace(/printExpr v/g, 'printExpr currentModPrefix allArities v');
code = code.replace(/printExpr m/g, 'printExpr currentModPrefix allArities m');
code = code.replace(/printExpr sw/g, 'printExpr currentModPrefix allArities sw');
code = code.replace(/printExpr cond/g, 'printExpr currentModPrefix allArities cond');
code = code.replace(/printExpr obj/g, 'printExpr currentModPrefix allArities obj');
code = code.replace(/printExpr left/g, 'printExpr currentModPrefix allArities left');
code = code.replace(/printExpr right/g, 'printExpr currentModPrefix allArities right');
code = code.replace(/printExpr subj/g, 'printExpr currentModPrefix allArities subj');
code = code.replace(/printExpr defExpr/g, 'printExpr currentModPrefix allArities defExpr');
code = code.replace(/printExpr abs/g, 'printExpr currentModPrefix allArities abs');
code = code.replace(/printExpr inner/g, 'printExpr currentModPrefix allArities inner');

// Ensure we didn't duplicate currentModPrefix allArities
code = code.replace(/printExpr currentModPrefix allArities currentModPrefix allArities/g, 'printExpr currentModPrefix allArities');
// Also clean up the signature we might have mangled
code = code.replace(/printExpr currentModPrefix allArities ::/g, 'printExpr ::');

fs.writeFileSync('src/Phpurs/Printer.purs', code);
