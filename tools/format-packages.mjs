// Format maintained PHP FFI and tests, never vendor or ignored generated output.
import { execFileSync } from 'node:child_process';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as prettier from 'prettier';
import * as php from '@prettier/plugin-php';

const siblings = fileURLToPath(new URL('../../', import.meta.url));
const args = process.argv.slice(2);
const write = args.includes('--write');
const selected = args.filter((arg) => arg !== '--write');
for (const name of selected) {
  if (!/^phpurs-[a-z0-9-]+$/.test(name)) throw new Error(`Invalid package name: ${name}`);
}
const packages = (await fs.readdir(siblings, { withFileTypes: true }))
  .filter((entry) => entry.isDirectory() && entry.name.startsWith('phpurs-'))
  .map((entry) => entry.name)
  .filter((name) => selected.length === 0 || selected.includes(name))
  .sort();
for (const name of selected) {
  if (!packages.includes(name)) throw new Error(`No sibling checkout: ${name}`);
}

let checked = 0;
let changed = 0;
for (const name of packages) {
  const root = path.join(siblings, name);
  const files = execFileSync(
    'git',
    ['ls-files', '-z', '--cached', '--others', '--exclude-standard'],
    {
      cwd: root,
      encoding: 'utf8',
    },
  )
    .split('\0')
    .filter((file) => /^(src|test|tests)\/.*\.php$/.test(file));
  for (const file of files) {
    const full = path.join(root, file);
    let source;
    try {
      source = await fs.readFile(full, 'utf8');
    } catch (error) {
      // Git still lists tracked files deleted from the working tree.
      if (error.code === 'ENOENT') continue;
      throw error;
    }
    const formatted = await prettier.format(source, {
      parser: 'php',
      plugins: [php],
      phpVersion: '8.4',
      printWidth: 100,
      tabWidth: 4,
      singleQuote: true,
    });
    checked++;
    if (formatted === source) continue;
    changed++;
    if (write) await fs.writeFile(full, formatted);
    else console.error(`Not formatted: ${name}/${file}`);
  }
}
console.log(`${checked} PHP files checked; ${changed} ${write ? 'formatted' : 'need formatting'}.`);
if (!write && changed > 0) process.exitCode = 1;
