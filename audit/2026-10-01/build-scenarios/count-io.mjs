// Preload with node --import; keep instrumentation outside the compiler bundle.
import fs from 'node:fs';
import path from 'node:path';
import { syncBuiltinESMExports } from 'node:module';

const counts = {
  reads: { corefn: { attempts: 0, files: 0, bytes: 0 }, php: { attempts: 0, files: 0, bytes: 0 } },
  writes: { php: [], purmeta: [], composer: [] },
};
const relative = file => path.relative(process.cwd(), path.resolve(String(file)));
const byteLength = data => typeof data === 'string' ? Buffer.byteLength(data) : data.byteLength;

const readFile = fs.readFile;
fs.readFile = function(file, ...args) {
  const target = relative(file);
  const kind = target.startsWith('output/') && target.endsWith('/corefn.json') ? 'corefn'
    : target.startsWith('output/') && target.endsWith('.php') ? 'php' : null;
  if (kind) {
    const entry = counts.reads[kind];
    entry.attempts++;
    const callback = args.at(-1);
    args[args.length - 1] = (error, data) => {
      if (!error) {
        entry.files++;
        entry.bytes += byteLength(data);
      }
      callback(error, data);
    };
  }
  return readFile.call(this, file, ...args);
};

for (const operation of ['writeFile', 'writeFileSync']) {
  const original = fs[operation];
  fs[operation] = function(file, data, ...args) {
    const target = relative(file);
    const kind = target.endsWith('.php') ? 'php' : target.endsWith('.purmeta') ? 'purmeta'
      : target === 'output/composer.json' ? 'composer' : null;
    if (kind) counts.writes[kind].push({ file: target, bytes: byteLength(data) });
    return original.call(this, file, data, ...args);
  };
}

syncBuiltinESMExports();
process.on('exit', () => {
  counts.peakRSSKiB = process.resourceUsage().maxRSS;
  fs.writeFileSync(process.env.PHPURS_AUDIT_COUNTS, JSON.stringify(counts, null, 2) + '\n');
});
