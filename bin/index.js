#!/usr/bin/env node

// Spago backend executables are usually called by `spago run/build`
// We need to require the compiled PureScript Main module
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const mainPath = path.join(__dirname, 'phpurs.js');

if (fs.existsSync(mainPath)) {
  await import('./phpurs.js');
} else {
  console.error("phpurs has not been built! Please run `npm run build` in the phpurs directory first.");
  process.exit(1);
}
