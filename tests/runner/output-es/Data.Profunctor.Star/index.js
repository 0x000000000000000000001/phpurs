import * as $runtime from "../runtime.js";
import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Data$dEither from "../Data.Either/index.js";
import * as Data$dFunctor from "../Data.Functor/index.js";
import * as Data$dTuple from "../Data.Tuple/index.js";
const Star = x => x;
const semigroupoidStar = dictBind => ({compose: v => v1 => x => dictBind.bind(v1(x))(v)});
const profunctorStar = dictFunctor => ({dimap: f => g => v => Control$dSemigroupoid.composeImpl(Control$dSemigroupoid.composeImpl(dictFunctor.map(g))(v))(f)});
const strongStar = dictFunctor => {
  const profunctorStar1 = profunctorStar(dictFunctor);
  return {
    first: v => v1 => {
      const $0 = v1._2;
      return dictFunctor.map(v2 => Data$dTuple.$Tuple(v2, $0))(v(v1._1));
    },
    second: v => v1 => dictFunctor.map(Data$dTuple.Tuple(v1._1))(v(v1._2)),
    Profunctor0: () => profunctorStar1
  };
};
const newtypeStar = {Coercible0: () => {}};
const invariantStar = dictInvariant => ({imap: f => g => v => Control$dSemigroupoid.composeImpl(dictInvariant.imap(f)(g))(v)});
const hoistStar = f => v => Control$dSemigroupoid.composeImpl(f)(v);
const functorStar = dictFunctor => ({map: f => v => Control$dSemigroupoid.composeImpl(dictFunctor.map(f))(v)});
const distributiveStar = dictDistributive => {
  const $0 = dictDistributive.Functor0();
  const functorStar1 = {map: f => v => Control$dSemigroupoid.composeImpl($0.map(f))(v)};
  return {
    distribute: dictFunctor => {
      const collect1 = dictDistributive.collect(dictFunctor);
      return f => a => collect1(v => v(a))(f);
    },
    collect: dictFunctor => f => Control$dSemigroupoid.composeImpl(distributiveStar(dictDistributive).distribute(dictFunctor))(dictFunctor.map(f)),
    Functor0: () => functorStar1
  };
};
const closedStar = dictDistributive => {
  const distribute = dictDistributive.distribute(Data$dFunctor.functorFn);
  const profunctorStar1 = profunctorStar(dictDistributive.Functor0());
  return {closed: v => g => distribute(Control$dSemigroupoid.composeImpl(v)(g)), Profunctor0: () => profunctorStar1};
};
const choiceStar = dictApplicative => {
  const Functor0 = dictApplicative.Apply0().Functor0();
  const pure = dictApplicative.pure;
  const profunctorStar1 = profunctorStar(Functor0);
  return {
    left: v => {
      const $0 = Control$dSemigroupoid.composeImpl(Functor0.map(Data$dEither.Left))(v);
      const $1 = Control$dSemigroupoid.composeImpl(pure)(Data$dEither.Right);
      return v2 => {
        if (v2.tag === "Left") { return $0(v2._1); }
        if (v2.tag === "Right") { return $1(v2._1); }
        $runtime.fail();
      };
    },
    right: v => {
      const $0 = Control$dSemigroupoid.composeImpl(pure)(Data$dEither.Left);
      const $1 = Control$dSemigroupoid.composeImpl(Functor0.map(Data$dEither.Right))(v);
      return v2 => {
        if (v2.tag === "Left") { return $0(v2._1); }
        if (v2.tag === "Right") { return $1(v2._1); }
        $runtime.fail();
      };
    },
    Profunctor0: () => profunctorStar1
  };
};
const categoryStar = dictMonad => {
  const $0 = dictMonad.Bind1();
  const semigroupoidStar1 = {compose: v => v1 => x => $0.bind(v1(x))(v)};
  return {identity: dictMonad.Applicative0().pure, Semigroupoid0: () => semigroupoidStar1};
};
const applyStar = dictApply => {
  const $0 = dictApply.Functor0();
  const functorStar1 = {map: f => v => Control$dSemigroupoid.composeImpl($0.map(f))(v)};
  return {apply: v => v1 => a => dictApply.apply(v(a))(v1(a)), Functor0: () => functorStar1};
};
const bindStar = dictBind => {
  const applyStar1 = applyStar(dictBind.Apply0());
  return {bind: v => f => x => dictBind.bind(v(x))(a => f(a)(x)), Apply0: () => applyStar1};
};
const applicativeStar = dictApplicative => {
  const applyStar1 = applyStar(dictApplicative.Apply0());
  return {pure: a => v => dictApplicative.pure(a), Apply0: () => applyStar1};
};
const monadStar = dictMonad => {
  const applicativeStar1 = applicativeStar(dictMonad.Applicative0());
  const bindStar1 = bindStar(dictMonad.Bind1());
  return {Applicative0: () => applicativeStar1, Bind1: () => bindStar1};
};
const altStar = dictAlt => {
  const $0 = dictAlt.Functor0();
  const functorStar1 = {map: f => v => Control$dSemigroupoid.composeImpl($0.map(f))(v)};
  return {alt: v => v1 => a => dictAlt.alt(v(a))(v1(a)), Functor0: () => functorStar1};
};
const plusStar = dictPlus => {
  const empty = dictPlus.empty;
  const altStar1 = altStar(dictPlus.Alt0());
  return {empty: v => empty, Alt0: () => altStar1};
};
const alternativeStar = dictAlternative => {
  const applicativeStar1 = applicativeStar(dictAlternative.Applicative0());
  const plusStar1 = plusStar(dictAlternative.Plus1());
  return {Applicative0: () => applicativeStar1, Plus1: () => plusStar1};
};
const monadPlusStar = dictMonadPlus => {
  const monadStar1 = monadStar(dictMonadPlus.Monad0());
  const alternativeStar1 = alternativeStar(dictMonadPlus.Alternative1());
  return {Monad0: () => monadStar1, Alternative1: () => alternativeStar1};
};
export {
  Star,
  altStar,
  alternativeStar,
  applicativeStar,
  applyStar,
  bindStar,
  categoryStar,
  choiceStar,
  closedStar,
  distributiveStar,
  functorStar,
  hoistStar,
  invariantStar,
  monadPlusStar,
  monadStar,
  newtypeStar,
  plusStar,
  profunctorStar,
  semigroupoidStar,
  strongStar
};
