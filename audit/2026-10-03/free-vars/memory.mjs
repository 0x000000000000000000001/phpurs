// Diagnostic-only bundle instrumentation. Keep a real module's analyzed TCO
// roots alive, measure retained heap with/without its memo table, then verify
// natural collection on a separate translation without clearing that table.
import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { setImmediate } from 'node:timers/promises';
import { pathToFileURL } from 'node:url';

const [library, manifestFile, destination] = process.argv.slice(2);
assert.equal(typeof global.gc, 'function', 'run node --expose-gc');
const original = fs.readFileSync(library, 'utf8');
let source = original;
function replaceOnce(from, to) {
  assert.equal(source.split(from).length, 2, from);
  source = source.replace(from, to);
}
replaceOnce('var tcoBindings = analyzeBindings(regions.module_);',
  'var tcoBindings = analyzeBindings(regions.module_); globalThis.phpursMemoryTco(tcoBindings);');
const memoized = source.includes('var memoizeFreeVars =');
if (memoized) {
  replaceOnce('const cache = /* @__PURE__ */ new WeakMap();',
    'let cache = new WeakMap(); globalThis.phpursMemoryReset(() => { cache = new WeakMap(); });');
  replaceOnce('cache.set(expr, result);',
    'cache.set(expr, result); globalThis.phpursMemoryCached(expr, result);');
}
const instrumented = path.resolve(destination + '.probe.mjs');
fs.writeFileSync(instrumented, source);
let hold = false, heldTco = null, observe = false, reset;
let keys = [], values = [], seenValues, entries = 0, totalMembers = 0, maxMembers = 0;
globalThis.phpursMemoryReset = fn => { reset = fn; };
globalThis.phpursMemoryTco = bindings => { if (hold) heldTco = bindings; };
globalThis.phpursMemoryCached = (expr, result) => {
  if (!observe) return;
  keys.push(new WeakRef(expr));
  const size = Set.size(result);
  entries++; totalMembers += size; maxMembers = Math.max(maxMembers, size);
  if (size > 0 && !seenValues.has(result)) {
    seenValues.add(result);
    values.push(new WeakRef(result));
  }
};
const { CodeGen, Cache, Set } = await import(pathToFileURL(instrumented));
assert.equal(typeof reset, memoized ? 'function' : 'undefined');
const manifest = JSON.parse(fs.readFileSync(manifestFile, 'utf8'));
const hash = value => createHash('sha256').update(JSON.stringify(value)).digest('hex');
async function heap() {
  const samples = [];
  for (let i = 0; i < 3; i++) {
    await setImmediate();
    global.gc();
    samples.push(process.memoryUsage().heapUsed);
  }
  return Math.min(...samples);
}
const rows = [];
for (const fixture of manifest.modules) {
  const state = Cache.loadModuleStateImpl(x => x)(null)(manifest.directory)(fixture.key)(fixture.name)();
  assert.ok(state, fixture.name);
  const translate = () => CodeGen.translate(fixture.imports)(state.backend);
  const expected = hash(translate());
  for (let i = 0; i < 3; i++) assert.equal(hash(translate()), expected);
  reset?.();
  await heap();
  // No weak-reference instrumentation in the cache-size comparison. The only
  // retained instrumentation object is the same root array in both readings.
  hold = true;
  assert.equal(hash(translate()), expected);
  const withCacheBytes = await heap();
  assert.ok(heldTco.length > 0);
  reset?.();
  const withoutCacheBytes = await heap();
  assert.ok(heldTco.length > 0);
  hold = false; heldTco = null;
  const releasedBeforeBytes = await heap();
  // Observe every computed key and distinct nonempty value weakly. WeakRef
  // keeps targets alive until the end of this turn, hence the awaited GC turns.
  observe = true; entries = totalMembers = maxMembers = 0;
  keys = []; values = []; seenValues = new WeakSet();
  assert.equal(hash(translate()), expected);
  observe = false;
  const releasedAfterBytes = await heap();
  const liveKeys = keys.filter(ref => ref.deref() !== undefined).length;
  const liveValues = values.filter(ref => ref.deref() !== undefined).length;
  assert.equal(liveKeys, 0, fixture.name + ': cached TCO nodes retained');
  assert.equal(liveValues, 0, fixture.name + ': cached sets retained');
  const distinctNonemptySets = values.length;
  keys = []; values = []; seenValues = null;
  // V8 may retain an empty weak table's allocated capacity after all keys die.
  // Measure that separately from the live cache, excluding our WeakRef arrays.
  const cacheAfterReleaseBytes = await heap();
  reset?.();
  const cacheEmptyBytes = await heap();
  rows.push({ name: fixture.name, phpAstSHA256: expected,
    withCacheBytes, withoutCacheBytes, retainedCacheBytes: withCacheBytes - withoutCacheBytes,
    releasedBeforeBytes, releasedAfterBytes, cachedNodes: entries,
    distinctNonemptySets, summedSetCardinalities: totalMembers,
    largestSet: maxMembers, liveKeys, liveValues,
    cacheAfterReleaseBytes, cacheEmptyBytes, emptyTableBytes: cacheAfterReleaseBytes - cacheEmptyBytes });
}
const result = { diagnostic: true, memoized, librarySHA256: createHash('sha256').update(original).digest('hex'),
  rows, peakRSSKiB: process.resourceUsage().maxRSS };
fs.writeFileSync(destination, JSON.stringify(result, null, 2) + '\n');
console.log(JSON.stringify(result));
