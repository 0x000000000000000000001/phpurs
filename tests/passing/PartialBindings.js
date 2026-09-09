let calls = 0;
export const opaque = x => x;
export const counted = x => { ++calls; return x + 3; };
export const readCalls = () => calls;
export const invoke = f => x => f(x);
