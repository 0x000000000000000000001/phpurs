import {Cell, Stop, Missing, Present} from './index.js';
export const opaque = x => x;
export const inspectChain = chain => {
  while (chain instanceof Cell) {
    if (!Number.isInteger(chain.value0)) return false;
    chain = chain.value1;
  }
  return chain instanceof Stop;
};
export const inspectWrapped = wrapped => wrapped instanceof Missing || (wrapped instanceof Present && inspectChain(wrapped.value0));
export const observe = f => chain => {
  if (!inspectChain(chain)) throw new Error('public tree lost');
  return f(chain);
};
