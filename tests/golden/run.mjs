// Byte-for-byte golden corpus for the generated PHP. Cases are pure renderers:
// they turn a fixture into a map of relative file names to contents. The
// expected tree is committed, so any change to the output is a reviewable diff.
//
//   npm run test:golden     compare
//   npm run golden:update   rewrite expected/ after an intentional change
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { cases } from './cases/index.mjs';

const here = path.dirname(fileURLToPath(import.meta.url));
const expectedRoot = path.join(here, 'expected');
const update = process.argv.includes('--update');

function walk(directory) {
  if (!fs.existsSync(directory)) return [];
  return fs
    .readdirSync(directory, { withFileTypes: true })
    .flatMap((entry) => {
      const full = path.join(directory, entry.name);
      return entry.isDirectory() ? walk(full) : [full];
    })
    .sort();
}

function describeDifference(expected, actual) {
  const expectedLines = expected.split('\n');
  const actualLines = actual.split('\n');
  const length = Math.max(expectedLines.length, actualLines.length);
  for (let index = 0; index < length; index++) {
    if (expectedLines[index] === actualLines[index]) continue;
    return [
      `  line ${index + 1}:`,
      `    expected: ${JSON.stringify(expectedLines[index] ?? '<missing>')}`,
      `    actual:   ${JSON.stringify(actualLines[index] ?? '<missing>')}`,
    ].join('\n');
  }
  return `  sizes differ: expected ${expected.length} bytes, actual ${actual.length} bytes`;
}

const failures = [];
const produced = new Set();
const updated = [];

for (const testCase of cases) {
  const files = await testCase.render();
  for (const [file, contents] of Object.entries(files)) {
    if (!file || path.isAbsolute(file) || file.split('/').includes('..'))
      throw new Error(`${testCase.name}: unsafe golden path ${JSON.stringify(file)}`);
    const relative = `${testCase.name}/${file}`;
    produced.add(relative);
    const target = path.join(expectedRoot, relative);
    if (update) {
      fs.mkdirSync(path.dirname(target), { recursive: true });
      fs.writeFileSync(target, contents);
      updated.push(relative);
      continue;
    }
    if (!fs.existsSync(target)) {
      failures.push(`${relative}: missing expected file (run npm run golden:update)`);
      continue;
    }
    const expected = fs.readFileSync(target, 'utf8');
    if (expected !== contents) {
      failures.push(`${relative}: output changed\n${describeDifference(expected, contents)}`);
    }
  }
}

for (const file of walk(expectedRoot)) {
  const relative = path.relative(expectedRoot, file).split(path.sep).join('/');
  if (produced.has(relative)) continue;
  if (update) {
    fs.rmSync(file);
    updated.push(`${relative} (removed)`);
  } else {
    failures.push(`${relative}: stale expected file (run npm run golden:update)`);
  }
}

if (update) {
  console.log(`${updated.length} golden files written.`);
  for (const file of updated) console.log(`  ${file}`);
  process.exit(0);
}

if (failures.length > 0) {
  for (const failure of failures) console.error(`✗ ${failure}`);
  console.error(
    `${failures.length} golden failure(s). If the output change is intentional, run npm run golden:update and review the diff.`,
  );
  process.exitCode = 1;
} else {
  console.log(`Golden: ${produced.size} files across ${cases.length} cases match.`);
}
