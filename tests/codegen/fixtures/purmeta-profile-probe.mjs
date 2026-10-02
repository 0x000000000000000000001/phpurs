// Independent I/O accounting for the CLI regression and the b8x assay.
import fs from 'node:fs';
import { syncBuiltinESMExports } from 'node:module';

const io = { reads: { attempts: 0, files: 0, bytes: 0 }, writes: { attempts: 0, files: 0, bytes: 0 } };
for (const [operation, kind] of [['readFileSync', 'reads'], ['writeFileSync', 'writes']]) {
  const original = fs[operation];
  fs[operation] = (file, ...args) => {
    if (!String(file).endsWith('.purmeta')) return original(file, ...args);
    io[kind].attempts++;
    const result = original(file, ...args);
    io[kind].files++;
    io[kind].bytes += kind === 'reads' ? result.byteLength : args[0].byteLength;
    return result;
  };
}
syncBuiltinESMExports();
process.on('exit', () => fs.writeFileSync(process.env.PHPURS_PURMETA_PROBE, JSON.stringify({ ...io, processPeakRSSKiB: process.resourceUsage().maxRSS }) + '\n'));
