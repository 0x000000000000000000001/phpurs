let trace = [];
export const opaque = x => x;
export const stamp = x => () => { trace.push(x); return x; };
export const reset = () => { trace = []; };
export const events = () => trace.join(',');
