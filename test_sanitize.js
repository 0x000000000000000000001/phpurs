function sanitizeImpl(s) {
  return s.split('').map(c => {
    if (c >= 'A' && c <= 'Z') return 'maj' + c;
    if (c === "'") return 'quote';
    if (c === '"') return 'dquote';
    if (c === '$') return 'dollar';
    return c;
  }).join('');
}
console.log(sanitizeImpl("Type_Equality_To"));
console.log(sanitizeImpl("Util_Html_Encode_Encode__decodeHtmlEntities"));
console.log(sanitizeImpl("SFail'"));
