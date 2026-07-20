// | This module defines an effect, actions and handlers for working
// | with JavaScript exceptions.
import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Data$dEither from "../Data.Either/index.js";
import * as Data$dMaybe from "../Data.Maybe/index.js";
import * as Effect from "../Effect/index.js";
import {catchException, error, errorWithCause, errorWithName, message, name, showErrorImpl, stackImpl, throwException} from "./foreign.js";
const $$try = action => catchException(Control$dSemigroupoid.composeImpl(Effect.pureE)(Data$dEither.Left))(() => {
  const a$p = action();
  return Data$dEither.$Either("Right", a$p);
});
const $$throw = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(throwException)(error);
const stack = /* #__PURE__ */ stackImpl(Data$dMaybe.Just)(Data$dMaybe.Nothing);
const showError = {show: showErrorImpl};
export {showError, stack, $$throw as throw, $$try as try};
export * from "./foreign.js";
