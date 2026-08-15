const { spawn } = require('child_process');
const fs = require('fs');
const child = spawn('node', ['--cpu-prof', '--max-old-space-size=16384', 'bin/phpurs.js', '--main', 'Test.Main']);
child.stdout.on('data', d => process.stdout.write(d));
child.stderr.on('data', d => process.stderr.write(d));

setTimeout(() => {
  console.log("Sending SIGINT to child...");
  child.kill('SIGINT');
}, 15000);
