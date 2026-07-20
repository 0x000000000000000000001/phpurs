import * as $runtime from "../runtime.js";
import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Data$dArray$dNonEmpty from "../Data.Array.NonEmpty/index.js";
import * as Data$dMaybe from "../Data.Maybe/index.js";
import * as Data$dSemigroup from "../Data.Semigroup/index.js";
import * as Data$dString$dCodePoints from "../Data.String.CodePoints/index.js";
import * as Data$dString$dCodeUnits from "../Data.String.CodeUnits/index.js";
import * as Data$dString$dNonEmpty$dInternal from "../Data.String.NonEmpty.Internal/index.js";
const fromJust = v => {
  if (v.tag === "Just") { return v._1; }
  $runtime.fail();
};
const snoc = c => s => s + Data$dString$dCodePoints.singleton(c);
const singleton = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dString$dNonEmpty$dInternal.NonEmptyString)(Data$dString$dCodePoints.singleton);
const liftS = f => v => f(v);
const takeWhile = f => Control$dSemigroupoid.composeImpl(Data$dString$dNonEmpty$dInternal.fromString)(v => Data$dString$dCodePoints.take(Data$dString$dCodePoints.countPrefix(f)(v))(v));
const lastIndexOf$p = pat => Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodePoints.lastIndexOf$p(pat));
const lastIndexOf = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodePoints.lastIndexOf);
const indexOf$p = pat => Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodePoints.indexOf$p(pat));
const indexOf = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodePoints.indexOf);
const fromNonEmptyString = v => v;
const length = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dString$dCodePoints.length)(fromNonEmptyString);
const splitAt = i => nes => {
  const v = Data$dString$dCodePoints.splitAt(i)(nes);
  return {before: v.before === "" ? Data$dMaybe.Nothing : Data$dMaybe.$Maybe("Just", v.before), after: v.after === "" ? Data$dMaybe.Nothing : Data$dMaybe.$Maybe("Just", v.after)};
};
const take = i => nes => {
  if (i < 1) { return Data$dMaybe.Nothing; }
  return Data$dMaybe.$Maybe("Just", Data$dString$dCodePoints.take(i)(nes));
};
const toCodePointArray = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dString$dCodePoints.toCodePointArray)(fromNonEmptyString);
const toNonEmptyCodePointArray = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromJust)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty.fromArray)(toCodePointArray));
const uncons = nes => (
  {
    head: (() => {
      const $0 = Data$dString$dCodePoints.codePointAt(0)(nes);
      if ($0.tag === "Just") { return $0._1; }
      $runtime.fail();
    })(),
    tail: (() => {
      const $0 = Data$dString$dCodeUnits.drop(Data$dString$dCodeUnits.length(Data$dString$dCodePoints.take(1)(nes)))(nes);
      if ($0 === "") { return Data$dMaybe.Nothing; }
      return Data$dMaybe.$Maybe("Just", $0);
    })()
  }
);
const fromFoldable1 = dictFoldable1 => dictFoldable1.foldMap1(Data$dSemigroup.semigroupString)(singleton);
const fromCodePointArray = v => {
  if (v.length === 0) { return Data$dMaybe.Nothing; }
  return Data$dMaybe.$Maybe("Just", Data$dString$dCodePoints.fromCodePointArray(v));
};
const fromNonEmptyCodePointArray = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromJust)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromCodePointArray)(Data$dArray$dNonEmpty.toArray));
const dropWhile = f => Control$dSemigroupoid.composeImpl(Data$dString$dNonEmpty$dInternal.fromString)(v => Data$dString$dCodePoints.dropWhile(f)(v));
const drop = i => nes => {
  if (i >= Data$dString$dCodePoints.length(nes)) { return Data$dMaybe.Nothing; }
  return Data$dMaybe.$Maybe("Just", Data$dString$dCodeUnits.drop(Data$dString$dCodeUnits.length(Data$dString$dCodePoints.take(i)(nes)))(nes));
};
const countPrefix = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodePoints.countPrefix);
const cons = c => s => Data$dString$dCodePoints.singleton(c) + s;
const codePointAt = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(liftS)(Data$dString$dCodePoints.codePointAt);
export {
  codePointAt,
  cons,
  countPrefix,
  drop,
  dropWhile,
  fromCodePointArray,
  fromFoldable1,
  fromJust,
  fromNonEmptyCodePointArray,
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
  takeWhile,
  toCodePointArray,
  toNonEmptyCodePointArray,
  uncons
};
