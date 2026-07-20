import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Data$dFunctor from "../Data.Functor/index.js";
import * as Data$dIdentity from "../Data.Identity/index.js";
import * as Data$dTuple from "../Data.Tuple/index.js";
import * as Unsafe$dCoerce from "../Unsafe.Coerce/index.js";
const identity = x => x;
const distributiveIdentity = {
  distribute: dictFunctor => Control$dSemigroupoid.composeImpl(Data$dIdentity.Identity)(dictFunctor.map(Unsafe$dCoerce.unsafeCoerce)),
  collect: dictFunctor => f => Control$dSemigroupoid.composeImpl(Data$dIdentity.Identity)(dictFunctor.map(Control$dSemigroupoid.composeImpl(Unsafe$dCoerce.unsafeCoerce)(f))),
  Functor0: () => Data$dIdentity.functorIdentity
};
const distribute = dict => dict.distribute;
const distributiveFunction = {
  distribute: dictFunctor => a => e => dictFunctor.map(v => v(e))(a),
  collect: dictFunctor => f => Control$dSemigroupoid.composeImpl(distributiveFunction.distribute(dictFunctor))(dictFunctor.map(f)),
  Functor0: () => Data$dFunctor.functorFn
};
const cotraverse = dictDistributive => dictFunctor => {
  const distribute2 = dictDistributive.distribute(dictFunctor);
  return f => Control$dSemigroupoid.composeImpl(dictDistributive.Functor0().map(f))(distribute2);
};
const collectDefault = dictDistributive => dictFunctor => {
  const distribute2 = dictDistributive.distribute(dictFunctor);
  return f => Control$dSemigroupoid.composeImpl(distribute2)(dictFunctor.map(f));
};
const distributiveTuple = dictTypeEquals => {
  const from = dictTypeEquals.proof(a => a);
  return {
    collect: dictFunctor => collectDefault(distributiveTuple(dictTypeEquals))(dictFunctor),
    distribute: dictFunctor => Control$dSemigroupoid.composeImpl(Data$dTuple.Tuple(from()))(dictFunctor.map(Data$dTuple.snd)),
    Functor0: () => Data$dTuple.functorTuple
  };
};
const collect = dict => dict.collect;
const distributeDefault = dictDistributive => dictFunctor => dictDistributive.collect(dictFunctor)(identity);
export {collect, collectDefault, cotraverse, distribute, distributeDefault, distributiveFunction, distributiveIdentity, distributiveTuple, identity};
