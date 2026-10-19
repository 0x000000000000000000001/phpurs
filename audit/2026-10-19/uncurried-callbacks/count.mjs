import { mainWithCache } from '../../../output/Main/index.js';
import { monadEffectAff } from '../../../output/Effect.Aff/index.js';
import { Nothing } from '../../../output/Data.Maybe/index.js';
const lift = monadEffectAff.liftEffect;
const rows = [];
function tagged(_key, value) {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    const prototype = Object.getPrototypeOf(value);
    if (prototype && prototype !== Object.prototype) return {$ctor: prototype.constructor.name, ...value};
  }
  return value;
}
function count(x, key) {
  if (Array.isArray(x)) return x.reduce((n, v) => n + count(v, key), 0);
  if (x && typeof x === 'object') {
    let n = x.$ctor === key ? 1 : 0;
    for (const v of Object.values(x)) n += count(v, key);
    return n;
  }
  return 0;
}
mainWithCache({
  load: () => lift(() => Nothing.value),
  store: state => lift(() => {
    const {name, bindings} = state.backend;
    const data = JSON.parse(JSON.stringify(bindings, tagged));
    const ua = count(data, 'UncurriedAbs');
    const ue = count(data, 'UncurriedEffectAbs');
    const app = count(data, 'UncurriedApp');
    const eapp = count(data, 'UncurriedEffectApp');
    if (ua + ue + app + eapp) rows.push({name, ua, ue, app, eapp});
  }),
})();
process.on('exit', code => {
  if (code === 0) {
    rows.sort((a, b) => (b.ua + b.ue) - (a.ua + a.ue) || a.name.localeCompare(b.name));
    console.log(JSON.stringify(rows, null, 2));
  }
});
