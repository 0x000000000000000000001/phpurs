import fs from 'node:fs';
import { syncBuiltinESMExports } from 'node:module';

const config = JSON.parse(fs.readFileSync(process.env.PHPURS_CLI_PROBE, 'utf8'));
const reads = [];
const writes = [];
const readFile = fs.readFile;
const writeFile = fs.writeFile;
let mutated = false;
fs.readFile = (file, ...args) => {
  reads.push(String(file));
  const callback = args.pop();
  readFile(file, ...args, (error, bytes) => {
    if (!mutated && !error && config.mutation && String(file) === config.mutation.afterRead) {
      // Replacement occurs after the captured bytes exist but before they are
      // hashed/decoded. A second read in the driver would cache the wrong state.
      fs.writeFileSync(config.mutation.file, Buffer.from(config.mutation.bytes, 'base64'));
      mutated = true;
    }
    callback(error, bytes);
  });
};
fs.writeFile = (file, ...args) => {
  writes.push(String(file));
  return writeFile(file, ...args);
};
if (config.host) {
  for (const [field, value] of Object.entries(config.host)) Object.defineProperty(process, field, { value });
}
syncBuiltinESMExports();
process.on('exit', () => fs.writeFileSync(config.stats, JSON.stringify({ reads, writes, mutated })));
