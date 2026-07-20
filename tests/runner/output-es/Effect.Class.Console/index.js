import * as Control$dSemigroupoid from "../Control.Semigroupoid/index.js";
import * as Effect$dConsole from "../Effect.Console/index.js";
const warnShow = dictMonadEffect => {
  const liftEffect = dictMonadEffect.liftEffect;
  return dictShow => Control$dSemigroupoid.composeImpl(liftEffect)(Effect$dConsole.warnShow(dictShow));
};
const warn = dictMonadEffect => Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.warn);
const timeLog = dictMonadEffect => Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.timeLog);
const timeEnd = dictMonadEffect => Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.timeEnd);
const time = dictMonadEffect => Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.time);
const logShow = dictMonadEffect => {
  const liftEffect = dictMonadEffect.liftEffect;
  return dictShow => Control$dSemigroupoid.composeImpl(liftEffect)(Effect$dConsole.logShow(dictShow));
};
const log = dictMonadEffect => Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.log);
const infoShow = dictMonadEffect => {
  const liftEffect = dictMonadEffect.liftEffect;
  return dictShow => Control$dSemigroupoid.composeImpl(liftEffect)(Effect$dConsole.infoShow(dictShow));
};
const info = dictMonadEffect => Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.info);
const groupEnd = dictMonadEffect => dictMonadEffect.liftEffect(Effect$dConsole.groupEnd);
const groupCollapsed = dictMonadEffect => Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.groupCollapsed);
const group = dictMonadEffect => Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.group);
const grouped = dictMonadEffect => {
  const Monad0 = dictMonadEffect.Monad0();
  const Bind1 = Monad0.Bind1();
  const group1 = Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.group);
  const groupEnd1 = dictMonadEffect.liftEffect(Effect$dConsole.groupEnd);
  return name => inner => Bind1.bind(group1(name))(() => Bind1.bind(inner)(result => Bind1.bind(groupEnd1)(() => Monad0.Applicative0().pure(result))));
};
const errorShow = dictMonadEffect => {
  const liftEffect = dictMonadEffect.liftEffect;
  return dictShow => Control$dSemigroupoid.composeImpl(liftEffect)(Effect$dConsole.errorShow(dictShow));
};
const error = dictMonadEffect => Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.error);
const debugShow = dictMonadEffect => {
  const liftEffect = dictMonadEffect.liftEffect;
  return dictShow => Control$dSemigroupoid.composeImpl(liftEffect)(Effect$dConsole.debugShow(dictShow));
};
const debug = dictMonadEffect => Control$dSemigroupoid.composeImpl(dictMonadEffect.liftEffect)(Effect$dConsole.debug);
const clear = dictMonadEffect => dictMonadEffect.liftEffect(Effect$dConsole.clear);
export {clear, debug, debugShow, error, errorShow, group, groupCollapsed, groupEnd, grouped, info, infoShow, log, logShow, time, timeEnd, timeLog, warn, warnShow};
