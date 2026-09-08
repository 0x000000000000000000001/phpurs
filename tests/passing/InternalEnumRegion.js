import { Red, Blue, End, Link } from './index.js';
export const opaque = x => x;
export const inspectColor = c => c instanceof Red;
export const inspectBox = box => {
  if (!(box.color instanceof Red)) return false;
  let chain = box.chain;
  while (chain instanceof Link) {
    if (!(chain.value0 instanceof Red || chain.value0 instanceof Blue)) return false;
    chain = chain.value2;
  }
  return chain instanceof End;
};
let retained;
export const retainColor = fn => () => { retained = fn; };
export const recalledColor = () => retained(undefined) instanceof Red;
