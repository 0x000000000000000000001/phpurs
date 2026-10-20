// Wrap generated PHP with the shared global runtime so unit tests can execute
// printed modules without emitting the output tree.
import { sharedRuntime } from '../../../output/Phpurs.Printer.Runtime/index.js';

export function withRuntime(php) {
  const cleaned = php.replace(/^require_once __DIR__ \. '\/\.\.\/runtime\.php';\n?/m, '')
    .replace(/^if \(!\\function_exists\('phpurs_curry_fallback'\).*runtime\.php';\n?/m, '');
  const open = cleaned.indexOf('<?php');
  if (open < 0) return cleaned;
  const after = cleaned.slice(open + 5).replace(/^\s*/, '');
  if (/^namespace\s*\{/.test(after)) {
    return `<?php\n\nnamespace {\n${sharedRuntime}}\n\n${after}`;
  }
  if (/^namespace\s+[^;{}]+\{/.test(after)) {
    return `<?php\n\nnamespace {\n${sharedRuntime}}\n\n${after}`;
  }
  const markers = [...after.matchAll(/^namespace\s+([^;]+);\s*$/gm)];
  if (markers.length === 0) {
    return `<?php\n\nnamespace {\n${sharedRuntime}\n${after}\n}\n`;
  }
  const prefix = after.slice(0, markers[0].index);
  let out = `<?php\n\nnamespace {\n${sharedRuntime}`;
  if (prefix.trim()) out += prefix;
  out += '}\n\n';
  for (let i = 0; i < markers.length; i++) {
    const start = markers[i].index + markers[i][0].length;
    const end = i + 1 < markers.length ? markers[i + 1].index : after.length;
    out += `namespace ${markers[i][1]} {\n${after.slice(start, end)}\n}\n`;
  }
  return out;
}

export function moduleBlock(php) {
  const cleaned = php.replace(/^<\?php\s*/, '');
  const markers = [...cleaned.matchAll(/^namespace\s+([^;]+);\s*$/gm)];
  if (markers.length === 0) return cleaned;
  let out = '';
  for (let i = 0; i < markers.length; i++) {
    const start = markers[i].index + markers[i][0].length;
    const end = i + 1 < markers.length ? markers[i + 1].index : cleaned.length;
    out += `namespace ${markers[i][1]} {\n${cleaned.slice(start, end)}\n}\n`;
  }
  return out;
}
