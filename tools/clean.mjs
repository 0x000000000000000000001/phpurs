// Remove generated build state without touching tracked files. Every target is
// resolved under the repository root and checked before deletion.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(fileURLToPath(new URL('..', import.meta.url)));
const dryRun = process.argv.includes('--dry-run');
const targets = [
  'output',
  '.spago',
  '.purmeta',
  'bin/phpurs.js',
  'tests/runner/output-test',
  'tests/runner/.purmeta',
  'tests/runner/.spago',
  '.tmp-home',
  '.tmp-cache',
];

let matched = 0;
for (const target of targets) {
  const full = path.resolve(root, target);
  if (full === root || !full.startsWith(`${root}${path.sep}`)) {
    throw new Error(`refusing to clean outside the repository: ${full}`);
  }
  if (!fs.existsSync(full)) continue;
  matched++;
  if (dryRun) {
    console.log(`would remove ${target}`);
    continue;
  }
  fs.rmSync(full, { recursive: true, force: true });
  console.log(`removed ${target}`);
}
console.log(`${matched} target(s) ${dryRun ? 'matched' : 'cleaned'}.`);
