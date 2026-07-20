import {composeImpl} from "./foreign.js";
const semigroupoidFn = {compose: composeImpl};
const compose = dict => dict.compose;
const composeFlipped = dictSemigroupoid => f => g => dictSemigroupoid.compose(g)(f);
export {compose, composeFlipped, semigroupoidFn};
export * from "./foreign.js";
