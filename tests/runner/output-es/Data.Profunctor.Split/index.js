import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
const $SplitF = (_1, _2, _3) => ({tag: "SplitF", _1, _2, _3});
const identity = x => x;
const SplitF = value0 => value1 => value2 => $SplitF(value0, value1, value2);
const unSplit = f => v => f(v._1)(v._2)(v._3);
const split = f => g => fx => $SplitF(f, g, fx);
const profunctorSplit = {dimap: f => g => v => $SplitF(Control$dSemigroupoid.composeImpl(v._1)(f), Control$dSemigroupoid.composeImpl(g)(v._2), v._3)};
const lowerSplit = dictInvariant => v => dictInvariant.imap(v._2)(v._1)(v._3);
const liftSplit = fx => $SplitF(identity, identity, fx);
const hoistSplit = nat => v => {
  const $0 = v._1;
  const $1 = v._2;
  return Control$dSemigroupoid.composeImpl(fx => $SplitF($0, $1, fx))(nat)(v._3);
};
const functorSplit = {map: f => v => $SplitF(v._1, Control$dSemigroupoid.composeImpl(f)(v._2), v._3)};
export {$SplitF, SplitF, functorSplit, hoistSplit, identity, liftSplit, lowerSplit, profunctorSplit, split, unSplit};
