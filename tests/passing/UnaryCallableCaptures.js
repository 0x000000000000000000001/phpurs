export const opaque = value => value;
export const invoke = f => x => f(x);
export const overapply = f => x => y => f(x)(y);
