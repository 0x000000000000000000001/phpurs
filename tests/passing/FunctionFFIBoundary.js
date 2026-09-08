export const opaque = value => value;
export const invokeClosure = f => x => f(x);
export const invokeRecord = handler => x => handler.callback(x);
export const roundTripForeign = value => value;
let retained;
export const retain = handler => () => { retained = handler.callback; };
export const recall = x => () => retained(x);
