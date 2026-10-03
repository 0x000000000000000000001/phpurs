// Isolated CodeGen.translate timing; input loading and output hashing are outside
// the clock. CPU profiling and copy counters are separate diagnostic modes.
import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import fs from 'node:fs';
import inspector from 'node:inspector';
import path from 'node:path';
import { performance } from 'node:perf_hooks';
import { pathToFileURL } from 'node:url';

const [library, manifestFile, destination, mode = 'bench', selected] = process.argv.slice(2);
const { CodeGen, Cache } = await import(pathToFileURL(path.resolve(library)));
const manifest = JSON.parse(fs.readFileSync(manifestFile, 'utf8'));
const fingerprint = value => createHash('sha256').update(JSON.stringify(value)).digest('hex');
const results = { mode, librarySHA256: createHash('sha256').update(fs.readFileSync(library)).digest('hex'), modules: [] };
const copies = {};
let active = false;
globalThis.phpursAuditCopy = (kind, length) => {
  if (!active) return;
  const row = copies[kind] ??= { calls: 0, copiedPrefixItems: 0, maxPrefix: 0 };
  row.calls++;
  row.copiedPrefixItems += length;
  row.maxPrefix = Math.max(row.maxPrefix, length);
};
const session = new inspector.Session();
const post = (method, params = {}) => new Promise((resolve, reject) => session.post(method, params, (error, result) => error ? reject(error) : resolve(result)));
if (mode === 'profile') {
  session.connect();
  await post('Profiler.enable');
  await post('Profiler.setSamplingInterval', { interval: 250 });
}
for (const input of manifest.modules.filter(m => !selected || m.name === selected)) {
  const state = Cache.loadModuleStateImpl(x => x)(null)(manifest.directory)(input.key)(input.name)();
  assert.ok(state, input.name);
  const translate = () => CodeGen.translate(input.imports)(state.backend);
  const expected = fingerprint(translate());
  for (let i = 0; i < 3; i++) assert.equal(fingerprint(translate()), expected);
  for (const kind of Object.keys(copies)) delete copies[kind];
  active = mode === 'copies';
  if (mode === 'profile') await post('Profiler.start');
  const timesMs = [];
  for (let i = 0; i < (mode === 'copies' ? 1 : 15); i++) {
    const started = performance.now();
    const output = translate();
    timesMs.push(performance.now() - started);
    if (mode !== 'profile') assert.equal(fingerprint(output), expected);
  }
  active = false;
  if (mode === 'profile') {
    const { profile } = await post('Profiler.stop');
    fs.writeFileSync(destination + '.' + input.name + '.cpuprofile', JSON.stringify(profile));
  }
  results.modules.push({ name: input.name, phpAstSHA256: expected, timesMs, copies: structuredClone(copies) });
}
if (mode === 'profile') session.disconnect();
results.peakRSSKiB = process.resourceUsage().maxRSS;
fs.writeFileSync(destination, JSON.stringify(results, null, 2) + '\n');
console.log(JSON.stringify(results));
