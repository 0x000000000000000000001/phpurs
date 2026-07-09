const fs = require('fs');
let code = fs.readFileSync('src/Phpurs/CodeGen.purs', 'utf8');

// Replace InlineIdentityErasedDict and InlineIdentityWithDict
code = code.replace(/InlineIdentityErasedDict/g, 'InlineIdentity 1');
code = code.replace(/InlineIdentityWithDict/g, 'InlineIdentity 2');
code = code.replace(/InlineNewtypeCoercion 2/g, 'InlineIdentity 2');
code = code.replace(/InlineNewtypeCoercion 3/g, 'InlineIdentity 2');

code = code.replace(
    /Just \(InlineIdentity \d+\) ->/g,
    '// removed'
);
// We'll just do it with sed to be safer, JS replace is tricky with newlines.
