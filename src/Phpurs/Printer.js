export const safeNameImpl = function(s) {
  return s.split('').map(c => {
    if (c === "'") return 'quote';
    if (c === '"') return 'dquote';
    if (c === '$') return 'dollar';
    if (c === '\\') return 'bslash';
    return c;
  }).join('');
};

export const safeFuncNameImpl = function(s) {
  return s.split('').map(c => {
    if (c >= 'A' && c <= 'Z') return 'maj' + c;
    if (c === "'") return 'quote';
    if (c === '"') return 'dquote';
    if (c === '$') return 'dollar';
    if (c === '\\') return 'bslash';
    return c;
  }).join('');
};

export const escapePhpStringImpl = function(s) {
  let out = "";
  for (let i = 0; i < s.length; i++) {
    const code = s.charCodeAt(i);
    if (code === 34) out += "\\\"";
    else if (code === 92) out += "\\\\";
    else if (code === 36) out += "\\$";
    else if (code >= 0xD800 && code <= 0xDFFF) {
       if (code >= 0xD800 && code <= 0xDBFF && i + 1 < s.length) {
         const next = s.charCodeAt(i + 1);
         if (next >= 0xDC00 && next <= 0xDFFF) {
           out += s.charAt(i) + s.charAt(i + 1);
           i++;
           continue;
         }
       }
       const b1 = 0xE0 | (code >> 12);
       const b2 = 0x80 | ((code >> 6) & 0x3F);
       const b3 = 0x80 | (code & 0x3F);
       out += "\\x" + b1.toString(16).toUpperCase() + 
              "\\x" + b2.toString(16).toUpperCase() + 
              "\\x" + b3.toString(16).toUpperCase();
    } else {
       out += s.charAt(i);
    }
  }
  return out;
};
