// Verify that the optimizer checkout matches the revision pinned for this
// compiler. A build against another PBO revision is not the reviewed toolchain.
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const config = JSON.parse(fs.readFileSync(path.join(root, 'tools', 'toolchain.json'), 'utf8'));
const expected = config.pbo.revision;
const checkout = path.resolve(root, config.pbo.path);
const errors = [];

if (!fs.existsSync(checkout)) {
  errors.push(
    `${config.pbo.path} is missing; clone ${config.pbo.repository} there or run ./bin/setup`,
  );
} else {
  const actual = execFileSync('git', ['-C', checkout, 'rev-parse', 'HEAD'], {
    encoding: 'utf8',
  }).trim();
  if (!actual.startsWith(expected)) {
    errors.push(
      `PBO checkout is at ${actual.slice(0, 12)}, expected ${expected.slice(0, 12)}; ` +
        'update tools/toolchain.json with the reviewed revision',
    );
  }
}

const spago = fs.readFileSync(path.join(root, 'spago.yaml'), 'utf8');
if (!spago.includes(`path: '${config.pbo.path}'`)) {
  errors.push(`spago.yaml does not point backend-optimizer at ${config.pbo.path}`);
}

if (errors.length > 0) {
  for (const error of errors) console.error(`toolchain: ${error}`);
  process.exitCode = 1;
} else {
  console.log(`Toolchain: PBO ${expected.slice(0, 12)} at ${config.pbo.path}`);
}
