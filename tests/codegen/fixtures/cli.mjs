// Shared temporary-root lifecycle, PHP execution and output snapshots.
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

export function temporaryRoot(t, prefix) {
  const root = fs.realpathSync(fs.mkdtempSync(path.join(os.tmpdir(), prefix)));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  return root;
}

export function runPhp(root, file, options = {}) {
  return spawnSync(
    'php',
    [...(options.phpArgs ?? ['-d', 'opcache.enable_cli=0']), file, ...(options.args ?? [])],
    {
      cwd: root,
      encoding: 'utf8',
      timeout: options.timeout ?? 10000,
      input: options.input,
      env: { ...process.env, ...options.env },
    },
  );
}

export function snapshotFiles(
  root,
  directory = 'output',
  filter = /\.php$|composer\.json$|\.phpurs-outputs\.json$/,
) {
  const target = path.resolve(root, directory);
  return Object.fromEntries(
    fs
      .readdirSync(target, { recursive: true })
      .filter((file) => filter.test(file))
      .sort()
      .map((file) => [file, fs.readFileSync(path.join(target, file))]),
  );
}

export function snapshotText(directory) {
  const files = {};
  const visit = (relative) => {
    for (const name of fs.readdirSync(path.join(directory, relative))) {
      const file = path.join(relative, name);
      const full = path.join(directory, file);
      if (fs.statSync(full).isDirectory()) visit(file);
      else files[file] = fs.readFileSync(full, 'utf8');
    }
  };
  visit('');
  return files;
}
