// Explicit test/audit hooks: callers provide a key plan for frozen inputs.
// The production CLI still calls Main.main with caching disabled.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { syncBuiltinESMExports } from 'node:module';
import { main, mainWithCache } from '../../../output/Main/index.js';
import { monadEffectAff } from '../../../output/Effect.Aff/index.js';
import { Just, Nothing } from '../../../output/Data.Maybe/index.js';
import { loadModuleState, saveModuleState } from '../../../output/Phpurs.ModuleCache/index.js';

const config = JSON.parse(fs.readFileSync(process.env.PHPURS_TEST_CACHE_CONFIG, 'utf8'));
const keys = new Map(config.keys.map(({ name, key }) => [name, key]));
const stats = { hits: [], misses: [], stored: [], roundTrips: [], writes: [], purmetaWrites: [] };
const lift = monadEffectAff.liftEffect;
const request = name => ({ directory: config.directory, key: keys.get(name), moduleName: name, emission: config.emission });

for (const operation of ['readFile', 'writeFile', 'writeFileSync']) {
  const original = fs[operation];
  fs[operation] = (file, ...args) => {
    if (operation.startsWith('writeFile') && String(file).endsWith('.php')) stats.writes.push(String(file));
    if (operation.startsWith('writeFile') && String(file).endsWith('.purmeta')) stats.purmetaWrites.push(String(file));
    if (config.failure?.operation === operation && String(file) === config.failure.file) {
      queueMicrotask(() => args.at(-1)(new Error('fixture output failure')));
      return;
    }
    return original(file, ...args);
  };
}
syncBuiltinESMExports();
process.on('exit', () => fs.writeFileSync(config.stats, JSON.stringify(stats)));

if (config.disabled) main();
else mainWithCache({
  load: name => lift(() => {
    assert.ok(keys.has(name), `unplanned module: ${name}`);
    const state = config.forcedMisses?.includes(name) ? Nothing.value : loadModuleState(request(name))();
    stats[state instanceof Just ? 'hits' : 'misses'].push(name);
    return state;
  }),
  store: state => lift(() => {
    if (config.readOnly) return;
    const name = state.backend.name;
    assert.ok(saveModuleState(config.directory)(keys.get(name))(state)(), `failed to store ${name}`);
    stats.stored.push(name);
    const restored = loadModuleState(request(name))();
    assert.ok(restored instanceof Just, `failed to restore ${name}`);
    // Includes constructor prototypes, all optimizer metadata and PHP strings.
    assert.deepEqual(restored.value0, state, `round trip: ${name}`);
    stats.roundTrips.push(name);
  }),
})();
