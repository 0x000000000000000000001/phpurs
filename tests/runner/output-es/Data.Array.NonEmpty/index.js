import * as $runtime from "../runtime.js";
import * as Control$dBind from "../Control.Bind/index.js";
import * as Control$dMonad$dRec$dClass from "../Control.Monad.Rec.Class/index.js";
import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Data$dArray from "../Data.Array/index.js";
import * as Data$dArray$dNonEmpty$dInternal from "../Data.Array.NonEmpty.Internal/index.js";
import * as Data$dFoldable from "../Data.Foldable/index.js";
import * as Data$dFunctor from "../Data.Functor/index.js";
import * as Data$dFunctorWithIndex from "../Data.FunctorWithIndex/index.js";
import * as Data$dMaybe from "../Data.Maybe/index.js";
import * as Data$dNonEmpty from "../Data.NonEmpty/index.js";
import * as Data$dOrd from "../Data.Ord/index.js";
import * as Data$dSemigroup$dFoldable from "../Data.Semigroup.Foldable/index.js";
import * as Data$dTuple from "../Data.Tuple/index.js";
import * as Unsafe$dCoerce from "../Unsafe.Coerce/index.js";
const max = x => y => {
  const v = Data$dOrd.ordInt.compare(x)(y);
  if (v === "LT") { return y; }
  if (v === "EQ") { return x; }
  if (v === "GT") { return x; }
  $runtime.fail();
};
const fromJust = v => {
  if (v.tag === "Just") { return v._1; }
  $runtime.fail();
};
const unsafeIndex1 = $0 => $1 => $0[$1];
const transpose = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Unsafe$dCoerce.unsafeCoerce)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.transpose)(Unsafe$dCoerce.unsafeCoerce));
const toArray = v => v;
const unionBy$p = eq => xs => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Data$dArray.unionBy(eq)(xs));
const union$p = dictEq => unionBy$p(dictEq.eq);
const unionBy = eq => xs => Control$dSemigroupoid.composeImpl(Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Data$dArray.unionBy(eq)(xs)))(toArray);
const union = dictEq => unionBy(dictEq.eq);
const unzip = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(v => Data$dTuple.$Tuple(v._1, v._2))(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.unzip)(toArray));
const updateAt = i => x => Control$dSemigroupoid.composeImpl(Unsafe$dCoerce.unsafeCoerce)(Control$dSemigroupoid.composeImpl(Data$dArray.updateAt(i)(x))(toArray));
const zip = xs => ys => Data$dArray.zipWithImpl(Data$dTuple.Tuple, xs, ys);
const zipWith = f => xs => ys => Data$dArray.zipWithImpl(f, xs, ys);
const zipWithA = dictApplicative => Data$dArray.zipWithA(dictApplicative);
const splitAt = i => xs => Data$dArray.splitAt(i)(xs);
const some = dictAlternative => dictLazy => Control$dSemigroupoid.composeImpl(Unsafe$dCoerce.unsafeCoerce)(Data$dArray.some(dictAlternative)(dictLazy));
const snoc$p = xs => x => Data$dArray.snoc(xs)(x);
const snoc = xs => x => Data$dArray.snoc(xs)(x);
const singleton = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Data$dArray.singleton);
const replicate = i => x => Data$dArray.replicateImpl(max(1)(i), x);
const range = x => y => Data$dArray.rangeImpl(x, y);
const prependArray = xs => ys => [...xs, ...ys];
const modifyAt = i => f => Control$dSemigroupoid.composeImpl(Unsafe$dCoerce.unsafeCoerce)(Control$dSemigroupoid.composeImpl(Data$dArray.modifyAt(i)(f))(toArray));
const intersectBy$p = eq => xs => Data$dArray.intersectBy(eq)(xs);
const intersectBy = eq => xs => Control$dSemigroupoid.composeImpl(intersectBy$p(eq)(xs))(toArray);
const intersect$p = dictEq => intersectBy$p(dictEq.eq);
const intersect = dictEq => intersectBy(dictEq.eq);
const intercalate = dictSemigroup => {
  const $0 = Data$dSemigroup$dFoldable.intercalateMap(Data$dArray$dNonEmpty$dInternal.foldable1NonEmptyArray)(dictSemigroup);
  return a => $0(a)(Data$dSemigroup$dFoldable.identity);
};
const insertAt = i => x => Control$dSemigroupoid.composeImpl(Unsafe$dCoerce.unsafeCoerce)(Control$dSemigroupoid.composeImpl(Data$dArray.insertAt(i)(x))(toArray));
const fromFoldable1 = dictFoldable1 => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)((() => {
  const $0 = dictFoldable1.Foldable0().foldr;
  return $1 => Data$dArray.fromFoldableImpl($0, $1);
})());
const fromArray = xs => {
  if (xs.length > 0) { return Data$dMaybe.$Maybe("Just", xs); }
  return Data$dMaybe.Nothing;
};
const fromFoldable = dictFoldable => Control$dSemigroupoid.composeImpl(fromArray)((() => {
  const $0 = dictFoldable.foldr;
  return $1 => Data$dArray.fromFoldableImpl($0, $1);
})());
const transpose$p = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromArray)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.transpose)(Unsafe$dCoerce.unsafeCoerce));
const foldr1 = /* #__PURE__ */ (() => Data$dArray$dNonEmpty$dInternal.foldable1NonEmptyArray.foldr1)();
const foldl1 = /* #__PURE__ */ (() => Data$dArray$dNonEmpty$dInternal.foldable1NonEmptyArray.foldl1)();
const foldMap1 = dictSemigroup => Data$dSemigroup$dFoldable.foldMap1DefaultL(Data$dArray$dNonEmpty$dInternal.foldable1NonEmptyArray)(Data$dFunctor.functorArray)(dictSemigroup);
const fold1 = dictSemigroup => Data$dSemigroup$dFoldable.foldMap1DefaultL(Data$dArray$dNonEmpty$dInternal.foldable1NonEmptyArray)(Data$dFunctor.functorArray)(dictSemigroup)(Data$dSemigroup$dFoldable.identity);
const difference$p = dictEq => Data$dFoldable.foldrArray(Data$dArray.delete(dictEq));
const cons$p = x => xs => [x, ...xs];
const fromNonEmpty = v => [v._1, ...v._2];
const concatMap = b => a => Control$dBind.arrayBind(a)(b);
const concat = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.concat)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(toArray)(/* #__PURE__ */ Data$dFunctor.arrayMap(toArray))));
const appendArray = xs => ys => [...xs, ...ys];
const alterAt = i => f => Control$dSemigroupoid.composeImpl(Data$dArray.alterAt(i)(f))(toArray);
const head = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromJust)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.head)(toArray));
const init = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromJust)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.init)(toArray));
const last = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromJust)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.last)(toArray));
const tail = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromJust)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.tail)(toArray));
const uncons = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromJust)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.uncons)(toArray));
const toNonEmpty = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(v => Data$dNonEmpty.$NonEmpty(v.head, v.tail))(uncons);
const unsnoc = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(fromJust)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.unsnoc)(toArray));
const all = p => Control$dSemigroupoid.composeImpl(Data$dArray.all(p))(toArray);
const any = p => Control$dSemigroupoid.composeImpl(Data$dArray.any(p))(toArray);
const catMaybes = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.catMaybes)(toArray);
const $$delete = dictEq => x => Control$dSemigroupoid.composeImpl(Data$dArray.delete(dictEq)(x))(toArray);
const deleteAt = i => Control$dSemigroupoid.composeImpl(Data$dArray.deleteAt(i))(toArray);
const deleteBy = f => x => Control$dSemigroupoid.composeImpl(Data$dArray.deleteBy(f)(x))(toArray);
const difference = dictEq => {
  const difference$p1 = Data$dFoldable.foldrArray(Data$dArray.delete(dictEq));
  return xs => Control$dSemigroupoid.composeImpl(difference$p1(xs))(toArray);
};
const drop = i => Control$dSemigroupoid.composeImpl(Data$dArray.drop(i))(toArray);
const dropEnd = i => Control$dSemigroupoid.composeImpl(Data$dArray.dropEnd(i))(toArray);
const dropWhile = f => Control$dSemigroupoid.composeImpl(Data$dArray.dropWhile(f))(toArray);
const elem = dictEq => x => Control$dSemigroupoid.composeImpl(Data$dArray.elem(dictEq)(x))(toArray);
const elemIndex = dictEq => x => Control$dSemigroupoid.composeImpl(Data$dArray.elemIndex(dictEq)(x))(toArray);
const elemLastIndex = dictEq => x => Control$dSemigroupoid.composeImpl(Data$dArray.elemLastIndex(dictEq)(x))(toArray);
const filter = f => Control$dSemigroupoid.composeImpl(Data$dArray.filter(f))(toArray);
const filterA = dictApplicative => {
  const filterA1 = Data$dArray.filterA(dictApplicative);
  return f => Control$dSemigroupoid.composeImpl(filterA1(f))(toArray);
};
const find = p => Control$dSemigroupoid.composeImpl(Data$dArray.find(p))(toArray);
const findIndex = p => Control$dSemigroupoid.composeImpl(Data$dArray.findIndex(p))(toArray);
const findLastIndex = x => Control$dSemigroupoid.composeImpl(Data$dArray.findLastIndex(x))(toArray);
const findMap = p => Control$dSemigroupoid.composeImpl(Data$dArray.findMap(p))(toArray);
const foldM = dictMonad => f => acc => Control$dSemigroupoid.composeImpl(Data$dArray.foldM(dictMonad)(f)(acc))(toArray);
const foldRecM = dictMonadRec => {
  const Monad0 = dictMonadRec.Monad0();
  const $0 = Monad0.Applicative0();
  return f => acc => Control$dSemigroupoid.composeImpl(array => dictMonadRec.tailRecM(o => {
    if (o.b >= array.length) { return $0.pure(Control$dMonad$dRec$dClass.$Step("Done", o.a)); }
    return Monad0.Bind1().bind(f(o.a)(array[o.b]))(res$p => $0.pure(Control$dMonad$dRec$dClass.$Step("Loop", {a: res$p, b: o.b + 1 | 0})));
  })({a: acc, b: 0}))(toArray);
};
const index = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.index)(toArray);
const length = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.length)(toArray);
const mapMaybe = f => Control$dSemigroupoid.composeImpl(Data$dArray.mapMaybe(f))(toArray);
const notElem = dictEq => x => Control$dSemigroupoid.composeImpl(Data$dArray.notElem(dictEq)(x))(toArray);
const partition = f => Control$dSemigroupoid.composeImpl(Data$dArray.partition(f))(toArray);
const slice = start => end => Control$dSemigroupoid.composeImpl(Data$dArray.slice(start)(end))(toArray);
const span = f => Control$dSemigroupoid.composeImpl(Data$dArray.span(f))(toArray);
const take = i => Control$dSemigroupoid.composeImpl(Data$dArray.take(i))(toArray);
const takeEnd = i => Control$dSemigroupoid.composeImpl(Data$dArray.takeEnd(i))(toArray);
const takeWhile = f => Control$dSemigroupoid.composeImpl(Data$dArray.takeWhile(f))(toArray);
const toUnfoldable = dictUnfoldable => Control$dSemigroupoid.composeImpl(xs => {
  const len = xs.length;
  return dictUnfoldable.unfoldr(i => {
    if (i < len) { return Data$dMaybe.$Maybe("Just", Data$dTuple.$Tuple(xs[i], i + 1 | 0)); }
    return Data$dMaybe.Nothing;
  })(0);
})(toArray);
const cons = x => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.cons(x))(toArray));
const group = dictEq => {
  const eq2 = dictEq.eq;
  return Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(xs => Data$dArray.groupBy(eq2)(xs))(toArray));
};
const groupAllBy = op => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.groupAllBy(op))(toArray));
const groupAll = dictOrd => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.groupAllBy(dictOrd.compare))(toArray));
const groupBy = op => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.groupBy(op))(toArray));
const insert = dictOrd => x => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.insert(dictOrd)(x))(toArray));
const insertBy = f => x => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.insertBy(f)(x))(toArray));
const intersperse = x => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.intersperse(x))(toArray));
const mapWithIndex = f => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dFunctorWithIndex.mapWithIndexArray(f))(toArray));
const modifyAtIndices = dictFoldable => {
  const modifyAtIndices1 = Data$dArray.modifyAtIndices(dictFoldable);
  return is => f => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(modifyAtIndices1(is)(f))(toArray));
};
const nub = dictOrd => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.nub(dictOrd))(toArray));
const nubBy = f => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.nubBy(f))(toArray));
const nubByEq = f => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.nubByEq(f))(toArray));
const nubEq = dictEq => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.nubEq(dictEq))(toArray));
const reverse = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(/* #__PURE__ */ Control$dSemigroupoid.composeImpl(Data$dArray.reverse)(toArray));
const scanl = f => x => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.scanl(f)(x))(toArray));
const scanr = f => x => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.scanr(f)(x))(toArray));
const sort = dictOrd => {
  const compare = dictOrd.compare;
  return Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(xs => Data$dArray.sortBy(compare)(xs))(toArray));
};
const sortBy = f => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.sortBy(f))(toArray));
const sortWith = dictOrd => f => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(Data$dArray.sortWith(dictOrd)(f))(toArray));
const updateAtIndices = dictFoldable => {
  const updateAtIndices1 = Data$dArray.updateAtIndices(dictFoldable);
  return pairs => Control$dSemigroupoid.composeImpl(Data$dArray$dNonEmpty$dInternal.NonEmptyArray)(Control$dSemigroupoid.composeImpl(updateAtIndices1(pairs))(toArray));
};
const unsafeIndex = () => Control$dSemigroupoid.composeImpl(unsafeIndex1)(toArray);
const unsafeIndex2 = /* #__PURE__ */ Control$dSemigroupoid.composeImpl(unsafeIndex1)(toArray);
const toUnfoldable1 = dictUnfoldable1 => xs => {
  const len = length(xs);
  return dictUnfoldable1.unfoldr1(i => Data$dTuple.$Tuple(unsafeIndex2(xs)(i), i < (len - 1 | 0) ? Data$dMaybe.$Maybe("Just", i + 1 | 0) : Data$dMaybe.Nothing))(0);
};
export {
  all,
  alterAt,
  any,
  appendArray,
  catMaybes,
  concat,
  concatMap,
  cons,
  cons$p,
  $$delete as delete,
  deleteAt,
  deleteBy,
  difference,
  difference$p,
  drop,
  dropEnd,
  dropWhile,
  elem,
  elemIndex,
  elemLastIndex,
  filter,
  filterA,
  find,
  findIndex,
  findLastIndex,
  findMap,
  fold1,
  foldM,
  foldMap1,
  foldRecM,
  foldl1,
  foldr1,
  fromArray,
  fromFoldable,
  fromFoldable1,
  fromJust,
  fromNonEmpty,
  group,
  groupAll,
  groupAllBy,
  groupBy,
  head,
  index,
  init,
  insert,
  insertAt,
  insertBy,
  intercalate,
  intersect,
  intersect$p,
  intersectBy,
  intersectBy$p,
  intersperse,
  last,
  length,
  mapMaybe,
  mapWithIndex,
  max,
  modifyAt,
  modifyAtIndices,
  notElem,
  nub,
  nubBy,
  nubByEq,
  nubEq,
  partition,
  prependArray,
  range,
  replicate,
  reverse,
  scanl,
  scanr,
  singleton,
  slice,
  snoc,
  snoc$p,
  some,
  sort,
  sortBy,
  sortWith,
  span,
  splitAt,
  tail,
  take,
  takeEnd,
  takeWhile,
  toArray,
  toNonEmpty,
  toUnfoldable,
  toUnfoldable1,
  transpose,
  transpose$p,
  uncons,
  union,
  union$p,
  unionBy,
  unionBy$p,
  unsafeIndex,
  unsafeIndex1,
  unsafeIndex2,
  unsnoc,
  unzip,
  updateAt,
  updateAtIndices,
  zip,
  zipWith,
  zipWithA
};
