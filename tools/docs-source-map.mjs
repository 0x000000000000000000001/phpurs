// Generate the module source map from module headers so it cannot drift.
// `--check` fails when the committed page differs from the sources.
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const output = path.join(root, 'docs', 'compiler', 'source-map.md');
const check = process.argv.includes('--check');

async function walk(directory) {
  const entries = await fs.readdir(directory, { withFileTypes: true });
  const files = [];
  for (const entry of entries.sort((a, b) => a.name.localeCompare(b.name))) {
    const full = path.join(directory, entry.name);
    if (entry.isDirectory()) files.push(...(await walk(full)));
    else if (entry.isFile() && entry.name.endsWith('.purs')) files.push(full);
  }
  return files;
}

const files = await walk(path.join(root, 'src'));
const modules = [];
for (const file of files) {
  const source = await fs.readFile(file, 'utf8');
  const moduleName = source.match(/^module\s+([\w.]+)/m)?.[1];
  if (!moduleName) continue;
  const header = source
    .split('\n')
    .filter((line) => line.startsWith('-- |'))
    .map((line) => line.replace(/^-- \| ?/, ''))
    .join(' ')
    .trim();
  const summary = header.split(/(?<=\.)\s/)[0] || 'No module header.';
  modules.push({
    moduleName,
    path: path
      .relative(path.join(root, 'docs', 'compiler'), file)
      .split(path.sep)
      .join('/'),
    summary: summary.replace(/\|/g, '\\|'),
  });
}
modules.sort((a, b) => a.moduleName.localeCompare(b.moduleName));

const page = [
  '# Source map',
  '',
  'Generated from module headers by `npm run docs:source-map`. Do not edit by',
  'hand; `npm run check:source-map` fails when this page is stale.',
  '',
  '| Module | Responsibility |',
  '| --- | --- |',
  ...modules.map((entry) => `| [\`${entry.moduleName}\`](${entry.path}) | ${entry.summary} |`),
  '',
].join('\n');

if (check) {
  let current = '';
  try {
    current = await fs.readFile(output, 'utf8');
  } catch (error) {
    if (error.code !== 'ENOENT') throw error;
  }
  if (current !== page) {
    console.error('docs/compiler/source-map.md is stale; run npm run docs:source-map');
    process.exitCode = 1;
  } else {
    console.log(`source map: ${modules.length} modules up to date.`);
  }
} else {
  await fs.mkdir(path.dirname(output), { recursive: true });
  await fs.writeFile(output, page);
  console.log(`source map: ${modules.length} modules written.`);
}
