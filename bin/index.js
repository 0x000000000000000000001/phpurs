#!/usr/bin/env node

// Spago backend executables are usually called by `spago run/build`
// We need to require the compiled PureScript Main module
const path = require('path');
const fs = require('fs');

// The output path is usually in output/Main/index.js if we compile phpurs itself
const mainPath = path.join(__dirname, '..', 'output', 'Main', 'index.js');

if (fs.existsSync(mainPath)) {
  const Main = require(mainPath);
  Main.main();
} else {
  console.error("phpurs has not been built! Please run `spago build` in the phpurs directory first.");
  process.exit(1);
}
