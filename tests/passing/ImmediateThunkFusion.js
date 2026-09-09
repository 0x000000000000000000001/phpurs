let calls = 0;
export const opaque = x => x;
export const countedSeed = _ => { ++calls; return 4; };
export const readCalls = () => calls;
export const invoke = f => f(undefined);
let depthCalls = 0;
export const countedDepth = n => { ++depthCalls; return n; };
export const readDepthCalls = () => depthCalls;
