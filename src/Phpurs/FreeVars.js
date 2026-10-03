export const memoizeFreeVars = step => {
  // Only immutable TcoExpr identities are keys; values are immutable sets of
  // local-ID strings, with no references back to the expression graph. Weak
  // keys let a completed translation release its entire TCO tree naturally.
  const cache = new WeakMap();
  const recur = expr => {
    const cached = cache.get(expr);
    if (cached !== undefined) return cached;
    const result = compute(expr);
    cache.set(expr, result);
    return result;
  };
  const compute = step(recur);
  return recur;
};
