import * as $runtime from "../runtime.js";
import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Data$dArray$dNonEmpty from "../Data.Array.NonEmpty/index.js";
import * as Data$dMaybe from "../Data.Maybe/index.js";
import * as Data$dSemigroup from "../Data.Semigroup/index.js";
import * as Data$dString$dCodeUnits from "../Data.String.CodeUnits/index.js";
import * as Data$dString$dNonEmpty$dInternal from "../Data.String.NonEmpty.Internal/index.js";
import * as Data$dString$dUnsafe from "../Data.String.Unsafe/index.js";
const fromJust = v => {
  if (v.tag === "Just") { return v._1; }
  $runtime.fail();
};
const snoc = c => s => s + Data$dString$dCodeUnits.singleton(c);
const singleton = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dString$dNonEmpty$dInternal.NonEmptyString)(Data$dString$dCodeUnits.singleton);
const liftS = f => v => f(v);
const takeWhile = f => Control$dSemigroupoid.composeImpl(Data$dString$dNonEmpty$dInternal.fromString)(v => Data$dString$dCodeUnits.take(Data$dString$dCodeUnits.countPrefix(f)(v))(v));
const lastIndexOf$p = pat => Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodeUnits.lastIndexOf$p(pat));
const lastIndexOf = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodeUnits.lastIndexOf);
const indexOf$p = pat => Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodeUnits.indexOf$p(pat));
const indexOf = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodeUnits.indexOf);
const fromNonEmptyString = v => v;
const length = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dString$dCodeUnits.length)(fromNonEmptyString);
const splitAt = i => nes => {
  const v = Data$dString$dCodeUnits.splitAt(i)(nes);
  return {before: v.before === "" ? Data$dMaybe.Nothing : Data$dMaybe.$Maybe("Just", v.before), after: v.after === "" ? Data$dMaybe.Nothing : Data$dMaybe.$Maybe("Just", v.after)};
};
const take = i => nes => {
  if (i < 1) { return Data$dMaybe.Nothing; }
  return Data$dMaybe.$Maybe("Just", Data$dString$dCodeUnits.take(i)(nes));
};
const takeRight = i => nes => {
  if (i < 1) { return Data$dMaybe.Nothing; }
  return Data$dMaybe.$Maybe("Just", Data$dString$dCodeUnits.drop(Data$dString$dCodeUnits.length(nes) - i | 0)(nes));
};
const toChar = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dString$dCodeUnits.toChar)(fromNonEmptyString);
const toCharArray = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dString$dCodeUnits.toCharArray)(fromNonEmptyString);
const toNonEmptyCharArray = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromJust)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty.fromArray)(toCharArray));
const uncons = nes => (
  {
    head: Data$dString$dUnsafe.charAt(0)(nes),
    tail: (() => {
      const $0 = Data$dString$dCodeUnits.drop(1)(nes);
      if ($0 === "") { return Data$dMaybe.Nothing; }
      return Data$dMaybe.$Maybe("Just", $0);
    })()
  }
);
const fromFoldable1 = dictFoldable1 => dictFoldable1.foldMap1(Data$dSemigroup.semigroupString)(singleton);
const fromCharArray = v => {
  if (v.length === 0) { return Data$dMaybe.Nothing; }
  return Data$dMaybe.$Maybe("Just", Data$dString$dCodeUnits.fromCharArray(v));
};
const fromNonEmptyCharArray = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromJust)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromCharArray)(Data$dArray$dNonEmpty.toArray));
const dropWhile = f => Control$dSemigroupoid.composeImpl(Data$dString$dNonEmpty$dInternal.fromString)(v => Data$dString$dCodeUnits.drop(Data$dString$dCodeUnits.countPrefix(f)(v))(v));
const dropRight = i => nes => {
  if (i >= Data$dString$dCodeUnits.length(nes)) { return Data$dMaybe.Nothing; }
  return Data$dMaybe.$Maybe("Just", Data$dString$dCodeUnits.take(Data$dString$dCodeUnits.length(nes) - i | 0)(nes));
};
const drop = i => nes => {
  if (i >= Data$dString$dCodeUnits.length(nes)) { return Data$dMaybe.Nothing; }
  return Data$dMaybe.$Maybe("Just", Data$dString$dCodeUnits.drop(i)(nes));
};
const countPrefix = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodeUnits.countPrefix);
const cons = c => s => Data$dString$dCodeUnits.singleton(c) + s;
const charAt = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodeUnits.charAt);
export {
  charAt,
  cons,
  countPrefix,
  drop,
  dropRight,
  dropWhile,
  fromCharArray,
  fromFoldable1,
  fromJust,
  fromNonEmptyCharArray,
  fromNonEmptyString,
  indexOf,
  indexOf$p,
  lastIndexOf,
  lastIndexOf$p,
  length,
  liftS,
  singleton,
  snoc,
  splitAt,
  take,
  takeRight,
  takeWhile,
  toChar,
  toCharArray,
  toNonEmptyCharArray,
  uncons
};
