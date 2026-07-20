import * as $runtime from "../runtime.js";
import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Data$dDivide from "../Data.Divide/index.js";
import * as Data$dOrdering from "../Data.Ordering/index.js";
const identity = x => x;
const choosePredicate = {
  choose: f => v => v1 => Control$dSemigroupoid.composeImpl(v2 => {
    if (v2.tag === "Left") { return v(v2._1); }
    if (v2.tag === "Right") { return v1(v2._1); }
    $runtime.fail();
  })(f),
  Divide0: () => Data$dDivide.dividePredicate
};
const chooseOp = dictSemigroup => {
  const divideOp = Data$dDivide.divideOp(dictSemigroup);
  return {
    choose: f => v => v1 => Control$dSemigroupoid.composeImpl(v2 => {
      if (v2.tag === "Left") { return v(v2._1); }
      if (v2.tag === "Right") { return v1(v2._1); }
      $runtime.fail();
    })(f),
    Divide0: () => divideOp
  };
};
const chooseEquivalence = {
  choose: f => v => v1 => a => b => {
    const v2 = f(a);
    if (v2.tag === "Left") {
      const v3 = f(b);
      if (v3.tag === "Left") { return v(v2._1)(v3._1); }
      if (v3.tag === "Right") { return false; }
      $runtime.fail();
    }
    if (v2.tag === "Right") {
      const v3 = f(b);
      if (v3.tag === "Left") { return false; }
      if (v3.tag === "Right") { return v1(v2._1)(v3._1); }
    }
    $runtime.fail();
  },
  Divide0: () => Data$dDivide.divideEquivalence
};
const chooseComparison = {
  choose: f => v => v1 => a => b => {
    const v2 = f(a);
    if (v2.tag === "Left") {
      const v3 = f(b);
      if (v3.tag === "Left") { return v(v2._1)(v3._1); }
      if (v3.tag === "Right") { return Data$dOrdering.LT; }
      $runtime.fail();
    }
    if (v2.tag === "Right") {
      const v3 = f(b);
      if (v3.tag === "Left") { return Data$dOrdering.GT; }
      if (v3.tag === "Right") { return v1(v2._1)(v3._1); }
    }
    $runtime.fail();
  },
  Divide0: () => Data$dDivide.divideComparison
};
const choose = dict => dict.choose;
const chosen = dictDecide => dictDecide.choose(identity);
export {choose, chooseComparison, chooseEquivalence, chooseOp, choosePredicate, chosen, identity};
