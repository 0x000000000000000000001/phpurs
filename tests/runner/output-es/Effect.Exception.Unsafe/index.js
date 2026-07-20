import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Effect$dException from "../Effect.Exception/index.js";
import * as Effect$dUnsafe from "../Effect.Unsafe/index.js";
const unsafeThrowException = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Effect$dUnsafe.unsafePerformEffect)(Effect$dException.throwException);
const unsafeThrow = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(unsafeThrowException)(Effect$dException.error);
export {unsafeThrow, unsafeThrowException};
