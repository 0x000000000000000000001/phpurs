import * as $runtime from "../runtime.js";
import * as Control$dApply from "../Control.Apply/index.js";
import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Data$dHeytingAlgebra from "../Data.HeytingAlgebra/index.js";
import * as Data$dMaybe from "../Data.Maybe/index.js";
import * as Data$dMaybe$dFirst from "../Data.Maybe.First/index.js";
import * as Unsafe$dCoerce from "../Unsafe.Coerce/index.js";
import {foldlArray, foldrArray} from "./foreign.js";
const $FreeMonoidTree = (tag, _1, _2) => ({tag, _1, _2});
const identity = x => x;
const monoidEndo = /* #__PURE__ */ (() => {
  const semigroupEndo1 = {append: v => v1 => Control$dSemigroupoid.composeImpl(v)(v1)};
  return {mempty: x => x, Semigroup0: () => semigroupEndo1};
})();
const Empty = /* #__PURE__ */ $FreeMonoidTree("Empty");
const Node = value0 => $FreeMonoidTree("Node", value0);
const Append = value0 => value1 => $FreeMonoidTree("Append", value0, value1);
const semigroupFreeMonoidTree = {append: Append};
const monoidFreeMonoidTree = {mempty: Empty, Semigroup0: () => semigroupFreeMonoidTree};
const foldr = dict => dict.foldr;
const indexr = dictFoldable => idx => Control$dSemigroupoid.composeImpl(v => v.elem)(dictFoldable.foldr(a => cursor => {
  if (cursor.elem.tag === "Just") { return cursor; }
  if (cursor.pos === idx) { return {elem: Data$dMaybe.$Maybe("Just", a), pos: cursor.pos}; }
  return {pos: cursor.pos + 1 | 0, elem: cursor.elem};
})({elem: Data$dMaybe.Nothing, pos: 0}));
const $$null = dictFoldable => dictFoldable.foldr(v => v1 => false)(true);
const oneOf = dictFoldable => dictPlus => dictFoldable.foldr(dictPlus.Alt0().alt)(dictPlus.empty);
const oneOfMap = dictFoldable => dictPlus => {
  const alt = dictPlus.Alt0().alt;
  const empty = dictPlus.empty;
  return f => dictFoldable.foldr(Control$dSemigroupoid.composeImpl(alt)(f))(empty);
};
const traverse_ = dictApplicative => {
  const $0 = dictApplicative.Apply0();
  return dictFoldable => f => dictFoldable.foldr(Control$dSemigroupoid.composeImpl(a => b => $0.apply($0.Functor0().map(v => Control$dApply.identity)(a))(b))(f))(dictApplicative.pure());
};
const for_ = dictApplicative => {
  const traverse_1 = traverse_(dictApplicative);
  return dictFoldable => {
    const $0 = traverse_1(dictFoldable);
    return b => a => $0(a)(b);
  };
};
const sequence_ = dictApplicative => {
  const traverse_1 = traverse_(dictApplicative);
  return dictFoldable => traverse_1(dictFoldable)(identity);
};
const foldl = dict => dict.foldl;
const indexl = dictFoldable => idx => Control$dSemigroupoid.composeImpl(v => v.elem)(dictFoldable.foldl(cursor => a => {
  if (cursor.elem.tag === "Just") { return cursor; }
  if (cursor.pos === idx) { return {elem: Data$dMaybe.$Maybe("Just", a), pos: cursor.pos}; }
  return {pos: cursor.pos + 1 | 0, elem: cursor.elem};
})({elem: Data$dMaybe.Nothing, pos: 0}));
const intercalate = dictFoldable => dictMonoid => {
  const $0 = dictMonoid.Semigroup0();
  const mempty = dictMonoid.mempty;
  return sep => xs => dictFoldable.foldl(v => v1 => {
    if (v.init) { return {init: false, acc: v1}; }
    return {init: false, acc: $0.append(v.acc)($0.append(sep)(v1))};
  })({init: true, acc: mempty})(xs).acc;
};
const length = dictFoldable => dictSemiring => {
  const one = dictSemiring.one;
  return dictFoldable.foldl(c => v => dictSemiring.add(one)(c))(dictSemiring.zero);
};
const maximumBy = dictFoldable => cmp => dictFoldable.foldl(v => v1 => {
  if (v.tag === "Nothing") { return Data$dMaybe.$Maybe("Just", v1); }
  if (v.tag === "Just") { return Data$dMaybe.$Maybe("Just", cmp(v._1)(v1) === "GT" ? v._1 : v1); }
  $runtime.fail();
})(Data$dMaybe.Nothing);
const maximum = dictOrd => dictFoldable => dictFoldable.foldl(v => v1 => {
  if (v.tag === "Nothing") { return Data$dMaybe.$Maybe("Just", v1); }
  if (v.tag === "Just") { return Data$dMaybe.$Maybe("Just", dictOrd.compare(v._1)(v1) === "GT" ? v._1 : v1); }
  $runtime.fail();
})(Data$dMaybe.Nothing);
const minimumBy = dictFoldable => cmp => dictFoldable.foldl(v => v1 => {
  if (v.tag === "Nothing") { return Data$dMaybe.$Maybe("Just", v1); }
  if (v.tag === "Just") { return Data$dMaybe.$Maybe("Just", cmp(v._1)(v1) === "LT" ? v._1 : v1); }
  $runtime.fail();
})(Data$dMaybe.Nothing);
const minimum = dictOrd => dictFoldable => dictFoldable.foldl(v => v1 => {
  if (v.tag === "Nothing") { return Data$dMaybe.$Maybe("Just", v1); }
  if (v.tag === "Just") { return Data$dMaybe.$Maybe("Just", dictOrd.compare(v._1)(v1) === "LT" ? v._1 : v1); }
  $runtime.fail();
})(Data$dMaybe.Nothing);
const product = dictFoldable => dictSemiring => dictFoldable.foldl(dictSemiring.mul)(dictSemiring.one);
const sum = dictFoldable => dictSemiring => dictFoldable.foldl(dictSemiring.add)(dictSemiring.zero);
const foldableTuple = {foldr: f => z => v => f(v._2)(z), foldl: f => z => v => f(z)(v._2), foldMap: dictMonoid => f => v => f(v._2)};
const foldableMultiplicative = {foldr: f => z => v => f(v)(z), foldl: f => z => v => f(z)(v), foldMap: dictMonoid => f => v => f(v)};
const foldableMaybe = {
  foldr: v => v1 => v2 => {
    if (v2.tag === "Nothing") { return v1; }
    if (v2.tag === "Just") { return v(v2._1)(v1); }
    $runtime.fail();
  },
  foldl: v => v1 => v2 => {
    if (v2.tag === "Nothing") { return v1; }
    if (v2.tag === "Just") { return v(v1)(v2._1); }
    $runtime.fail();
  },
  foldMap: dictMonoid => {
    const mempty = dictMonoid.mempty;
    return v => v1 => {
      if (v1.tag === "Nothing") { return mempty; }
      if (v1.tag === "Just") { return v(v1._1); }
      $runtime.fail();
    };
  }
};
const foldableIdentity = {foldr: f => z => v => f(v)(z), foldl: f => z => v => f(z)(v), foldMap: dictMonoid => f => v => f(v)};
const foldableEither = {
  foldr: v => v1 => v2 => {
    if (v2.tag === "Left") { return v1; }
    if (v2.tag === "Right") { return v(v2._1)(v1); }
    $runtime.fail();
  },
  foldl: v => v1 => v2 => {
    if (v2.tag === "Left") { return v1; }
    if (v2.tag === "Right") { return v(v1)(v2._1); }
    $runtime.fail();
  },
  foldMap: dictMonoid => {
    const mempty = dictMonoid.mempty;
    return v => v1 => {
      if (v1.tag === "Left") { return mempty; }
      if (v1.tag === "Right") { return v(v1._1); }
      $runtime.fail();
    };
  }
};
const foldableDual = {foldr: f => z => v => f(v)(z), foldl: f => z => v => f(z)(v), foldMap: dictMonoid => f => v => f(v)};
const foldableDisj = {foldr: f => z => v => f(v)(z), foldl: f => z => v => f(z)(v), foldMap: dictMonoid => f => v => f(v)};
const foldableConst = {
  foldr: v => z => v1 => z,
  foldl: v => z => v1 => z,
  foldMap: dictMonoid => {
    const mempty = dictMonoid.mempty;
    return v => v1 => mempty;
  }
};
const foldableConj = {foldr: f => z => v => f(v)(z), foldl: f => z => v => f(z)(v), foldMap: dictMonoid => f => v => f(v)};
const foldableAdditive = {foldr: f => z => v => f(v)(z), foldl: f => z => v => f(z)(v), foldMap: dictMonoid => f => v => f(v)};
const foldMapDefaultR = dictFoldable => dictMonoid => {
  const mempty = dictMonoid.mempty;
  return f => dictFoldable.foldr(x => acc => dictMonoid.Semigroup0().append(f(x))(acc))(mempty);
};
const foldableArray = {
  foldr: foldrArray,
  foldl: foldlArray,
  foldMap: dictMonoid => {
    const mempty = dictMonoid.mempty;
    return f => foldableArray.foldr(x => acc => dictMonoid.Semigroup0().append(f(x))(acc))(mempty);
  }
};
const foldableFreeMonoidTree = {
  foldl: fn => {
    const go = go$a0$copy => go$a1$copy => go$a2$copy => {
      let go$a0 = go$a0$copy, go$a1 = go$a1$copy, go$a2 = go$a2$copy, go$c = true, go$r;
      while (go$c) {
        const acc = go$a0, lhs = go$a1, rhs = go$a2;
        if (lhs.tag === "Node") {
          go$a0 = fn(acc)(lhs._1);
          go$a1 = rhs;
          go$a2 = Empty;
          continue;
        }
        if (lhs.tag === "Append") {
          if (lhs._2.tag === "Empty") {
            go$a0 = acc;
            go$a1 = lhs._1;
            go$a2 = rhs;
            continue;
          }
          if (rhs.tag === "Empty") {
            go$a0 = acc;
            go$a1 = lhs._1;
            go$a2 = lhs._2;
            continue;
          }
          go$a0 = acc;
          go$a1 = lhs._1;
          go$a2 = $FreeMonoidTree("Append", lhs._2, rhs);
          continue;
        }
        if (lhs.tag === "Empty") {
          if (rhs.tag === "Empty") {
            go$c = false;
            go$r = acc;
            continue;
          }
          go$a0 = acc;
          go$a1 = rhs;
          go$a2 = Empty;
          continue;
        }
        $runtime.fail();
      }
      return go$r;
    };
    return a => b => go(a)(b)(Empty);
  },
  foldr: fn => {
    const go = go$a0$copy => go$a1$copy => go$a2$copy => {
      let go$a0 = go$a0$copy, go$a1 = go$a1$copy, go$a2 = go$a2$copy, go$c = true, go$r;
      while (go$c) {
        const acc = go$a0, lhs = go$a1, rhs = go$a2;
        if (rhs.tag === "Node") {
          go$a0 = fn(rhs._1)(acc);
          go$a1 = Empty;
          go$a2 = lhs;
          continue;
        }
        if (rhs.tag === "Append") {
          if (rhs._1.tag === "Empty") {
            go$a0 = acc;
            go$a1 = lhs;
            go$a2 = rhs._2;
            continue;
          }
          if (lhs.tag === "Empty") {
            go$a0 = acc;
            go$a1 = rhs._1;
            go$a2 = rhs._2;
            continue;
          }
          go$a0 = acc;
          go$a1 = $FreeMonoidTree("Append", lhs, rhs._1);
          go$a2 = rhs._2;
          continue;
        }
        if (rhs.tag === "Empty") {
          if (lhs.tag === "Empty") {
            go$c = false;
            go$r = acc;
            continue;
          }
          go$a0 = acc;
          go$a1 = Empty;
          go$a2 = lhs;
          continue;
        }
        $runtime.fail();
      }
      return go$r;
    };
    return a => b => go(a)(Empty)(b);
  },
  foldMap: dictMonoid => {
    const mempty = dictMonoid.mempty;
    return f => foldableFreeMonoidTree.foldr(x => acc => dictMonoid.Semigroup0().append(f(x))(acc))(mempty);
  }
};
const foldMapDefaultL = dictFoldable => dictMonoid => {
  const mempty = dictMonoid.mempty;
  return f => dictFoldable.foldl(acc => x => dictMonoid.Semigroup0().append(acc)(f(x)))(mempty);
};
const foldMap = dict => dict.foldMap;
const foldableApp = dictFoldable => (
  {foldr: f => i => v => dictFoldable.foldr(f)(i)(v), foldl: f => i => v => dictFoldable.foldl(f)(i)(v), foldMap: dictMonoid => dictFoldable.foldMap(dictMonoid)}
);
const foldableCompose = dictFoldable => dictFoldable1 => (
  {
    foldr: f => i => v => dictFoldable.foldr((() => {
      const $0 = dictFoldable1.foldr(f);
      return b => a => $0(a)(b);
    })())(i)(v),
    foldl: f => i => v => dictFoldable.foldl(dictFoldable1.foldl(f))(i)(v),
    foldMap: dictMonoid => {
      const foldMap4 = dictFoldable.foldMap(dictMonoid);
      const foldMap5 = dictFoldable1.foldMap(dictMonoid);
      return f => v => foldMap4(foldMap5(f))(v);
    }
  }
);
const foldableCoproduct = dictFoldable => dictFoldable1 => (
  {
    foldr: f => z => {
      const $0 = dictFoldable.foldr(f)(z);
      const $1 = dictFoldable1.foldr(f)(z);
      return v2 => {
        if (v2.tag === "Left") { return $0(v2._1); }
        if (v2.tag === "Right") { return $1(v2._1); }
        $runtime.fail();
      };
    },
    foldl: f => z => {
      const $0 = dictFoldable.foldl(f)(z);
      const $1 = dictFoldable1.foldl(f)(z);
      return v2 => {
        if (v2.tag === "Left") { return $0(v2._1); }
        if (v2.tag === "Right") { return $1(v2._1); }
        $runtime.fail();
      };
    },
    foldMap: dictMonoid => {
      const foldMap4 = dictFoldable.foldMap(dictMonoid);
      const foldMap5 = dictFoldable1.foldMap(dictMonoid);
      return f => {
        const $0 = foldMap4(f);
        const $1 = foldMap5(f);
        return v2 => {
          if (v2.tag === "Left") { return $0(v2._1); }
          if (v2.tag === "Right") { return $1(v2._1); }
          $runtime.fail();
        };
      };
    }
  }
);
const foldableFirst = {
  foldr: f => z => v => {
    if (v.tag === "Nothing") { return z; }
    if (v.tag === "Just") { return f(v._1)(z); }
    $runtime.fail();
  },
  foldl: f => z => v => {
    if (v.tag === "Nothing") { return z; }
    if (v.tag === "Just") { return f(z)(v._1); }
    $runtime.fail();
  },
  foldMap: dictMonoid => {
    const mempty = dictMonoid.mempty;
    return v => v1 => {
      if (v1.tag === "Nothing") { return mempty; }
      if (v1.tag === "Just") { return v(v1._1); }
      $runtime.fail();
    };
  }
};
const foldableLast = {
  foldr: f => z => v => {
    if (v.tag === "Nothing") { return z; }
    if (v.tag === "Just") { return f(v._1)(z); }
    $runtime.fail();
  },
  foldl: f => z => v => {
    if (v.tag === "Nothing") { return z; }
    if (v.tag === "Just") { return f(z)(v._1); }
    $runtime.fail();
  },
  foldMap: dictMonoid => {
    const mempty = dictMonoid.mempty;
    return v => v1 => {
      if (v1.tag === "Nothing") { return mempty; }
      if (v1.tag === "Just") { return v(v1._1); }
      $runtime.fail();
    };
  }
};
const foldableProduct = dictFoldable => dictFoldable1 => (
  {
    foldr: f => z => v => dictFoldable.foldr(f)(dictFoldable1.foldr(f)(z)(v._2))(v._1),
    foldl: f => z => v => dictFoldable1.foldl(f)(dictFoldable.foldl(f)(z)(v._1))(v._2),
    foldMap: dictMonoid => {
      const foldMap4 = dictFoldable.foldMap(dictMonoid);
      const foldMap5 = dictFoldable1.foldMap(dictMonoid);
      return f => v => dictMonoid.Semigroup0().append(foldMap4(f)(v._1))(foldMap5(f)(v._2));
    }
  }
);
const foldlDefault = dictFoldable => {
  const foldMap2 = dictFoldable.foldMap(monoidFreeMonoidTree);
  return c => u => xs => foldableFreeMonoidTree.foldl(c)(u)(foldMap2(Node)(xs));
};
const foldrDefault = dictFoldable => {
  const foldMap2 = dictFoldable.foldMap(monoidFreeMonoidTree);
  return c => u => xs => foldableFreeMonoidTree.foldr(c)(u)(foldMap2(Node)(xs));
};
const lookup = dictFoldable => {
  const foldMap2 = dictFoldable.foldMap(Data$dMaybe$dFirst.monoidFirst);
  return dictEq => a => Control$dSemigroupoid.composeImpl(Unsafe$dCoerce.unsafeCoerce)(foldMap2(v => {
    if (dictEq.eq(a)(v._1)) { return Data$dMaybe.$Maybe("Just", v._2); }
    return Data$dMaybe.Nothing;
  }));
};
const surroundMap = dictFoldable => {
  const foldMap2 = dictFoldable.foldMap(monoidEndo);
  return dictSemigroup => d => t => f => foldMap2(a => m => dictSemigroup.append(d)(dictSemigroup.append(t(a))(m)))(f)(d);
};
const surround = dictFoldable => {
  const surroundMap1 = surroundMap(dictFoldable);
  return dictSemigroup => {
    const surroundMap2 = surroundMap1(dictSemigroup);
    return d => surroundMap2(d)(identity);
  };
};
const foldM = dictFoldable => dictMonad => f => b0 => dictFoldable.foldl(b => a => dictMonad.Bind1().bind(b)(a$1 => f(a$1)(a)))(dictMonad.Applicative0().pure(b0));
const fold = dictFoldable => dictMonoid => dictFoldable.foldMap(dictMonoid)(identity);
const findMap = dictFoldable => p => dictFoldable.foldl(v => v1 => {
  if (v.tag === "Nothing") { return p(v1); }
  return v;
})(Data$dMaybe.Nothing);
const find = dictFoldable => p => dictFoldable.foldl(v => v1 => {
  if (v.tag === "Nothing" && p(v1)) { return Data$dMaybe.$Maybe("Just", v1); }
  return v;
})(Data$dMaybe.Nothing);
const any = dictFoldable => dictHeytingAlgebra => dictFoldable.foldMap((() => {
  const semigroupDisj1 = {append: v => v1 => dictHeytingAlgebra.disj(v)(v1)};
  return {mempty: dictHeytingAlgebra.ff, Semigroup0: () => semigroupDisj1};
})());
const elem = dictFoldable => {
  const any1 = dictFoldable.foldMap((() => {
    const semigroupDisj1 = {append: v => v1 => v || v1};
    return {mempty: false, Semigroup0: () => semigroupDisj1};
  })());
  return dictEq => Control$dSemigroupoid.composeImpl(any1)(dictEq.eq);
};
const notElem = dictFoldable => {
  const elem1 = elem(dictFoldable);
  return dictEq => {
    const elem2 = elem1(dictEq);
    return x => Control$dSemigroupoid.composeImpl(Data$dHeytingAlgebra.boolNot)(elem2(x));
  };
};
const or = dictFoldable => dictHeytingAlgebra => dictFoldable.foldMap((() => {
  const semigroupDisj1 = {append: v => v1 => dictHeytingAlgebra.disj(v)(v1)};
  return {mempty: dictHeytingAlgebra.ff, Semigroup0: () => semigroupDisj1};
})())(identity);
const all = dictFoldable => dictHeytingAlgebra => dictFoldable.foldMap((() => {
  const semigroupConj1 = {append: v => v1 => dictHeytingAlgebra.conj(v)(v1)};
  return {mempty: dictHeytingAlgebra.tt, Semigroup0: () => semigroupConj1};
})());
const and = dictFoldable => dictHeytingAlgebra => dictFoldable.foldMap((() => {
  const semigroupConj1 = {append: v => v1 => dictHeytingAlgebra.conj(v)(v1)};
  return {mempty: dictHeytingAlgebra.tt, Semigroup0: () => semigroupConj1};
})())(identity);
export {
  $FreeMonoidTree,
  Append,
  Empty,
  Node,
  all,
  and,
  any,
  elem,
  find,
  findMap,
  fold,
  foldM,
  foldMap,
  foldMapDefaultL,
  foldMapDefaultR,
  foldableAdditive,
  foldableApp,
  foldableArray,
  foldableCompose,
  foldableConj,
  foldableConst,
  foldableCoproduct,
  foldableDisj,
  foldableDual,
  foldableEither,
  foldableFirst,
  foldableFreeMonoidTree,
  foldableIdentity,
  foldableLast,
  foldableMaybe,
  foldableMultiplicative,
  foldableProduct,
  foldableTuple,
  foldl,
  foldlDefault,
  foldr,
  foldrDefault,
  for_,
  identity,
  indexl,
  indexr,
  intercalate,
  length,
  lookup,
  maximum,
  maximumBy,
  minimum,
  minimumBy,
  monoidEndo,
  monoidFreeMonoidTree,
  notElem,
  $$null as null,
  oneOf,
  oneOfMap,
  or,
  product,
  semigroupFreeMonoidTree,
  sequence_,
  sum,
  surround,
  surroundMap,
  traverse_
};
export * from "./foreign.js";
