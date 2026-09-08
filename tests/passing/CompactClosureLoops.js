export const opaque = x => x;
export const invoke = f => x => f(x);
export const inspect = f => typeof f === 'function';
