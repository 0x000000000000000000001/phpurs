import * as $runtime from "../runtime.js";
import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Data$dEither from "../Data.Either/index.js";
import * as Data$dFoldableWithIndex from "../Data.FoldableWithIndex/index.js";
import * as Data$dFunctor$dApp from "../Data.Functor.App/index.js";
import * as Data$dFunctor$dCompose from "../Data.Functor.Compose/index.js";
import * as Data$dFunctor$dCoproduct from "../Data.Functor.Coproduct/index.js";
import * as Data$dFunctor$dProduct from "../Data.Functor.Product/index.js";
import * as Data$dFunctorWithIndex from "../Data.FunctorWithIndex/index.js";
import * as Data$dIdentity from "../Data.Identity/index.js";
import * as Data$dTraversable from "../Data.Traversable/index.js";
import * as Data$dTraversable$dAccum$dInternal from "../Data.Traversable.Accum.Internal/index.js";
import * as Data$dTuple from "../Data.Tuple/index.js";
const traverseWithIndexDefault = dictTraversableWithIndex => dictApplicative => {
  const sequence1 = dictTraversableWithIndex.Traversable2().sequence(dictApplicative);
  return f => Control$dSemigroupoid.composeImpl(sequence1)(dictTraversableWithIndex.FunctorWithIndex0().mapWithIndex(f));
};
const traverseWithIndex = dict => dict.traverseWithIndex;
const traverseDefault = dictTraversableWithIndex => dictApplicative => {
  const traverseWithIndex2 = dictTraversableWithIndex.traverseWithIndex(dictApplicative);
  return f => traverseWithIndex2(v => f);
};
const traversableWithIndexTuple = {
  traverseWithIndex: dictApplicative => f => v => dictApplicative.Apply0().Functor0().map(Data$dTuple.Tuple(v._1))(f()(v._2)),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexTuple,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexTuple,
  Traversable2: () => Data$dTraversable.traversableTuple
};
const traversableWithIndexProduct = dictTraversableWithIndex => {
  const functorWithIndexProduct = Data$dFunctorWithIndex.functorWithIndexProduct(dictTraversableWithIndex.FunctorWithIndex0());
  const foldableWithIndexProduct = Data$dFoldableWithIndex.foldableWithIndexProduct(dictTraversableWithIndex.FoldableWithIndex1());
  const traversableProduct = Data$dTraversable.traversableProduct(dictTraversableWithIndex.Traversable2());
  return dictTraversableWithIndex1 => {
    const functorWithIndexProduct1 = functorWithIndexProduct(dictTraversableWithIndex1.FunctorWithIndex0());
    const foldableWithIndexProduct1 = foldableWithIndexProduct(dictTraversableWithIndex1.FoldableWithIndex1());
    const traversableProduct1 = traversableProduct(dictTraversableWithIndex1.Traversable2());
    return {
      traverseWithIndex: dictApplicative => {
        const $0 = dictApplicative.Apply0();
        const traverseWithIndex3 = dictTraversableWithIndex.traverseWithIndex(dictApplicative);
        const traverseWithIndex4 = dictTraversableWithIndex1.traverseWithIndex(dictApplicative);
        return f => v => $0.apply($0.Functor0().map(Data$dFunctor$dProduct.product)(traverseWithIndex3(Control$dSemigroupoid.composeImpl(f)(Data$dEither.Left))(v._1)))(traverseWithIndex4(Control$dSemigroupoid.composeImpl(f)(Data$dEither.Right))(v._2));
      },
      FunctorWithIndex0: () => functorWithIndexProduct1,
      FoldableWithIndex1: () => foldableWithIndexProduct1,
      Traversable2: () => traversableProduct1
    };
  };
};
const traversableWithIndexMultiplicative = {
  traverseWithIndex: dictApplicative => f => Data$dTraversable.traversableMultiplicative.traverse(dictApplicative)(f()),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexMultiplicative,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexMultiplicative,
  Traversable2: () => Data$dTraversable.traversableMultiplicative
};
const traversableWithIndexMaybe = {
  traverseWithIndex: dictApplicative => f => Data$dTraversable.traversableMaybe.traverse(dictApplicative)(f()),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexMaybe,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexMaybe,
  Traversable2: () => Data$dTraversable.traversableMaybe
};
const traversableWithIndexLast = {
  traverseWithIndex: dictApplicative => f => Data$dTraversable.traversableLast.traverse(dictApplicative)(f()),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexLast,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexLast,
  Traversable2: () => Data$dTraversable.traversableLast
};
const traversableWithIndexIdentity = {
  traverseWithIndex: dictApplicative => f => v => dictApplicative.Apply0().Functor0().map(Data$dIdentity.Identity)(f()(v)),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexIdentity,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexIdentity,
  Traversable2: () => Data$dTraversable.traversableIdentity
};
const traversableWithIndexFirst = {
  traverseWithIndex: dictApplicative => f => Data$dTraversable.traversableFirst.traverse(dictApplicative)(f()),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexFirst,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexFirst,
  Traversable2: () => Data$dTraversable.traversableFirst
};
const traversableWithIndexEither = {
  traverseWithIndex: dictApplicative => v => v1 => {
    if (v1.tag === "Left") { return dictApplicative.pure(Data$dEither.$Either("Left", v1._1)); }
    if (v1.tag === "Right") { return dictApplicative.Apply0().Functor0().map(Data$dEither.Right)(v()(v1._1)); }
    $runtime.fail();
  },
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexEither,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexEither,
  Traversable2: () => Data$dTraversable.traversableEither
};
const traversableWithIndexDual = {
  traverseWithIndex: dictApplicative => f => Data$dTraversable.traversableDual.traverse(dictApplicative)(f()),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexDual,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexDual,
  Traversable2: () => Data$dTraversable.traversableDual
};
const traversableWithIndexDisj = {
  traverseWithIndex: dictApplicative => f => Data$dTraversable.traversableDisj.traverse(dictApplicative)(f()),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexDisj,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexDisj,
  Traversable2: () => Data$dTraversable.traversableDisj
};
const traversableWithIndexCoproduct = dictTraversableWithIndex => {
  const functorWithIndexCoproduct = Data$dFunctorWithIndex.functorWithIndexCoproduct(dictTraversableWithIndex.FunctorWithIndex0());
  const foldableWithIndexCoproduct = Data$dFoldableWithIndex.foldableWithIndexCoproduct(dictTraversableWithIndex.FoldableWithIndex1());
  const traversableCoproduct = Data$dTraversable.traversableCoproduct(dictTraversableWithIndex.Traversable2());
  return dictTraversableWithIndex1 => {
    const functorWithIndexCoproduct1 = functorWithIndexCoproduct(dictTraversableWithIndex1.FunctorWithIndex0());
    const foldableWithIndexCoproduct1 = foldableWithIndexCoproduct(dictTraversableWithIndex1.FoldableWithIndex1());
    const traversableCoproduct1 = traversableCoproduct(dictTraversableWithIndex1.Traversable2());
    return {
      traverseWithIndex: dictApplicative => {
        const $0 = dictApplicative.Apply0().Functor0();
        const traverseWithIndex3 = dictTraversableWithIndex.traverseWithIndex(dictApplicative);
        const traverseWithIndex4 = dictTraversableWithIndex1.traverseWithIndex(dictApplicative);
        return f => {
          const $1 = Control$dSemigroupoid.composeImpl($0.map(Control$dSemigroupoid.composeImpl(Data$dFunctor$dCoproduct.Coproduct)(Data$dEither.Left)))(traverseWithIndex3(Control$dSemigroupoid.composeImpl(f)(Data$dEither.Left)));
          const $2 = Control$dSemigroupoid.composeImpl($0.map(Control$dSemigroupoid.composeImpl(Data$dFunctor$dCoproduct.Coproduct)(Data$dEither.Right)))(traverseWithIndex4(Control$dSemigroupoid.composeImpl(f)(Data$dEither.Right)));
          return v2 => {
            if (v2.tag === "Left") { return $1(v2._1); }
            if (v2.tag === "Right") { return $2(v2._1); }
            $runtime.fail();
          };
        };
      },
      FunctorWithIndex0: () => functorWithIndexCoproduct1,
      FoldableWithIndex1: () => foldableWithIndexCoproduct1,
      Traversable2: () => traversableCoproduct1
    };
  };
};
const traversableWithIndexConst = {
  traverseWithIndex: dictApplicative => v => v1 => dictApplicative.pure(v1),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexConst,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexConst,
  Traversable2: () => Data$dTraversable.traversableConst
};
const traversableWithIndexConj = {
  traverseWithIndex: dictApplicative => f => Data$dTraversable.traversableConj.traverse(dictApplicative)(f()),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexConj,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexConj,
  Traversable2: () => Data$dTraversable.traversableConj
};
const traversableWithIndexCompose = dictTraversableWithIndex => {
  const functorWithIndexCompose = Data$dFunctorWithIndex.functorWithIndexCompose(dictTraversableWithIndex.FunctorWithIndex0());
  const foldableWithIndexCompose = Data$dFoldableWithIndex.foldableWithIndexCompose(dictTraversableWithIndex.FoldableWithIndex1());
  const traversableCompose = Data$dTraversable.traversableCompose(dictTraversableWithIndex.Traversable2());
  return dictTraversableWithIndex1 => {
    const functorWithIndexCompose1 = functorWithIndexCompose(dictTraversableWithIndex1.FunctorWithIndex0());
    const foldableWithIndexCompose1 = foldableWithIndexCompose(dictTraversableWithIndex1.FoldableWithIndex1());
    const traversableCompose1 = traversableCompose(dictTraversableWithIndex1.Traversable2());
    return {
      traverseWithIndex: dictApplicative => {
        const traverseWithIndex3 = dictTraversableWithIndex.traverseWithIndex(dictApplicative);
        const traverseWithIndex4 = dictTraversableWithIndex1.traverseWithIndex(dictApplicative);
        return f => v => dictApplicative.Apply0().Functor0().map(Data$dFunctor$dCompose.Compose)(traverseWithIndex3(Control$dSemigroupoid.composeImpl(traverseWithIndex4)(a => b => f(Data$dTuple.$Tuple(
          a,
          b
        ))))(v));
      },
      FunctorWithIndex0: () => functorWithIndexCompose1,
      FoldableWithIndex1: () => foldableWithIndexCompose1,
      Traversable2: () => traversableCompose1
    };
  };
};
const traversableWithIndexArray = {
  traverseWithIndex: dictApplicative => traverseWithIndexDefault(traversableWithIndexArray)(dictApplicative),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexArray,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexArray,
  Traversable2: () => Data$dTraversable.traversableArray
};
const traversableWithIndexApp = dictTraversableWithIndex => {
  const $0 = dictTraversableWithIndex.FunctorWithIndex0();
  const $1 = $0.Functor0();
  const functorWithIndexApp = {mapWithIndex: f => v => $0.mapWithIndex(f)(v), Functor0: () => $1};
  const $2 = dictTraversableWithIndex.FoldableWithIndex1();
  const $3 = $2.Foldable0();
  const foldableWithIndexApp = (() => {
    const foldableApp = {foldr: f => i => v => $3.foldr(f)(i)(v), foldl: f => i => v => $3.foldl(f)(i)(v), foldMap: dictMonoid => $3.foldMap(dictMonoid)};
    return {
      foldrWithIndex: f => z => v => $2.foldrWithIndex(f)(z)(v),
      foldlWithIndex: f => z => v => $2.foldlWithIndex(f)(z)(v),
      foldMapWithIndex: dictMonoid => $2.foldMapWithIndex(dictMonoid),
      Foldable0: () => foldableApp
    };
  })();
  const traversableApp = Data$dTraversable.traversableApp(dictTraversableWithIndex.Traversable2());
  return {
    traverseWithIndex: dictApplicative => {
      const traverseWithIndex2 = dictTraversableWithIndex.traverseWithIndex(dictApplicative);
      return f => v => dictApplicative.Apply0().Functor0().map(Data$dFunctor$dApp.App)(traverseWithIndex2(f)(v));
    },
    FunctorWithIndex0: () => functorWithIndexApp,
    FoldableWithIndex1: () => foldableWithIndexApp,
    Traversable2: () => traversableApp
  };
};
const traversableWithIndexAdditive = {
  traverseWithIndex: dictApplicative => f => Data$dTraversable.traversableAdditive.traverse(dictApplicative)(f()),
  FunctorWithIndex0: () => Data$dFunctorWithIndex.functorWithIndexAdditive,
  FoldableWithIndex1: () => Data$dFoldableWithIndex.foldableWithIndexAdditive,
  Traversable2: () => Data$dTraversable.traversableAdditive
};
const mapAccumRWithIndex = dictTraversableWithIndex => {
  const traverseWithIndex1 = dictTraversableWithIndex.traverseWithIndex(Data$dTraversable$dAccum$dInternal.applicativeStateR);
  return f => s0 => xs => traverseWithIndex1(i => a => s => f(i)(s)(a))(xs)(s0);
};
const scanrWithIndex = dictTraversableWithIndex => {
  const mapAccumRWithIndex1 = mapAccumRWithIndex(dictTraversableWithIndex);
  return f => b0 => xs => mapAccumRWithIndex1(i => b => a => {
    const b$p = f(i)(a)(b);
    return {accum: b$p, value: b$p};
  })(b0)(xs).value;
};
const mapAccumLWithIndex = dictTraversableWithIndex => {
  const traverseWithIndex1 = dictTraversableWithIndex.traverseWithIndex(Data$dTraversable$dAccum$dInternal.applicativeStateL);
  return f => s0 => xs => traverseWithIndex1(i => a => s => f(i)(s)(a))(xs)(s0);
};
const scanlWithIndex = dictTraversableWithIndex => {
  const mapAccumLWithIndex1 = mapAccumLWithIndex(dictTraversableWithIndex);
  return f => b0 => xs => mapAccumLWithIndex1(i => b => a => {
    const b$p = f(i)(b)(a);
    return {accum: b$p, value: b$p};
  })(b0)(xs).value;
};
const forWithIndex = dictApplicative => dictTraversableWithIndex => {
  const $0 = dictTraversableWithIndex.traverseWithIndex(dictApplicative);
  return b => a => $0(a)(b);
};
export {
  forWithIndex,
  mapAccumLWithIndex,
  mapAccumRWithIndex,
  scanlWithIndex,
  scanrWithIndex,
  traversableWithIndexAdditive,
  traversableWithIndexApp,
  traversableWithIndexArray,
  traversableWithIndexCompose,
  traversableWithIndexConj,
  traversableWithIndexConst,
  traversableWithIndexCoproduct,
  traversableWithIndexDisj,
  traversableWithIndexDual,
  traversableWithIndexEither,
  traversableWithIndexFirst,
  traversableWithIndexIdentity,
  traversableWithIndexLast,
  traversableWithIndexMaybe,
  traversableWithIndexMultiplicative,
  traversableWithIndexProduct,
  traversableWithIndexTuple,
  traverseDefault,
  traverseWithIndex,
  traverseWithIndexDefault
};
