const fs = require('fs');
let content = fs.readFileSync('src/Phpurs/Printer.purs', 'utf-8');
const ffiFallback = `
if (!\\function_exists(__NAMESPACE__ . '\\\\phpurs_ffi_fallback')) {
  function phpurs_ffi_fallback(&$ffi, $key) {
    static $inst = null;
    if ($inst === null) $inst = new class { public function __invoke(...$args) { return $this; } };
    return \\array_key_exists($key, $ffi) ? null : $inst;
  }
}
`;
content = content.replace('"  }\\n" <>\\n      "}\\n"', '"  }\\n" <>\\n      "}\\n" <>\n' + ffiFallback.split('\n').filter(l => l.trim()).map(l => '      "' + l.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '\\n" <>').join('\n') + '\n      ""\n');
fs.writeFileSync('src/Phpurs/Printer.purs', content);
