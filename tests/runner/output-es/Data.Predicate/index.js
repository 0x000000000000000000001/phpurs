import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Data$dBooleanAlgebra from "../Data.BooleanAlgebra/index.js";
const Predicate = x => x;
const newtypePredicate = {Coercible0: () => {}};
const heytingAlgebraPredicate = {
  ff: v => false,
  tt: v => true,
  implies: f => g => a => !f(a) || g(a),
  conj: f => g => a => f(a) && g(a),
  disj: f => g => a => f(a) || g(a),
  not: f => a => !f(a)
};
const contravariantPredicate = {cmap: f => v => Control$dSemigroupoid.composeImpl(v)(f)};
const booleanAlgebraPredicate = /* #__PURE__ */ Data$dBooleanAlgebra.booleanAlgebraFn(Data$dBooleanAlgebra.booleanAlgebraBoolean);
export {Predicate, booleanAlgebraPredicate, contravariantPredicate, heytingAlgebraPredicate, newtypePredicate};
