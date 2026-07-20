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
