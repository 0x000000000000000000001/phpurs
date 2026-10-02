import fs from 'node:fs';
import path from 'node:path';
import { createHash, randomUUID } from 'node:crypto';

const digest = bytes => createHash('sha256').update(bytes).digest('hex');
const manifestName = '.phpurs-outputs.json';

// Only the compiler's fixed PHP output shapes can authorize removal. In
// particular, a damaged manifest must never name CoreFn, FFI or arbitrary paths.
function validEntry(entry) {
  if (!entry || !['modules', 'bundles'].includes(entry.family)
    || typeof entry.path !== 'string' || typeof entry.sha256 !== 'string' || !/^[0-9a-f]{64}$/.test(entry.sha256)) return false;
  if (entry.path === 'bundle.php') return entry.family === 'bundles';
  const parts = entry.path.split('/');
  return parts.length === 2 && parts[0] !== '' && !['.', '..'].includes(parts[0])
    && !/[\\:\0]/.test(parts[0])
    && (entry.family === 'modules' ? ['index.php', 'main.mod.php'].includes(parts[1]) : parts[1] === 'main.bundle.php');
}

function previousOutputs(file) {
  let bytes;
  try { bytes = fs.readFileSync(file); }
  catch (error) {
    if (error.code !== 'ENOENT') throw error;
    return { entries: [], bytes: null };
  }
  let parsed;
  try { parsed = JSON.parse(bytes.toString('utf8')); }
  catch { return { entries: [], bytes }; }
  if (parsed?.version !== 1 || !Array.isArray(parsed.files) || !parsed.files.every(validEntry)
    || new Set(parsed.files.map(entry => entry.path)).size !== parsed.files.length) return { entries: [], bytes };
  return { entries: parsed.files, bytes };
}

function removeUnchanged(root, entry) {
  const target = path.join(root, entry.path);
  try {
    // A module directory replaced by a symlink no longer belongs to this build.
    const parent = path.dirname(target);
    if (parent !== root && !fs.lstatSync(parent).isDirectory()) return;
    if (!fs.lstatSync(target).isFile()) return;
    if (digest(fs.readFileSync(target)) === entry.sha256) fs.unlinkSync(target);
  } catch (error) {
    if (error.code !== 'ENOENT') throw error;
  }
}

export const newOutputTracker = () => new Map();

export const rememberOutput = tracker => bundle => relative => contents => () => {
  const entry = { path: relative, family: bundle ? 'bundles' : 'modules', sha256: digest(contents) };
  if (validEntry(entry)) tracker.set(relative, entry);
};

export const finalizeOutputs = tracker => outputDir => emission => complete => () => {
  const root = fs.realpathSync(outputDir);
  const file = path.join(root, manifestName);
  const previous = previousOutputs(file);
  const next = new Map(tracker);
  for (const entry of previous.entries) {
    if (next.has(entry.path)) continue;
    const active = entry.family === 'modules' ? emission.emitModules : emission.emitBundle;
    if (!complete || !active) next.set(entry.path, entry);
    else removeUnchanged(root, entry);
    // Modified files and symlinks are preserved but cease to be compiler-owned.
  }
  const files = [...next.values()].sort((a, b) => a.path < b.path ? -1 : a.path > b.path ? 1 : 0);
  const bytes = Buffer.from(JSON.stringify({ version: 1, files }, null, 2) + '\n');
  if (previous.bytes?.equals(bytes)) return;
  let temporary = file + '.' + randomUUID() + '.tmp';
  try {
    fs.writeFileSync(temporary, bytes, { flag: 'wx' });
    fs.renameSync(temporary, file);
    temporary = null;
  } finally {
    if (temporary !== null) { try { fs.unlinkSync(temporary); } catch {} }
  }
};
