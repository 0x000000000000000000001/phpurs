import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const [library, manifestFile, destination] = process.argv.slice(2);
const { CodeGen, Cache } = await import(pathToFileURL(path.resolve(library)));
const manifest = JSON.parse(fs.readFileSync(manifestFile, 'utf8'));
const rows = [];
let seen, calls, repeats;
globalThis.phpursAuditFreeVars = expr => {
  calls++;
  if (seen.has(expr)) repeats++;
  else seen.add(expr);
};
for (const fixture of manifest.modules) {
  const state = Cache.loadModuleStateImpl(x => x)(null)(manifest.directory)(fixture.key)(fixture.name)();
  assert.ok(state, fixture.name);
  seen = new Set(); calls = 0; repeats = 0;
  const output = CodeGen.translate(fixture.imports)(state.backend);
  rows.push({ name: fixture.name, calls, repeats, uniqueNodes: seen.size,
    phpAstSHA256: createHash('sha256').update(JSON.stringify(output)).digest('hex') });
  seen.clear();
}
fs.writeFileSync(destination, JSON.stringify(rows, null, 2) + '\n');
console.log(JSON.stringify(rows));
