// Compile and execute one language fixture with the TAST fork when both the
// compiler checkout and the sibling package checkouts are available.
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const fork = process.env.PHPURS_TAST_PURS
  ? path.resolve(process.env.PHPURS_TAST_PURS)
  : path.resolve(root, '../../purescript');
const packages = ['prelude', 'effect', 'console', 'arrays'].map((name) =>
  path.resolve(root, `../../phpurs-${name}`),
);
const missing = [];
if (!fs.existsSync(path.join(fork, 'cabal.project'))) missing.push(`fork compiler at ${fork}`);
for (const p of packages) if (!fs.existsSync(p)) missing.push(p);
if (missing.length > 0) {
  console.log(`TAST smoke skipped: ${missing.join(', ')} not available.`);
  console.log('Build the TAST fork and the phpurs-* packages, then rerun bin/test.');
  process.exit(0);
}
const result = spawnSync('bash', ['bin/test', process.env.PHPURS_TAST_FIXTURE ?? '1110.purs'], {
  cwd: root,
  stdio: 'inherit',
  env: process.env,
});
process.exit(result.status ?? 1);
